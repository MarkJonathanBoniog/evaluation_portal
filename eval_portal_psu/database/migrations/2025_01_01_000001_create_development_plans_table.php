<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('development_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academic_period_id');
            $table->unsignedBigInteger('instructor_user_id');
            $table->unsignedBigInteger('chairman_user_id');
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->text('development_plan')->nullable();
            $table->timestamps();

            $table->unique(['academic_period_id', 'instructor_user_id', 'chairman_user_id'], 'dev_plan_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('development_plans');
    }
};
