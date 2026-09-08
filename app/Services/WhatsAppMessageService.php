<?php

namespace App\Services;

use App\Models\LeaveRequest;
use Carbon\Carbon;

class WhatsAppMessageService
{
    /**
     * Normalize phone number to international format without +, spaces, or dashes.
     * Example: 08123456789 -> 628123456789
     */
    public function normalizePhone(string $phone): string
    {
        // Strip everything except digits
        $digits = preg_replace('/[^\d]/', '', $phone);

        if (empty($digits)) {
            return '';
        }

        // If starts with 0, replace leading 0 with 62
        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        // If starts with 8, prefix 62
        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }

    /**
     * Build date information string based on leave type.
     */
    public function formatLeaveDate(LeaveRequest $request): string
    {
        $dateFormatted = Carbon::parse($request->leave_date)->translatedFormat('d F Y');

        if ($request->type === 'half_day' && $request->start_time && $request->end_time) {
            return "{$dateFormatted} ({$request->start_time} - {$request->end_time})";
        }

        if ($request->type === 'late' && $request->estimated_arrival) {
            return "{$dateFormatted} (Estimasi: {$request->estimated_arrival} WIB)";
        }

        if ($request->duration && $request->duration > 1) {
            return "{$dateFormatted} ({$request->duration} Hari)";
        }

        return $dateFormatted;
    }

    /**
     * Format processor role (HRD or Admin)
     */
    public function formatProcessorRole(LeaveRequest $request): string
    {
        $role = $request->processor?->role ?? 'Petugas';

        return strtoupper($role);
    }

    /**
     * Format processed time in Asia/Jakarta
     */
    public function formatProcessedAt(LeaveRequest $request): string
    {
        if (! $request->processed_at) {
            return '-';
        }

        return Carbon::parse($request->processed_at)->setTimezone('Asia/Jakarta')->translatedFormat('d F Y, H:i').' WIB';
    }

    /**
     * Build pre-filled WhatsApp message for approved request.
     */
    public function buildApprovedMessage(LeaveRequest $request): string
    {
        $dateInfo = $this->formatLeaveDate($request);
        $role = $this->formatProcessorRole($request);
        $processedAt = $this->formatProcessedAt($request);

        return "Halo {$request->name},\n\n".
            "Pengajuan izin Anda telah DISETUJUI.\n\n".
            "Nomor Pengajuan: {$request->request_number}\n".
            "Jenis Izin: {$request->type_label}\n".
            "Tanggal: {$dateInfo}\n".
            "Status: DISETUJUI\n".
            "Diproses oleh: {$role}\n".
            "Waktu proses: {$processedAt}\n\n".
            'Silakan simpan pesan ini sebagai informasi pengajuan izin Anda.';
    }

    /**
     * Build pre-filled WhatsApp message for rejected request.
     */
    public function buildRejectedMessage(LeaveRequest $request): string
    {
        $dateInfo = $this->formatLeaveDate($request);
        $role = $this->formatProcessorRole($request);
        $processedAt = $this->formatProcessedAt($request);
        $reason = $request->rejection_reason ?: 'Tidak memenuhi ketentuan kebijakan kantor.';

        return "Halo {$request->name},\n\n".
            "Pengajuan izin Anda telah DITOLAK.\n\n".
            "Nomor Pengajuan: {$request->request_number}\n".
            "Jenis Izin: {$request->type_label}\n".
            "Tanggal: {$dateInfo}\n".
            "Status: DITOLAK\n\n".
            "Alasan penolakan:\n{$reason}\n\n".
            "Diproses oleh: {$role}\n".
            "Waktu proses: {$processedAt}\n\n".
            'Silakan hubungi HRD apabila memerlukan informasi lebih lanjut.';
    }

    /**
     * Build WhatsApp Click to Chat URL.
     */
    public function buildUrl(string $phone, string $message): string
    {
        $normalized = $this->normalizePhone($phone);
        if (empty($normalized)) {
            return '';
        }

        return 'https://wa.me/'.$normalized.'?text='.rawurlencode($message);
    }
}
