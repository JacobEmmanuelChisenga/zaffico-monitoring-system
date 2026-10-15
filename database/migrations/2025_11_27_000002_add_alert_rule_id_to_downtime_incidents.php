<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('downtime_incidents', function (Blueprint $table) {
            if (!Schema::hasColumn('downtime_incidents', 'alert_rule_id')) {
                $table->foreignId('alert_rule_id')->nullable()->constrained('alert_rules')->onDelete('set null')->after('application_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('downtime_incidents', function (Blueprint $table) {
            if (Schema::hasColumn('downtime_incidents', 'alert_rule_id')) {
                $table->dropForeignIdFor('alert_rules');
                $table->dropColumn('alert_rule_id');
            }
        });
    }
};
