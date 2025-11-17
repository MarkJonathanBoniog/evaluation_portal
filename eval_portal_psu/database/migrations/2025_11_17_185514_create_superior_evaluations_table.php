<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('superior_evaluations', function (Blueprint $table) {
            $table->id();

            // Who is doing the evaluation (chairman / dean / ced)
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Which instructor is being evaluated
            $table->foreignId('instructor_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Academic period context for the evaluation
            $table->foreignId('academic_period_id')
                ->constrained()
                ->cascadeOnDelete();

            // Evaluator role
            $table->enum('evaluated_as', ['chairman', 'dean', 'ced']);

            // Part A (1–6)
            $table->unsignedTinyInteger('a1')->nullable();
            $table->unsignedTinyInteger('a2')->nullable();
            $table->unsignedTinyInteger('a3')->nullable();
            $table->unsignedTinyInteger('a4')->nullable();
            $table->unsignedTinyInteger('a5')->nullable();
            $table->unsignedTinyInteger('a6')->nullable();

            // Part B (7–12)
            $table->unsignedTinyInteger('b7')->nullable();
            $table->unsignedTinyInteger('b8')->nullable();
            $table->unsignedTinyInteger('b9')->nullable();
            $table->unsignedTinyInteger('b10')->nullable();
            $table->unsignedTinyInteger('b11')->nullable();
            $table->unsignedTinyInteger('b12')->nullable();

            // Part C (12–15)
            $table->unsignedTinyInteger('c12')->nullable();
            $table->unsignedTinyInteger('c13')->nullable();
            $table->unsignedTinyInteger('c14')->nullable();
            $table->unsignedTinyInteger('c15')->nullable();

            // Final comment / suggestion
            $table->text('comment')->nullable();

            $table->timestamps();

            // Avoid duplicate evaluations for same combo
            $table->unique(
                ['user_id', 'instructor_user_id', 'academic_period_id', 'evaluated_as'],
                'superior_evals_unique_combo'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('superior_evaluations');
    }
};
