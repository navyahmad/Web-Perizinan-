<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('hrd_processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hrd_processed_at')->nullable();
            $table->string('hrd_decision', 20)->nullable();
            $table->text('hrd_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hrd_processed_by');
            $table->dropColumn(['hrd_processed_at', 'hrd_decision', 'hrd_note']);
        });
    }
};
