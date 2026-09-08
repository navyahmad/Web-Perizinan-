<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the authenticated user profile.
     */
    public function show(Request $request): View
    {
        $user = $request->user();

        $processedCount = LeaveRequest::where('processed_by', $user->id)->count();
        $approvedCount = LeaveRequest::where('processed_by', $user->id)->where('status', 'approved')->count();
        $rejectedCount = LeaveRequest::where('processed_by', $user->id)->where('status', 'rejected')->count();

        return view('profile.show', [
            'user' => $user,
            'processedCount' => $processedCount,
            'approvedCount' => $approvedCount,
            'rejectedCount' => $rejectedCount,
        ]);
    }
}
