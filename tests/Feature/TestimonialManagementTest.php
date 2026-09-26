<?php

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(Testimonial::PHOTO_DISK);
});

test('only admins can manage testimonials', function () {
    $instructor = User::factory()->instructor()->create();
    $student = User::factory()->student()->create();

    $this->get(route('admin.testimonials.index'))->assertRedirect(route('login'));
    $this->actingAs($instructor)->get(route('admin.testimonials.index'))->assertForbidden();
    $this->actingAs($student)
        ->post(route('admin.testimonials.store'), ['name' => 'X', 'quote' => 'Y', 'rating' => 5])
        ->assertForbidden();

    expect(Testimonial::query()->count())->toBe(0);
});

test('an admin can add a testimonial with a photo', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.testimonials.store'), [
            'name' => 'Nadia Putri',
            'subtitle' => 'Universitas Pertamina',
            'quote' => 'Materinya mudah dipahami.',
            'rating' => 5,
            'mask_name' => '1',
            'is_active' => '1',
            'photo' => UploadedFile::fake()->image('nadia.jpg', 200, 200),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.testimonials.index'));

    $testimonial = Testimonial::query()->sole();

    expect($testimonial->mask_name)->toBeTrue()
        ->and($testimonial->display_name)->toBe('N***a P***i');
    Storage::disk(Testimonial::PHOTO_DISK)->assertExists($testimonial->photo_path);

    $this->actingAs($admin)
        ->get(route('admin.testimonials.index'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/Testimonials/Index')
            ->where('testimonials.0.name', 'Nadia Putri')
            ->where('testimonials.0.display_name', 'N***a P***i')
            ->missing('testimonials.0.photo_path'));
});

test('a testimonial needs a name, a quote and a rating from 1 to 5', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.testimonials.store'), ['rating' => 6])
        ->assertSessionHasErrors(['name', 'quote', 'rating']);
});

test('an admin can edit a testimonial and remove its photo', function () {
    $admin = User::factory()->admin()->create();
    $photo = UploadedFile::fake()->image('old.jpg')->store(Testimonial::PHOTO_DIRECTORY, Testimonial::PHOTO_DISK);
    $testimonial = Testimonial::factory()->create(['photo_path' => $photo]);

    $this->actingAs($admin)
        ->post(route('admin.testimonials.update', $testimonial), [
            'name' => 'Nama Baru',
            'quote' => 'Kutipan baru.',
            'rating' => 4,
            'is_active' => '0',
            'remove_photo' => '1',
        ])
        ->assertSessionHasNoErrors();

    $testimonial->refresh();

    expect($testimonial->name)->toBe('Nama Baru')
        ->and($testimonial->rating)->toBe(4)
        ->and($testimonial->is_active)->toBeFalse()
        ->and($testimonial->photo_path)->toBeNull();
    Storage::disk(Testimonial::PHOTO_DISK)->assertMissing($photo);
});

test('an admin can reorder and delete testimonials', function () {
    $admin = User::factory()->admin()->create();
    $first = Testimonial::factory()->create(['sort_order' => 0]);
    $second = Testimonial::factory()->create(['sort_order' => 1]);

    $this->actingAs($admin)->post(route('admin.testimonials.move', $second), ['direction' => 'up']);

    expect(Testimonial::query()->ordered()->pluck('id')->all())->toBe([$second->id, $first->id]);

    $this->actingAs($admin)
        ->delete(route('admin.testimonials.destroy', $first))
        ->assertRedirect(route('admin.testimonials.index'));

    expect(Testimonial::query()->pluck('id')->all())->toBe([$second->id]);
});

test('the homepage shows the active testimonials in order', function () {
    Testimonial::factory()->create(['name' => 'Kedua Orang', 'sort_order' => 2]);
    Testimonial::factory()->create(['name' => 'Pertama Orang', 'subtitle' => 'PT Contoh', 'sort_order' => 1, 'mask_name' => true]);
    Testimonial::factory()->inactive()->create(['name' => 'Tersembunyi', 'sort_order' => 0]);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->has('testimonials', 2)
            ->where('testimonials.0.name', 'P*****a O***g')
            ->where('testimonials.0.subtitle', 'PT Contoh')
            ->where('testimonials.1.name', 'Kedua Orang'));
});

test('the homepage falls back to course reviews until a testimonial is added', function () {
    $course = Course::factory()->published()->create(['title' => 'Kursus Hebat']);
    CourseReview::factory()->for($course)->create(['rating' => 5, 'comment' => 'Sangat membantu.']);
    CourseReview::factory()->for($course)->create(['rating' => 2, 'comment' => 'Kurang.']);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->has('testimonials', 1)
            ->where('testimonials.0.quote', 'Sangat membantu.')
            ->where('testimonials.0.subtitle', 'Kursus Hebat'));

    Testimonial::factory()->create(['quote' => 'Dari admin.']);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->has('testimonials', 1)
            ->where('testimonials.0.quote', 'Dari admin.'));
});
