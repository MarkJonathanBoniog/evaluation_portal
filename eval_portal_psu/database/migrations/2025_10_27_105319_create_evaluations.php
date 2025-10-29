<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('evaluation_forms', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('evaluation_questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('form_id')->constrained('evaluation_forms')->cascadeOnDelete();
            $t->string('text');
            $t->enum('type', ['scale','text'])->default('scale');
            $t->unsignedTinyInteger('scale_max')->default(5);
            $t->unsignedSmallInteger('position')->default(1);
            $t->timestamps();
        });

        Schema::create('evaluation_responses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('form_id')->constrained('evaluation_forms')->cascadeOnDelete();
            $t->foreignId('question_id')->constrained('evaluation_questions')->cascadeOnDelete();
            $t->foreignId('section_id')->constrained()->cascadeOnDelete();
            $t->foreignId('instructor_user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $t->unsignedTinyInteger('score')->nullable();
            $t->text('answer_text')->nullable();
            $t->timestamps();

            $t->index(['section_id','instructor_user_id','student_user_id'], 'eval_resp_idx');
        });
    }
    public function down(): void {
        Schema::dropIfExists('evaluation_responses');
        Schema::dropIfExists('evaluation_questions');
        Schema::dropIfExists('evaluation_forms');
    }
};
