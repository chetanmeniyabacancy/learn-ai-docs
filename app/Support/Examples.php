<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

/**
 * The example library.
 *
 * Every example is a small, self-contained PHP file under resources/examples
 * holding code AND the output that code produces. Nothing here calls an API,
 * so examples work for a reader with no key, offline, forever.
 *
 * Outputs are recorded, not live — the views say so plainly. Where an example
 * can be run for real, it carries a `live` key pointing at a playground panel.
 */
class Examples
{
    /** @return Collection<int, array<string, mixed>> */
    public function all(): Collection
    {
        return collect(File::files(resource_path('examples')))
            ->filter(fn ($file) => $file->getExtension() === 'php')
            ->sortBy(fn ($file) => $file->getFilename())
            ->map(fn ($file) => require $file->getPathname())
            ->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    public function forModule(string $slug): Collection
    {
        return $this->all()->where('module', $slug)->values();
    }

    /** @return array<string, mixed>|null */
    public function find(string $id): ?array
    {
        $examples = $this->all();
        $index = $examples->search(fn (array $example) => $example['id'] === $id);

        if ($index === false) {
            return null;
        }

        $example = $examples[$index];
        $example['previous'] = $examples[$index - 1] ?? null;
        $example['next'] = $examples[$index + 1] ?? null;

        return $example;
    }

    /** Examples grouped under their module, in curriculum order. */
    public function grouped(Curriculum $curriculum): Collection
    {
        $examples = $this->all()->groupBy('module');

        return collect($curriculum->modules())
            ->map(fn (array $module) => [
                'module' => $module,
                'examples' => $examples->get($module['slug'], collect()),
            ])
            ->filter(fn (array $group) => $group['examples']->isNotEmpty())
            ->values();
    }

    public function count(): int
    {
        return $this->all()->count();
    }
}
