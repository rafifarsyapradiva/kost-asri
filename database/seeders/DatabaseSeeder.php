<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database idempotently (DB-032).
     */
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            RoomTypeSeeder::class,
            RoomSeeder::class,
            AdminSeeder::class,
            NotificationTemplateSeeder::class,
            ChatbotKeywordSeeder::class,
            CmsSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call([
                DemoTransactionSeeder::class,
            ]);
        }
    }
}
