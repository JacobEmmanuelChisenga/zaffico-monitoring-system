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
        Schema::table('alert_rules', function (Blueprint $table) {
            // Add columns if they don't exist
            if (!Schema::hasColumn('alert_rules', 'condition_type')) {
                $table->string('condition_type')->default('status_down')->after('trigger_type');
            }
            if (!Schema::hasColumn('alert_rules', 'condition_value')) {
                $table->integer('condition_value')->nullable()->after('condition_type');
            }
            if (!Schema::hasColumn('alert_rules', 'severity')) {
                $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('high')->after('condition_value');
            }
            if (!Schema::hasColumn('alert_rules', 'notification_email')) {
                $table->string('notification_email')->nullable()->after('severity');
            }
            if (!Schema::hasColumn('alert_rules', 'notification_slack_webhook')) {
                $table->string('notification_slack_webhook')->nullable()->after('notification_email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alert_rules', function (Blueprint $table) {
            if (Schema::hasColumn('alert_rules', 'condition_type')) {
                $table->dropColumn('condition_type');
            }
            if (Schema::hasColumn('alert_rules', 'condition_value')) {
                $table->dropColumn('condition_value');
            }
            if (Schema::hasColumn('alert_rules', 'severity')) {
                $table->dropColumn('severity');
            }
            if (Schema::hasColumn('alert_rules', 'notification_email')) {
                $table->dropColumn('notification_email');
            }
            if (Schema::hasColumn('alert_rules', 'notification_slack_webhook')) {
                $table->dropColumn('notification_slack_webhook');
            }
        });
    }
};
