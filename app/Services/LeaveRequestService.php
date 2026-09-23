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
     * @return array{success: bool, message: string, request?: LeaveRequest}
     */
    public function approve(int|LeaveRequest $leaveRequest, User $user, ?string $note = null): array
    {
        return $this->processDecision($leaveRequest, $user, 'approved', $note);
    }

    /**
     * @return array{success: bool, message: string, request?: LeaveRequest}
     */
    public function reject(int|LeaveRequest $leaveRequest, User $user, string $reason): array
    {
        if (trim($reason) === '') {
            return ['success' => false, 'message' => 'Alasan penolakan wajib diisi.'];
        }

        return $this->processDecision($leaveRequest, $user, 'rejected', $reason);
    }

    /**
     * Validate the review stage while holding the row lock, then record one decision.
     *
     * @return array{success: bool, message: string, request?: LeaveRequest}
     */
    private function processDecision(int|LeaveRequest $leaveRequest, User $user, string $decision, ?string $note): array
    {
        $id = $leaveRequest instanceof LeaveRequest ? $leaveRequest->id : $leaveRequest;
        $note = $note !== null ? trim($note) : null;

        $result = DB::transaction(function () use ($id, $user, $decision, $note): array {
            $target = LeaveRequest::whereKey($id)->lockForUpdate()->first();

            if (! $target) {
                return ['success' => false, 'message' => 'Data pengajuan tidak ditemukan.'];
            }

            if (! $target->isPending()) {
                return ['success' => false, 'message' => 'Pengajuan ini sudah memiliki keputusan final.'];
            }

            abort_unless($target->canBeProcessedBy($user), 403, 'Anda tidak berwenang memproses pengajuan pada tahap ini.');

            $processedAt = now();
            $isHrdReview = $target->status === 'pending';

            if ($isHrdReview) {
                $target->hrd_processed_by = $user->id;
                $target->hrd_processed_at = $processedAt;
                $target->hrd_decision = $decision;
                $target->hrd_note = $note;
            }

            if ($isHrdReview && $decision === 'approved') {
                $target->status = 'pending_manager';
                $message = "Pengajuan {$target->request_number} disetujui HRD dan menunggu persetujuan Manager.";
            } else {
                $target->status = $decision;
                $target->processed_by = $user->id;
                $target->processed_at = $processedAt;
                $target->approval_note = $decision === 'approved' ? $note : null;
                $target->rejection_reason = $decision === 'rejected' ? $note : null;
                $label = $decision === 'approved' ? 'disetujui' : 'ditolak';
                $message = "Pengajuan {$target->request_number} berhasil {$label}.";
            }

            $target->save();

            return ['success' => true, 'message' => $message, 'request' => $target];
        });

        if ($result['success'] && ! $result['request']->isPending()) {
            $this->dispatchNotificationEmail($result['request'], $decision);
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
