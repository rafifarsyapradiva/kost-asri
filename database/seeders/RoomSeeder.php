<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoomClass;
use App\Enums\RoomStatus;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $types = RoomType::all()->keyBy(fn (RoomType $t) => $t->room_class->value);

        $standardId = $types[RoomClass::Standard->value]->id;
        $vipId = $types[RoomClass::Vip->value]->id;
        $deluxeId = $types[RoomClass::Deluxe->value]->id;

        // Lantai 1: 16 Kamar
        // Row 1: 101-108 Standard (col 1..8)
        for ($col = 1; $col <= 8; $col++) {
            $code = (string) (100 + $col);
            Room::updateOrCreate(
                ['code' => $code],
                [
                    'floor' => 1,
                    'grid_row' => 1,
                    'grid_col' => $col,
                    'room_type_id' => $standardId,
                    'status' => RoomStatus::Available,
                ]
            );
        }

        // Row 2: 109-112 Standard (col 1..4)
        for ($col = 1; $col <= 4; $col++) {
            $code = (string) (108 + $col);
            Room::updateOrCreate(
                ['code' => $code],
                [
                    'floor' => 1,
                    'grid_row' => 2,
                    'grid_col' => $col,
                    'room_type_id' => $standardId,
                    'status' => RoomStatus::Available,
                ]
            );
        }

        // Row 2: 113-116 VIP (col 5..8)
        for ($col = 5; $col <= 8; $col++) {
            $code = (string) (108 + $col);
            Room::updateOrCreate(
                ['code' => $code],
                [
                    'floor' => 1,
                    'grid_row' => 2,
                    'grid_col' => $col,
                    'room_type_id' => $vipId,
                    'status' => RoomStatus::Available,
                ]
            );
        }

        // Lantai 2: 16 Kamar
        // Row 1: 201-204 Standard (col 1..4)
        for ($col = 1; $col <= 4; $col++) {
            $code = (string) (200 + $col);
            Room::updateOrCreate(
                ['code' => $code],
                [
                    'floor' => 2,
                    'grid_row' => 1,
                    'grid_col' => $col,
                    'room_type_id' => $standardId,
                    'status' => RoomStatus::Available,
                ]
            );
        }

        // Row 1: 205-208 VIP (col 5..8)
        for ($col = 5; $col <= 8; $col++) {
            $code = (string) (200 + $col);
            Room::updateOrCreate(
                ['code' => $code],
                [
                    'floor' => 2,
                    'grid_row' => 1,
                    'grid_col' => $col,
                    'room_type_id' => $vipId,
                    'status' => RoomStatus::Available,
                ]
            );
        }

        // Row 2: 209-210 VIP (col 1..2)
        for ($col = 1; $col <= 2; $col++) {
            $code = (string) (208 + $col);
            Room::updateOrCreate(
                ['code' => $code],
                [
                    'floor' => 2,
                    'grid_row' => 2,
                    'grid_col' => $col,
                    'room_type_id' => $vipId,
                    'status' => RoomStatus::Available,
                ]
            );
        }

        // Row 2: 211-216 Deluxe (col 3..8)
        for ($col = 3; $col <= 8; $col++) {
            $code = (string) (208 + $col);
            Room::updateOrCreate(
                ['code' => $code],
                [
                    'floor' => 2,
                    'grid_row' => 2,
                    'grid_col' => $col,
                    'room_type_id' => $deluxeId,
                    'status' => RoomStatus::Available,
                ]
            );
        }
    }
}
