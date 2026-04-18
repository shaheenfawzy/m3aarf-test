<?php

declare(strict_types=1);

use App\Ai\Agents\YoutubeTitleGenerator;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Services\CourseDiscoveryService;
use App\Services\YoutubeService;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    cache()->flush();
    config()->set('services.ai.titles_per_category_min', 1);
    config()->set('services.ai.titles_per_category_max', 1);

    $this->service = new CourseDiscoveryService(new YoutubeService('test-key'));
});

function fakeYoutubeRoutes(): void
{
    Http::fake([
        '*search*' => Http::response([
            'items' => [
                [
                    'id' => ['playlistId' => 'PL1'],
                    'snippet' => [
                        'title' => 'Laravel Course',
                        'description' => 'desc',
                        'thumbnails' => ['medium' => ['url' => 'https://img/PL1.jpg']],
                        'channelTitle' => 'Laracasts',
                    ],
                ],
            ],
        ]),
        '*playlistItems*' => Http::response([
            'items' => [['contentDetails' => ['videoId' => 'V1']]],
        ]),
        '*videos*' => Http::response([
            'items' => [[
                'id' => 'V1',
                'contentDetails' => ['duration' => 'PT1H'],
                'statistics' => ['viewCount' => '500'],
            ]],
        ]),
    ]);
}

it('upserts categories and courses end-to-end', function (): void {
    YoutubeTitleGenerator::fake([['laravel' => ['laravel full course']]]);
    fakeYoutubeRoutes();

    $ids = $this->service->discover(['laravel']);

    expect($ids)->toHaveCount(1)
        ->and(CourseCategory::where('name', 'laravel')->exists())->toBeTrue();

    $course = Course::firstWhere('playlist_id', 'PL1');

    expect($course)->not->toBeNull()
        ->and($course->title)->toBe('Laravel Course')
        ->and($course->channel)->toBe('Laracasts')
        ->and($course->video_count)->toBe(1)
        ->and($course->total_duration_seconds)->toBe(3600)
        ->and($course->total_views)->toBe(500);
});

it('is idempotent — running twice does not create duplicates', function (): void {
    YoutubeTitleGenerator::fake([['laravel' => ['laravel full course']]]);
    fakeYoutubeRoutes();

    $this->service->discover(['laravel']);
    $this->service->discover(['laravel']);

    expect(Course::count())->toBe(1)
        ->and(CourseCategory::count())->toBe(1);
});

it('caches generated titles per category', function (): void {
    YoutubeTitleGenerator::fake([['laravel' => ['laravel full course']]]);
    fakeYoutubeRoutes();

    expect(cache()->get('ai.titles.'.md5('laravel')))->toBeNull();

    $this->service->discover(['laravel']);

    expect(cache()->get('ai.titles.'.md5('laravel')))->toBe(['laravel full course']);
});

it('deduplicates the same playlist when returned for multiple titles', function (): void {
    YoutubeTitleGenerator::fake([[
        'laravel' => ['laravel full course', 'laravel tutorial'],
    ]]);
    config()->set('services.ai.titles_per_category_max', 2);
    fakeYoutubeRoutes();

    $this->service->discover(['laravel']);

    expect(Course::count())->toBe(1);
});

it('skips course upsert when youtube returns no playlists', function (): void {
    YoutubeTitleGenerator::fake([['laravel' => ['laravel full course']]]);
    Http::fake([
        '*search*' => Http::response(['items' => []]),
    ]);

    $ids = $this->service->discover(['laravel']);

    expect($ids)->toHaveCount(1)
        ->and(Course::count())->toBe(0);
});
