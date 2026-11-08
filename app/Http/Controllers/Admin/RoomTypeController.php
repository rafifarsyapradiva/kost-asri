<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\RoomClass;
use App\Http\Controllers\Controller;
use App\Models\RoomType;
use App\Models\RoomTypeImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class RoomTypeController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $types = RoomType::with(['images', 'rooms'])->orderBy('sort_order')->get();

        if ($request->wantsJson()) {
            return response()->json($types);
        }

        return Inertia::render('Admin/RoomTypes/Index', [
            'room_types' => $types,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:90', 'unique:room_types,slug'],
            'room_class' => ['required', 'string', 'unique:room_types,room_class'],
            'daily_rate' => ['required', 'integer', 'min:0'],
            'weekly_rate' => ['required', 'integer', 'min:0'],
            'monthly_rate' => ['required', 'integer', 'min:0'],
            'size_m2' => ['nullable', 'numeric', 'min:0'],
            'facilities_json' => ['nullable', 'array'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_published' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $roomType = new RoomType($validated);
        $roomType->room_class = RoomClass::from($validated['room_class']);
        $roomType->is_published = $validated['is_published'] ?? true;
        $roomType->save();

        if ($request->hasFile('images')) {
            $this->processAndStoreImages($roomType, $request->file('images'));
        }

        return redirect()->route('admin.room-types.index')->with('success', "Tipe kamar {$roomType->name} berhasil ditambahkan.");
    }

    public function update(Request $request, RoomType $roomType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:90', 'unique:room_types,slug,' . $roomType->id],
            'daily_rate' => ['required', 'integer', 'min:0'],
            'weekly_rate' => ['required', 'integer', 'min:0'],
            'monthly_rate' => ['required', 'integer', 'min:0'],
            'size_m2' => ['nullable', 'numeric', 'min:0'],
            'facilities_json' => ['nullable', 'array'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_published' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $roomType->update($validated);

        if ($request->hasFile('images')) {
            $this->processAndStoreImages($roomType, $request->file('images'));
        }

        return redirect()->route('admin.room-types.index')->with('success', "Tipe kamar {$roomType->name} berhasil diperbarui.");
    }

    public function destroy(RoomType $roomType): RedirectResponse|JsonResponse
    {
        if ($roomType->rooms()->exists()) {
            abort(422, 'Tipe kamar tidak dapat dihapus karena masih digunakan oleh unit kamar.');
        }

        foreach ($roomType->images as $img) {
            Storage::disk('public')->delete($img->image_path);
            $img->delete();
        }

        $roomType->delete();

        return redirect()->route('admin.room-types.index')->with('success', 'Tipe kamar berhasil dihapus.');
    }

    /**
     * Re-encode uploaded images via Intervention Image and store on public disk (BE-009, SEC-011).
     *
     * @param  list<\Illuminate\Http\UploadedFile>  $files
     */
    protected function processAndStoreImages(RoomType $roomType, array $files): void
    {
        $manager = new ImageManager(new Driver());
        $currentMaxOrder = (int) $roomType->images()->max('sort_order');

        foreach ($files as $file) {
            $uuid = (string) Str::uuid();
            $path = "rooms/{$uuid}.webp";

            $image = $manager->read($file->getRealPath());
            $encoded = (string) $image->toWebp(85);

            Storage::disk('public')->put($path, $encoded);

            $currentMaxOrder++;
            RoomTypeImage::create([
                'room_type_id' => $roomType->id,
                'image_path' => $path,
                'caption' => $roomType->name,
                'sort_order' => $currentMaxOrder,
                'is_primary' => ($currentMaxOrder === 1),
            ]);
        }
    }
}
