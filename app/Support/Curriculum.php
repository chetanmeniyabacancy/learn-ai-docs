<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Reads the curriculum defined in config/curriculum.php and pairs each module
 * with its markdown body and quiz file.
 */
class Curriculum
{
    /** @return array<int, array<string, mixed>> */
    public function modules(): array
    {
        return config('curriculum.modules');
    }

    /** @return array<string, mixed>|null */
    public function find(string $slug): ?array
    {
        foreach ($this->modules() as $index => $module) {
            if ($module['slug'] === $slug) {
                $module['index'] = $index;
                $module['previous'] = $this->modules()[$index - 1] ?? null;
                $module['next'] = $this->modules()[$index + 1] ?? null;

                return $module;
            }
        }

        return null;
    }

    public function first(): array
    {
        return $this->modules()[0];
    }

    /**
     * Modules grouped under their level, in order.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function byLevel(): Collection
    {
        $grouped = collect($this->modules())->groupBy('level');

        return collect(config('curriculum.levels'))
            ->map(fn (array $level, int $key) => $level + [
                'key' => $key,
                'modules' => $grouped->get($key, collect()),
            ])
            ->values();
    }

    public function count(): int
    {
        return count($this->modules());
    }

    /** Total estimated minutes for the whole track. */
    public function minutes(): int
    {
        return array_sum(array_column($this->modules(), 'minutes'));
    }

    /**
     * Render a lesson body to HTML. Cached in production so we parse markdown
     * once per deploy rather than once per request.
     */
    public function body(string $slug): string
    {
        $path = resource_path("content/{$slug}.md");

        if (! File::exists($path)) {
            return '<p>This lesson has not been written yet.</p>';
        }

        $render = fn () => $this->anchorHeadings(
            Str::markdown(File::get($path), [
                'html_input' => 'allow',
                'allow_unsafe_links' => false,
            ])
        );

        return app()->isProduction()
            ? cache()->rememberForever("lesson.{$slug}.".filemtime($path), $render)
            : $render();
    }

    /**
     * CommonMark does not add id attributes to headings, so the table of
     * contents would link nowhere. Add them here using the same slug the
     * outline uses, so the two always agree.
     */
    private function anchorHeadings(string $html): string
    {
        return preg_replace_callback(
            '/<(h[23])>(.*?)<\/\1>/s',
            fn (array $m) => sprintf(
                '<%s id="%s">%s</%s>',
                $m[1],
                // Decode entities first: CommonMark escapes quotes and
                // apostrophes, which would otherwise slug as "quot" and "039".
                Str::slug(html_entity_decode(strip_tags($m[2]), ENT_QUOTES | ENT_HTML5)),
                $m[2],
                $m[1],
            ),
            $html,
        ) ?? $html;
    }

    /**
     * Extract `## ` headings from the markdown so the lesson page can render a
     * table of contents pointing at the anchors added above.
     *
     * @return array<int, array{title: string, anchor: string}>
     */
    public function outline(string $slug): array
    {
        $path = resource_path("content/{$slug}.md");

        if (! File::exists($path)) {
            return [];
        }

        preg_match_all('/^## (.+)$/m', File::get($path), $matches);

        return collect($matches[1])
            ->map(fn (string $title) => [
                'title' => trim($title),
                'anchor' => Str::slug($title),
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function quiz(string $slug): array
    {
        $path = resource_path("quizzes/{$slug}.php");

        return File::exists($path) ? require $path : [];
    }

    public function hasQuiz(string $slug): bool
    {
        return count($this->quiz($slug)) > 0;
    }
}
