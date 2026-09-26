<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsStaff
{
    /**
     * Keep everyone but admins and instructors out of the dashboard area.
     *
     * Students browsing to a dashboard page are sent back to the public site;
     * any attempt to change data is refused outright.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ($user->isAdmin() || $user->isInstructor())) {
            return $next($request);
        }

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return redirect()->route('home');
        }

        abort(403);
    }
}
