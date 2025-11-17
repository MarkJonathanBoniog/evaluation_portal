<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuperiorEvaluation extends Model
{
    protected $fillable = [
        'user_id',
        'instructor_user_id',
        'academic_period_id',
        'evaluated_as',
        'a1','a2','a3','a4','a5','a6',
        'b7','b8','b9','b10','b11','b12',
        'c12','c13','c14','c15',
        'comment',
    ];

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_user_id');
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }
}
