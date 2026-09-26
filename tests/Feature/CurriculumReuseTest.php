<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Section;
use App\Models\User;
use Inertia\Inertia;

function sectionOwnedBy(User $instructor): Section
{
    return Section::factory()->create([
        'course_id' => Course::factory()->ownedBy($instructor)->create()->id,
    ]);
}

test('an instructor can move an existing lesson into another section', function () {
    $instructor = User::factory()->instructor()->create();
    $origin = sectionOwnedBy($instructor);
    $destination = sectionOwnedBy($instructor);

    $lesson = Lesson::factory()->atPosition(1)->create([
        'section_id' => $origin->id,
        'content' => '<p>Materi lama</p>',
    ]);

    $this->actingAs($instructor)
        ->patch(route('lessons.section.update', $lesson), ['section_id' => $destination->id])
        ->assertRedirect();

    $lesson->refresh();

    expect($lesson->section_id)->toBe($destination->id)
        ->and($lesson->position)->toBe(1)
        ->and($lesson->content)->toBe('<p>Materi lama</p>')
        ->and($origin->lessons()->count())->toBe(0);
});

test('a moved lesson is appended after the lessons already in the destination', function () {
    $instructor = User::factory()->instructor()->create();
    $origin = sectionOwnedBy($instructor);
    $destination = sectionOwnedBy($instructor);

    Lesson::factory()->atPosition(1)->create(['section_id' => $destination->id]);
    Lesson::factory()->atPosition(2)->create(['section_id' => $destination->id]);
    $lesson = Lesson::factory()->atPosition(1)->create(['section_id' => $origin->id]);

    $this->actingAs($instructor)
        ->patch(route('lessons.section.update', $lesson), ['section_id' => $destination->id]);

    expect($lesson->refresh()->position)->toBe(3);
});

test('moving a lesson away closes the gap in the section it leaves', function () {
    $instructor = User::factory()->instructor()->create();
    $origin = sectionOwnedBy($instructor);
    $destination = sectionOwnedBy($instructor);

    $first = Lesson::factory()->atPosition(1)->create(['section_id' => $origin->id]);
    $second = Lesson::factory()->atPosition(2)->create(['section_id' => $origin->id]);
    $third = Lesson::factory()->atPosition(3)->create(['section_id' => $origin->id]);

    $this->actingAs($instructor)
        ->patch(route('lessons.section.update', $second), ['section_id' => $destination->id]);

    expect($first->refresh()->position)->toBe(1)
        ->and($third->refresh()->position)->toBe(2);
});

test('moving a lesson into its own section changes nothing', function () {
    $instructor = User::factory()->instructor()->create();
    $section = sectionOwnedBy($instructor);
    $lesson = Lesson::factory()->atPosition(1)->create(['section_id' => $section->id]);

    $this->actingAs($instructor)
        ->patch(route('lessons.section.update', $lesson), ['section_id' => $section->id])
        ->assertSessionHasNoErrors();

    expect($lesson->refresh()->position)->toBe(1)
        ->and($lesson->section_id)->toBe($section->id);
});

test('an instructor cannot move a lesson they do not own', function () {
    $instructor = User::factory()->instructor()->create();
    $destination = sectionOwnedBy($instructor);
    $lesson = Lesson::factory()->create();

    $this->actingAs($instructor)
        ->patch(route('lessons.section.update', $lesson), ['section_id' => $destination->id])
        ->assertForbidden();

    expect($lesson->refresh()->section_id)->not->toBe($destination->id);
});

test('an instructor cannot move a lesson into a section they do not own', function () {
    $instructor = User::factory()->instructor()->create();
    $lesson = Lesson::factory()->create(['section_id' => sectionOwnedBy($instructor)->id]);
    $foreign = Section::factory()->create();

    $this->actingAs($instructor)
        ->patch(route('lessons.section.update', $lesson), ['section_id' => $foreign->id])
        ->assertSessionHasErrors('section_id');

    expect($lesson->refresh()->section_id)->not->toBe($foreign->id);
});

