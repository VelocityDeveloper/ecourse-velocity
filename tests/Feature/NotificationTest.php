<?php

use App\Models\Course;
use App\Models\Order;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\Withdrawal;
use App\Notifications\CourseSold;
use App\Notifications\OrderCancelled;
use App\Notifications\OrderPaid;
use App\Notifications\OrderRejected;
use App\Notifications\PaymentProofSubmitted;
use App\Notifications\WithdrawalProcessed;
use App\Support\PaymentSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(Order::PROOF_DISK);

    SiteSetting::put(PaymentSettings::BANK_ACCOUNTS, json_encode([
        ['bank' => 'BCA', 'account_number' => '1234567890', 'account_name' => 'PT Velocity'],
    ]));
});

/**
 * @return array{0: User, 1: User, 2: Order}
 */
function orderWithProof(): array
{
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->published()->ownedBy($instructor)->create(['price' => 100000, 'title' => 'Figma Dasar']);
    $student = User::factory()->student()->create(['name' => 'Nadia']);

    test()->actingAs($student)->post(route('orders.store', $course), ['payment_method' => 'bank_transfer']);
    $order = Order::sole();

    test()->actingAs($student)->post(route('orders.proof.store', $order), [
        'bank_index' => 0,
        'payer_name' => 'Nadia',
        'proof' => UploadedFile::fake()->image('bukti.jpg'),
    ])->assertSessionHasNoErrors();

    return [$student, $instructor, $order->refresh()];
}

test('a payment proof reaches the bell of every active admin', function () {
    $admin = User::factory()->admin()->create();
    $suspended = User::factory()->admin()->create(['suspended_at' => now()]);

    [, , $order] = orderWithProof();

    expect($admin->notifications()->sole()->type)->toBe(PaymentProofSubmitted::class)
        ->and($admin->notifications()->sole()->data['url'])->toBe("/dasbor/keuangan/pesanan/{$order->number}")
        ->and($suspended->notifications()->count())->toBe(0);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('notifications.unread', 1)
            ->where('notifications.recent.0.title', 'Bukti bayar baru perlu dicek')
            ->where('notifications.recent.0.kind', 'payment')
        );
});

test('confirming tells the student and the instructor', function () {
    $admin = User::factory()->admin()->create();
    [$student, $instructor, $order] = orderWithProof();

    $this->actingAs($admin)->post(route('admin.orders.confirm', $order))->assertRedirect();

    expect($student->notifications()->sole()->type)->toBe(OrderPaid::class)
        ->and($instructor->notifications()->sole()->type)->toBe(CourseSold::class)
        ->and($instructor->notifications()->sole()->data['body'])->toContain('Rp 100.000');
});

test('rejecting and cancelling tell the student', function () {
    $admin = User::factory()->admin()->create();
    [$student, , $order] = orderWithProof();

    $this->actingAs($admin)->post(route('admin.orders.reject', $order), ['reason' => 'Nominal kurang']);

    $notification = $student->notifications()->sole();
    expect($notification->type)->toBe(OrderRejected::class)
        ->and($notification->data['body'])->toContain('Nominal kurang');

    $this->actingAs($admin)->post(route('admin.orders.cancel', $order));

    expect($student->notifications()->count())->toBe(2);
});

test('admins hear about instructor applications and withdrawal requests', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs(User::factory()->student()->create())->post(route('instructor-applications.store'), [
        'headline' => 'Web Developer',
        'expertise' => 'Laravel',
        'experience' => 'Lima tahun membangun aplikasi web dengan Laravel dan Vue.',
        'motivation' => 'Saya ingin membuat kursus Laravel dasar untuk pemula.',
        'phone' => '081234567890',
        'agreement' => true,
    ])->assertSessionHasNoErrors();

    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->published()->ownedBy($instructor)->create();
    $order = Order::factory()->create(['course_id' => $course->id, 'status' => Order::STATUS_PAID, 'total' => 200000, 'paid_at' => now()]);
    $order->transaction()->create([
        'user_id' => $order->user_id, 'amount' => 200000, 'commission_rate' => 0, 'commission_amount' => 0,
        'instructor_amount' => 200000, 'payment_method' => Order::METHOD_BANK_TRANSFER, 'paid_at' => now(),
    ]);

    $this->actingAs($instructor)->post(route('admin.withdrawals.store'), [
        'amount' => 100000, 'bank_name' => 'BCA', 'account_number' => '123', 'account_name' => 'Rizky',
    ])->assertSessionHasNoErrors();

    expect($admin->notifications()->pluck('data')->pluck('title')->all())
        ->toContain('Pengajuan instruktur baru')
        ->toContain('Permintaan penarikan Rp 100.000');
});

test('opening a notification marks it read and goes to its page', function () {
    $admin = User::factory()->admin()->create();
    [, , $order] = orderWithProof();
    $notification = $admin->notifications()->sole();

    $this->actingAs($admin)
        ->post(route('notifications.open', $notification->id))
        ->assertRedirect("/dasbor/keuangan/pesanan/{$order->number}");

    expect($notification->fresh()->read_at)->not->toBeNull();

    // Someone else's notification stays out of reach.
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('notifications.open', $notification->id))
        ->assertNotFound();
});

test('the notification page lists, filters and marks all read', function () {
    $student = User::factory()->student()->create();
    $student->notify(new OrderCancelled(Order::factory()->for($student)->create()));
    $student->notify(new OrderCancelled(Order::factory()->for($student)->create()));
    $student->notifications()->first()->markAsRead();

    $this->actingAs($student)
        ->get(route('notifications.index', ['filter' => 'unread']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('notifications/Index')
            ->has('items.data', 1)
            ->where('notifications.unread', 1)
            ->where('filter', 'unread')
        );

    $this->actingAs($student)->post(route('notifications.read-all'))->assertRedirect();

    expect($student->unreadNotifications()->count())->toBe(0);

    $this->get('/notifikasi')->assertOk();
    auth()->logout();
    $this->get('/notifikasi')->assertRedirect(route('login'));
});

test('guests have no bell', function () {
    $this->get(route('home'))->assertInertia(fn ($page) => $page->where('notifications', null));
});

test('a withdrawal notification points to the withdrawal page', function () {
    $withdrawal = Withdrawal::factory()->create(['status' => Withdrawal::STATUS_PAID]);
    $withdrawal->user->notify(new WithdrawalProcessed($withdrawal));

    expect($withdrawal->user->notifications()->sole()->data['url'])->toBe('/dasbor/keuangan/penarikan-dana');
});
