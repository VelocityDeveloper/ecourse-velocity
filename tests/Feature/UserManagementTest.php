<?php

use App\Models\Course;
use App\Models\User;

test('admin user list includes profile details for each user', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create([
        'avatar_path' => 'avatars/instructor.jpg',
        'headline' => 'Senior Laravel Developer',
        'bio' => 'Mengajar Laravel sejak 2015.',
    ]);
    Course::factory()->ownedBy($instructor)->count(2)->create();

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['search' => $instructor->email]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Users/Index')
            ->has('users.data', 1)
            ->where('users.data.0.avatar', Storage::disk(User::AVATAR_DISK)->url('avatars/instructor.jpg'))
            ->where('users.data.0.headline', 'Senior Laravel Developer')
            ->where('users.data.0.bio', 'Mengajar Laravel sejak 2015.')
            ->where('users.data.0.courses_count', 2)
            ->missing('users.data.0.avatar_path')
        );
});

test('non admins cannot view the user list', function () {
    $this->actingAs(User::factory()->instructor()->create())
        ->get(route('admin.users.index'))
        ->assertForbidden();
});
