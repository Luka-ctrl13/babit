<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default admin user — change password after first login!
        User::factory()->create([
            'name'     => 'Admin',
            'email'    => 'admin@trading.local',
            'password' => bcrypt('change_me_immediately'),
        ]);

        $defaults = [
            ['key' => 'emergency_stop',    'value' => '0', 'type' => 'boolean', 'description' => 'Stop all trading immediately'],
            ['key' => 'ai_filter_enabled', 'value' => '0', 'type' => 'boolean', 'description' => 'Enable GPT AI risk filter'],
            ['key' => 'paper_trade',       'value' => '1', 'type' => 'boolean', 'description' => 'Paper trading — no real orders'],
        ];

        foreach ($defaults as $setting) {
            \App\Models\Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
