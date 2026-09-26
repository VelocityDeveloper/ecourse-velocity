<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

test('an admin can give a category a background image', function () {
    Storage::fake(Category::IMAGE_DISK);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.categories.store'), [
            'name' => 'Teknik Sipil',
            'image' => UploadedFile::fake()->image('sipil.jpg', 1200, 600),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.categories.index'));

    $category = Category::query()->where('slug', 'teknik-sipil')->sole();

    Storage::disk(Category::IMAGE_DISK)->assertExists($category->image_path);
    expect($category->image_url)->toBe(Storage::disk(Category::IMAGE_DISK)->url($category->image_path));
});

test('a category image can be replaced through a spoofed PUT and then removed', function () {
    Storage::fake(Category::IMAGE_DISK);
    $admin = User::factory()->admin()->create();
    $old = UploadedFile::fake()->image('old.jpg')->store(Category::IMAGE_DIRECTORY, Category::IMAGE_DISK);
    $category = Category::factory()->create(['image_path' => $old]);

    $this->actingAs($admin)
        ->post(route('admin.categories.update', $category), [
            '_method' => 'put',
            'name' => $category->name,
            'slug' => $category->slug,
            'image' => UploadedFile::fake()->image('new.webp'),
        ])
        ->assertSessionHasNoErrors();

    $new = $category->fresh()->image_path;

    expect($new)->not->toBe($old);
    Storage::disk(Category::IMAGE_DISK)->assertMissing($old);

    $this->actingAs($admin)
        ->put(route('admin.categories.update', $category), [
            'name' => $category->name,
            'slug' => $category->slug,
            'remove_image' => '1',
        ])
        ->assertSessionHasNoErrors();

    expect($category->fresh()->image_path)->toBeNull();
    Storage::disk(Category::IMAGE_DISK)->assertMissing($new);
});

test('a category image must be a small png, jpg or webp', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.categories.store'), [
            'name' => 'Salah',
            'image' => UploadedFile::fake()->create('file.pdf', 10),
        ])
        ->assertSessionHasErrors('image');

    $this->actingAs($admin)
        ->post(route('admin.categories.store'), [
            'name' => 'Besar',
            'image' => UploadedFile::fake()->image('big.jpg')->size(3073),
        ])
        ->assertSessionHasErrors('image');
});

test('deleting a category removes its image', function () {
    Storage::fake(Category::IMAGE_DISK);
    $admin = User::factory()->admin()->create();
    $path = UploadedFile::fake()->image('c.jpg')->store(Category::IMAGE_DIRECTORY, Category::IMAGE_DISK);
    $category = Category::factory()->create(['image_path' => $path]);

    $this->actingAs($admin)->delete(route('admin.categories.destroy', $category));

    Storage::disk(Category::IMAGE_DISK)->assertMissing($path);
});

test('the homepage learning paths carry the category image', function () {
    Storage::fake(Category::IMAGE_DISK);
    $category = Category::factory()->create(['image_path' => 'categories/sipil.jpg']);
    Course::factory()->published()->create(['category_id' => $category->id]);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('categories.0.image_url', Storage::disk(Category::IMAGE_DISK)->url('categories/sipil.jpg')));
});
