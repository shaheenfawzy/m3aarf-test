<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Ai\Agents\YoutubeTitleGenerator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;

use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

#[Signature('chat')]
#[Description('test ai models')]
class ChatCommand extends Command
{
    public function handle(): void
    {
        $categories = array_map(
            'trim',
            explode(',', text('what are the comma separated categories'))
        );

        $agent = YoutubeTitleGenerator::make(
            model: 'google/gemini-3.1-flash-lite-preview',
            categories: $categories
        );

        $response = spin(
            fn (): AgentResponse => $agent->prompt('suggest titles for those categories '.implode(', ', $categories)),
            'thinking...'
        );

        if (! $response instanceof StructuredAgentResponse) {
            throw new RuntimeException('Expected structured response');
        }

        dd($response->structured);
    }
}
