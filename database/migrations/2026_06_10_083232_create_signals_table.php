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
        Schema::create('signals', function (Blueprint $table) {
            $table->id();
            $table->string('action', 10);
            $table->string('symbol', 20);
            $table->decimal('price', 20, 8)->nullable();
            $table->decimal('volume', 20, 8);
            $table->decimal('stop_loss_pct', 8, 4)->nullable();
            $table->decimal('take_profit_pct', 8, 4)->nullable();
            $table->enum('status', [
                'pending',
                'processing',
                'executed',
                'rejected_by_ai',
                'rejected_emergency',
                'failed',
            ])->default('pending');
            $table->string('ai_decision', 20)->nullable();
            $table->text('ai_reason')->nullable();
            $table->string('source_ip', 45)->nullable();
            $table->json('raw_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['symbol', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signals');
    }
};
