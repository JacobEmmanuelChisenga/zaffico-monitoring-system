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
        Schema::create('monitoring_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['up', 'down', 'timeout', 'error']);
            $table->integer('response_time')->nullable();
            $table->integer('status_code')->nullable();
            $table->text('error_message')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['application_id', 'checked_at']); // most queries
            $table->index(['status', 'checked_at']);         // for downtime reports
            $table->index('response_time');                  // for slow response alerts
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_checks');
    }
};
