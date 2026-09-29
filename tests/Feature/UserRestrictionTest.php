<?php

use App\Models\Course;
use App\Models\Order;
use App\Models\User;

test('only admins can change account permissions', function () {
    $student = User::factory()->student()->create();

    $this->actingAs(User::factory()->instructor()->create())
        ->post(route('admin.users.suspend', $student->slug))
        ->assertForbidden();

    expect($student->fresh()->isSuspended())->toBeFalse();
});

test('a suspended user cannot log in', function () {
    $student = User::factory()->student()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.users.suspend', $student->slug), ['reason' => 'Spam diskusi'])
        ->assertRedirect();

    $student->refresh();
    expect($student->isSuspended())->toBeTrue()
        ->and($student->suspension_reason)->toBe('Spam diskusi');

    auth()->logout();

    $this->post(route('login.store'), ['email' => $student->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('a suspended user who is still logged in is logged out', function () {
    $student = User::factory()->student()->create(['suspended_at' => now()]);

    $this->actingAs($student)
        ->get(route('learning.dashboard'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('an unsuspended user can log in again', function () {
    $student = User::factory()->student()->create(['suspended_at' => now()]);

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.users.unsuspend', $student->slug));

    auth()->logout();

    $this->post(route('login.store'), ['email' => $student->email, 'password' => 'password']);

    $this->assertAuthenticatedAs($student->fresh());
});

test('an admin cannot suspend themselves', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.users.suspend', $admin->slug));

    expect($admin->fresh()->isSuspended())->toBeFalse();
});

test('a user with blocked purchases cannot buy but keeps learning', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->published()->create(['price' => 150000]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.users.block-purchases', $student->slug))
        ->assertRedirect();

    expect($student->fresh()->isPurchaseBlocked())->toBeTrue();

    $this->actingAs($student->fresh())
        ->get(route('orders.checkout', $course->slug))
        ->assertRedirect(route('catalog.show', $course->permalinkParameters()));

    $this->actingAs($student->fresh())
        ->post(route('orders.store', $course->slug), ['payment_method' => Order::METHOD_BANK_TRANSFER])
        ->assertRedirect(route('catalog.show', $course->permalinkParameters()));

    expect($student->orders()->count())->toBe(0);

    $this->actingAs($student->fresh())->get(route('learning.dashboard'))->assertOk();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.users.unblock-purchases', $student->slug));

    expect($student->fresh()->isPurchaseBlocked())->toBeFalse();
});

test('an account with courses or paid orders is not deleted', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create();
    Course::factory()->ownedBy($instructor)->create();

    $buyer = User::factory()->student()->create();
    Order::factory()->for($buyer)->create(['status' => Order::STATUS_PAID]);

    $this->actingAs($admin)->delete(route('admin.users.destroy', $instructor->slug));
    $this->actingAs($admin)->delete(route('admin.users.destroy', $buyer->slug));

    expect($instructor->fresh())->not->toBeNull()
        ->and($buyer->fresh())->not->toBeNull();

    $fresh = User::factory()->student()->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $fresh->slug))
        ->assertRedirect(route('admin.users.index'));

    expect($fresh->fresh())->toBeNull();
});

test('the user list filters by restriction', function () {
    User::factory()->student()->create(['suspended_at' => now()]);
    User::factory()->student()->create(['purchase_blocked_at' => now()]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.users.index', ['status' => 'suspended']))
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 1)
            ->where('restrictionCounts.suspended', 1)
            ->where('restrictionCounts.purchase_blocked', 1)
        );
});
