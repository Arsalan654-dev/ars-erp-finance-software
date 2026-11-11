<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingCourse extends Model
{
    protected $fillable = ['title', 'description', 'category', 'duration_hours', 'status'];

    public function assignments()
    {
        return $this->hasMany(TrainingAssignment::class);
    }
}