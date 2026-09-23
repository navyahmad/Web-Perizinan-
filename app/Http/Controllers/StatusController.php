<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatusController extends Controller
{
    /**
     * Show the public status check form.
     */
    public function index(): View
    {
        return view('public.status');
    }

    /**
     * Look up leave request status by request_number + email.
     */
    public function check(Request $request): View
    {
        $validated = $request->validate([
            'request_number' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
        ], [
            'request_number.required' => 'Nomor pengajuan wajib diisi.',
            'email.required' => 'Email pribadi wajib diisi.',
            'email.email' => 'Format email pribadi tidak valid.',
        ]);

        $requestNumber = strtoupper(trim($validated['request_number']));
        $email = strtolower(trim($validated['email']));

        $leaveRequest = LeaveRequest::with('processor')
            ->where('request_number', $requestNumber)
            ->where('email', $email)
            ->first();

        if (! $leaveRequest) {
            return view('public.status', [
                'searched' => true,
                'notFound' => true,
                'inputNumber' => $requestNumber,
                'inputEmail' => $email,
            ]);
        }

        // Expose only non-sensitive summary fields
        $statusData = [
            'request_number' => $leaveRequest->request_number,
            'name' => $leaveRequest->name,
            'type' => $leaveRequest->type,
            'type_label' => $leaveRequest->type_label,
            'leave_date' => $leaveRequest->leave_date,
            'status' => $leaveRequest->status,
            'status_label' => $leaveRequest->status_label,
            'created_at' => $leaveRequest->created_at,
            'processed_at' => $leaveRequest->processed_at,
            'processor_role' => $leaveRequest->processor ? ($leaveRequest->processor->isAdmin() ? 'MANAGER' : 'HRD') : null,
            'rejection_reason' => $leaveRequest->status === 'rejected' ? $leaveRequest->rejection_reason : null,
            'duration' => $leaveRequest->duration,
            'start_time' => $leaveRequest->start_time,
            'end_time' => $leaveRequest->end_time,
            'estimated_arrival' => $leaveRequest->estimated_arrival,
        ];

        return view('public.status', [
            'searched' => true,
            'notFound' => false,
            'statusData' => $statusData,
            'leaveRequest' => $leaveRequest,
            'inputNumber' => $requestNumber,
            'inputEmail' => $email,
        ]);
    }
}
