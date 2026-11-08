<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoomClass;
use App\Models\RoomType;
use Illuminate\Database\Seeder;

class RoomTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Kamar Standard',
                'slug' => 'standard',
                'room_class' => RoomClass::Standard,
                'daily_rate' => 60_000,
                'weekly_rate' => 300_000,
                'monthly_rate' => 900_000,
                'size_m2' => 12.00,
                'facilities_json' => [
                    'Kasur Single Springbed',
                    'Lemari Pakaian 2 Pintu',
                    'Meja & Kursi Belajar',
                    'Kamar Mandi Dalam (Shower & Kloset Duduk)',
                    'Kipas Angin Dinding',
                    'Ventilasi Udara Alami',
                ],
                'description' => 'Tipe Standard menawarkan hunian hemat dan nyaman dengan fasilitas esensial lengkap, pencahayaan alami yang baik, dan kamar mandi dalam.',
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'name' => 'Kamar VIP',
                'slug' => 'vip',
                'room_class' => RoomClass::Vip,
                'daily_rate' => 85_000,
                'weekly_rate' => 450_000,
                'monthly_rate' => 1_300_000,
                'size_m2' => 16.00,
                'facilities_json' => [
                    'Kasur Queen Springbed',
                    'AC 1/2 PK Hemat Energi',
                    'Lemari Pakaian Luas + Cermin',
                    'Meja Kerja Ergonomis',
                    'Kamar Mandi Dalam (Shower & Kloset Duduk)',
                    'Water Heater',
                    'Jendela Menghadap Taman',
                ],
                'description' => 'Tipe VIP hadir dengan ruang yang lebih luas, pendingin udara AC, water heater, dan tempat tidur ukuran queen untuk kenyamanan maksimal belajar dan istirahat.',
                'sort_order' => 2,
                'is_published' => true,
            ],
            [
                'name' => 'Kamar Deluxe',
                'slug' => 'deluxe',
                'room_class' => RoomClass::Deluxe,
                'daily_rate' => 120_000,
                'weekly_rate' => 650_000,
                'monthly_rate' => 1_800_000,
                'size_m2' => 20.00,
                'facilities_json' => [
                    'Kasur King Springbed Premium',
                    'AC 3/4 PK Inverter',
                    'Smart TV 32 Inch',
                    'Kulkas Mini Pribadi',
                    'Kamar Mandi Dalam Luas + Water Heater',
                    'Balkon Pribadi Lantai 2',
                    'Nakas & Lemari Pakaian 3 Pintu',
                ],
                'description' => 'Tipe tertinggi Deluxe di Lantai 2 dengan pemandangan terbuka, balkon pribadi, smart TV, kulkas mini, dan furnitur kayu jati solid berkonsep Biophilic.',
                'sort_order' => 3,
                'is_published' => true,
            ],
        ];

        foreach ($types as $type) {
            RoomType::updateOrCreate(
                ['room_class' => $type['room_class']],
                $type
            );
        }
    }
}
