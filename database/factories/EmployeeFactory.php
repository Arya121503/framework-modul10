<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Position;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'firstname' => fake() -> firstName(),
            'lastname' => fake() -> lastName(),
            'email' => fake() -> email(),
            'age' => fake() -> numberBetween(20, 50),
            'position_id' => Position::factory(),
        ];
    }
}
