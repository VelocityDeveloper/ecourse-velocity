<?php

namespace App\Actions\Orders;

use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConfirmOrder
{
    /**
     * Mark the order paid, record the payment and enroll the student.
     */
    public function __invoke(Order $order, User $admin, ?string $note = null): Transaction
    {
        return DB::transaction(function () use ($order, $admin, $note): Transaction {
            $order->update([
                'status' => Order::STATUS_PAID,
                'paid_at' => now(),
                'handled_by' => $admin->id,
                'rejection_reason' => null,
            ]);

            $transaction = $order->transaction()->create([
                'user_id' => $order->user_id,
                'amount' => $order->total,
                'payment_method' => $order->payment_method ?? Order::METHOD_BANK_TRANSFER,
                'payment_details' => $order->payment_details,
                'confirmed_by' => $admin->id,
                'note' => $note,
                'paid_at' => now(),
            ]);

            if ($order->course !== null) {
                $alreadyEnrolled = $order->course->enrollments()->active()->where('user_id', $order->user_id)->exists();

                if (! $alreadyEnrolled) {
                    Enrollment::enroll($order->user, $order->course);
                }
            }

            return $transaction;
        });
    }
}
