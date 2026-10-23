<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_approved) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your account is still waiting for approval.'], 403);
            }

            return redirect()
                ->route('dashboard')
                ->with('status', 'Your account is still waiting for approval.');
        }

        return $next($request);
    }
}
