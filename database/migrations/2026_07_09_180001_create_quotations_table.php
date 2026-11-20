<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();

            // Identificador interno del CRM
            $table->string('folio')->unique();

            // Trazabilidad de importación futura desde Google Sheets
            $table->string('source_sheet')->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->string('source_hash')->nullable()->unique();
            $table->timestamp('imported_at')->nullable();

            // Columna A - Cotizacion asignada a: (valor original de la hoja, no es la asignación oficial del CRM)
            $table->string('sheet_assigned_to')->nullable();

            // Columna B - Fecha
            $table->dateTime('registered_at');

            // Zona / pestaña de origen
            $table->string('zone');

            // Columna C - Nombre Cliente
            $table->string('client_name');

            // Columna D - Numero de telefono
            $table->string('phone_raw');
            $table->string('phone_normalized')->index();

            // Columna E - Cotizacion
            $table->text('description')->nullable();

            // Columna F - Estado de la cotizacion (texto original de la hoja)
            $table->string('sheet_status')->nullable();

            // Columna G - Ubicacion
            $table->string('location_link')->nullable();

            // Columna H - ¿Cotizacion enviada?
            $table->string('quote_sent_status')->nullable();

            // Columna I - No. de cotización
            $table->string('quote_number')->nullable();

            // Columna J - Ventas concluida
            $table->string('sale_closed')->nullable();

            // Columna K - Retorno
            $table->string('return_status')->nullable();

            // Columna L - Comentarios
            $table->text('sheet_comments')->nullable();

            // Columna M - Columna 1 (uso todavía no definido por PROCON)
            $table->string('extra_column_1')->nullable();

            // Estado operativo del CRM, independiente del estado de la hoja
            $table->string('crm_status')->default('nueva')->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
