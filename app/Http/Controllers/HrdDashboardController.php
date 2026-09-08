<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HrdDashboardController extends Controller
{
    /**
     * Display the HRD Dashboard.
     */
    public function index(Request $request): View
    {
        $today = Carbon::today('Asia/Jakarta');

        $stats = [
            'total' => LeaveRequest::count(),
            'pending' => LeaveRequest::where('status', 'pending')->count(),
            'approved' => LeaveRequest::where('status', 'approved')->count(),
            'rejected' => LeaveRequest::where('status', 'rejected')->count(),
            'today' => LeaveRequest::whereDate('created_at', $today)->count(),
        ];

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

        $requests = $query->latest('id')->paginate(10)->withQueryString();

        return view('hrd.dashboard', [
            'stats' => $stats,
            'requests' => $requests,
            'role' => 'hrd',
        ]);
    }
}
