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
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signal_id')->constrained()->cascadeOnDelete();
            $table->string('bybit_order_id', 100)->nullable();
            $table->string('bybit_order_link_id', 100)->nullable();
            $table->string('symbol', 20);
            $table->string('side', 10); // Buy / Sell
            $table->string('order_type', 20)->default('Market');
            $table->decimal('qty', 20, 8);
            $table->decimal('price', 20, 8)->nullable();
            $table->decimal('stop_loss', 20, 8)->nullable();
            $table->decimal('take_profit', 20, 8)->nullable();
            $table->string('status', 30)->nullable(); // Bybit order status
            $table->json('bybit_response')->nullable();
            $table->boolean('is_paper_trade')->default(false);
            $table->timestamps();

            $table->index(['symbol', 'created_at']);
            $table->index('bybit_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
