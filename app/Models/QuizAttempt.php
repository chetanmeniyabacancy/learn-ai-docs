<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    protected $fillable = ['learner_id', 'module_slug', 'score', 'total'];

    public function percentage(): int
    {
        return $this->total === 0 ? 0 : (int) round($this->score / $this->total * 100);
    }
}