test('an admin can move a lesson between two different instructors courses', function () {
    $admin = User::factory()->admin()->create();
    $lesson = Lesson::factory()->create();
    $destination = Section::factory()->create();

    $this->actingAs($admin)
        ->patch(route('lessons.section.update', $lesson), ['section_id' => $destination->id])
        ->assertRedirect();

    expect($lesson->refresh()->section_id)->toBe($destination->id);
});

test('a student cannot move anything', function () {
    $student = User::factory()->student()->create();
    $lesson = Lesson::factory()->create();
    $destination = Section::factory()->create();

    $this->actingAs($student)
        ->patch(route('lessons.section.update', $lesson), ['section_id' => $destination->id])
        ->assertForbidden();
});

test('an instructor can move an existing quiz into another section', function () {
    $instructor = User::factory()->instructor()->create();
    $origin = sectionOwnedBy($instructor);
    $destination = sectionOwnedBy($instructor);

    $quiz = Quiz::factory()->atPosition(1)->create(['section_id' => $origin->id]);
    QuizQuestion::factory()->count(2)->create(['quiz_id' => $quiz->id]);

    $this->actingAs($instructor)
        ->patch(route('quizzes.section.update', $quiz), ['section_id' => $destination->id])
        ->assertRedirect();

    $quiz->refresh();

    expect($quiz->section_id)->toBe($destination->id)
        ->and($quiz->position)->toBe(1)
        ->and($quiz->questions()->count())->toBe(2)
        ->and($origin->quizzes()->count())->toBe(0);
});

test('moving a quiz away closes the gap in the section it leaves', function () {
    $instructor = User::factory()->instructor()->create();
    $origin = sectionOwnedBy($instructor);
    $destination = sectionOwnedBy($instructor);

    $first = Quiz::factory()->atPosition(1)->create(['section_id' => $origin->id]);
    $second = Quiz::factory()->atPosition(2)->create(['section_id' => $origin->id]);
    $third = Quiz::factory()->atPosition(3)->create(['section_id' => $origin->id]);

    $this->actingAs($instructor)
        ->patch(route('quizzes.section.update', $second), ['section_id' => $destination->id]);

    expect($first->refresh()->position)->toBe(1)
        ->and($third->refresh()->position)->toBe(2);
});

test('an instructor cannot move a quiz they do not own', function () {
    $instructor = User::factory()->instructor()->create();
    $destination = sectionOwnedBy($instructor);
    $quiz = Quiz::factory()->create();

    $this->actingAs($instructor)
        ->patch(route('quizzes.section.update', $quiz), ['section_id' => $destination->id])
        ->assertForbidden();
});

test('the course page leaves the movable lists out until they are asked for', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->ownedBy($instructor)->create();
    $section = Section::factory()->create(['course_id' => $course->id]);
    Lesson::factory()->create(['section_id' => $section->id]);
    Quiz::factory()->create(['section_id' => $section->id]);

    $this->actingAs($instructor)
        ->get(route('courses.show', $course))
        ->assertInertia(fn ($page) => $page
            ->missing('movableLessons')
            ->missing('movableQuizzes'));
});
test('the movable lists are scoped to the courses the user manages', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->ownedBy($instructor)->create();
    $section = Section::factory()->create(['course_id' => $course->id]);
    Lesson::factory()->create(['section_id' => $section->id]);
    Quiz::factory()->create(['section_id' => $section->id]);

    Lesson::factory()->create();
    Quiz::factory()->create();

    $this->actingAs($instructor)->get(route('courses.show', $course))->assertOk();

    $partial = fn (string $prop) => $this->actingAs($instructor)->get(
        route('courses.show', $course),
        [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
            'X-Inertia-Partial-Component' => 'courses/Show',
            'X-Inertia-Partial-Data' => $prop,
        ],
    );

    $partial('movableLessons')
        ->assertOk()
        ->assertJsonCount(1, 'props.movableLessons');

    $partial('movableQuizzes')
        ->assertOk()
        ->assertJsonCount(1, 'props.movableQuizzes');
});
