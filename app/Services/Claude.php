<?php

namespace App\Services;

use Anthropic\Client;
use Anthropic\Messages\Message;
use Anthropic\Messages\ToolUseBlock;

/**
 * A thin wrapper around the official Anthropic PHP SDK.
 *
 * Everything the playground does goes through here, so the code you read in
 * the lessons is the same code that actually runs on this site.
 */
class Claude
{
    public function __construct(private ?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?: config('claude.api_key');
    }

    public function configured(): bool
    {
        return filled($this->apiKey);
    }

    public function client(): Client
    {
        return new Client(apiKey: $this->apiKey);
    }

    /**
     * Concatenate every text block in a response.
     *
     * A response is a *list* of blocks, not a string. With thinking enabled the
     * first block may not be text at all, so never reach for `content[0]->text`.
     */
    public static function text(Message $message): string
    {
        $parts = [];

        foreach ($message->content as $block) {
            if (($block->type ?? null) === 'text') {
                $parts[] = $block->text;
            }
        }

        return trim(implode("\n", $parts));
    }

    /** @return array<int, ToolUseBlock> */
    public static function toolCalls(Message $message): array
    {
        return array_values(array_filter(
            $message->content,
            fn ($block) => ($block->type ?? null) === 'tool_use',
        ));
    }

    /**
     * Token counts plus a rough dollar estimate, for the "what did that cost?"
     * panel in the playground.
     *
     * @return array<string, mixed>
     */
    public static function usage(Message $message): array
    {
        $prices = config("claude.models.{$message->model}")
            ?? ['input' => 0.0, 'output' => 0.0];

        $input = $message->usage->inputTokens;
        $output = $message->usage->outputTokens;
        $cacheRead = $message->usage->cacheReadInputTokens ?? 0;
        $cacheWrite = $message->usage->cacheCreationInputTokens ?? 0;

        $cost = ($input / 1_000_000) * $prices['input']
            + ($output / 1_000_000) * $prices['output']
            + ($cacheRead / 1_000_000) * $prices['input'] * 0.1
            + ($cacheWrite / 1_000_000) * $prices['input'] * 1.25;

        return [
            'model' => $message->model,
            'input_tokens' => $input,
            'output_tokens' => $output,
            'cache_read_tokens' => $cacheRead,
            'cache_write_tokens' => $cacheWrite,
            'stop_reason' => $message->stopReason,
            'cost' => $cost,
            'cost_label' => '$'.number_format($cost, 6),
        ];
    }

    /**
     * Safety classifiers can decline a request. That is an HTTP 200 with
     * stop_reason "refusal" — not an exception — so check it before you read
     * the content, or you will render an empty answer.
     */
    public static function wasRefused(Message $message): bool
    {
        return $message->stopReason === 'refusal';
    }
}
