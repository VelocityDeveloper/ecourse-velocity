<?php

namespace App\Actions;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;

class IssueCertificate
{
    public function __construct(private CheckCertificateEligibility $checkEligibility) {}

    /**
     * Issue the student's certificate for the course, or return the one already issued.
     *
     * Returns null when the student has not earned it yet.
     */
    public function __invoke(User $student, Course $course): ?Certificate
    {
        $existing = Certificate::query()->where('user_id', $student->id)->where('course_id', $course->id)->first();

        if ($existing !== null) {
            return $existing;
        }

        $eligibility = ($this->checkEligibility)($student, $course);

        if (! $eligibility['eligible']) {
            return null;
        }

        return Certificate::query()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'code' => Certificate::newCode(),
            'student_name' => $student->name,
            'course_title' => $course->title,
            'instructor_name' => $course->instructor?->name,
            'final_percent' => $eligibility['final_percent'],
            'letter' => $eligibility['letter'],
            'issued_at' => now(),
        ]);
    }
}
