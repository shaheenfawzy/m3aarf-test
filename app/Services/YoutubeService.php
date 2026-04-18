<?php

declare(strict_types=1);

namespace App\Services;

use DateInterval;
use DateTimeImmutable;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class YoutubeService
{
    private const string BASE_URL = 'https://www.googleapis.com/youtube/v3';

    private const int CACHE_TTL_HOURS = 24;

    public function __construct(private readonly string $apiKey) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, int $limit = 2): array
    {
        return cache()->remember(
            $this->searchCacheKey($query, $limit),
            now()->addHours(self::CACHE_TTL_HOURS),
            fn (): array => $this
                ->request('/search', $this->searchParams($query, $limit))
                ->json('items', [])
        );
    }

    /**
     * @param  array<int, string>  $queries
     * @return array<string, array<int, array<string, mixed>>> keyed by query
     */
    public function searchMany(array $queries, int $limit = 2): array
    {
        [$hits, $misses] = $this->partitionByCache($queries, $limit);

        if ($misses === []) {
            return $hits;
        }

        $responses = Http::pool(fn (Pool $pool): array => array_map(
            fn (string $q) => $pool
                ->as($q)
                ->get(self::BASE_URL.'/search', $this->searchParams($q, $limit)),
            $misses
        ));

        foreach ($misses as $query) {
            $response = $responses[$query] ?? null;
            $items = $response instanceof Response && $response->successful()
                ? $response->json('items', [])
                : [];

            cache()->put(
                $this->searchCacheKey($query, $limit),
                $items,
                now()->addHours(self::CACHE_TTL_HOURS)
            );

            $hits[$query] = $items;
        }

        return $hits;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function playlist(string $playlistId): ?array
    {
        return $this->playlistsMany([$playlistId])[$playlistId] ?? null;
    }

    /**
     * @param  array<int, string>  $playlistIds
     * @return array<string, array<string, mixed>>
     */
    public function playlistsMany(array $playlistIds): array
    {
        $result = [];

        foreach (array_chunk(array_unique($playlistIds), 50) as $chunk) {
            $items = $this
                ->request('/playlists', [
                    'key' => $this->apiKey,
                    'id' => implode(',', $chunk),
                    'part' => 'snippet,contentDetails',
                    'maxResults' => 50,
                ])
                ->json('items', []);

            foreach ($items as $item) {
                $result[$item['id']] = $item;
            }
        }

        return $result;
    }

    /**
     * Aggregate stats per playlist: video count, total duration, total views.
     *
     * @param  array<int, string>  $playlistIds
     * @return array<string, array{video_count: int, total_duration_seconds: int, total_views: int}>
     */
    public function enrichPlaylists(array $playlistIds): array
    {
        $videoIdsByPlaylist = $this->videoIdsForPlaylists($playlistIds);
        $allVideoIds = array_values(array_unique(array_merge(...array_values($videoIdsByPlaylist))));
        $stats = $this->videoStats($allVideoIds);

        $result = [];

        foreach ($videoIdsByPlaylist as $playlistId => $videoIds) {
            $duration = 0;
            $views = 0;

            foreach ($videoIds as $videoId) {
                $duration += $stats[$videoId]['duration_seconds'] ?? 0;
                $views += $stats[$videoId]['views'] ?? 0;
            }

            $result[$playlistId] = [
                'video_count' => count($videoIds),
                'total_duration_seconds' => $duration,
                'total_views' => $views,
            ];
        }

        return $result;
    }

    /**
     * @param  array<int, string>  $playlistIds
     * @return array<string, array<int, string>> keyed by playlist id
     */
    public function videoIdsForPlaylists(array $playlistIds): array
    {
        $playlistIds = array_values(array_unique($playlistIds));

        $responses = Http::pool(fn (Pool $pool): array => array_map(
            fn (string $id) => $pool
                ->as($id)
                ->get(self::BASE_URL.'/playlistItems', [
                    'key' => $this->apiKey,
                    'playlistId' => $id,
                    'part' => 'contentDetails',
                    'maxResults' => 50,
                ]),
            $playlistIds
        ));

        $result = [];

        foreach ($playlistIds as $playlistId) {
            $response = $responses[$playlistId] ?? null;
            $items = $response instanceof Response && $response->successful()
                ? $response->json('items', [])
                : [];

            $result[$playlistId] = array_values(array_filter(
                array_map(fn (array $item): ?string => $item['contentDetails']['videoId'] ?? null, $items)
            ));
        }

        return $result;
    }

    /**
     * @param  array<int, string>  $videoIds
     * @return array<string, array{duration_seconds: int, views: int}>
     */
    public function videoStats(array $videoIds): array
    {
        $chunks = array_chunk(array_unique($videoIds), 50);

        if ($chunks === []) {
            return [];
        }

        $responses = Http::pool(fn (Pool $pool): array => array_map(
            fn (array $chunk, int $i) => $pool
                ->as((string) $i)
                ->get(self::BASE_URL.'/videos', [
                    'key' => $this->apiKey,
                    'id' => implode(',', $chunk),
                    'part' => 'contentDetails,statistics',
                    'maxResults' => 50,
                ]),
            $chunks,
            array_keys($chunks),
        ));

        $result = [];

        foreach ($responses as $response) {
            if (! $response instanceof Response) {
                continue;
            }
            if (! $response->successful()) {
                continue;
            }
            foreach ($response->json('items', []) as $item) {
                $result[$item['id']] = [
                    'duration_seconds' => $this->isoDurationToSeconds($item['contentDetails']['duration'] ?? 'PT0S'),
                    'views' => (int) ($item['statistics']['viewCount'] ?? 0),
                ];
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function request(string $path, array $params): Response
    {
        return Http::baseUrl(self::BASE_URL)->get($path, $params)->throw();
    }

    private function isoDurationToSeconds(string $iso): int
    {
        return (new DateTimeImmutable('@0'))->add(new DateInterval($iso))->getTimestamp();
    }

    /**
     * @return array<string, mixed>
     */
    private function searchParams(string $query, int $limit): array
    {
        return [
            'key' => $this->apiKey,
            'q' => $query,
            'type' => 'playlist',
            'part' => 'snippet',
            'maxResults' => $limit,
        ];
    }

    private function searchCacheKey(string $query, int $limit): string
    {
        return 'yt.search.'.md5($query).'.'.$limit;
    }

    /**
     * @param  array<int, string>  $queries
     * @return array{0: array<string, array<int, array<string, mixed>>>, 1: array<int, string>}
     */
    private function partitionByCache(array $queries, int $limit): array
    {
        $hits = [];
        $misses = [];

        foreach ($queries as $query) {
            $key = $this->searchCacheKey($query, $limit);

            cache()->has($key)
                ? $hits[$query] = cache()->get($key)
                : $misses[] = $query;
        }

        return [$hits, $misses];
    }
}
