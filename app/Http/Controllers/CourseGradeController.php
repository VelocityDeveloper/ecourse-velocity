<?php

namespace App\Http\Controllers;

use App\Actions\BuildCourseGradebook;
use App\Http\Requests\UpdateCourseGradingRequest;
use App\Models\Course;
use App\Support\GradeScale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourseGradeController extends Controller
{
    /**
     * Show the gradebook: every student's quiz grades and final course grade.
     */
    public function index(Request $request, Course $course, BuildCourseGradebook $buildGradebook): Response
    {
        Gate::authorize('update', $course);

        $search = $request->string('search')->toString();
        $gradebook = $buildGradebook($course, $search);
        $finals = collect($gradebook['students'])->pluck('final_percent')->filter(fn (?int $value): bool => $value !== null);

        return Inertia::render('courses/Grades', [
            'course' => ['id' => $course->id, 'slug' => $course->slug, 'title' => $course->title, 'passing_grade' => $course->passing_grade],
            'quizzes' => $gradebook['quizzes'],
            'students' => $gradebook['students'],
            'totalWeight' => $gradebook['total_weight'],
            'gradeScale' => GradeScale::rows(),
            'summary' => [
                'students' => count($gradebook['students']),
                'average_final' => $finals->isEmpty() ? null : (int) round($finals->avg()),
                'passed' => collect($gradebook['students'])->where('passed', true)->count(),
                'not_passed' => collect($gradebook['students'])->where('passed', false)->count(),
            ],
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Download the gradebook as a CSV file that opens in Excel.
     */
    public function export(Course $course, BuildCourseGradebook $buildGradebook): StreamedResponse
    {
        Gate::authorize('update', $course);

        $gradebook = $buildGradebook($course);
        $filename = 'nilai-'.Str::slug($course->title).'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($gradebook, $course): void {
            $out = fopen('php://output', 'w');
            assert($out !== false);

            // A byte order mark makes Excel read the file as UTF-8.
            fwrite($out, "\xEF\xBB\xBF");

            $header = ['Nama', 'Email'];

            foreach ($gradebook['quizzes'] as $quiz) {
                $header[] = "{$quiz['title']} (%)";
            }

            array_push($header, 'Nilai akhir (%)', 'Predikat', "Status (KKM kursus {$course->passing_grade})", 'Nomor sertifikat');
            fputcsv($out, $header, ';');

            foreach ($gradebook['students'] as $row) {
                $line = [$row['student']['name'], $row['student']['email']];

                foreach ($gradebook['quizzes'] as $quiz) {
                    $line[] = $row['grades'][$quiz['id']]['percent'] ?? '';
                }

                $line[] = $row['final_percent'] ?? '';
                $line[] = $row['letter'] ?? '';
                $line[] = match ($row['passed']) {
                    true => 'Lulus',
                    false => 'Belum lulus',
                    null => '',
                };
                $line[] = $row['certificate_code'] ?? '';

                fputcsv($out, $line, ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Change the final grade a student needs to pass the course.
     */
    public function update(UpdateCourseGradingRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $course->update(['passing_grade' => $request->integer('passing_grade')]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Passing grade saved.')]);

        return back();
    }
}
