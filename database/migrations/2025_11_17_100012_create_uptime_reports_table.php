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
        Schema::create('uptime_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->date('report_date');
            $table->enum('period_type', ['daily', 'weekly', 'monthly']);
            $table->decimal('uptime_percentage', 5, 2);
            $table->integer('total_checks');
            $table->integer('successful_checks');
            $table->integer('failed_checks');
            $table->integer('total_downtime_minutes');
            $table->integer('incident_count');
            $table->integer('avg_response_time')->nullable();
            $table->integer('min_response_time')->nullable();
            $table->integer('max_response_time')->nullable();
            $table->timestamps();

            $table->unique(['application_id', 'report_date', 'period_type']);
            $table->index(['report_date', 'period_type']);
            $table->index('uptime_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uptime_reports');
    }
};
