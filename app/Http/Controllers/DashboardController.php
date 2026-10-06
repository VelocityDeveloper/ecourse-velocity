<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Dashboard\BuildDashboard;
use App\Support\Dashboard\DashboardPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The staff dashboard: admins see the whole platform, instructors their own courses.
 */
class DashboardController extends Controller
{
    /**
     * Show the dashboard of the signed-in admin or instructor for the chosen period.
     */
    public function __invoke(Request $request): Response
    {
        $actor = $request->user();
        assert($actor instanceof User);

        $dashboard = new BuildDashboard($actor, DashboardPeriod::fromKey($request->query('periode')));

        return $actor->isAdmin()
            ? Inertia::render('dashboard/Admin', $dashboard->admin())
            : Inertia::render('dashboard/Instructor', $dashboard->instructor());
    }
}
