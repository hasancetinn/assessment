<?php

namespace Database\Factories;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'idempotency_key' => $this->faker->uuid(),
            'recipient' => $this->faker->phoneNumber(),
            'channel' => $this->faker->randomElement(['sms', 'email', 'push']),
            'content' => $this->faker->sentence(),
            'priority' => $this->faker->randomElement(['high', 'normal', 'low']),
            'status' => 'pending',
        ];
    }
}
