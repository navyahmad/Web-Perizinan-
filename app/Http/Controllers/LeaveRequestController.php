<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveRequestAttachment;
use App\Services\LeaveRequestService;
use App\Services\WhatsAppMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    /**
     * Display a listing of leave requests with filters.
     */
    public function index(Request $request): View
    {
        $query = LeaveRequest::with('processor');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('leave_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('leave_date', '<=', $request->end_date);
        }

        $requests = $query->latest('id')->paginate(15)->withQueryString();

        return view('requests.index', [
            'requests' => $requests,
        ]);
    }

    /**
     * Display details of a single leave request.
     */
    public function show(int $id, WhatsAppMessageService $waService): View
    {
        $leaveRequest = LeaveRequest::with(['processor', 'hrdProcessor', 'attachments'])->findOrFail($id);

        $waUrl = null;
        if ($leaveRequest->isApproved()) {
            $msg = $waService->buildApprovedMessage($leaveRequest);
            $waUrl = $waService->buildUrl($leaveRequest->phone, $msg);
        } elseif ($leaveRequest->isRejected()) {
            $msg = $waService->buildRejectedMessage($leaveRequest);
            $waUrl = $waService->buildUrl($leaveRequest->phone, $msg);
        }

        $isSickWithoutMedicalProof = ($leaveRequest->type === 'sick' && $leaveRequest->attachments->isEmpty());

        return view('requests.show', [
            'leaveRequest' => $leaveRequest,
            'waUrl' => $waUrl,
            'isSickWithoutMedicalProof' => $isSickWithoutMedicalProof,
        ]);
    }

    /**
     * Approve the current review stage atomically.
     */
    public function approve(Request $request, int $id, LeaveRequestService $service): RedirectResponse
    {
        $validated = $request->validate([
            'approval_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $note = $validated['approval_note'] ?? null;
        $result = $service->approve($id, $request->user(), $note);

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Reject the current review stage with a mandatory reason.
     */
    public function reject(Request $request, int $id, LeaveRequestService $service): RedirectResponse
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.min' => 'Alasan penolakan minimal 3 karakter.',
        ]);

        $reason = $request->input('rejection_reason');
        $result = $service->reject($id, $request->user(), $reason);

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Download or view private supporting document attachment with authorization.
     */
    public function attachment(int $id, int $attachmentId)
    {
        $attachment = LeaveRequestAttachment::where('id', $attachmentId)
            ->where('leave_request_id', $id)
            ->firstOrFail();

        if (! Storage::disk('local')->exists($attachment->file_path)) {
            abort(404, 'File lampiran tidak ditemukan pada penyimpanan server.');
        }

        return Storage::disk('local')->response($attachment->file_path, $attachment->file_name);
    }

    /**
     * Display historical requests log.
     */
    public function history(Request $request): View
    {
        $query = LeaveRequest::with('processor');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('leave_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('leave_date', '<=', $request->end_date);
        }

        $requests = $query->latest('id')->paginate(20)->withQueryString();

        return view('requests.history', [
            'requests' => $requests,
        ]);
    }
}
