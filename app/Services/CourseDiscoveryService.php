<?php

declare(strict_types=1);

namespace App\Services;

use App\Ai\Agents\YoutubeTitleGenerator;
use App\Models\Course;
use App\Models\CourseCategory;
use Laravel\Ai\Exceptions\AiException;
use Laravel\Ai\Responses\StructuredAgentResponse;

class CourseDiscoveryService
{
    public function __construct(private readonly YoutubeService $youtube) {}

    /**
     * @param  array<int, string>  $categories
     * @return array<int, int> affected category ids
     */
    public function discover(array $categories): array
    {
        $titlesByCategory = $this->generateTitles($categories);
        $names = array_keys($titlesByCategory);

        CourseCategory::upsert(
            array_map(fn (string $category): array => ['name' => $category], $names),
            ['name'],
            ['name'],
        );

        /** @var array<string, int> $categoryIds */
        $categoryIds = CourseCategory::query()
            ->whereIn('name', $names)
            ->pluck('id', 'name')
            ->all();

        $categoryByTitle = [];

        foreach ($titlesByCategory as $category => $titles) {
            foreach ($titles as $title) {
                $categoryByTitle[$title] ??= $category;
            }
        }

        $courseRows = [];
        $queries = $this->youtube->searchMany(array_keys($categoryByTitle), limit: 2);

        foreach ($queries as $query => $items) {
            $category = $categoryByTitle[$query];

            foreach ($items as $item) {
                $playlistId = $item['id']['playlistId'] ?? null;
                $snippet = $item['snippet'] ?? null;

                if ($playlistId === null) {
                    continue;
                }

                if ($snippet === null) {
                    continue;
                }

                $courseRows[$playlistId] ??= [
                    'playlist_id' => $playlistId,
                    'title' => $snippet['title'] ?? '',
                    'description' => $snippet['description'] ?? '',
                    'thumbnail' => $snippet['thumbnails']['medium']['url']
                        ?? $snippet['thumbnails']['default']['url']
                        ?? '',
                    'channel' => $snippet['channelTitle'] ?? '',
                    'category_id' => $categoryIds[$category],
                    'video_count' => 0,
                    'total_duration_seconds' => 0,
                    'total_views' => 0,
                ];
            }
        }

        if ($courseRows !== []) {
            $stats = $this->youtube->enrichPlaylists(array_keys($courseRows));

            $rows = array_map(
                fn (array $row): array => [...$row, ...($stats[$row['playlist_id']] ?? [])],
                array_values($courseRows),
            );

            Course::upsert($rows, ['playlist_id'], [
                'title', 'description', 'thumbnail', 'channel', 'category_id',
                'video_count', 'total_duration_seconds', 'total_views',
            ]);
        }

        return array_values($categoryIds);
    }

    /**
     * @param  array<int, string>  $categories
     * @return array<string, array<int, string>>
     */
    private function generateTitles(array $categories): array
    {
        $cached = [];
        $misses = [];

        foreach ($categories as $category) {
            $hit = cache()->get($this->titlesCacheKey($category));
            $hit !== null ? $cached[$category] = $hit : $misses[] = $category;
        }

        if ($misses === []) {
            return $cached;
        }

        $agent = YoutubeTitleGenerator::make(
            model: config()->string('services.ai.title_generator_model'),
            categories: $misses,
        );

        $response = $agent->prompt('generate search queries for: '.implode(', ', $misses));

        if (! $response instanceof StructuredAgentResponse) {
            throw new AiException('Expected structured response from agent');
        }

        foreach ($response->structured as $category => $titles) {
            cache()->put(
                $this->titlesCacheKey($category),
                $titles,
                now()->addMinutes(5)
            );

            $cached[$category] = $titles;
        }

        return $cached;
    }

    private function titlesCacheKey(string $category): string
    {
        return 'ai.titles.'.md5($category);
    }
}
