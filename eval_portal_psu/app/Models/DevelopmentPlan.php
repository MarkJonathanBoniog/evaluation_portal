<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DevelopmentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_period_id',
        'instructor_user_id',
        'chairman_user_id',
        'areas_for_improvement',
        'proposed_activities',
        'action_plan',
    ];
}
