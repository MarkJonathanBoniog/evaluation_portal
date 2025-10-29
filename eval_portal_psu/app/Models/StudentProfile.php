<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    protected $fillable = ['user_id','student_number','program_id'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function program(): BelongsTo { return $this->belongsTo(Program::class); }
}
