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
        Schema::table('facade_requests', function (Blueprint $table) {
            $table->string('client_signature_path')->nullable()->after('additional_comments');
            $table->string('client_signature_name')->nullable()->after('client_signature_path');
            $table->string('agent_signature_path')->nullable()->after('client_signature_name');
            $table->string('agent_signature_name')->nullable()->after('agent_signature_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facade_requests', function (Blueprint $table) {
            $table->dropColumn([
                'client_signature_path',
                'client_signature_name',
                'agent_signature_path',
                'agent_signature_name',
            ]);
        });
    }
};
