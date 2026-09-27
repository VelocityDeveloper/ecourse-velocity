<?php

namespace App\Http\Controllers;

use App\Actions\CheckCertificateEligibility;
use App\Actions\IssueCertificate;
use App\Actions\RenderCertificatePdf;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class CertificateController extends Controller
{
    /**
     * Issue the signed-in student's certificate for the course and open it.
     */
    public function store(Request $request, Course $course, IssueCertificate $issue, CheckCertificateEligibility $checkEligibility): RedirectResponse
    {
        $student = $request->user();
        assert($student instanceof User);

        $certificate = $issue($student, $course);

        if ($certificate === null) {
            $missing = $checkEligibility($student, $course)['missing'];

            Inertia::flash('toast', [
                'type' => 'warning',
                'message' => __('Not eligible for a certificate yet: :missing.', ['missing' => implode('; ', $missing)]),
            ]);

            return back();
        }

        return to_route('certificates.show', $certificate);
    }

    /**
     * Show the public verification page of a certificate.
     */
    public function show(Request $request, Certificate $certificate): InertiaResponse
    {
        return Inertia::render('certificates/Show', [
            'certificate' => [
                'code' => $certificate->code,
                'student_name' => $certificate->student_name,
                'course_title' => $certificate->course_title,
                'course_id' => $certificate->course_id,
                'instructor_name' => $certificate->instructor_name,
                'final_percent' => $certificate->final_percent,
                'letter' => $certificate->letter,
                'issued_at' => $certificate->issued_at->toIso8601String(),
            ],
            'canDownload' => $this->canDownload($request->user(), $certificate),
            'verifyUrl' => route('certificates.show', $certificate),
        ]);
    }

    /**
     * Download the certificate as a PDF: for its owner and the course's staff.
     */
    public function download(Request $request, Certificate $certificate, RenderCertificatePdf $render): Response
    {
        abort_unless($this->canDownload($request->user(), $certificate), 403);

        return $render($certificate)->download('sertifikat-'.Str::slug($certificate->course_title).'-'.$certificate->code.'.pdf');
    }

    /**
     * The student it was issued to and anyone who manages the course may download it.
     */
    private function canDownload(?User $user, Certificate $certificate): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->id === $certificate->user_id
            || ($certificate->course !== null && Gate::forUser($user)->allows('update', $certificate->course));
    }
}
