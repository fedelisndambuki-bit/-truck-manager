<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CargoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'client_id' => Client::factory(),
            'description' => $this->faker->sentence(),
            'cargo_type' => $this->faker->word(),
            'weight' => $this->faker->randomFloat(2, 1, 30),
        ];
    }
}
