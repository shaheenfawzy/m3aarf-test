<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

#[MaxTokens(2048)]
class YoutubeTitleGenerator implements Agent, Conversational, HasStructuredOutput, HasTools
{
    use Promptable;

    /**
     * @param  array<int, string>  $categories
     */
    public function __construct(private array $categories) {}

    public function instructions(): Stringable|string
    {
        $count = random_int(
            config()->integer('services.ai.titles_per_category_min'),
            config()->integer('services.ai.titles_per_category_max'),
        );

        return <<<PROMPT
            Generate YouTube search queries to find FULL COURSE PLAYLISTS for each category.
            Queries will be sent to YouTube Data API. Goal: discover multi-video learning series, not single videos.

            Rules:
            - Exactly $count queries per category
            - Each query MUST target a course or playlist (e.g. "full course", "tutorial series", "masterclass")
            - 3-8 words
            - English only, no duplicates, no dates, no emojis, no hashtags, no quotes
            - Vary skill levels (beginner, intermediate, advanced) across queries
        PROMPT;
    }

    /**
     * @return array{}
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * @return array{}
     */
    public function tools(): iterable
    {
        return [];
    }

    public function schema(JsonSchema $schema): array
    {
        $rules = [];

        foreach ($this->categories as $category) {
            $rules[$category] = $schema
                ->array()
                ->items($schema->string())
                ->required();
        }

        return $rules;
    }
}
