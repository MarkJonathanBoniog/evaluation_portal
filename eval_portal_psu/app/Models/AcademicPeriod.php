<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicPeriod extends Model
{
    protected $fillable = [
        'college_id','department_id','year_start','year_end','term','created_by'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function college(): BelongsTo { return $this->belongsTo(College::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function sections(): HasMany { return $this->hasMany(Section::class); }
}
