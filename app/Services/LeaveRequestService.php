<?php

namespace App\Services;

use App\Mail\LeaveRequestApprovedMail;
use App\Mail\LeaveRequestRejectedMail;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LeaveRequestService
{
    /**
     * Generate unique sequential request number: IZN-YYYY-XXXXXX
     */
    public function generateRequestNumber(): string
    {
        $year = Carbon::now('Asia/Jakarta')->year;
        $prefix = "IZN-{$year}-";

        return DB::transaction(function () use ($year, $prefix) {
            DB::table('leave_request_sequences')->insertOrIgnore([
                [
                    'year' => $year,
                    'last_sequence' => 0,
                ],
            ]);

            $lastSequence = DB::table('leave_request_sequences')
                ->where('year', $year)
                ->lockForUpdate()
                ->value('last_sequence');

            $nextSequence = ((int) $lastSequence) + 1;

            DB::table('leave_request_sequences')
                ->where('year', $year)
                ->update(['last_sequence' => $nextSequence]);

            return sprintf('%s%06d', $prefix, $nextSequence);
        });
    }

    /**
     * Approve pending leave request (Atomic with Concurrency Row Lock)
     *
     * @return array{success: bool, message: string, request?: LeaveRequest}
     */
    public function approve(int|LeaveRequest $leaveRequest, User $user, ?string $note = null): array
    {
        $id = $leaveRequest instanceof LeaveRequest ? $leaveRequest->id : $leaveRequest;

        $result = DB::transaction(function () use ($id, $user, $note) {
            /** @var LeaveRequest|null $target */
            $target = LeaveRequest::with('processor')->where('id', $id)->lockForUpdate()->first();

            if (! $target) {
                return ['success' => false, 'message' => 'Data pengajuan tidak ditemukan.'];
            }

            if ($target->status !== 'pending') {
                $role = strtoupper($target->processor->role ?? 'Petugas');

                return [
                    'success' => false,
                    'message' => "Pengajuan ini sudah diproses sebelumnya oleh {$role}.",
                    'request' => $target,
                ];
            }

            $target->status = 'approved';
            $target->processed_by = $user->id;
            $target->processed_at = now();
            $target->approval_note = $note;
            $target->save();

            return [
                'success' => true,
                'message' => "Pengajuan {$target->request_number} berhasil disetujui.",
                'request' => $target,
            ];
        });

        if ($result['success'] && isset($result['request'])) {
            $this->dispatchNotificationEmail($result['request'], 'approved');
        }

        return $result;
    }

    /**
     * Reject pending leave request (Atomic with Concurrency Row Lock & Mandatory Reason)
     *
     * @return array{success: bool, message: string, request?: LeaveRequest}
     */
    public function reject(int|LeaveRequest $leaveRequest, User $user, string $reason): array
    {
        $id = $leaveRequest instanceof LeaveRequest ? $leaveRequest->id : $leaveRequest;

        $cleanReason = trim($reason);
        if (empty($cleanReason)) {
            return [
                'success' => false,
                'message' => 'Alasan penolakan wajib diisi.',
            ];
        }

        $result = DB::transaction(function () use ($id, $user, $cleanReason) {
            /** @var LeaveRequest|null $target */
            $target = LeaveRequest::with('processor')->where('id', $id)->lockForUpdate()->first();

            if (! $target) {
                return ['success' => false, 'message' => 'Data pengajuan tidak ditemukan.'];
            }

            if ($target->status !== 'pending') {
                $role = strtoupper($target->processor->role ?? 'Petugas');

                return [
                    'success' => false,
                    'message' => "Pengajuan ini sudah diproses sebelumnya oleh {$role}.",
                    'request' => $target,
                ];
            }

            $target->status = 'rejected';
            $target->rejection_reason = $cleanReason;
            $target->processed_by = $user->id;
            $target->processed_at = now();
            $target->save();

            return [
                'success' => true,
                'message' => "Pengajuan {$target->request_number} berhasil ditolak.",
                'request' => $target,
            ];
        });

        if ($result['success'] && isset($result['request'])) {
            $this->dispatchNotificationEmail($result['request'], 'rejected');
        }

        return $result;
    }

    /**
     * Safely dispatch email notification without breaking approval/rejection state if mail fails
     */
    protected function dispatchNotificationEmail(LeaveRequest $request, string $action): void
    {
        try {
            if ($action === 'approved') {
                Mail::to($request->email)->send(new LeaveRequestApprovedMail($request));
            } else {
                Mail::to($request->email)->send(new LeaveRequestRejectedMail($request));
            }

            $request->update([
                'email_status' => 'sent',
                'email_sent_at' => now(),
                'email_error' => null,
            ]);
        } catch (\Throwable $e) {
            Log::error("Gagal mengirim email {$action} untuk pengajuan {$request->request_number}: ".$e->getMessage(), [
                'exception' => $e,
            ]);

            $request->update([
                'email_status' => 'failed',
                'email_error' => substr($e->getMessage(), 0, 500),
            ]);
        }
    }
}
