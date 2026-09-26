<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});

test('profile headline, bio and avatar can be updated', function () {
    Storage::fake(User::AVATAR_DISK);

    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'headline' => 'Senior Laravel Developer',
            'bio' => 'Mengajar Laravel sejak 2015.',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->headline)->toBe('Senior Laravel Developer');
    expect($user->bio)->toBe('Mengajar Laravel sejak 2015.');
    expect($user->avatar_path)->not->toBeNull();
    expect($user->avatar)->toBe(Storage::disk(User::AVATAR_DISK)->url($user->avatar_path));
    Storage::disk(User::AVATAR_DISK)->assertExists($user->avatar_path);
});

test('uploading a new avatar replaces the previous file', function () {
    Storage::fake(User::AVATAR_DISK);

    $oldAvatarPath = UploadedFile::fake()->image('old.jpg')->store(User::AVATAR_DIRECTORY, User::AVATAR_DISK);
    $user = User::factory()->create(['avatar_path' => $oldAvatarPath]);

    $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('new.jpg'),
        ])
        ->assertSessionHasNoErrors();

    Storage::disk(User::AVATAR_DISK)->assertMissing($oldAvatarPath);
    Storage::disk(User::AVATAR_DISK)->assertExists($user->refresh()->avatar_path);
});

test('avatar can be removed', function () {
    Storage::fake(User::AVATAR_DISK);

    $avatarPath = UploadedFile::fake()->image('avatar.jpg')->store(User::AVATAR_DIRECTORY, User::AVATAR_DISK);
    $user = User::factory()->create(['avatar_path' => $avatarPath]);

    $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'remove_avatar' => '1',
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->avatar_path)->toBeNull();
    Storage::disk(User::AVATAR_DISK)->assertMissing($avatarPath);
});

test('avatar must be an image', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('avatar');

    expect($user->refresh()->avatar_path)->toBeNull();
});
