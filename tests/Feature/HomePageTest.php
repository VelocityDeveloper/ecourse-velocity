<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

test('guests see the homepage with only published content', function () {
    $category = Category::factory()->create(['name' => 'Web Development']);
    $instructor = User::factory()->instructor()->create(['headline' => 'Laravel Developer']);
    $published = Course::factory()->ownedBy($instructor)->published()->create(['category_id' => $category->id]);
    Course::factory()->ownedBy($instructor)->create(['category_id' => $category->id]);
    Category::factory()->create(['name' => 'Kosong']);
    User::factory()->instructor()->create();
    Enrollment::factory()->for($published)->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->where('canRegister', true)
            ->where('stats.courses', 1)
            ->has('featuredCourses', 1)
            ->where('featuredCourses.0.id', $published->id)
            ->where('featuredCourses.0.students_count', 1)
            ->where('featuredCourses.0.is_enrolled', false)
            ->has('categories', 1)
            ->where('categories.0.name', 'Web Development')
            ->where('categories.0.courses_count', 1)
            ->has('instructors', 1)
            ->where('instructors.0.id', $instructor->id)
            ->where('instructors.0.courses_count', 1)
        );
});

test('the homepage flags featured courses the student is enrolled in', function () {
    $student = User::factory()->student()->create();
    $enrollment = Enrollment::factory()->for($student)->create();

    $this->actingAs($student)
        ->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('featuredCourses.0.id', $enrollment->course_id)
            ->where('featuredCourses.0.is_enrolled', true)
        );
});
