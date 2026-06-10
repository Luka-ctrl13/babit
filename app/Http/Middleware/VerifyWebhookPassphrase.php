<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookPassphrase
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $passphrase = $request->input('passphrase');
        $expected   = config('trading.webhook_passphrase');

        if (empty($expected) || !hash_equals($expected, (string) $passphrase)) {
            \Illuminate\Support\Facades\Log::warning('Webhook auth failure', [
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
