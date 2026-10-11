<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Session>
 */
class SessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jti' => fake()->unique()->uuid(),
            'user_id' => fake()->unique()->randomNumber(2),
            'issued_at' => fake()->dateTimeBetween('-1 hour', 'now'),
            'expires_at' => fake()->dateTimeBetween('now', '+5 hour'),
            'revoked_at' => fake()->boolean() ? fake()->dateTimeBetween('-1 hour', 'now') : null,
        ];
    }
}
