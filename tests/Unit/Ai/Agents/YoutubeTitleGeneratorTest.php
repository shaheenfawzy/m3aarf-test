<?php

declare(strict_types=1);

use App\Ai\Agents\YoutubeTitleGenerator;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;

beforeEach(function (): void {
    config()->set('services.ai.titles_per_category_min', 5);
    config()->set('services.ai.titles_per_category_max', 7);
});

it('builds instructions with the configured count range', function (): void {
    $agent = new YoutubeTitleGenerator(['laravel']);

    $instructions = (string) $agent->instructions();

    expect($instructions)->toContain('FULL COURSE PLAYLISTS');
    expect($instructions)->toMatch('/Exactly [567] queries per category/');
});

it('builds schema with one required string array per category', function (): void {
    $agent = new YoutubeTitleGenerator(['laravel', 'react native']);

    $rules = $agent->schema(new JsonSchemaTypeFactory);

    expect($rules)->toHaveKeys(['laravel', 'react native']);
    expect($rules['laravel']->toArray())
        ->toMatchArray(['type' => 'array', 'items' => ['type' => 'string']]);
});

it('returns empty messages and tools', function (): void {
    $agent = new YoutubeTitleGenerator(['laravel']);

    expect($agent->messages())->toBe([]);
    expect($agent->tools())->toBe([]);
});

it('throws when min is greater than max', function (): void {
    config()->set('services.ai.titles_per_category_min', 20);
    config()->set('services.ai.titles_per_category_max', 10);

    (new YoutubeTitleGenerator(['laravel']))->instructions();
})->throws(ValueError::class);
