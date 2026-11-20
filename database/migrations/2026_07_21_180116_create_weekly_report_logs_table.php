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
        Schema::create('weekly_report_logs', function (Blueprint $table) {
            $table->id();
            $table->string('report_type')->default('weekly_commercial')->index();
            $table->date('period_start');
            $table->date('period_end');
            $table->text('recipients')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('status'); // sent | failed | skipped_no_recipients
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['report_type', 'period_start', 'period_end'], 'weekly_report_logs_period_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_report_logs');
    }
};
