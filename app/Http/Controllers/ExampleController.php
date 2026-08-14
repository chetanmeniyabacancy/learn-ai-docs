<?php

namespace App\Http\Controllers;

use App\Support\Curriculum;
use App\Support\Examples;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExampleController extends Controller
{
    public function __construct(
        private Examples $examples,
        private Curriculum $curriculum,
    ) {}

    public function index()
    {
        return view('examples.index', [
            'groups' => $this->examples->grouped($this->curriculum),
            'total' => $this->examples->count(),
        ]);
    }

    public function show(string $id)
    {
        $example = $this->examples->find($id) ?? throw new NotFoundHttpException;

        return view('examples.show', [
            'example' => $example,
            'module' => $this->curriculum->find($example['module']),
        ]);
    }
}
