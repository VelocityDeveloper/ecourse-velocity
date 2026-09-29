<?php

use App\Models\Course;
use App\Models\Order;
use App\Models\SiteSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdrawal;
use App\Notifications\WithdrawalProcessed;
use App\Support\InstructorBalance;
use App\Support\PaymentSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Give the instructor a confirmed sale worth $amount, of which they keep 90%.
 */
function earn(User $instructor, int $amount): void
{
    $course = Course::factory()->ownedBy($instructor)->create();
    $order = Order::factory()->for($course)->create(['total' => $amount, 'price' => $amount, 'status' => Order::STATUS_PAID]);

    Transaction::query()->create([
        'order_id' => $order->id,
        'user_id' => $order->user_id,
        'amount' => $amount,
        ...Transaction::split($amount, 10),
        'payment_method' => Order::METHOD_BANK_TRANSFER,
        'paid_at' => now(),
    ]);
}

function payoutInput(array $overrides = []): array
{
    return [
        'amount' => 100000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_name' => 'Sari Wulandari',
        ...$overrides,
    ];
}

test('an instructor sees their balance after commission', function () {
    $instructor = User::factory()->instructor()->create();
    earn($instructor, 200000);
    Withdrawal::factory()->for($instructor)->create(['amount' => 50000, 'status' => Withdrawal::STATUS_PAID]);
    Withdrawal::factory()->for($instructor)->create(['amount' => 30000]);
    Withdrawal::factory()->for($instructor)->create(['amount' => 99999, 'status' => Withdrawal::STATUS_REJECTED]);

    $this->actingAs($instructor)
        ->get(route('admin.withdrawals.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('withdrawals/Index')
            ->where('balance.earned', 180000)
            ->where('balance.paid_out', 50000)
            ->where('balance.pending', 30000)
            ->where('balance.available', 100000)
            ->where('hasPending', true)
            ->has('withdrawals.data', 3)
        );
});

test('an instructor requests a payout within the available balance', function () {
    $instructor = User::factory()->instructor()->create();
    earn($instructor, 200000);

    $this->actingAs($instructor)
        ->post(route('admin.withdrawals.store'), payoutInput(['amount' => 200000]))
        ->assertSessionHasErrors('amount');

    $this->actingAs($instructor)
        ->post(route('admin.withdrawals.store'), payoutInput(['amount' => 10000]))
        ->assertSessionHasErrors('amount');

    $this->actingAs($instructor)
        ->post(route('admin.withdrawals.store'), payoutInput(['amount' => 180000]))
        ->assertRedirect(route('admin.withdrawals.index'));

    expect(InstructorBalance::for($instructor)->available())->toBe(0);

    // One request at a time.
    earn($instructor, 200000);
    $this->actingAs($instructor)
        ->post(route('admin.withdrawals.store'), payoutInput())
        ->assertSessionHasErrors('amount');

    expect($instructor->withdrawals()->count())->toBe(1);
});

test('the admin sets the minimum payout', function () {
    SiteSetting::put(PaymentSettings::WITHDRAWAL_MINIMUM, '150000');
    $instructor = User::factory()->instructor()->create();
    earn($instructor, 200000);

    $this->actingAs($instructor)
        ->post(route('admin.withdrawals.store'), payoutInput(['amount' => 100000]))
        ->assertSessionHasErrors('amount');
});

test('students and admins cannot request payouts', function () {
    $this->actingAs(User::factory()->student()->create())
        ->post(route('admin.withdrawals.store'), payoutInput())
        ->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.withdrawals.store'), payoutInput())
        ->assertForbidden();
});

test('an instructor can cancel only their own pending request', function () {
    $withdrawal = Withdrawal::factory()->create();

    $this->actingAs(User::factory()->instructor()->create())
        ->post(route('admin.withdrawals.cancel', $withdrawal))
        ->assertForbidden();

    $this->actingAs($withdrawal->user)
        ->post(route('admin.withdrawals.cancel', $withdrawal));

    expect($withdrawal->fresh()->status)->toBe(Withdrawal::STATUS_CANCELLED);
});

test('the admin marks a payout as transferred with a receipt', function () {
    Notification::fake();
    Storage::fake('local');

    $withdrawal = Withdrawal::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($withdrawal->user)
        ->post(route('admin.withdrawals.pay', $withdrawal))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('admin.withdrawals.index'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/Withdrawals/Index')
            ->has('withdrawals.data', 1)
            ->where('summary.pending_amount', 100000)
            ->where('pendingWithdrawals', 1)
        );

    $this->actingAs($admin)
        ->post(route('admin.withdrawals.pay', $withdrawal), [
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
            'note' => 'Ref 123',
        ])
        ->assertRedirect();

    $withdrawal->refresh();
    expect($withdrawal->status)->toBe(Withdrawal::STATUS_PAID)
        ->and($withdrawal->processed_by)->toBe($admin->id)
        ->and($withdrawal->proof_path)->not->toBeNull();

    Storage::disk('local')->assertExists($withdrawal->proof_path);
    Notification::assertSentTo($withdrawal->user, WithdrawalProcessed::class);

    $this->actingAs($withdrawal->user)->get(route('admin.withdrawals.proof', $withdrawal))->assertOk();
    $this->actingAs(User::factory()->instructor()->create())->get(route('admin.withdrawals.proof', $withdrawal))->assertForbidden();
});

test('a rejected payout needs a reason and returns to the balance', function () {
    Notification::fake();

    $instructor = User::factory()->instructor()->create();
    earn($instructor, 200000);
    $withdrawal = Withdrawal::factory()->for($instructor)->create(['amount' => 180000]);
    $admin = User::factory()->admin()->create();

    expect(InstructorBalance::for($instructor)->available())->toBe(0);

    $this->actingAs($admin)
        ->post(route('admin.withdrawals.reject', $withdrawal))
        ->assertSessionHasErrors('note');

    $this->actingAs($admin)
        ->post(route('admin.withdrawals.reject', $withdrawal), ['note' => 'Rekening tidak sesuai']);

    expect($withdrawal->fresh()->status)->toBe(Withdrawal::STATUS_REJECTED)
        ->and(InstructorBalance::for($instructor)->available())->toBe(180000);

    // A processed request cannot be processed again.
    $this->actingAs($admin)->post(route('admin.withdrawals.pay', $withdrawal));

    expect($withdrawal->fresh()->status)->toBe(Withdrawal::STATUS_REJECTED);
});
