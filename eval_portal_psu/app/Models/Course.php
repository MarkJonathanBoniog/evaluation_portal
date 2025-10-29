<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = ['course_code', 'course_name'];

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'program_course')
            ->withTimestamps();
    }

    public function sections() { return $this->hasMany(\App\Models\Section::class); }
}