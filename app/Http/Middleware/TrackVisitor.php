<?php

namespace App\Http\Middleware;

use App\Support\VisitorStatistics;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackVisitor
{
    /**
     * Count each public page a person opens for the footer's visitor statistics.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only pages that opened: redirects, missing pages and errors are not visits.
        if ($response->getStatusCode() === 200 && VisitorStatistics::counts($request)) {
            try {
                VisitorStatistics::record($request);
            } catch (Throwable $exception) {
                // A counter must never take the page down with it.
                report($exception);
            }
        }

        return $response;
    }
}
