<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Redirect to the appropriate dashboard based on user role.
     */
    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user && $user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('hrd.dashboard');
    }
}
