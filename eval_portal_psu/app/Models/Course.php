<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = ['program_id', 'course_code', 'course_name'];

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'program_course')
            ->withTimestamps();
    }
}