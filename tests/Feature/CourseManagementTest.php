<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function coursePayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Laravel From Scratch',
        'description' => 'Belajar Laravel dari nol.',
        'category_id' => null,
        'price' => '199000.00',
        'level' => 'beginner',
        'status' => Course::STATUS_DRAFT,
    ], $overrides);
}

test('guests are redirected to the login page', function () {
    $this->get(route('courses.index'))->assertRedirect(route('login'));
});

test('students cannot reach course management', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)->get(route('courses.index'))->assertRedirect(route('home'));
    $this->actingAs($student)->get(route('courses.create'))->assertRedirect(route('home'));
});

test('admins see every course', function () {
    $admin = User::factory()->admin()->create();
    Course::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('courses.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('courses/Index')
            ->has('courses.data', 3));
});

test('instructors only see their own courses', function () {
    $instructor = User::factory()->instructor()->create();
    Course::factory()->count(2)->ownedBy($instructor)->create();
    Course::factory()->count(3)->create();

    $this->actingAs($instructor)
        ->get(route('courses.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('courses.data', 2));
});

test('an instructor owns the course they create', function () {
    $instructor = User::factory()->instructor()->create();
    $other = User::factory()->instructor()->create();

    $this->actingAs($instructor)
        ->post(route('courses.store'), coursePayload(['instructor_id' => $other->id]))
        ->assertRedirect();

    expect(Course::sole()->instructor_id)->toBe($instructor->id);
});

test('an admin can create a course for any instructor', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($admin)
        ->post(route('courses.store'), coursePayload(['instructor_id' => $instructor->id]))
        ->assertRedirect();

    expect(Course::sole()->instructor_id)->toBe($instructor->id);
});

test('a course slug is generated from the title and kept unique', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)->post(route('courses.store'), coursePayload());
    $this->actingAs($instructor)->post(route('courses.store'), coursePayload());

    expect(Course::pluck('slug')->all())
        ->toBe(['laravel-from-scratch', 'laravel-from-scratch-2']);
});

test('an instructor cannot open a course owned by someone else', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->create();

    $this->actingAs($instructor)->get(route('courses.show', $course))->assertForbidden();
    $this->actingAs($instructor)->get(route('courses.edit', $course))->assertForbidden();
});

test('an instructor cannot update a course owned by someone else', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->create(['title' => 'Untouched']);

    $this->actingAs($instructor)
        ->put(route('courses.update', $course), coursePayload(['title' => 'Hijacked']))
        ->assertForbidden();

    expect($course->refresh()->title)->toBe('Untouched');
});

test('an instructor can update their own course', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->ownedBy($instructor)->create();

    $this->actingAs($instructor)
        ->put(route('courses.update', $course), coursePayload(['title' => 'Revised Title']))
        ->assertRedirect(route('courses.show', 'revised-title'));

    expect($course->refresh()->title)->toBe('Revised Title');
});

test('an admin can update any instructors course', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->ownedBy($instructor)->create();

    $this->actingAs($admin)
        ->put(route('courses.update', $course), coursePayload([
            'title' => 'Edited By Admin',
            'instructor_id' => $instructor->id,
        ]))
        ->assertRedirect();

    expect($course->refresh()->title)->toBe('Edited By Admin');
});

test('an instructor cannot reassign their course to another instructor', function () {
    $instructor = User::factory()->instructor()->create();
    $other = User::factory()->instructor()->create();
    $course = Course::factory()->ownedBy($instructor)->create();

    $this->actingAs($instructor)->put(route('courses.update', $course), coursePayload([
        'instructor_id' => $other->id,
    ]))->assertRedirect();

    expect($course->refresh()->instructor_id)->toBe($instructor->id);
});

test('a course can be filtered by status and by category', function () {
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();

    Course::factory()->published()->create(['category_id' => $category->id]);
    Course::factory()->create();

    $this->actingAs($admin)
        ->get(route('courses.index', ['status' => Course::STATUS_PUBLISHED]))
        ->assertInertia(fn ($page) => $page->has('courses.data', 1));

    $this->actingAs($admin)
        ->get(route('courses.index', ['category_id' => $category->id]))
        ->assertInertia(fn ($page) => $page->has('courses.data', 1));
});

test('an instructor can submit their own draft for approval', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->ownedBy($instructor)->create();

    $this->actingAs($instructor)
        ->patch(route('courses.status.update', $course), ['status' => Course::STATUS_PENDING])
        ->assertRedirect();

    expect($course->refresh()->status)->toBe(Course::STATUS_PENDING);
});

test('an instructor cannot publish or archive a course', function (string $status) {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->ownedBy($instructor)->create();

    $this->actingAs($instructor)
        ->patch(route('courses.status.update', $course), ['status' => $status])
        ->assertSessionHasErrors('status');

    expect($course->refresh()->status)->toBe(Course::STATUS_DRAFT);
})->with([Course::STATUS_PUBLISHED, Course::STATUS_ARCHIVED]);

test('an admin can move a course to any status', function (string $status) {
    $admin = User::factory()->admin()->create();
    $course = Course::factory()->create();

    $this->actingAs($admin)
        ->patch(route('courses.status.update', $course), ['status' => $status])
        ->assertSessionHasNoErrors();

    expect($course->refresh()->status)->toBe($status);
})->with(Course::STATUSES);

