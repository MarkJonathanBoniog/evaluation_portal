<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // programs belong to a department and a specific academic period
        Schema::create('programs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('academic_period_id')->constrained()->cascadeOnDelete();
            $t->foreignId('department_id')->constrained()->cascadeOnDelete();
            $t->string('name');              // e.g. BS IT
            $t->string('major')->nullable(); // e.g. Web & Mobile Dev
            $t->timestamps();

            // avoid duplicate program/major within the same department AND period
            $t->unique(['academic_period_id','department_id','name','major'], 'programs_unique_period_dept_name_major');
        });

        // courses are global; linked to programs via pivot
        Schema::create('courses', function (Blueprint $t) {
            $t->id();
            $t->string('course_code')->unique(); // e.g. SIA101
            $t->string('course_name');
            $t->timestamps();
        });

        // many-to-many: a program offers many courses; a course can be in many programs
        Schema::create('program_course', function (Blueprint $t) {
            $t->id();
            $t->foreignId('program_id')->constrained()->cascadeOnDelete();
            $t->foreignId('course_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['program_id','course_id'], 'program_course_unique');
        });
    }

    public function down(): void {
        Schema::dropIfExists('program_course');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('programs');
    }
};
