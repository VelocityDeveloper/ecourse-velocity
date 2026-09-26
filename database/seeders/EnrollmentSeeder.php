<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class EnrollmentSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Enroll students who have no enrollments yet in a few published courses.
     *
     * Most enrollments are self-service; the demo student also gets one added
     * by an instructor and one that was cancelled, so every state is visible.
     */
    public function run(): void
    {
        $courses = Course::query()->published()->orderBy('id')->get();

        if ($courses->isEmpty()) {
            return;
        }

        User::query()
            ->where('role', User::ROLE_STUDENT)
            ->whereDoesntHave('enrollments')
            ->orderBy('id')
            ->get()
            ->each(function (User $student, int $index) use ($courses): void {
                $picked = $courses->shuffle()->take(min(3, $courses->count()))->values();

                foreach ($picked as $course) {
                    $this->enrollAt($student, $course, null, $index);
                }
            });

        $this->seedDemoStudentStates($courses);
    }

    /**
     * Give the demo student a staff-made enrollment and a cancelled one.
     *
     * @param  Collection<int, Course>  $courses
     */
    private function seedDemoStudentStates(Collection $courses): void
    {
        $student = User::query()->where('email', 'student@ecourse.com')->first();

        if (! $student instanceof User || $student->enrollments()->where('status', Enrollment::STATUS_CANCELLED)->exists()) {
            return;
        }

        $available = $courses
            ->reject(fn (Course $course): bool => $student->enrollments()->where('course_id', $course->id)->exists())
            ->values();

        if ($available->count() < 2) {
            return;
        }

        [$manualCourse, $cancelledCourse] = [$available[0], $available[1]];

        $this->enrollAt($student, $manualCourse, $manualCourse->instructor, 0);

        $cancelled = $this->enrollAt($student, $cancelledCourse, null, 5);
        $cancelled->cancel($student, 'Jadwal kuliah sedang padat, akan lanjut lagi nanti.');
    }

    /**
     * Enroll the student and backdate the enrollment so the history looks lived in.
     */
    private function enrollAt(User $student, Course $course, ?User $enroller, int $daysAgoSeed): Enrollment
    {
        $enrollment = Enrollment::enroll($student, $course, $enroller);

        $enrollment->forceFill(['enrolled_at' => now()->subDays(3 + ($daysAgoSeed * 2 + $course->id) % 40)])->save();

        return $enrollment;
    }
}
