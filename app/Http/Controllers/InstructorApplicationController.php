<?php

namespace App\Http\Controllers;

use App\Models\InstructorApplication;
use App\Models\User;
use App\Notifications\InstructorApplicationSubmitted;
use App\Support\PaymentSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Jadi Instruktur": a student asks to teach, and keeps the student role
 * until an admin approves the request.
 */
class InstructorApplicationController extends Controller
{
    /**
     * Show the page explaining how to become an instructor, with the form or
     * the status of the student's latest request.
     */
    public function create(Request $request): Response
    {
        $user = $request->user();

        $application = $user instanceof User
            ? $user->instructorApplications()->latest('id')->first()
            : null;

        return Inertia::render('instructor-applications/Create', [
            'application' => $application === null ? null : [
                'id' => $application->id,
                'status' => $application->status,
                'headline' => $application->headline,
                'expertise' => $application->expertise,
                'experience' => $application->experience,
                'motivation' => $application->motivation,
                'portfolio_url' => $application->portfolio_url,
                'phone' => $application->phone,
                'admin_note' => $application->admin_note,
                'created_at' => $application->created_at?->toIso8601String(),
                'reviewed_at' => $application->reviewed_at?->toIso8601String(),
            ],
            // What instructors keep of each sale, shown among the benefits.
            'commissionRate' => PaymentSettings::commissionRate(),
        ]);
    }

    /**
     * Send a guest to log in (or register) and back to the form: the auth
     * middleware remembers this URL as the intended one.
     */
    public function login(): RedirectResponse
    {
        return redirect()->to(route('instructor-applications.create').'#formulir');
    }

    /**
     * Send a request to become an instructor.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $this->actor($request);

        if (! $user->isStudent()) {
            Inertia::flash('toast', ['type' => 'info', 'message' => __('Your account can already teach, or cannot apply to be an instructor.')]);

            return to_route('instructor-applications.create');
        }

        if ($user->instructorApplications()->pending()->exists()) {
            Inertia::flash('toast', ['type' => 'info', 'message' => __('Your application is still being reviewed.')]);

            return to_route('instructor-applications.create');
        }

        $validated = $request->validate([
            'headline' => ['required', 'string', 'max:150'],
            'expertise' => ['required', 'string', 'max:150'],
            'experience' => ['required', 'string', 'min:30', 'max:3000'],
            'motivation' => ['required', 'string', 'min:30', 'max:3000'],
            'portfolio_url' => ['nullable', 'url:http,https', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'agreement' => ['accepted'],
        ], attributes: ['headline' => __('profession'), 'phone' => __('WhatsApp number')]);

        unset($validated['agreement']);

        $application = $user->instructorApplications()->create([
            ...$validated,
            'status' => InstructorApplication::STATUS_PENDING,
        ]);

        User::notifyAdmins(new InstructorApplicationSubmitted($application));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application sent. We will let you know once an admin has reviewed it.')]);

        return to_route('instructor-applications.create');
    }

    /**
     * Get the authenticated user making the request.
     */
    private function actor(Request $request): User
    {
        $actor = $request->user();

        assert($actor instanceof User);

        return $actor;
    }
}
