<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'id',
        'stem',
        'options',
        'correct',
        'strand',
        'hint'
    ];

    protected $casts = [
        'options' => 'array'
    ];
}
