<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoOrder extends Model
{
    protected $fillable = [
        'reference', 'customer_name', 'customer_email', 'status',
        'total', 'currency', 'placed_on', 'expected_on', 'carrier',
    ];

    protected $casts = [
        'placed_on' => 'date',
        'expected_on' => 'date',
        'total' => 'decimal:2',
    ];
}
