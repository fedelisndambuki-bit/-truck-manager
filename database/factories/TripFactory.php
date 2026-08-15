<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Truck;
use App\Enums\TripStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class TripFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'truck_id' => Truck::factory(),
            'employee_id' => Employee::factory(),
            'origin' => $this->faker->city(),
            'destination' => $this->faker->city(),
            'departure_time' => now()->addDay(),
            'expected_arrival' => now()->addDays(2),
            'status' => TripStatus::Scheduled,
            'cargo_type' => 'General',
            'cargo_weight' => 10,
            'route_name' => 'Test Route',
            'client_name' => 'Test Client',
        ];
    }
}
