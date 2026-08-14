<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearnerProgress extends Model
{
    protected $table = 'learner_progress';

    protected $fillable = ['learner_id', 'module_slug', 'completed_at'];

    protected $casts = ['completed_at' => 'datetime'];
}
