<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'dp_percent',
                'value' => '30',
                'type' => 'int',
                'group' => 'reservation',
                'description' => 'Minimal DP (%) dari total tagihan reservasi',
            ],
            [
                'key' => 'penalty_percent',
                'value' => '10',
                'type' => 'int',
                'group' => 'billing',
                'description' => 'Denda (%) dari pokok sewa invoice',
            ],
            [
                'key' => 'monthly_issue_day',
                'value' => '1',
                'type' => 'int',
                'group' => 'billing',
                'description' => 'Tanggal terbit tagihan bulanan',
            ],
            [
                'key' => 'monthly_due_day',
                'value' => '10',
                'type' => 'int',
                'group' => 'billing',
                'description' => 'Tanggal jatuh tempo tagihan bulanan',
            ],
            [
                'key' => 'weekly_invoice_days_before',
                'value' => '2',
                'type' => 'int',
                'group' => 'billing',
                'description' => 'Invoice perpanjangan mingguan terbit H-2',
            ],
            [
                'key' => 'weekly_tolerance_days',
                'value' => '1',
                'type' => 'int',
                'group' => 'billing',
                'description' => 'Toleransi keterlambatan mingguan H+1',
            ],
            [
                'key' => 'booking_hold_hours',
                'value' => '24',
                'type' => 'int',
                'group' => 'reservation',
                'description' => 'Lama kamar ditahan untuk reservasi yang belum dibayar (jam)',
            ],
            [
                'key' => 'checkin_time',
                'value' => '14:00',
                'type' => 'string',
                'group' => 'system',
                'description' => 'Waktu standar check-in',
            ],
            [
                'key' => 'checkout_time',
                'value' => '12:00',
                'type' => 'string',
                'group' => 'system',
                'description' => 'Waktu standar check-out',
            ],
            [
                'key' => 'rounding_unit',
                'value' => '1',
                'type' => 'int',
                'group' => 'billing',
                'description' => 'Pembulatan nominal (Rp 1 = pembulatan rupiah terdekat)',
            ],
            [
                'key' => 'monthly_notice_days',
                'value' => '7',
                'type' => 'int',
                'group' => 'billing',
                'description' => 'Pemberitahuan berhenti sewa bulanan minimal H-7 akhir bulan',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'group' => $setting['group'],
                    'description' => $setting['description'],
                    'updated_at' => now(),
                ]
            );
        }
    }
}
