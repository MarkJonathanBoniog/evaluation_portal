<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('academic_periods', function (Blueprint $t) {
            $t->id();
            $t->foreignId('college_id')->constrained()->cascadeOnDelete();
            $t->foreignId('department_id')->constrained()->cascadeOnDelete();
            $t->unsignedSmallInteger('year_start'); // 2025
            $t->unsignedSmallInteger('year_end');   // 2026
            $t->enum('term', ['first','second','summer']);
            $t->foreignId('created_by')->constrained('users')->cascadeOnDelete(); // chairman/ced
            $t->timestamps();
            $t->unique(['college_id','department_id','year_start','year_end','term'],
            'academic_periods_unique_combo');
        });
    }
    public function down(): void {
        Schema::dropIfExists('academic_periods');
    }
};
