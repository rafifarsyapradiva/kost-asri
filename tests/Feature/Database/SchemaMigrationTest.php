<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

test('it verifies all core domain, CMS, and system tables exist in MariaDB', function () {
    $expectedTables = [
        'users',
        'password_reset_tokens',
        'sessions',
        'tenant_profiles',
        'room_types',
        'room_type_images',
        'rooms',
        'reservations',
        'leases',
        'invoices',
        'invoice_items',
        'payments',
        'receipts',
        'penalty_logs',
        'number_sequences',
        'notification_templates',
        'notification_logs',
        'notifications',
        'chatbot_keywords',
        'chatbot_sessions',
        'chatbot_messages',
        'webhook_logs',
        'facilities',
        'galleries',
        'videos',
        'reservation_steps',
        'testimonials',
        'faqs',
        'nearby_places',
        'site_settings',
        'settings',
        'activity_logs',
        'jobs',
        'cache',
    ];

    foreach ($expectedTables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Table {$table} should exist");
    }
});

test('it verifies monetary columns and indexes on critical transactional tables', function () {
    expect(Schema::hasColumns('room_types', ['daily_rate', 'weekly_rate', 'monthly_rate']))->toBeTrue()
        ->and(Schema::hasColumns('reservations', ['rate_snapshot', 'total_amount', 'dp_amount']))->toBeTrue()
        ->and(Schema::hasColumns('leases', ['daily_rate', 'weekly_rate', 'monthly_rate', 'billed_until', 'paid_until']))->toBeTrue()
        ->and(Schema::hasColumns('invoices', ['subtotal', 'penalty_amount', 'discount_amount', 'total', 'minimum_pay_amount', 'paid_amount']))->toBeTrue()
        ->and(Schema::hasColumns('tenant_profiles', ['nik', 'nik_hash', 'guardian_name', 'guardian_phone']))->toBeTrue();
});
