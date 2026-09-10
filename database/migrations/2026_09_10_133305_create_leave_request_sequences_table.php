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
        Schema::create('leave_request_sequences', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last_sequence')->default(0);
        });

        $sequences = [];

        DB::table('leave_requests')
            ->select('request_number')
            ->orderBy('id')
            ->each(function (object $leaveRequest) use (&$sequences): void {
                if (preg_match('/^IZN-(\d{4})-(\d{6})$/', $leaveRequest->request_number, $matches) !== 1) {
                    return;
                }

                $year = (int) $matches[1];
                $sequence = (int) $matches[2];
                $sequences[$year] = max($sequences[$year] ?? 0, $sequence);
            });

        foreach ($sequences as $year => $lastSequence) {
            DB::table('leave_request_sequences')->upsert([
                [
                    'year' => $year,
                    'last_sequence' => $lastSequence,
                ],
            ], ['year'], ['last_sequence']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_request_sequences');
    }
};
