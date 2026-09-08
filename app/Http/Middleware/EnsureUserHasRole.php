<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        if (! empty($roles) && ! in_array($user->role, $roles, true)) {
            abort(403, 'Akses ditolak. Anda tidak memiliki wewenang untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
