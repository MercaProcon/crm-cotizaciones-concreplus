<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 15 (Concreplus): el modulo de fachadas (heredado de PROCON) no
     * aplica para Concreplus. Nunca tuvo permisos asignados ni registros
     * reales (facade_requests en 0 antes de esta migracion) -- se elimina
     * el esquema junto con el codigo de la aplicacion.
     */
    public function up(): void
    {
        Schema::dropIfExists('facade_request_photos');
        Schema::dropIfExists('facade_request_events');
        Schema::dropIfExists('facade_requests');
    }

    public function down(): void
    {
        // Intencionalmente sin reversa: el modulo se elimino del codigo de
        // la aplicacion en el mismo cambio, recrear solo las tablas vacias
        // no tendria utilidad.
    }
};
