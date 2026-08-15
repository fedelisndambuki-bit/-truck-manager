<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'full_name' => $this->faker->name(),
            'phone_number' => $this->faker->unique()->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'national_id' => $this->faker->unique()->numerify('########'),
            'license_number' => strtoupper($this->faker->unique()->bothify('DL-#######')),
            'license_expiry' => $this->faker->dateTimeBetween('+1 year', '+5 years'),
            'category' => 'Local',
            'status' => 'Active',
            'date_hired' => $this->faker->date(),
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_phone' => $this->faker->phoneNumber(),
        ];
    }
}
