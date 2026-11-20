<?php

namespace Database\Factories;

use App\Models\FacadeRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FacadeRequest>
 */
class FacadeRequestFactory extends Factory
{
    protected $model = FacadeRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'folio' => 'FCH-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'status' => FacadeRequest::STATUS_CAPTURADA,
            'submitted_at' => now(),

            'agent_user_id' => null,
            'agent_name' => fake()->name(),
            'agent_email' => fake()->safeEmail(),
            'agent_phone' => fake()->numerify('##########'),
            'zone' => fake()->randomElement(['Norte', 'Sur', 'Centro', 'Oriente', 'Poniente']),

            'store_name' => 'Tienda '.fake()->company(),
            'client_name' => fake()->name(),
            'client_phone' => fake()->numerify('##########'),
            'manager_name' => fake()->name(),
            'manager_phone' => fake()->numerify('##########'),
            'client_registered_tax' => fake()->boolean(),

            'street' => fake()->streetName(),
            'number' => fake()->buildingNumber(),
            'cross_streets' => fake()->streetName(),
            'neighborhood' => fake()->citySuffix(),
            'city' => fake()->city(),
            'municipality' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'google_maps_url' => 'https://maps.google.com/?q='.fake()->latitude().','.fake()->longitude(),

            'is_corner' => fake()->boolean(),
            'is_mid_block' => fake()->boolean(),
            'is_end_of_street' => fake()->boolean(),
            'near_existing_procon_facade' => fake()->boolean(),
            'on_main_avenue' => fake()->boolean(),
            'has_clear_visibility' => fake()->boolean(),
            'location_notes' => fake()->optional()->sentence(),

            'months_as_distributor' => (string) fake()->numberBetween(1, 60),
            'estimated_monthly_consumption' => fake()->randomElement(['$5,000 - $10,000', '$10,000 - $20,000', '$20,000+']),
            'frequent_products' => fake()->sentence(),
            'additional_comments' => fake()->optional()->paragraph(),

            'authorizer_key' => fake()->randomElement(['adrian_preciado', 'pastor_martinez', 'mario_cervera']),
            'authorizer_name' => fake()->name(),
            'authorizer_email' => fake()->safeEmail(),
        ];
    }
}
