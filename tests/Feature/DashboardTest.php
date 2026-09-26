<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('staff can visit the dashboard', function (string $role) {
    $this->actingAs(User::factory()->create(['role' => $role]))
        ->get(route('dashboard'))
        ->assertOk();
})->with([User::ROLE_ADMIN, User::ROLE_INSTRUCTOR]);

test('students are sent to the public site instead of the dashboard', function () {
    $this->actingAs(User::factory()->student()->create())
        ->get(route('dashboard'))
        ->assertRedirect(route('home'));
});

test('students land on the public site after logging in', function () {
    $student = User::factory()->student()->create();

    $this->post(route('login.store'), [
        'email' => $student->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))->assertRedirect(route('home'));
});
