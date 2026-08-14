<?php

namespace App\Support;

use App\Models\LearnerProgress;
use App\Models\QuizAttempt;
use Illuminate\Support\Collection;

class Progress
{
    public function __construct(private Curriculum $curriculum) {}

    /** Slugs this learner has marked complete. */
    public function completed(string $learnerId): Collection
    {
        return LearnerProgress::query()
            ->where('learner_id', $learnerId)
            ->whereNotNull('completed_at')
            ->pluck('module_slug');
    }

    public function isComplete(string $learnerId, string $slug): bool
    {
        return $this->completed($learnerId)->contains($slug);
    }

    public function complete(string $learnerId, string $slug): void
    {
        LearnerProgress::updateOrCreate(
            ['learner_id' => $learnerId, 'module_slug' => $slug],
            ['completed_at' => now()],
        );
    }

    public function uncomplete(string $learnerId, string $slug): void
    {
        LearnerProgress::query()
            ->where('learner_id', $learnerId)
            ->where('module_slug', $slug)
            ->delete();
    }

    public function reset(string $learnerId): void
    {
        LearnerProgress::where('learner_id', $learnerId)->delete();
        QuizAttempt::where('learner_id', $learnerId)->delete();
    }

    public function percentage(string $learnerId): int
    {
        $total = $this->curriculum->count();

        return $total === 0 ? 0 : (int) round($this->completed($learnerId)->count() / $total * 100);
    }

    /** Best quiz score per module, keyed by slug. */
    public function bestScores(string $learnerId): Collection
    {
        return QuizAttempt::query()
            ->where('learner_id', $learnerId)
            ->get()
            ->groupBy('module_slug')
            ->map(fn (Collection $attempts) => $attempts->sortByDesc('score')->first());
    }

    /** The module the learner should open next. */
    public function nextModule(string $learnerId): array
    {
        $done = $this->completed($learnerId);

        foreach ($this->curriculum->modules() as $module) {
            if (! $done->contains($module['slug'])) {
                return $module;
            }
        }

        return $this->curriculum->first();
    }
}
