<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_records', function (Blueprint $table) {
            $table->id();

            // Foreign key linking to section_student
            $table->foreignId('section_student_fk')
                  ->constrained('section_student')
                  ->cascadeOnDelete();

            // Evaluator Type: student | chairman | dean | ced
            $table->enum('evaluated_as', ['student', 'chairman', 'dean', 'ced']);

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

            // Part C (12–15) — note: your numbering starts at 12 again
            $table->unsignedTinyInteger('c12')->nullable();
            $table->unsignedTinyInteger('c13')->nullable();
            $table->unsignedTinyInteger('c14')->nullable();
            $table->unsignedTinyInteger('c15')->nullable();

            // Final comment / suggestion
            $table->text('comment')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_records');
    }
};
