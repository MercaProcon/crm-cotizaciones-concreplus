<?php

namespace Database\Seeders;

use App\Models\FacadeRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class FacadeRequestSeeder extends Seeder
{
    /**
     * Datos de prueba para validar el listado/detalle de Fachadas por rol.
     * No se llama desde DatabaseSeeder: correr manualmente con
     * `php artisan db:seed --class=FacadeRequestSeeder`.
     */
    public function run(): void
    {
        $agents = User::whereIn('email', [
            'leny@procon.com.mx',
            'monica@procon.com.mx',
            'elsy@procon.com.mx',
        ])->get()->keyBy('email');

        $this->createForAgent($agents->get('leny@procon.com.mx'), 3);
        $this->createForAgent($agents->get('monica@procon.com.mx'), 2);
        $this->createForAgent($agents->get('elsy@procon.com.mx'), 2);

        // Una solicitud sin agent_user_id, para probar el caso sin usuario ligado.
        FacadeRequest::factory()->create(['agent_user_id' => null]);
    }

    private function createForAgent(?User $agent, int $count): void
    {
        if (! $agent) {
            return;
        }

        FacadeRequest::factory()->count($count)->create([
            'agent_user_id' => $agent->id,
            'agent_name' => $agent->name,
            'agent_email' => $agent->email,
        ]);
    }
}
