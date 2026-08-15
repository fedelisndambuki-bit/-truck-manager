<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\Client;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Trip;
use App\Models\Truck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_populates_route_name_from_origin_and_destination()
    {
        $trip = Trip::factory()->create([
            'origin' => 'Nairobi',
            'destination' => 'Mombasa',
            'route_name' => '', // Should be auto-filled
        ]);

        $this->assertEquals('Nairobi -> Mombasa', $trip->route_name);
    }

    public function test_it_populates_cargo_details_from_cargo_id()
    {
        $company = Company::factory()->create();
        $cargo = Cargo::factory()->create([
            'company_id' => $company->id,
            'cargo_type' => 'Electronic',
            'weight' => 5.5,
        ]);

        $trip = Trip::factory()->create([
            'company_id' => $company->id,
            'cargo_id' => $cargo->id,
            'cargo_type' => '',
            'cargo_weight' => 0,
        ]);

        $this->assertEquals('Electronic', $trip->cargo_type);
        $this->assertEquals(5.5, $trip->cargo_weight);
    }

    public function test_it_populates_client_details_from_client_id()
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'company_name' => 'Acme Corp',
            'contact_person' => 'John Doe',
        ]);

        $trip = Trip::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'client_name' => '',
            'client_contact' => '',
        ]);

        $this->assertEquals('Acme Corp', $trip->client_name);
        $this->assertEquals('John Doe', $trip->client_contact);
    }
}
