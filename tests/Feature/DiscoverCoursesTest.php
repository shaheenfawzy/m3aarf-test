<?php

declare(strict_types=1);

use App\Ai\Agents\YoutubeTitleGenerator;
use App\Models\Course;
use App\Models\CourseCategory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function (): void {
    cache()->flush();
    RateLimiter::clear('5,1|127.0.0.1');
    config()->set('services.ai.titles_per_category_min', 1);
    config()->set('services.ai.titles_per_category_max', 1);
});

function fakeYoutubeRoutesWithPlaylists(int $playlistCount): void
{
    $playlists = collect(range(1, $playlistCount))->map(fn (int $i): array => [
        'id' => ['playlistId' => "PL$i"],
        'snippet' => [
            'title' => "Course $i",
            'description' => 'desc',
            'thumbnails' => ['medium' => ['url' => "https://img/PL$i.jpg"]],
            'channelTitle' => 'Channel',
        ],
    ])->all();

    Http::fake([
        '*search*' => Http::response(['items' => $playlists]),
        '*playlistItems*' => Http::response([
            'items' => [['contentDetails' => ['videoId' => 'V1']]],
        ]),
        '*videos*' => Http::response([
            'items' => [[
                'id' => 'V1',
                'contentDetails' => ['duration' => 'PT30M'],
                'statistics' => ['viewCount' => '100'],
            ]],
        ]),
    ]);
}

it('returns category ids for the requested categories', function (): void {
    YoutubeTitleGenerator::fake([['laravel' => ['laravel full course']]]);
    fakeYoutubeRoutesWithPlaylists(3);

    $response = $this->postJson('/courses/discover', ['categories' => ['laravel']]);

    $response->assertOk()->assertJsonStructure(['category_ids']);

    expect($response->json('category_ids'))
        ->toEqual(CourseCategory::query()->where('name', 'laravel')->pluck('id')->all());
});

it('persists discovered courses to the database', function (): void {
    YoutubeTitleGenerator::fake([['laravel' => ['laravel full course']]]);
    fakeYoutubeRoutesWithPlaylists(12);

    $this->postJson('/courses/discover', ['categories' => ['laravel']])->assertOk();

    expect(Course::count())->toBe(12);
});

it('is idempotent across repeat calls', function (): void {
    YoutubeTitleGenerator::fake([['laravel' => ['laravel full course']]]);
    fakeYoutubeRoutesWithPlaylists(3);

    $this->postJson('/courses/discover', ['categories' => ['laravel']])->assertOk();
    $this->postJson('/courses/discover', ['categories' => ['laravel']])->assertOk();

    expect(Course::count())->toBe(3);
});

it('rejects missing categories', function (): void {
    $this->postJson('/courses/discover', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('categories');
});

it('rejects too many categories', function (): void {
    $this->postJson('/courses/discover', [
        'categories' => array_fill(0, 11, 'cat'),
    ])->assertJsonValidationErrors('categories');
});

it('throttles after 5 requests per minute', function (): void {
    YoutubeTitleGenerator::fake([['laravel' => ['laravel full course']]]);
    fakeYoutubeRoutesWithPlaylists(1);

    foreach (range(1, 5) as $_) {
        $this->postJson('/courses/discover', ['categories' => ['laravel']])->assertOk();
    }

    $this->postJson('/courses/discover', ['categories' => ['laravel']])
        ->assertStatus(429);
});

it('degrades gracefully when youtube search returns nothing', function (): void {
    YoutubeTitleGenerator::fake([['laravel' => ['laravel full course']]]);
    Http::fake(['*search*' => Http::response(['items' => []])]);

    $this->postJson('/courses/discover', ['categories' => ['laravel']])
        ->assertOk()
        ->assertJsonStructure(['category_ids']);

    expect(Course::count())->toBe(0);
});

it('degrades gracefully when video stats endpoint fails', function (): void {
    YoutubeTitleGenerator::fake([['laravel' => ['laravel full course']]]);
    Http::fake([
        '*search*' => Http::response(['items' => [[
            'id' => ['playlistId' => 'PL1'],
            'snippet' => [
                'title' => 'Course', 'description' => 'd',
                'thumbnails' => ['medium' => ['url' => 'x']],
                'channelTitle' => 'c',
            ],
        ]]]),
        '*playlistItems*' => Http::response([
            'items' => [['contentDetails' => ['videoId' => 'V1']]],
        ]),
        '*videos*' => Http::response(['error' => 'quota'], 403),
    ]);

    $this->postJson('/courses/discover', ['categories' => ['laravel']])
        ->assertOk();

    expect(Course::first())
        ->video_count->toBe(1)
        ->total_views->toBe(0)
        ->total_duration_seconds->toBe(0);
});
