<?php

namespace Database\Factories;

use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'request_number' => 'IZN-'.now()->year.'-'.fake()->unique()->numerify('######'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '6281234567890',
            'department' => 'General Solusindo',
            'position' => 'Sales',
            'type' => 'leave',
            'leave_date' => today()->addDays(7),
            'duration' => 1,
            'reason' => 'Keperluan keluarga',
            'status' => 'pending',
        ];
    }
}
