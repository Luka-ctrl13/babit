<?php

namespace Tests\Feature;

use App\Jobs\ProcessTradingSignal;
use App\Models\Signal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookUrl;

    protected function setUp(): void
    {
        parent::setUp();
        config(['trading.webhook_passphrase' => 'test-secret']);
        $this->webhookUrl = '/api/v1/tv-hook-' . env('WEBHOOK_URL_SUFFIX', 'xyz987');
    }

    public function test_webhook_rejects_missing_passphrase(): void
    {
        $response = $this->postJson($this->webhookUrl, [
            'action' => 'buy',
            'symbol' => 'BTCUSDT',
            'volume' => 0.01,
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_rejects_wrong_passphrase(): void
    {
        $response = $this->postJson($this->webhookUrl, [
            'passphrase' => 'wrong-secret',
            'action'     => 'buy',
            'symbol'     => 'BTCUSDT',
            'volume'     => 0.01,
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_accepts_valid_signal_and_queues_job(): void
    {
        Queue::fake();

        $response = $this->postJson($this->webhookUrl, [
            'passphrase'      => 'test-secret',
            'action'          => 'buy',
            'symbol'          => 'BTCUSDT',
            'price'           => 64500,
            'volume'          => 0.01,
            'stop_loss_pct'   => 2,
            'take_profit_pct' => 4,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'signal_id']);

        $this->assertDatabaseHas('signals', [
            'action' => 'buy',
            'symbol' => 'BTCUSDT',
            'status' => 'pending',
        ]);

        Queue::assertPushedOn('trading', ProcessTradingSignal::class);
    }

    public function test_webhook_rejects_invalid_action(): void
    {
        $response = $this->postJson($this->webhookUrl, [
            'passphrase' => 'test-secret',
            'action'     => 'hold',
            'symbol'     => 'BTCUSDT',
            'volume'     => 0.01,
        ]);

        $response->assertStatus(422);
    }

    public function test_paper_trade_job_creates_trade_and_sets_executed(): void
    {
        config([
            'trading.webhook_passphrase' => 'test-secret',
            'queue.default'              => 'sync',
        ]);

        \App\Models\Setting::updateOrCreate(
            ['key' => 'paper_trade'],
            ['value' => '1', 'type' => 'boolean']
        );
        \App\Models\Setting::updateOrCreate(
            ['key' => 'emergency_stop'],
            ['value' => '0', 'type' => 'boolean']
        );
        \App\Models\Setting::updateOrCreate(
            ['key' => 'ai_filter_enabled'],
            ['value' => '0', 'type' => 'boolean']
        );

        $this->mock(\App\Services\TelegramService::class)
            ->shouldReceive('notifyTradeExecuted')->once();

        $this->postJson($this->webhookUrl, [
            'passphrase'      => 'test-secret',
            'action'          => 'buy',
            'symbol'          => 'BTCUSDT',
            'price'           => 64500,
            'volume'          => 0.01,
            'stop_loss_pct'   => 2,
            'take_profit_pct' => 4,
        ]);

        $this->assertDatabaseHas('signals', ['status' => 'executed']);
        $this->assertDatabaseHas('trades', [
            'symbol'        => 'BTCUSDT',
            'side'          => 'Buy',
            'is_paper_trade'=> 1,
        ]);
    }

    public function test_emergency_stop_rejects_signal(): void
    {
        config(['queue.default' => 'sync']);

        \App\Models\Setting::updateOrCreate(
            ['key' => 'emergency_stop'],
            ['value' => '1', 'type' => 'boolean']
        );

        $this->mock(\App\Services\TelegramService::class)
            ->shouldReceive('notifyEmergencyStop')->once();

        $this->postJson($this->webhookUrl, [
            'passphrase'      => 'test-secret',
            'action'          => 'buy',
            'symbol'          => 'ETHUSDT',
            'price'           => 3000,
            'volume'          => 0.1,
            'stop_loss_pct'   => 2,
            'take_profit_pct' => 4,
        ]);

        $this->assertDatabaseHas('signals', ['status' => 'rejected_emergency']);
    }
}
