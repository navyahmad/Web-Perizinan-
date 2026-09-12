<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveRequest;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestAttachment;
use App\Services\LeaveRequestService;
use App\Services\TelegramNotificationService;
use App\Services\WhatsAppMessageService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PublicLeaveRequestController extends Controller
{
    /**
     * Show the public dynamic leave submission form.
     */
    public function create(): View
    {
        return view('public.form');
    }

    /**
     * Store a newly submitted leave request.
     */
    public function store(
        StoreLeaveRequest $request,
        LeaveRequestService $leaveService,
        WhatsAppMessageService $waService,
        TelegramNotificationService $telegramService
    ): RedirectResponse {
        $validated = $request->validated();

        $type = $validated['type'];
        $duration = null;

        if ($type === 'half_day' && ! empty($validated['start_time']) && ! empty($validated['end_time'])) {
            $start = Carbon::createFromFormat('H:i', $validated['start_time']);
            $end = Carbon::createFromFormat('H:i', $validated['end_time']);
            $duration = round(abs($end->diffInMinutes($start)) / 60, 2);
        } elseif ($type === 'sick' || $type === 'leave') {
            $duration = isset($validated['duration']) ? (float) $validated['duration'] : 1.0;
        } elseif ($type === 'emergency') {
            $duration = 1.0;
        }

        $phone = $waService->normalizePhone($validated['phone']);
        // For type emergency, the request is inherently emergency
        $emergency = $type === 'emergency' ? true : filter_var($request->input('emergency', false), FILTER_VALIDATE_BOOLEAN);

        $leaveRequest = DB::transaction(function () use ($validated, $type, $duration, $phone, $emergency, $leaveService, $request) {
            $requestNumber = $leaveService->generateRequestNumber();

            $record = LeaveRequest::create([
                'request_number' => $requestNumber,
                'name' => trim($validated['name']),
                'email' => strtolower(trim($validated['email'])),
                'phone' => $phone,
                'department' => $validated['department'],
                'position' => trim($validated['position']),
                'type' => $type,
                'leave_date' => $validated['leave_date'],
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'half_day_type' => $validated['half_day_type'] ?? null,
                'estimated_arrival' => $validated['estimated_arrival'] ?? null,
                'duration' => $duration,
                'reason' => $validated['reason'] ?? null,
                'emergency' => $emergency,
                'emergency_reason' => ($emergency && $type !== 'emergency') ? ($validated['emergency_reason'] ?? null) : null,
                'contactable' => isset($validated['contactable']) ? filter_var($validated['contactable'], FILTER_VALIDATE_BOOLEAN) : null,
                'status' => 'pending',
                'email_status' => 'pending',
                'telegram_status' => 'pending',
            ]);

            // Process file uploads
            $files = [];
            if ($request->hasFile('attachments')) {
                $files = $request->file('attachments');
            } elseif ($request->hasFile('attachment')) {
                $files = [$request->file('attachment')];
            }

            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    $originalName = $file->getClientOriginalName();
                    $mime = $file->getClientMimeType() ?: 'application/octet-stream';
                    $size = $file->getSize();

                    // Generate random secure filename in private storage
                    $storedPath = $file->store("attachments/{$record->id}", 'local');

                    LeaveRequestAttachment::create([
                        'leave_request_id' => $record->id,
                        'file_name' => $originalName,
                        'file_path' => $storedPath,
                        'mime_type' => $mime,
                        'file_size' => $size,
                    ]);
                }
            }

            return $record;
        });

        // Strictly after DB commit: trigger Telegram notification fail-safely
        try {
            $telegramService->sendNewRequestNotification($leaveRequest);
        } catch (\Throwable $e) {
            Log::error("Failed sending telegram notification for {$leaveRequest->request_number}: {$e->getMessage()}");
            $leaveRequest->update([
                'telegram_status' => 'failed',
                'telegram_error' => substr($e->getMessage(), 0, 500),
            ]);
        }

        session()->put("leave_success_access.{$leaveRequest->request_number}", true);

        return redirect()->route('public.success', $leaveRequest->request_number)
            ->with('success', 'Pengajuan izin Anda berhasil dikirim.');
    }

    /**
     * Display the submission success page.
     *
     * Only viewable by the browser session that just submitted this request,
     * since request numbers are sequential and must not be enumerable by outsiders.
     */
    public function success(string $requestNumber): View
    {
        abort_unless(session("leave_success_access.{$requestNumber}"), 404);

        $leaveRequest = LeaveRequest::where('request_number', $requestNumber)->firstOrFail();

        return view('public.success', [
            'leaveRequest' => $leaveRequest,
        ]);
    }
}
