<?php

use App\Models\Banner;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(Banner::IMAGE_DISK);
});

test('only admins can manage banners', function () {
    $instructor = User::factory()->instructor()->create();
    $student = User::factory()->student()->create();

    $this->get(route('admin.banners.index'))->assertRedirect(route('login'));
    $this->actingAs($instructor)->get(route('admin.banners.index'))->assertForbidden();
    $this->actingAs($student)
        ->post(route('admin.banners.store'), ['title' => 'Promo', 'image' => UploadedFile::fake()->image('b.jpg')])
        ->assertForbidden();

    expect(Banner::query()->count())->toBe(0);
});

test('an admin can add banners and they are appended in order', function () {
    $admin = User::factory()->admin()->create();

    foreach (['Pertama', 'Kedua'] as $title) {
        $this->actingAs($admin)
            ->post(route('admin.banners.store'), [
                'title' => $title,
                'image' => UploadedFile::fake()->image('b.jpg', 1500, 500),
                'link_url' => '/catalog',
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.banners.index'));
    }

    $banners = Banner::query()->ordered()->get();

    expect($banners->pluck('title')->all())->toBe(['Pertama', 'Kedua']);
    Storage::disk(Banner::IMAGE_DISK)->assertExists($banners[0]->image_path);

    $this->actingAs($admin)
        ->get(route('admin.banners.index'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/Banners/Index')
            ->has('banners', 2)
            ->where('banners.0.image_url', Storage::disk(Banner::IMAGE_DISK)->url($banners[0]->image_path))
            ->missing('banners.0.image_path'));
});

test('a new banner needs a title and an image', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.banners.store'), ['link_url' => 'javascript:alert(1)'])
        ->assertSessionHasErrors(['title', 'image', 'link_url']);
});

test('an admin can edit a banner and replace its image', function () {
    $admin = User::factory()->admin()->create();
    $old = UploadedFile::fake()->image('old.jpg')->store(Banner::IMAGE_DIRECTORY, Banner::IMAGE_DISK);
    $banner = Banner::factory()->create(['image_path' => $old]);

    $this->actingAs($admin)
        ->post(route('admin.banners.update', $banner), [
            'title' => 'Diubah',
            'link_url' => 'https://contoh.id/promo',
            'is_active' => '0',
            'image' => UploadedFile::fake()->image('new.webp'),
        ])
        ->assertSessionHasNoErrors();

    $banner->refresh();

    expect($banner->title)->toBe('Diubah')
        ->and($banner->is_active)->toBeFalse()
        ->and($banner->image_path)->not->toBe($old);
    Storage::disk(Banner::IMAGE_DISK)->assertMissing($old);
});

test('editing without a new image keeps the current one', function () {
    $admin = User::factory()->admin()->create();
    $banner = Banner::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.banners.update', $banner), ['title' => 'Tetap'])
        ->assertSessionHasNoErrors();

    expect($banner->fresh()->image_path)->toBe($banner->image_path);
});

test('an admin can reorder banners', function () {
    $admin = User::factory()->admin()->create();
    $first = Banner::factory()->create(['sort_order' => 0]);
    $second = Banner::factory()->create(['sort_order' => 1]);

    $this->actingAs($admin)
        ->post(route('admin.banners.move', $second), ['direction' => 'up'])
        ->assertRedirect(route('admin.banners.index'));

    expect(Banner::query()->ordered()->pluck('id')->all())->toBe([$second->id, $first->id]);

    $this->actingAs($admin)->post(route('admin.banners.move', $second), ['direction' => 'up']);

    expect(Banner::query()->ordered()->pluck('id')->all())->toBe([$second->id, $first->id]);
});

test('an admin can delete a banner and its image', function () {
    $admin = User::factory()->admin()->create();
    $path = UploadedFile::fake()->image('b.jpg')->store(Banner::IMAGE_DIRECTORY, Banner::IMAGE_DISK);
    $banner = Banner::factory()->create(['image_path' => $path]);

    $this->actingAs($admin)->delete(route('admin.banners.destroy', $banner))->assertRedirect(route('admin.banners.index'));

    expect(Banner::query()->count())->toBe(0);
    Storage::disk(Banner::IMAGE_DISK)->assertMissing($path);
});

test('the homepage slider shows only active banners in order', function () {
    Banner::factory()->create(['title' => 'Dua', 'sort_order' => 2]);
    Banner::factory()->create(['title' => 'Satu', 'sort_order' => 1]);
    Banner::factory()->inactive()->create(['title' => 'Tersembunyi', 'sort_order' => 0]);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->has('banners', 2)
            ->where('banners.0.title', 'Satu')
            ->where('banners.1.title', 'Dua'));
});

test('a banner image over the limit gets a clear message', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.banners.store'), [
            'title' => 'Terlalu besar',
            'image' => UploadedFile::fake()->image('b.jpg')->size(3073),
        ])
        ->assertSessionHasErrors(['image' => __('The image may not be larger than :size MB.', ['size' => 3])]);
});
