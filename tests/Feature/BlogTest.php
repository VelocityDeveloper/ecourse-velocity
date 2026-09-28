<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('visitors see only published articles on the blog', function () {
    $live = Post::factory()->create(['title' => 'Artikel Tayang']);
    Post::factory()->draft()->create();
    Post::factory()->create(['published_at' => now()->addDay()]);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('blog/Index')
            ->has('posts.data', 1)
            ->where('posts.data.0.slug', $live->slug));
});

test('the blog can be searched by title', function () {
    Post::factory()->create(['title' => 'Belajar Laravel dari Nol']);
    Post::factory()->create(['title' => 'Tips Karier Developer']);

    $this->get(route('blog.index', ['search' => 'laravel']))
        ->assertInertia(fn ($page) => $page
            ->has('posts.data', 1)
            ->where('posts.data.0.title', 'Belajar Laravel dari Nol'));
});

test('a published article can be read with a summary taken from its content', function () {
    $post = Post::factory()->create(['excerpt' => null, 'content' => '<p>Isi <strong>pertama</strong> artikel.</p>']);
    Post::factory()->count(2)->create();

    $this->get(route('blog.show', $post->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('blog/Show')
            ->where('post.title', $post->title)
            ->where('post.summary', 'Isi pertama artikel.')
            ->where('post.reading_minutes', 1)
            ->has('related', 2)
            ->where('can.edit', false));
});

test('drafts and scheduled articles are hidden from visitors but previewable by admins', function () {
    $draft = Post::factory()->draft()->create();
    $scheduled = Post::factory()->create(['published_at' => now()->addWeek()]);

    $this->get(route('blog.show', $draft->slug))->assertNotFound();
    $this->get(route('blog.show', $scheduled->slug))->assertNotFound();
    $this->actingAs(User::factory()->instructor()->create())->get(route('blog.show', $draft->slug))->assertNotFound();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('blog.show', $draft->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('post.is_live', false)->where('can.edit', true));
});

test('only admins can manage the blog', function () {
    $this->get(route('admin.posts.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->instructor()->create())
        ->get(route('admin.posts.index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->student()->create())
        ->get(route('admin.posts.index'))
        ->assertRedirect(route('home'));
});

test('an admin can list articles filtered by status', function () {
    $admin = User::factory()->admin()->create();
    Post::factory()->count(2)->create();
    Post::factory()->draft()->create();

    $this->actingAs($admin)
        ->get(route('admin.posts.index', ['status' => Post::STATUS_DRAFT]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Posts/Index')
            ->has('posts.data', 1)
            ->where('counts.all', 3)
            ->where('counts.published', 2)
            ->where('counts.draft', 1));
});

test('an admin can write an article with a cover and cleaned content', function () {
    Storage::fake(Post::COVER_DISK);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.posts.store'), [
            'title' => 'Tips Belajar Coding',
            'content' => '<p>Halo</p><script>alert(1)</script>',
            'status' => Post::STATUS_PUBLISHED,
            'cover' => UploadedFile::fake()->image('cover.jpg', 1200, 675),
        ])
        ->assertRedirect(route('admin.posts.edit', 'tips-belajar-coding'));

    $post = Post::sole();

    expect($post->slug)->toBe('tips-belajar-coding')
        ->and($post->user_id)->toBe($admin->id)
        ->and($post->content)->toBe('<p>Halo</p>')
        ->and($post->published_at)->not->toBeNull()
        ->and($post->isPublished())->toBeTrue();

    Storage::disk(Post::COVER_DISK)->assertExists((string) $post->cover_path);
});

test('a draft keeps no publish date until it is published', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.posts.store'), [
        'title' => 'Masih Draf',
        'status' => Post::STATUS_DRAFT,
    ]);

    expect(Post::sole()->published_at)->toBeNull();
});

test('an admin can schedule, update and remove the cover of an article', function () {
    Storage::fake(Post::COVER_DISK);
    $admin = User::factory()->admin()->create();
    $post = Post::factory()->create(['cover_path' => UploadedFile::fake()->image('old.jpg')->store(Post::COVER_DIRECTORY, Post::COVER_DISK)]);
    $oldCover = (string) $post->cover_path;

    $this->actingAs($admin)
        ->put(route('admin.posts.update', $post->slug), [
            'title' => 'Judul Baru',
            'slug' => 'judul-baru',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->addDays(3)->toIso8601String(),
            'remove_cover' => true,
        ])
        ->assertRedirect(route('admin.posts.edit', 'judul-baru'));

    $post->refresh();

    expect($post->title)->toBe('Judul Baru')
        ->and($post->cover_path)->toBeNull()
        ->and($post->isPublished())->toBeFalse();

    Storage::disk(Post::COVER_DISK)->assertMissing($oldCover);
});

test('article slugs must be unique', function () {
    $admin = User::factory()->admin()->create();
    Post::factory()->create(['slug' => 'sama']);

    $this->actingAs($admin)
        ->post(route('admin.posts.store'), ['title' => 'Sama', 'status' => Post::STATUS_DRAFT])
        ->assertSessionHasErrors('slug');
});

test('an admin can delete an article', function () {
    $admin = User::factory()->admin()->create();
    $post = Post::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.posts.destroy', $post->slug))
        ->assertRedirect(route('admin.posts.index'));

    expect(Post::query()->count())->toBe(0);
});
