<?php

namespace App\Http\Controllers;

class GlossaryController extends Controller
{
    public function index()
    {
        return view('glossary', [
            'terms' => require resource_path('glossary.php'),
        ]);
    }
}
