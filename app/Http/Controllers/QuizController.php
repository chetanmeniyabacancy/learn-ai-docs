<?php

namespace App\Http\Controllers;

use App\Models\QuizAttempt;
use App\Support\Curriculum;
use App\Support\Progress;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class QuizController extends Controller
{
    public function __construct(
        private Curriculum $curriculum,
        private Progress $progress,
    ) {}

    public function show(string $slug)
    {
        $module = $this->curriculum->find($slug) ?? throw new NotFoundHttpException;
        $questions = $this->curriculum->quiz($slug);

        abort_if($questions === [], 404);

        return view('quiz', [
            'module' => $module,
            'questions' => $questions,
            'result' => null,
        ]);
    }

    public function submit(Request $request, string $slug)
    {
        $module = $this->curriculum->find($slug) ?? throw new NotFoundHttpException;
        $questions = $this->curriculum->quiz($slug);

        abort_if($questions === [], 404);

        $answers = $request->input('answers', []);
        $score = 0;
        $review = [];

        foreach ($questions as $index => $question) {
            $given = isset($answers[$index]) ? (int) $answers[$index] : null;
            $correct = $given === $question['answer'];
            $score += $correct ? 1 : 0;

            $review[] = [
                'question' => $question,
                'given' => $given,
                'correct' => $correct,
            ];
        }

        $learnerId = $request->attributes->get('learner_id');

        QuizAttempt::create([
            'learner_id' => $learnerId,
            'module_slug' => $slug,
            'score' => $score,
            'total' => count($questions),
        ]);

        // Passing the quiz is what marks the module done — reading alone is
        // not evidence you can use the idea.
        if ($score / count($questions) >= 0.7) {
            $this->progress->complete($learnerId, $slug);
        }

        return view('quiz', [
            'module' => $module,
            'questions' => $questions,
            'result' => [
                'score' => $score,
                'total' => count($questions),
                'percentage' => (int) round($score / count($questions) * 100),
                'passed' => $score / count($questions) >= 0.7,
                'review' => $review,
            ],
        ]);
    }
}
