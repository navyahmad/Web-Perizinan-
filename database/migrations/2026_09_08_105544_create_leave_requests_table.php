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
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 50)->unique();

            // Karyawan snapshot
            $table->string('name');
            $table->string('email')->index();
            $table->string('phone', 50);
            $table->string('department', 50); // General Solusindo / Tabinaco
            $table->string('position');

            // Leave details
            $table->string('type', 20)->index(); // late, half_day, leave, personal, sick
            $table->date('leave_date')->index(); // uniform leave date

            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('half_day_type', 50)->nullable();
            $table->time('estimated_arrival')->nullable();
            $table->decimal('duration', 5, 2)->nullable();

            $table->text('reason')->nullable();
            $table->boolean('emergency')->default(false);
            $table->text('emergency_reason')->nullable();
            $table->boolean('contactable')->nullable();

            // Status & Approval
            $table->string('status', 20)->default('pending')->index(); // pending, approved, rejected
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->text('approval_note')->nullable();
            $table->text('rejection_reason')->nullable();

            // Email Notification Tracking
            $table->string('email_status', 20)->default('pending'); // pending, sent, failed
            $table->timestamp('email_sent_at')->nullable();
            $table->text('email_error')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
