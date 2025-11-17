<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SectionStudent extends Model
{
    use HasFactory;

    protected $table = 'section_student';

    protected $fillable = [
        'section_id',
        'student_user_id',
        'evaluated_at'
    ];

    protected $casts = [
        'evaluated_at' => 'datetime',
    ];

    /**
     * Get the section that this record belongs to
     */
    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    /**
     * Get the student user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    /**
     * Get the evaluation record for this section-student
     */
    public function evaluationRecord()
    {
        return $this->hasOne(EvaluationRecord::class, 'section_student_fk');
    }

    /**
     * Check if this student has evaluated
     */
    public function hasEvaluated()
    {
        return $this->evaluationRecord()->exists();
    }
}