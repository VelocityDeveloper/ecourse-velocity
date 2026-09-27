<?php

namespace App\Actions\Orders;

use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Support\PaymentSettings;

class PlaceOrder
{
    /**
     * Create the invoice for the course with the chosen payment method, or
     * return the one the student already has open for it.
     */
    public function __invoke(User $student, Course $course, string $paymentMethod): Order
    {
        Order::expireOverdue();

        $open = $student->orders()->open()->where('course_id', $course->id)->latest('id')->first();

        if ($open !== null) {
            return $open;
        }

        $price = (int) round((float) $course->price);

        return $student->orders()->create([
            'number' => Order::newNumber(),
            'course_id' => $course->id,
            'course_title' => $course->title,
            'price' => $price,
            'total' => $price,
            'status' => Order::STATUS_PENDING,
            'payment_method' => $paymentMethod,
            'expires_at' => now()->addHours(PaymentSettings::expiryHours()),
        ]);
    }
}
