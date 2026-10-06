<?php

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\Captcha;
use Illuminate\Support\Facades\Cache;

test('the picture is a png and its digits are kept in the session', function () {
    $response = $this->get(route('captcha', 'login'));

    $response->assertOk()->assertHeader('Content-Type', 'image/png');
    expect(substr($response->getContent(), 0, 8))->toBe("\x89PNG\r\n\x1a\n")
        ->and(getimagesizefromstring($response->getContent())[0])->toBe(168);

    $issued = session('captcha.login');
    expect($issued['code'])->toMatch('/^\d{5}$/');
    expect(session('captcha.register'))->toBeNull();
});

test('only the login and registration forms have a picture', function () {
    $this->get('/captcha/contact')->assertNotFound();
});

test('the login and registration pages say whether they ask for the code', function () {
    $this->get(route('login'))->assertInertia(fn ($page) => $page->where('captcha', true));
    $this->get(route('register'))->assertInertia(fn ($page) => $page->where('captcha', true));

    SiteSetting::put(Captcha::settingKey('login'), '0');

    $this->get(route('login'))->assertInertia(fn ($page) => $page->where('captcha', false));
    $this->get(route('register'))->assertInertia(fn ($page) => $page->where('captcha', true));
});

test('login without the code is refused before the password is checked', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['captcha' => __('Type the numbers shown in the picture.')]);

    $this->assertGuest();
});

test('login with a wrong code is refused and the code is spent', function () {
    $user = User::factory()->create();

    captcha();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'captcha' => '00000'])
        ->assertSessionHasErrors(['captcha' => __('The security code is wrong. Type the numbers in the new picture.')]);

    $this->assertGuest();
    expect(session('captcha.login'))->toBeNull();

    // The right digits no longer work once the code was used.
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'captcha' => '12345'])
        ->assertSessionHasErrors('captcha');
    $this->assertGuest();
});

test('the digits shown in the picture let the person in', function () {
    $user = User::factory()->create();

    $this->get(route('captcha', 'login'));
    $code = session('captcha.login')['code'];

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'captcha' => $code])
        ->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($user);
});

test('an expired code is refused', function () {
    $user = User::factory()->create();

    captcha();
    $this->travel(Captcha::TTL_MINUTES + 1)->minutes();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'captcha' => '12345'])
        ->assertSessionHasErrors('captcha');

    $this->assertGuest();
});

test('a code solved once can not be replayed against more passwords', function () {
    $user = User::factory()->create();

    captcha();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong', 'captcha' => '12345'])
        ->assertSessionHasErrors('email');
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'captcha' => '12345'])
        ->assertSessionHasErrors('captcha');

    $this->assertGuest();
});

test('registration with a wrong code creates no account and shows every error at once', function () {
    captcha('register');

    $this->post(route('register.store'), [
        'name' => 'Bot',
        'email' => 'bukan-email',
        'password' => 'password',
        'password_confirmation' => 'password',
        'captcha' => '99999',
    ])->assertSessionHasErrors(['captcha', 'email']);

    expect(User::query()->where('name', 'Bot')->exists())->toBeFalse();
    $this->assertGuest();
});

test('the login code does not answer the registration form', function () {
    captcha('login');

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'captcha' => '12345',
    ])->assertSessionHasErrors('captcha');

    $this->assertGuest();
});

test('a form whose code is switched off works without it', function () {
    SiteSetting::put(Captcha::settingKey('login'), '0');
    SiteSetting::put(Captcha::settingKey('register'), '0');
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($user);

    $this->post(route('logout'));

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();
    $this->assertAuthenticated();
});

test('the admin switches the code per form under Keamanan', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.settings.edit', 'keamanan'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/Settings/Security')
            ->where('captcha', ['login' => true, 'register' => true])
            ->where('loginAttempts', 5));

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), ['section' => 'keamanan', 'captcha' => ['login' => true, 'register' => false]])
        ->assertRedirect(route('admin.settings.edit', 'keamanan'));

    expect(Captcha::enabled('login'))->toBeTrue()
        ->and(Captcha::enabled('register'))->toBeFalse()
        ->and(SiteSetting::get(Captcha::settingKey('login')))->toBeNull();

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), ['section' => 'keamanan', 'captcha' => ['login' => true, 'register' => true]]);

    expect(Captcha::enabled('register'))->toBeTrue();
});

test('instructors can not switch the code off', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)
        ->post(route('admin.settings.update'), ['section' => 'keamanan', 'captcha' => ['login' => false, 'register' => false]])
        ->assertForbidden();

    expect(Captcha::enabled('login'))->toBeTrue();
});

test('opening the picture is not counted as a visit', function () {
    Cache::flush();

    $this->withHeader('User-Agent', 'Mozilla/5.0')->get(route('captcha', 'login'))->assertOk();

    $this->assertDatabaseCount('visitor_days', 0);
});
