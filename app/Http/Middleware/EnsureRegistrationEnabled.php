<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

// D-S1: V1 is strictly single-user. Registration is blocked once any user row
// exists so the tool cannot be opened up to additional users by accident.
// To reset for a fresh install: truncate the users table.
class EnsureRegistrationEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (User::exists()) {
            return Inertia::render('auth/setup-complete')
                ->toResponse($request)
                ->setStatusCode(403);
        }

        return $next($request);
    }
}
