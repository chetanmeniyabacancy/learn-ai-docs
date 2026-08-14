<?php

use App\Http\Controllers\ExampleController;
use App\Http\Controllers\GlossaryController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\PlaygroundController;
use App\Http\Controllers\QuizController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LessonController::class, 'index'])->name('home');

Route::get('/glossary', [GlossaryController::class, 'index'])->name('glossary');

// Examples: code plus its output, no API key needed.
Route::get('/examples', [ExampleController::class, 'index'])->name('examples.index');
Route::get('/examples/{id}', [ExampleController::class, 'show'])->name('examples.show');

Route::prefix('learn')->name('lesson.')->group(function () {
    Route::get('/{slug}', [LessonController::class, 'show'])->name('show');
    Route::post('/{slug}/complete', [LessonController::class, 'complete'])->name('complete');
    Route::get('/{slug}/quiz', [QuizController::class, 'show'])->name('quiz');
    Route::post('/{slug}/quiz', [QuizController::class, 'submit'])->name('quiz.submit');
});

Route::post('/progress/reset', [LessonController::class, 'reset'])->name('progress.reset');

Route::prefix('playground')->name('playground.')->group(function () {
    Route::get('/', [PlaygroundController::class, 'index'])->name('index');
    Route::post('/key', [PlaygroundController::class, 'saveKey'])->name('key');
    Route::delete('/key', [PlaygroundController::class, 'forgetKey'])->name('key.forget');

    Route::post('/chat', [PlaygroundController::class, 'chat'])->name('chat');
    Route::post('/extract', [PlaygroundController::class, 'extract'])->name('extract');
    Route::post('/tools', [PlaygroundController::class, 'tools'])->name('tools');
    Route::post('/rag', [PlaygroundController::class, 'rag'])->name('rag');
});
