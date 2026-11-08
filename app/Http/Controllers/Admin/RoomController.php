<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $rooms = Room::with('roomType')->orderBy('floor')->orderBy('code')->get();

        if ($request->wantsJson()) {
            return response()->json($rooms);
        }

        return Inertia::render('Admin/Rooms/Index', [
            'rooms' => $rooms,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:rooms,code'],
            'floor' => ['required', 'integer', 'in:1,2'],
            'grid_row' => ['required', 'integer', 'min:1'],
            'grid_col' => ['required', 'integer', 'min:1'],
            'room_type_id' => ['required', 'exists:room_types,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $room = new Room($validated);
        $room->status = RoomStatus::Available;
        $room->save();

        return redirect()->route('admin.rooms.index')->with('success', "Kamar {$room->code} berhasil ditambahkan.");
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:rooms,code,' . $room->id],
            'floor' => ['required', 'integer', 'in:1,2'],
            'grid_row' => ['required', 'integer', 'min:1'],
            'grid_col' => ['required', 'integer', 'min:1'],
            'room_type_id' => ['required', 'exists:room_types,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $room->update($validated);

        return redirect()->route('admin.rooms.index')->with('success', "Kamar {$room->code} berhasil diperbarui.");
    }

    public function destroy(Room $room): RedirectResponse|JsonResponse
    {
        if ($room->reservations()->exists() || $room->leases()->exists()) {
            abort(422, 'Kamar yang memiliki riwayat transaksi tidak dapat dihapus.');
        }

        $code = $room->code;
        $room->delete();

        return redirect()->route('admin.rooms.index')->with('success', "Kamar {$code} berhasil dihapus.");
    }

    public function toggleMaintenance(Request $request, Room $room): RedirectResponse|JsonResponse
    {
        if ($room->status === RoomStatus::Maintenance) {
            $room->update([
                'status' => RoomStatus::Available,
                'maintenance_note' => null,
            ]);

            return back()->with('success', "Kamar {$room->code} telah diaktifkan kembali.");
        }

        if ($room->status !== RoomStatus::Available) {
            abort(422, 'Hanya kamar berstatus available yang dapat diubah ke maintenance.');
        }

        $note = $request->input('maintenance_note', 'Perbaikan kamar oleh pengelola');
        $room->update([
            'status' => RoomStatus::Maintenance,
            'maintenance_note' => $note,
        ]);

        return back()->with('success', "Kamar {$room->code} ditandai dalam perbaikan.");
    }
}
