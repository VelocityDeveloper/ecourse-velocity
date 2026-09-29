<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Withdrawal>
 */
class WithdrawalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->instructor(),
            'amount' => 100000,
            'bank_name' => 'BCA',
            'account_number' => fake()->numerify('##########'),
            'account_name' => fake()->name(),
            'status' => Withdrawal::STATUS_PENDING,
        ];
    }
}
