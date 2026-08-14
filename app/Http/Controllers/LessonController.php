<?php

namespace App\Http\Controllers;

use App\Support\Curriculum;
use App\Support\Examples;
use App\Support\Progress;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LessonController extends Controller
{
    public function __construct(
        private Curriculum $curriculum,
        private Progress $progress,
        private Examples $examples,
    ) {}

    public function index(Request $request)
    {
        $learnerId = $request->attributes->get('learner_id');

        return view('home', [
            'modules' => $this->curriculum->modules(),
            'levels' => $this->curriculum->byLevel(),
            'completed' => $this->progress->completed($learnerId),
            'percentage' => $this->progress->percentage($learnerId),
            'next' => $this->progress->nextModule($learnerId),
            'scores' => $this->progress->bestScores($learnerId),
            'minutes' => $this->curriculum->minutes(),
            'exampleCount' => $this->examples->count(),
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $module = $this->curriculum->find($slug) ?? throw new NotFoundHttpException;
        $learnerId = $request->attributes->get('learner_id');

        return view('lesson', [
            'module' => $module,
            'body' => $this->curriculum->body($slug),
            'outline' => $this->curriculum->outline($slug),
            'examples' => $this->examples->forModule($slug),
            'hasQuiz' => $this->curriculum->hasQuiz($slug),
            'isComplete' => $this->progress->isComplete($learnerId, $slug),
            'completed' => $this->progress->completed($learnerId),
            'percentage' => $this->progress->percentage($learnerId),
            'levels' => $this->curriculum->byLevel(),
        ]);
    }

    public function complete(Request $request, string $slug)
    {
        $module = $this->curriculum->find($slug) ?? throw new NotFoundHttpException;
        $learnerId = $request->attributes->get('learner_id');

        if ($request->boolean('undo')) {
            $this->progress->uncomplete($learnerId, $slug);

            return back();
        }

        $this->progress->complete($learnerId, $slug);

        return $module['next']
            ? redirect()->route('lesson.show', $module['next']['slug'])
            : redirect()->route('home')->with('status', 'Level 1 complete. Go build something.');
    }

    public function reset(Request $request)
    {
        $this->progress->reset($request->attributes->get('learner_id'));

        return redirect()->route('home')->with('status', 'Progress cleared.');
    }
}
