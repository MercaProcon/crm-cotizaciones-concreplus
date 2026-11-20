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
        Schema::create('facade_requests', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();
            $table->string('status')->default('pendiente_autorizacion');
            $table->timestamp('submitted_at')->nullable();

            // Agente
            $table->foreignId('agent_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('agent_name');
            $table->string('agent_email');
            $table->string('agent_phone')->nullable();
            $table->string('zone')->nullable();

            // Cliente y tienda
            $table->string('store_name');
            $table->string('client_name');
            $table->string('client_phone');
            $table->string('manager_name');
            $table->string('manager_phone');
            $table->boolean('client_registered_tax')->nullable();

            // Ubicación de la tienda
            $table->string('street');
            $table->string('number');
            $table->string('cross_streets')->nullable();
            $table->string('neighborhood');
            $table->string('city');
            $table->string('municipality');
            $table->string('state');
            $table->string('postal_code')->nullable();
            $table->text('google_maps_url');

            // Características de la ubicación
            $table->boolean('is_corner')->nullable();
            $table->boolean('is_mid_block')->nullable();
            $table->boolean('is_end_of_street')->nullable();
            $table->boolean('near_existing_procon_facade')->nullable();
            $table->boolean('on_main_avenue')->nullable();
            $table->boolean('has_clear_visibility')->nullable();
            $table->text('location_notes')->nullable();

            // Relación PROCON - cliente
            $table->string('months_as_distributor');
            $table->string('estimated_monthly_consumption');
            $table->text('frequent_products');
            $table->text('additional_comments')->nullable();

            // Autorización (snapshot preparado; el flujo de aprobación/rechazo
            // y el token público llegan en una fase posterior)
            $table->string('authorizer_key');
            $table->string('authorizer_name');
            $table->string('authorizer_email');

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['agent_email', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facade_requests');
    }
};
