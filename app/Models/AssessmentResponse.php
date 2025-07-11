<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StudentResponse extends Model
{
    protected $fillable = [
        'student',
        'assessment',
        'responses',
        'completed'
    ];

    protected $casts = [
        'responses' => 'array',
        'completed' => 'datetime'
    ];

    public function getCompletedFormattedAttribute()
    {
        return $this->completed ? $this->completed->format('jS F Y g:i A') : null;
    }

    public function isCompleted()
    {
        return !is_null($this->completed);
    }
}