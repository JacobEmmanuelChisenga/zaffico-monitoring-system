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
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->enum('trigger_type', ['downtime', 'slow_response', 'status_code', 'consecutive_failures']);
            $table->integer('threshold_value')->nullable();
            $table->integer('consecutive_failures')->default(3);
            $table->json('notification_channels');
            $table->json('recipients');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['application_id', 'is_active']);
            $table->index('trigger_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
