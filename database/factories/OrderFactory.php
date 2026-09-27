<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => fn (): string => Order::newNumber(),
            'user_id' => User::factory()->student(),
            'course_id' => Course::factory(),
            'course_title' => fake()->sentence(3),
            'price' => 150000,
            'total' => 150000,
            'payment_method' => Order::METHOD_BANK_TRANSFER,
            'status' => Order::STATUS_PENDING,
            'expires_at' => now()->addDay(),
        ];
    }

    /**
     * Indicate that the student has uploaded a payment proof.
     */
    public function awaitingConfirmation(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Order::STATUS_AWAITING_CONFIRMATION,
            'payment_details' => ['bank' => 'BCA', 'account_number' => '1234567890', 'account_name' => 'PT Contoh'],
            'proof_path' => 'payment-proofs/proof.jpg',
            'payer_name' => 'Nadia Putri',
            'proof_uploaded_at' => now(),
        ]);
    }
}
