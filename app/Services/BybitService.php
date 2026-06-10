<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BybitService
{
    private string $apiKey;
    private string $apiSecret;
    private string $baseUrl;
    private int $recvWindow = 5000;

    public function __construct()
    {
        $this->apiKey    = config('bybit.api_key', '');
        $this->apiSecret = config('bybit.api_secret', '');
        $this->baseUrl   = config('bybit.testnet', true)
            ? 'https://api-testnet.bybit.com'
            : 'https://api.bybit.com';
    }

    public function placeOrder(array $params): array
    {
        $endpoint  = '/v5/order/create';
        $timestamp = now()->valueOf();
        $orderLinkId = Str::uuid()->toString();

        $body = array_merge([
            'orderLinkId' => $orderLinkId,
            'orderType'   => 'Market',
            'category'    => 'linear',
            'timeInForce' => 'IOC',
        ], $params);

        $bodyJson  = json_encode($body);
        $signature = $this->sign($timestamp, $bodyJson);

        $response = Http::withHeaders([
            'X-BAPI-API-KEY'    => $this->apiKey,
            'X-BAPI-SIGN'       => $signature,
            'X-BAPI-TIMESTAMP'  => (string) $timestamp,
            'X-BAPI-RECV-WINDOW' => (string) $this->recvWindow,
            'Content-Type'      => 'application/json',
        ])->post($this->baseUrl . $endpoint, $body);

        $data = $response->json();

        Log::channel('trading')->info('Bybit order response', [
            'endpoint' => $endpoint,
            'body'     => $body,
            'response' => $data,
        ]);

        if (!$response->successful() || ($data['retCode'] ?? -1) !== 0) {
            throw new \RuntimeException(
                'Bybit API error: ' . ($data['retMsg'] ?? 'Unknown error')
            );
        }

        return $data['result'] ?? [];
    }

    private function sign(int $timestamp, string $payload): string
    {
        $str = $timestamp . $this->apiKey . $this->recvWindow . $payload;
        return hash_hmac('sha256', $str, $this->apiSecret);
    }

    public function getWalletBalance(string $accountType = 'UNIFIED'): array
    {
        $endpoint  = '/v5/account/wallet-balance';
        $timestamp = now()->valueOf();
        $query     = "accountType={$accountType}";
        $signature = $this->sign($timestamp, $query);

        $response = Http::withHeaders([
            'X-BAPI-API-KEY'     => $this->apiKey,
            'X-BAPI-SIGN'        => $signature,
            'X-BAPI-TIMESTAMP'   => (string) $timestamp,
            'X-BAPI-RECV-WINDOW' => (string) $this->recvWindow,
        ])->get($this->baseUrl . $endpoint, ['accountType' => $accountType]);

        return $response->json()['result'] ?? [];
    }
}
