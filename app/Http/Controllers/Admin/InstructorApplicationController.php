<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstructorApplication;
use App\Models\User;
use App\Notifications\InstructorApplicationReviewed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Where admins approve or reject requests to become an instructor.
 */
class InstructorApplicationController extends Controller
{
    /**
     * List the applications, pending ones first by default.
     */
    public function index(Request $request): Response
    {
        $status = in_array($request->query('status'), [...InstructorApplication::STATUSES, 'all'], true)
            ? (string) $request->query('status')
            : InstructorApplication::STATUS_PENDING;

        $search = $request->string('search')->trim()->toString();

        $applications = InstructorApplication::query()
            ->with(['user:id,name,slug,email,role,avatar_path,created_at', 'reviewer:id,name'])
            ->when($status !== 'all', fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = '%'.$search.'%';
                $query->where(fn (Builder $query) => $query
                    ->where('headline', 'like', $term)
                    ->orWhere('expertise', 'like', $term)
                    ->orWhereHas('user', fn (Builder $query) => $query
                        ->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)));
            })
            // Oldest pending first, so nobody waits longest; reviewed ones newest first.
            ->when(
                $status === InstructorApplication::STATUS_PENDING,
                fn (Builder $query) => $query->oldest('id'),
                fn (Builder $query) => $query->latest('id'),
            )
            ->paginate(15)
            ->withQueryString();

        $counts = InstructorApplication::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('admin/InstructorApplications/Index', [
            'applications' => $applications,
            'counts' => [
                'pending' => (int) ($counts[InstructorApplication::STATUS_PENDING] ?? 0),
                'approved' => (int) ($counts[InstructorApplication::STATUS_APPROVED] ?? 0),
                'rejected' => (int) ($counts[InstructorApplication::STATUS_REJECTED] ?? 0),
            ],
            'filters' => (object) [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Make the applicant an instructor.
     *
     * Their headline and bio are filled from the application when still empty,
     * so the public instructor profile is not blank on day one.
     */
    public function approve(Request $request, InstructorApplication $application): RedirectResponse
    {
        $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        if (! $application->isPending()) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => __('This application has already been reviewed.')]);

            return back();
        }

        $applicant = $application->user;

        DB::transaction(function () use ($request, $application, $applicant): void {
            $application->update([
                'status' => InstructorApplication::STATUS_APPROVED,
                'admin_note' => $request->input('note'),
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now(),
            ]);

            // An admin keeps their role; only a student is promoted.
            if ($applicant->isStudent()) {
                $applicant->update([
                    'role' => User::ROLE_INSTRUCTOR,
                    'headline' => $applicant->headline ?: $application->headline,
                    'bio' => $applicant->bio ?: $application->experience,
                ]);
            }
        });

        $applicant->notify(new InstructorApplicationReviewed($application));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name is now an instructor.', ['name' => $applicant->name])]);

        return back();
    }

    /**
     * Turn the application down, with a reason the applicant can read.
     */
    public function reject(Request $request, InstructorApplication $application): RedirectResponse
    {
        $request->validate(['note' => ['required', 'string', 'max:1000']], attributes: ['note' => __('rejection reason')]);

        if (! $application->isPending()) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => __('This application has already been reviewed.')]);

            return back();
        }

        $application->update([
            'status' => InstructorApplication::STATUS_REJECTED,
            'admin_note' => $request->string('note')->toString(),
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ]);

        $application->user->notify(new InstructorApplicationReviewed($application));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application rejected. The applicant can apply again.')]);

        return back();
    }
}