test('an instructor cannot change the status of another instructors course', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->create();

    $this->actingAs($instructor)
        ->patch(route('courses.status.update', $course), ['status' => Course::STATUS_PENDING])
        ->assertForbidden();
});

test('an instructor can delete their own draft course', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->ownedBy($instructor)->create();

    $this->actingAs($instructor)
        ->delete(route('courses.destroy', $course))
        ->assertRedirect(route('courses.index'));

    expect(Course::query()->count())->toBe(0);
});

test('an instructor cannot delete their own course once it leaves draft', function (string $status) {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->ownedBy($instructor)->create(['status' => $status]);

    $this->actingAs($instructor)->delete(route('courses.destroy', $course))->assertForbidden();

    expect(Course::query()->count())->toBe(1);
})->with([Course::STATUS_PENDING, Course::STATUS_PUBLISHED, Course::STATUS_ARCHIVED]);

test('an instructor cannot delete a course owned by someone else', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->create();

    $this->actingAs($instructor)->delete(route('courses.destroy', $course))->assertForbidden();

    expect(Course::query()->count())->toBe(1);
});

test('an admin can delete any course whatever its status', function (string $status) {
    $admin = User::factory()->admin()->create();
    $course = Course::factory()->create(['status' => $status]);

    $this->actingAs($admin)->delete(route('courses.destroy', $course))->assertRedirect();

    expect(Course::query()->count())->toBe(0);
})->with(Course::STATUSES);

test('a thumbnail is stored on upload and removed when the course is deleted', function () {
    Storage::fake(Course::THUMBNAIL_DISK);

    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($admin)->post(route('courses.store'), coursePayload([
        'instructor_id' => $instructor->id,
        'thumbnail' => UploadedFile::fake()->image('cover.jpg'),
    ]));

    $course = Course::sole();
    expect($course->thumbnail_path)->not->toBeNull();
    Storage::disk(Course::THUMBNAIL_DISK)->assertExists($course->thumbnail_path);

    $this->actingAs($admin)->delete(route('courses.destroy', $course));

    Storage::disk(Course::THUMBNAIL_DISK)->assertMissing($course->thumbnail_path);
});

test('replacing a thumbnail deletes the previous file', function () {
    Storage::fake(Course::THUMBNAIL_DISK);

    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->ownedBy($instructor)->create();

    $this->actingAs($instructor)->put(route('courses.update', $course), coursePayload([
        'thumbnail' => UploadedFile::fake()->image('first.jpg'),
    ]));
    $first = $course->refresh()->thumbnail_path;

    $this->actingAs($instructor)->put(route('courses.update', $course), coursePayload([
        'thumbnail' => UploadedFile::fake()->image('second.jpg'),
    ]));
    $second = $course->refresh()->thumbnail_path;

    expect($second)->not->toBe($first);
    Storage::disk(Course::THUMBNAIL_DISK)->assertMissing($first);
    Storage::disk(Course::THUMBNAIL_DISK)->assertExists($second);
});

test('updating a course without a new thumbnail keeps the existing one', function () {
    Storage::fake(Course::THUMBNAIL_DISK);

    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->ownedBy($instructor)->create(['thumbnail_path' => 'course-thumbnails/keep.jpg']);

    $this->actingAs($instructor)->put(route('courses.update', $course), coursePayload());

    expect($course->refresh()->thumbnail_path)->toBe('course-thumbnails/keep.jpg');
});

test('a non image file is rejected as a thumbnail', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)
        ->post(route('courses.store'), coursePayload([
            'thumbnail' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
        ]))
        ->assertSessionHasErrors('thumbnail');
});

test('an admin can only assign a course to a user who is an instructor', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->student()->create();

    $this->actingAs($admin)
        ->post(route('courses.store'), coursePayload(['instructor_id' => $student->id]))
        ->assertSessionHasErrors('instructor_id');
});

test('the old edit link opens the edit tabs of the course page', function () {
    $admin = User::factory()->admin()->create();
    $course = Course::factory()->create();

    $this->actingAs($admin)
        ->get(route('courses.edit', $course))
        ->assertRedirect(route('courses.show', ['course' => $course, 'tab' => 'informasi']));

    $this->actingAs($admin)
        ->get(route('courses.show', $course))
        ->assertInertia(fn ($page) => $page
            ->component('courses/Show')
            ->where('form.slug', $course->slug)
            ->where('isAdmin', true)
            ->has('levels'));
});

test('an instructor can edit a published course without changing its status', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->ownedBy($instructor)->published()->create();

    $this->actingAs($instructor)
        ->put(route('courses.update', $course), coursePayload(['title' => 'Judul Baru', 'status' => Course::STATUS_PUBLISHED]))
        ->assertSessionHasNoErrors();

    expect($course->refresh()->title)->toBe('Judul Baru')
        ->and($course->status)->toBe(Course::STATUS_PUBLISHED);

    $this->actingAs($instructor)
        ->put(route('courses.update', $course), coursePayload(['status' => Course::STATUS_ARCHIVED]))
        ->assertSessionHasErrors('status');
});
