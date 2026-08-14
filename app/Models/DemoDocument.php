<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoDocument extends Model
{
    protected $fillable = ['learner_id', 'title', 'body'];
}
