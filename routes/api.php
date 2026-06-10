<?php

use App\Http\Controllers\Api\WebhookController;
use App\Http\Middleware\VerifyWebhookPassphrase;
use Illuminate\Support\Facades\Route;

Route::post(
    '/v1/tv-hook-' . env('WEBHOOK_URL_SUFFIX', 'xyz987'),
    [WebhookController::class, 'receive']
)->middleware(VerifyWebhookPassphrase::class);
