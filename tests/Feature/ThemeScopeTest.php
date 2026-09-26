<?php

use App\Models\User;
use App\Support\ThemeScope;

test('public pages are always light, even with a dark preference saved', function () {
    $this->withUnencryptedCookie('appearance', 'dark')
        ->get(route('home'))
        ->assertOk()
        ->assertSee('data-theme-scope="site"', false)
        ->assertDontSee('class="dark"', false);

    $this->withUnencryptedCookie('appearance', 'dark')
        ->get(route('catalog.index'))
        ->assertSee('data-theme-scope="site"', false);
});

test('the staff dashboard follows the saved dark mode choice', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->withUnencryptedCookie('appearance', 'dark')
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-theme-scope="app"', false)
        ->assertSee('class="dark"', false);
});

test('only dashboard pages get the dark mode scope', function (string $component, ?string $role, string $scope) {
    $user = $role === null ? null : User::factory()->{$role}()->make();

    expect(ThemeScope::for($component, $user))->toBe($scope);
})->with([
    ['Welcome', null, ThemeScope::SITE],
    ['catalog/Show', 'student', ThemeScope::SITE],
    ['learn/Lesson', 'student', ThemeScope::SITE],
    ['learning/Dashboard', 'student', ThemeScope::SITE],
    ['auth/Login', null, ThemeScope::SITE],
    ['settings/Profile', 'student', ThemeScope::SITE],
    ['settings/Profile', 'instructor', ThemeScope::APP],
    ['courses/Index', 'instructor', ThemeScope::APP],
    ['admin/Settings/Colors', 'admin', ThemeScope::APP],
]);

test('students have no appearance settings, staff do', function () {
    $student = User::factory()->student()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($student)->get(route('appearance.edit'))->assertRedirect(route('profile.edit'));
    $this->actingAs($admin)->get(route('appearance.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/Appearance'));
});
