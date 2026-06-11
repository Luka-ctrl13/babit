<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Parse Railway/Render DATABASE_URL → DB_* env vars automatically
        if ($url = env('DATABASE_URL')) {
            $parsed = parse_url($url);
            config([
                'database.default'                         => 'pgsql',
                'database.connections.pgsql.host'          => $parsed['host'] ?? '127.0.0.1',
                'database.connections.pgsql.port'          => $parsed['port'] ?? 5432,
                'database.connections.pgsql.database'      => ltrim($parsed['path'] ?? 'trading', '/'),
                'database.connections.pgsql.username'      => $parsed['user'] ?? '',
                'database.connections.pgsql.password'      => $parsed['pass'] ?? '',
                'database.connections.pgsql.sslmode'       => 'require',
            ]);
        }

        // Parse REDIS_URL → Redis config
        if ($url = env('REDIS_URL')) {
            $parsed = parse_url($url);
            config([
                'database.redis.default.host'     => $parsed['host'] ?? '127.0.0.1',
                'database.redis.default.port'     => $parsed['port'] ?? 6379,
                'database.redis.default.password' => $parsed['pass'] ?? null,
            ]);
        }
    }
}
