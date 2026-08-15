<?php

namespace Database\Factories;

use App\Models\Company;
use App\Enums\TruckStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class TruckFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'plate_number' => strtoupper($this->faker->bothify('K?? ###?')),
            'truck_type' => $this->faker->randomElement(['Trailer', 'Lorry', 'Pickup']),
            'status' => TruckStatus::Available,
            'current_mileage' => $this->faker->numberBetween(1000, 500000),
        ];
    }
}
