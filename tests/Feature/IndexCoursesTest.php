<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\CourseCategory;

it('returns courses for the requested categories ordered by views', function (): void {
    $laravel = CourseCategory::create(['name' => 'laravel']);
    $vue = CourseCategory::create(['name' => 'vue']);

    Course::create([
        'playlist_id' => 'PL1', 'title' => 'Laravel A', 'description' => '', 'thumbnail' => '',
        'channel' => 'c', 'category_id' => $laravel->id,
        'video_count' => 10, 'total_duration_seconds' => 1000, 'total_views' => 500,
    ]);
    Course::create([
        'playlist_id' => 'PL2', 'title' => 'Laravel B', 'description' => '', 'thumbnail' => '',
        'channel' => 'c', 'category_id' => $laravel->id,
        'video_count' => 5, 'total_duration_seconds' => 600, 'total_views' => 2000,
    ]);
    Course::create([
        'playlist_id' => 'PL3', 'title' => 'Vue A', 'description' => '', 'thumbnail' => '',
        'channel' => 'c', 'category_id' => $vue->id,
        'video_count' => 8, 'total_duration_seconds' => 800, 'total_views' => 100,
    ]);

    $this->getJson('/courses?categories[]=laravel')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.title', 'Laravel B')
        ->assertJsonPath('data.1.title', 'Laravel A');
});

it('returns empty when no categories match in the database', function (): void {
    $this->getJson('/courses?categories[]=nonexistent')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('rejects missing categories', function (): void {
    $this->getJson('/courses')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('categories');
});

it('rejects too many categories', function (): void {
    $params = http_build_query(['categories' => array_fill(0, 11, 'cat')]);

    $this->getJson("/courses?$params")
        ->assertJsonValidationErrors('categories');
});

it('eager loads category relation in payload', function (): void {
    $cat = CourseCategory::create(['name' => 'laravel']);

    Course::create([
        'playlist_id' => 'PL1', 'title' => 'X', 'description' => '', 'thumbnail' => '',
        'channel' => 'c', 'category_id' => $cat->id,
        'video_count' => 1, 'total_duration_seconds' => 60, 'total_views' => 5,
    ]);

    $this->getJson('/courses?categories[]=laravel')
        ->assertJsonPath('data.0.category.id', $cat->id)
        ->assertJsonPath('data.0.category.name', 'laravel');
});
