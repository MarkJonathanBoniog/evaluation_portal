<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('development_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('development_plans', 'proposed_activities')) {
                $table->text('proposed_activities')->nullable()->after('areas_for_improvement');
            }
            if (! Schema::hasColumn('development_plans', 'action_plan')) {
                $table->text('action_plan')->nullable()->after('proposed_activities');
            }
        });
    }

    public function down(): void
    {
        Schema::table('development_plans', function (Blueprint $table) {
            if (Schema::hasColumn('development_plans', 'proposed_activities')) {
                $table->dropColumn('proposed_activities');
            }
            if (Schema::hasColumn('development_plans', 'action_plan')) {
                $table->dropColumn('action_plan');
            }
        });
    }
};
