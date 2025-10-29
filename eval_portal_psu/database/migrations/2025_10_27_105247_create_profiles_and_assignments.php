<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('student_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('student_number', 32)->unique();
            $t->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('instructor_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('instructor_uid', 64)->unique();
            $t->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('chairman_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('department_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['user_id','department_id']);
        });

        Schema::create('ced_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('college_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['user_id','college_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('ced_assignments');
        Schema::dropIfExists('chairman_assignments');
        Schema::dropIfExists('instructor_profiles');
        Schema::dropIfExists('student_profiles');
    }
};
