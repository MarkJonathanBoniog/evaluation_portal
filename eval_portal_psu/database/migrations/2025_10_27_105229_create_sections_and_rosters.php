<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('academic_period_id')->constrained()->cascadeOnDelete();
            $t->foreignId('program_id')->constrained()->cascadeOnDelete();
            $t->foreignId('course_id')->constrained()->cascadeOnDelete();
            $t->string('section_label', 16); // "A", "B", "3A"
            $t->foreignId('instructor_user_id')->constrained('users')->cascadeOnDelete();
            $t->timestamps();

            $t->unique(['academic_period_id','program_id','course_id','section_label'], 'sections_unique_combo');
            $t->index(['program_id','course_id']);
        });

        Schema::create('section_student', function (Blueprint $t) {
            $t->id();
            $t->foreignId('section_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $t->timestamp('evaluated_at')->nullable()->index('evaluated_at'); 
            $t->timestamps();
            $t->unique(['section_id','student_user_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('section_student');
        Schema::dropIfExists('sections');
    }
};
