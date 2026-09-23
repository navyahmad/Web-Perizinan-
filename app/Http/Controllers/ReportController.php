<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Display simple summary reports.
     */
    public function index(Request $request): View
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = LeaveRequest::query();

        if ($startDate) {
            $query->whereDate('leave_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('leave_date', '<=', $endDate);
        }

        $totalRequests = (clone $query)->count();

        $byStatus = [
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'pending_manager' => (clone $query)->where('status', 'pending_manager')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
        ];

        $byType = [
            'late' => (clone $query)->where('type', 'late')->count(),
            'half_day' => (clone $query)->where('type', 'half_day')->count(),
            'early_departure' => (clone $query)->where('type', 'early_departure')->count(),
            'temporary_exit' => (clone $query)->where('type', 'temporary_exit')->count(),
            'leave' => (clone $query)->where('type', 'leave')->count(),
            'emergency' => (clone $query)->whereIn('type', ['emergency', 'personal'])->count(),
            'sick' => (clone $query)->where('type', 'sick')->count(),
        ];

        $byDepartment = [
            'General Solusindo' => (clone $query)->where('department', 'General Solusindo')->count(),
            'Tabinaco' => (clone $query)->where('department', 'Tabinaco')->count(),
        ];

        // Detailed records for display on page
        $records = (clone $query)
            ->with(['processor', 'attachments'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('reports.index', [
            'totalRequests' => $totalRequests,
            'byStatus' => $byStatus,
            'byType' => $byType,
            'byDepartment' => $byDepartment,
            'records' => $records,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Export leave requests to CSV with Excel-optimized formatting.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = LeaveRequest::with(['processor', 'hrdProcessor', 'attachments']);

        if ($startDate) {
            $query->whereDate('leave_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('leave_date', '<=', $endDate);
        }

        $records = $query->latest('id')->get();

        $filename = 'laporan-izin-kantor-'.date('Y-m-d-His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Clean, clear, and distinct CSV Headers
            fputcsv($handle, [
                'No. Pengajuan',
                'Nama Karyawan',
                'Departemen',
                'Jabatan',
                'Email Pribadi',
                'No. WhatsApp',
                'Jenis Izin',
                'Tanggal Izin',
                'Jadwal / Jam Izin',
                'Durasi',
                'Kondisi Darurat',
                'Keterangan / Alasan Izin',
                'Bukti Lampiran',
                'Status Pengajuan',
                'Diproses Oleh',
                'Waktu Keputusan',
                'Catatan Keputusan HRD/Manager',
                'Waktu Pengajuan Dibuat',
                'Keputusan HRD',
                'Ditinjau HRD Oleh',
                'Waktu Peninjauan HRD',
                'Catatan HRD',
            ]);

            foreach ($records as $item) {
                // Prevent Excel from converting phone number to scientific notation (6.28E+11)
                $formattedPhone = '="'.$item->phone.'"';

                // Format schedule details based on type
                $schedule = '-';
                if ($item->type === 'late' && $item->estimated_arrival) {
                    $schedule = 'Estimasi Tiba '.$item->estimated_arrival.' WIB';
                } elseif (in_array($item->type, ['half_day', 'temporary_exit'], true)) {
                    $scheduleLabel = $item->half_day_type ?: $item->type_label;
                    $times = ($item->start_time && $item->end_time) ? " ({$item->start_time} - {$item->end_time} WIB)" : '';
                    $schedule = $scheduleLabel.$times;
                } elseif ($item->type === 'early_departure') {
                    $schedule = 'Pulang pukul '.$item->start_time.' WIB';
                }

                // Format duration with clear units
                $duration = '-';
                if (in_array($item->type, ['half_day', 'temporary_exit'], true)) {
                    $duration = $item->duration !== null ? $item->duration.' Jam' : '-';
                } elseif ($item->type === 'leave' || $item->type === 'sick') {
                    $duration = ($item->duration ?: 1).' Hari';
                } elseif ($item->type === 'emergency' || $item->type === 'personal') {
                    $duration = '1 Hari';
                }

                // Format emergency status
                $emergency = 'Normal';
                if ($item->emergency) {
                    $emergency = 'Ya (Darurat)'.($item->emergency_reason ? ': '.$item->emergency_reason : '');
                }

                // Format attachments count
                $attachmentCount = $item->attachments->count();
                $attachments = $attachmentCount > 0 ? $attachmentCount.' Lampiran' : 'Tidak Ada';

                // Format decision note (approval note or rejection reason)
                $decisionNote = '-';
                if ($item->status === 'approved') {
                    $decisionNote = $item->approval_note ? '[Disetujui] '.$item->approval_note : '[Disetujui] Tanpa catatan khusus';
                } elseif ($item->status === 'rejected') {
                    $decisionNote = '[Ditolak] '.($item->rejection_reason ?: '-');
                } else {
                    $decisionNote = '['.$item->status_label.']';
                }

                fputcsv($handle, [
                    $item->request_number,
                    $item->name,
                    $item->department,
                    $item->position,
                    $item->email,
                    $formattedPhone,
                    $item->type_label,
                    $item->leave_date ? Carbon::parse($item->leave_date)->format('d/m/Y') : '-',
                    $schedule,
                    $duration,
                    $emergency,
                    $item->reason ?: '-',
                    $attachments,
                    $item->status_label,
                    $item->processor ? strtoupper($item->processor->role).' ('.$item->processor->name.')' : 'Belum Diproses',
                    $item->processed_at ? Carbon::parse($item->processed_at)->format('d/m/Y H:i') : '-',
                    $decisionNote,
                    $item->created_at ? Carbon::parse($item->created_at)->format('d/m/Y H:i') : '-',
                    match ($item->hrd_decision) {
                        'approved' => 'Disetujui HRD',
                        'rejected' => 'Ditolak HRD',
                        default => '-',
                    },
                    $item->hrdProcessor?->name ?? '-',
                    $item->hrd_processed_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') ?? '-',
                    $item->hrd_note ?? '-',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
