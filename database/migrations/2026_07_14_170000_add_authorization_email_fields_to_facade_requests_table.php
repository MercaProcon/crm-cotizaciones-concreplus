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
            $table->string('authorization_token')->nullable()->unique()->after('pdf_path');
            $table->timestamp('authorization_token_expires_at')->nullable()->after('authorization_token');
            $table->timestamp('authorization_email_sent_at')->nullable()->after('authorization_token_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facade_requests', function (Blueprint $table) {
            $table->dropColumn([
                'authorization_token',
                'authorization_token_expires_at',
                'authorization_email_sent_at',
            ]);
        });
    }
};
