<?php

namespace Database\Factories;

use App\Models\Quotation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quotation>
 */
class QuotationFactory extends Factory
{
    protected static array $zoneCodes = [
        'Yucatán' => 'YUC',
        'Quintana Roo' => 'QROO',
        'Campeche' => 'CAM',
        'Tabasco' => 'TAB',
    ];

    protected static array $requests = [
        'Block de 12, polvo, grava y cal',
        'Cemento gris 50kg y varilla 3/8',
        'Lámina galvanizada y clavos',
        'Arena, grava y cemento para losa',
        'Tinaco de 1100 litros',
        'Pintura vinílica y sellador',
        'Tubería PVC para drenaje',
        'Malla electrosoldada y alambre recocido',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $zoneName = fake()->randomElement(array_keys(self::$zoneCodes));
        $zoneCode = self::$zoneCodes[$zoneName];
        $registeredAt = fake()->dateTimeBetween('-30 days', 'now');

        $phoneDigits = '521'.fake()->numerify('##########');

        return [
            'folio' => sprintf(
                '%s-%s-%06d',
                $zoneCode,
                $registeredAt->format('Ymd'),
                fake()->unique()->numberBetween(1, 999999)
            ),
            'source_sheet' => null,
            'source_row' => null,
            'source_hash' => fake()->unique()->sha256(),
            'imported_at' => null,
            'sheet_assigned_to' => fake()->optional(0.6)->name(),
            'registered_at' => $registeredAt,
            'zone' => $zoneName,
            'client_name' => fake()->name(),
            'phone_raw' => $phoneDigits,
            'phone_normalized' => $phoneDigits,
            'description' => fake()->randomElement(self::$requests),
            'sheet_status' => fake()->optional(0.7)->randomElement(['Nuevo', 'En proceso', 'Cerrado', 'Sin respuesta']),
            'location_link' => fake()->optional(0.3)->url(),
            'quote_sent_status' => fake()->optional(0.6)->randomElement(['Sí', 'No']),
            'quote_number' => fake()->optional(0.5)->bothify('COT-####'),
            'sale_closed' => fake()->optional(0.4)->randomElement(['Sí', 'No']),
            'return_status' => fake()->optional(0.2)->randomElement(['Sí', 'No']),
            'sheet_comments' => fake()->optional(0.3)->sentence(),
            'extra_column_1' => null,
            'crm_status' => fake()->randomElement(Quotation::STATUSES),
        ];
    }

    /**
     * Force a specific normalized phone (and a raw variant of it), useful to seed duplicates on purpose.
     */
    public function withPhone(string $normalizedPhone): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_raw' => fake()->randomElement([
                $normalizedPhone,
                '+'.$normalizedPhone,
                preg_replace('/^52(1)?(\d{3})(\d{3})(\d{4})$/', '$2 $3 $4', $normalizedPhone),
            ]),
            'phone_normalized' => $normalizedPhone,
        ]);
    }
}
