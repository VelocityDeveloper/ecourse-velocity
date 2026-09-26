<?php

use App\Models\Course;
use App\Models\User;

test('guests can view instructor profiles', function () {
    $instructor = User::factory()->instructor()->create();

    $this->get(route('users.show', $instructor))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('profile.id', $instructor->id));
});

test('guests cannot view student profiles', function () {
    $student = User::factory()->student()->create();

    $this->get(route('users.show', $student))->assertNotFound();
});

test('authenticated users can view another user profile', function () {
    $viewer = User::factory()->student()->create();
    $user = User::factory()->create([
        'headline' => 'Senior Laravel Developer',
        'bio' => 'Mengajar Laravel sejak 2015.',
    ]);

    $this->actingAs($viewer)
        ->get(route('users.show', $user))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('users/Show')
            ->where('profile.name', $user->name)
            ->where('profile.headline', 'Senior Laravel Developer')
            ->where('profile.bio', 'Mengajar Laravel sejak 2015.')
            ->missing('profile.email')
            ->has('courses', 0)
        );
});

test('instructor profile lists only published courses', function () {
    $instructor = User::factory()->instructor()->create();
    Course::factory()->ownedBy($instructor)->published()->create();
    Course::factory()->ownedBy($instructor)->create();

    $this->actingAs(User::factory()->student()->create())
        ->get(route('users.show', $instructor))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('courses', 1));
});
