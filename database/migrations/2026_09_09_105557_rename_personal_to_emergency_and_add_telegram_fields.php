<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('telegram_status', 20)->default('pending');
            $table->timestamp('telegram_sent_at')->nullable();
            $table->text('telegram_error')->nullable();
        });

        // Migrate historical personal records to emergency
        DB::table('leave_requests')->where('type', 'personal')->update(['type' => 'emergency']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('leave_requests')->where('type', 'emergency')->update(['type' => 'personal']);

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['telegram_status', 'telegram_sent_at', 'telegram_error']);
        });
    }
};
