<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.categories.index'))->assertRedirect(route('login'));
});

test('instructors cannot manage categories', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)->get(route('admin.categories.index'))->assertForbidden();
    $this->actingAs($instructor)->post(route('admin.categories.store'), ['name' => 'Nope'])->assertForbidden();
});

test('students are sent back to the public site and cannot manage categories', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)->get(route('admin.categories.index'))->assertRedirect(route('home'));
    $this->actingAs($student)->post(route('admin.categories.store'), ['name' => 'Nope'])->assertForbidden();
});

test('an admin can list categories with their course counts', function () {
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();
    Course::factory()->count(2)->create(['category_id' => $category->id]);

    $this->actingAs($admin)
        ->get(route('admin.categories.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Categories/Index')
            ->has('categories.data', 1)
            ->where('categories.data.0.courses_count', 2));
});

test('an admin can create a category and its slug is derived from the name', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.categories.store'), ['name' => 'Web Development'])
        ->assertRedirect(route('admin.categories.index'));

    expect(Category::sole()->slug)->toBe('web-development');
});

test('a category name requires a unique slug', function () {
    $admin = User::factory()->admin()->create();
    Category::factory()->create(['slug' => 'web-development']);

    $this->actingAs($admin)
        ->post(route('admin.categories.store'), ['name' => 'Web Development'])
        ->assertSessionHasErrors('slug');
});

test('an admin can update a category', function () {
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.categories.update', $category), ['name' => 'Data Science'])
        ->assertRedirect(route('admin.categories.index'));

    expect($category->refresh()->name)->toBe('Data Science')
        ->and($category->slug)->toBe('data-science');
});

test('a category keeps its own slug when updated', function () {
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create(['name' => 'DevOps', 'slug' => 'devops']);

    $this->actingAs($admin)
        ->put(route('admin.categories.update', $category), ['name' => 'DevOps', 'slug' => 'devops'])
        ->assertSessionHasNoErrors();
});

test('deleting a category leaves its courses uncategorised', function () {
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();
    $course = Course::factory()->create(['category_id' => $category->id]);

    $this->actingAs($admin)
        ->delete(route('admin.categories.destroy', $category))
        ->assertRedirect(route('admin.categories.index'));

    expect(Category::query()->count())->toBe(0)
        ->and($course->refresh()->category_id)->toBeNull();
});
