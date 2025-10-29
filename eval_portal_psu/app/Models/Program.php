<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    protected $fillable = ['academic_period_id','department_id','name','major'];

    public function academicPeriod() { return $this->belongsTo(\App\Models\AcademicPeriod::class); }
    public function department()     { return $this->belongsTo(\App\Models\Department::class); }
    public function courses()        { return $this->belongsToMany(\App\Models\Course::class, 'program_course')->withTimestamps(); }
    public function sections(): HasMany { return $this->hasMany(Section::class); }
}
