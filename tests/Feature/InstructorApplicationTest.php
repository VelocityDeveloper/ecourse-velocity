<?php

use App\Models\InstructorApplication;
use App\Models\User;
use App\Notifications\InstructorApplicationReviewed;
use Illuminate\Support\Facades\Notification;

function applicationInput(array $overrides = []): array
{
    return [
        'headline' => 'Web Developer di PT Maju',
        'expertise' => 'Laravel',
        'experience' => 'Lima tahun membangun aplikasi web dengan Laravel dan Vue.',
        'motivation' => 'Saya ingin membuat kursus Laravel dasar untuk pemula.',
        'portfolio_url' => 'https://github.com/contoh',
        'phone' => '081234567890',
        'agreement' => true,
        ...$overrides,
    ];
}

test('anyone can open the become an instructor page', function () {
    $this->get(route('instructor-applications.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('instructor-applications/Create')
            ->where('application', null)
        );
});

test('a guest is sent to log in and back to the form', function () {
    $this->get(route('instructor-applications.login'))->assertRedirect(route('login'));

    $this->post(route('instructor-applications.store'), applicationInput())
        ->assertRedirect(route('login'));

    expect(InstructorApplication::query()->count())->toBe(0);
});

test('a student applies without becoming an instructor yet', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->post(route('instructor-applications.store'), applicationInput())
        ->assertRedirect(route('instructor-applications.create'));

    expect($student->fresh()->role)->toBe(User::ROLE_STUDENT)
        ->and($student->instructorApplications()->sole()->status)->toBe(InstructorApplication::STATUS_PENDING);

    $this->actingAs($student)
        ->get(route('instructor-applications.create'))
        ->assertInertia(fn ($page) => $page->where('application.status', 'pending'));

    // Still a student: the dashboard stays closed.
    $this->actingAs($student->fresh())->get(route('dashboard'))->assertRedirect();
});

test('the application is validated', function () {
    $this->actingAs(User::factory()->student()->create())
        ->post(route('instructor-applications.store'), applicationInput([
            'experience' => 'Terlalu pendek',
            'portfolio_url' => 'bukan-url',
            'agreement' => false,
        ]))
        ->assertSessionHasErrors(['experience', 'portfolio_url', 'agreement']);
});

test('a student cannot send a second application while one is pending', function () {
    $student = User::factory()->student()->create();
    InstructorApplication::factory()->for($student)->create();

    $this->actingAs($student)
        ->post(route('instructor-applications.store'), applicationInput());

    expect($student->instructorApplications()->count())->toBe(1);
});

test('instructors and admins cannot apply', function (string $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->post(route('instructor-applications.store'), applicationInput());

    expect(InstructorApplication::query()->count())->toBe(0);
})->with([User::ROLE_INSTRUCTOR, User::ROLE_ADMIN]);

test('only admins can review applications', function () {
    $application = InstructorApplication::factory()->create();

    $this->actingAs(User::factory()->instructor()->create())
        ->get(route('admin.instructor-applications.index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->instructor()->create())
        ->post(route('admin.instructor-applications.approve', $application))
        ->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.instructor-applications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/InstructorApplications/Index')
            ->has('applications.data', 1)
            ->where('counts.pending', 1)
            ->where('pendingInstructorApplications', 1)
        );

    expect($application->fresh()->status)->toBe(InstructorApplication::STATUS_PENDING);
});

test('approving makes the student an instructor with a filled profile', function () {
    Notification::fake();

    $student = User::factory()->student()->create(['headline' => null, 'bio' => null]);
    $application = InstructorApplication::factory()->for($student)->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.instructor-applications.approve', $application), ['note' => 'Selamat bergabung'])
        ->assertRedirect();

    $student->refresh();
    $application->refresh();

    expect($student->role)->toBe(User::ROLE_INSTRUCTOR)
        ->and($student->headline)->toBe($application->headline)
        ->and($student->bio)->toBe($application->experience)
        ->and($application->status)->toBe(InstructorApplication::STATUS_APPROVED)
        ->and($application->reviewed_by)->toBe($admin->id)
        ->and($application->admin_note)->toBe('Selamat bergabung');

    Notification::assertSentTo($student, InstructorApplicationReviewed::class);

    $this->actingAs($student)->get(route('dashboard'))->assertOk();
});

test('rejecting needs a reason and lets the student apply again', function () {
    Notification::fake();

    $student = User::factory()->student()->create();
    $application = InstructorApplication::factory()->for($student)->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.instructor-applications.reject', $application))
        ->assertSessionHasErrors('note');

    $this->actingAs($admin)
        ->post(route('admin.instructor-applications.reject', $application), ['note' => 'Lengkapi portofolio'])
        ->assertRedirect();

    expect($application->fresh()->status)->toBe(InstructorApplication::STATUS_REJECTED)
        ->and($student->fresh()->role)->toBe(User::ROLE_STUDENT);

    Notification::assertSentTo($student, InstructorApplicationReviewed::class);

    $this->actingAs($student)
        ->get(route('instructor-applications.create'))
        ->assertInertia(fn ($page) => $page
            ->where('application.status', 'rejected')
            ->where('application.admin_note', 'Lengkapi portofolio')
        );

    $this->actingAs($student)
        ->post(route('instructor-applications.store'), applicationInput());

    expect($student->instructorApplications()->pending()->count())->toBe(1);
});

test('a reviewed application cannot be reviewed again', function () {
    $application = InstructorApplication::factory()->create([
        'status' => InstructorApplication::STATUS_REJECTED,
        'admin_note' => 'Belum cukup',
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.instructor-applications.approve', $application));

    expect($application->fresh()->status)->toBe(InstructorApplication::STATUS_REJECTED)
        ->and($application->user->fresh()->role)->toBe(User::ROLE_STUDENT);
});
