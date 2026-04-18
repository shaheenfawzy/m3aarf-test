<?php

declare(strict_types=1);

use App\Services\YoutubeService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    cache()->flush();
    $this->yt = new YoutubeService('test-key');
});

/**
 * @return array<string, array<int|string, array<string, array<string, string|array<string, array<string, string>>>>>>
 */
function searchResponse(string ...$playlistIds): array
{
    return [
        'items' => array_map(fn (string $id): array => [
            'id' => ['kind' => 'youtube#playlist', 'playlistId' => $id],
            'snippet' => [
                'title' => "Title for $id",
                'description' => 'desc',
                'thumbnails' => ['medium' => ['url' => "https://img/$id.jpg"]],
                'channelTitle' => 'Channel',
            ],
        ], $playlistIds),
    ];
}

it('caches search results to skip subsequent http calls', function (): void {
    Http::fake([
        '*search*' => Http::response(searchResponse('PL1', 'PL2')),
    ]);

    $first = $this->yt->search('laravel', 2);
    $second = $this->yt->search('laravel', 2);

    expect($first)->toBe($second)
        ->and($first)->toHaveCount(2);

    Http::assertSentCount(1);
});

it('searches many queries in one pool, mixing cache hits and misses', function (): void {
    cache()->put('yt.search.'.md5('cached').'.2', [['cached' => true]], 60);

    Http::fake([
        '*search*' => Http::response(searchResponse('PLfresh')),
    ]);

    $results = $this->yt->searchMany(['cached', 'fresh'], limit: 2);

    expect($results)->toHaveKeys(['cached', 'fresh'])
        ->and($results['cached'])->toBe([['cached' => true]])
        ->and($results['fresh'])->toHaveCount(1);

    Http::assertSentCount(1);
});

it('returns empty items for failed pool responses without throwing', function (): void {
    Http::fake([
        '*search*' => Http::sequence()
            ->push(searchResponse('PLok'))
            ->push(['error' => 'boom'], 500),
    ]);

    $results = $this->yt->searchMany(['ok', 'bad'], limit: 2);

    expect($results['ok'])->toHaveCount(1)
        ->and($results['bad'])->toBe([]);
});

it('throws on non-pooled search failure', function (): void {
    Http::fake([
        '*search*' => Http::response(['error' => 'quota'], 403),
    ]);

    $this->yt->search('laravel', 2);
})->throws(RequestException::class);

it('aggregates playlist stats from videos endpoint', function (): void {
    Http::fake([
        '*playlistItems*' => Http::response([
            'items' => [
                ['contentDetails' => ['videoId' => 'V1']],
                ['contentDetails' => ['videoId' => 'V2']],
            ],
        ]),
        '*videos*' => Http::response([
            'items' => [
                ['id' => 'V1', 'contentDetails' => ['duration' => 'PT1H'], 'statistics' => ['viewCount' => '100']],
                ['id' => 'V2', 'contentDetails' => ['duration' => 'PT30M'], 'statistics' => ['viewCount' => '50']],
            ],
        ]),
    ]);

    $stats = $this->yt->enrichPlaylists(['PL1']);

    expect($stats['PL1'])->toBe([
        'video_count' => 2,
        'total_duration_seconds' => 5400,
        'total_views' => 150,
    ]);
});

it('returns empty array when enriching no playlists', function (): void {
    Http::fake();

    expect($this->yt->enrichPlaylists([]))->toBe([]);

    Http::assertSentCount(0);
});

it('handles playlists with no videos gracefully', function (): void {
    Http::fake([
        '*playlistItems*' => Http::response(['items' => []]),
        '*videos*' => Http::response(['items' => []]),
    ]);

    $stats = $this->yt->enrichPlaylists(['PLempty']);

    expect($stats['PLempty'])->toBe([
        'video_count' => 0,
        'total_duration_seconds' => 0,
        'total_views' => 0,
    ]);
});
