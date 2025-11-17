<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationRecord extends Model
{
    use HasFactory;

    protected $table = 'evaluation_records';

    protected $fillable = [
        'section_student_fk',
        'evaluated_as',
        'a1', 'a2', 'a3', 'a4', 'a5', 'a6',
        'b7', 'b8', 'b9', 'b10', 'b11', 'b12',
        'c12', 'c13', 'c14', 'c15',
        'comment'
    ];

    protected $casts = [
        'a1' => 'integer',
        'a2' => 'integer',
        'a3' => 'integer',
        'a4' => 'integer',
        'a5' => 'integer',
        'a6' => 'integer',
        'b7' => 'integer',
        'b8' => 'integer',
        'b9' => 'integer',
        'b10' => 'integer',
        'b11' => 'integer',
        'b12' => 'integer',
        'c12' => 'integer',
        'c13' => 'integer',
        'c14' => 'integer',
        'c15' => 'integer',
    ];

    /**
     * Get the section student relationship
     */
    public function sectionStudent()
    {
        return $this->belongsTo(SectionStudent::class, 'section_student_fk');
    }

    /**
     * Calculate total score
     */
    public function getTotalScoreAttribute()
    {
        return $this->a1 + $this->a2 + $this->a3 + $this->a4 + $this->a5 + $this->a6 +
            $this->b7 + $this->b8 + $this->b9 + $this->b10 + $this->b11 + $this->b12 +
            $this->c12 + $this->c13 + $this->c14 + $this->c15;
    }

    /**
     * Calculate computed rating
     */
    public function getComputedRatingAttribute()
    {
        return round(($this->total_score / 75) * 100, 2);
    }
}