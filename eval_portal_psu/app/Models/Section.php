<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Section extends Model
{
    protected $fillable = [
        'academic_period_id','program_id','course_id','section_label','instructor_user_id'
    ];

    public function period(): BelongsTo { return $this->belongsTo(AcademicPeriod::class,'academic_period_id'); }
    public function program(): BelongsTo { return $this->belongsTo(Program::class); }
    public function course(): BelongsTo { return $this->belongsTo(Course::class); }
    public function instructor(): BelongsTo { return $this->belongsTo(User::class,'instructor_user_id'); }

    public function students(): BelongsToMany {
        return $this->belongsToMany(User::class,'section_student','section_id','student_user_id')->withPivot('evaluated_at')->withTimestamps();
    }
}
