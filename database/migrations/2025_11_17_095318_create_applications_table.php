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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address');
            $table->integer('port')->default(80);
            $table->enum('protocol', ['http', 'https', 'ping', 'tcp'])->default('http');
            $table->string('endpoint')->nullable();
            $table->text('description')->nullable();
            $table->integer('check_interval')->default(5);
            $table->integer('timeout')->default(30);
            $table->enum('status', ['up', 'down', 'unknown'])->default('unknown');
            $table->timestamp('last_checked_at')->nullable();
            $table->integer('response_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_active']);
            $table->index('ip_address');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
