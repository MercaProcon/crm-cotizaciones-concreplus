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
            $table->text('authorization_comment')->nullable()->after('authorization_email_sent_at');
            $table->timestamp('authorized_at')->nullable()->after('authorization_comment');
            $table->timestamp('rejected_at')->nullable()->after('authorized_at');
            $table->timestamp('authorization_token_used_at')->nullable()->after('rejected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facade_requests', function (Blueprint $table) {
            $table->dropColumn([
                'authorization_comment',
                'authorized_at',
                'rejected_at',
                'authorization_token_used_at',
            ]);
        });
    }
};
