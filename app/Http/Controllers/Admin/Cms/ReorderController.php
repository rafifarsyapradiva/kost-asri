<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\NearbyPlace;
use App\Models\ReservationStep;
use App\Models\RoomType;
use App\Models\Testimonial;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReorderController extends Controller
{
    /**
     * Map type string to Eloquent model class.
     *
     * @var array<string, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    protected array $models = [
        'facilities' => Facility::class,
        'steps' => ReservationStep::class,
        'videos' => Video::class,
        'galleries' => Gallery::class,
        'faqs' => Faq::class,
        'testimonials' => Testimonial::class,
        'nearby-places' => NearbyPlace::class,
        'room-types' => RoomType::class,
    ];

    /**
     * Generic drag and drop reordering for CMS models (BE-013).
     */
    public function __invoke(Request $request, string $type): JsonResponse
    {
        if (! array_key_exists($type, $this->models)) {
            abort(404, "Tipe CMS '{$type}' tidak didukung untuk reorder.");
        }

        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        $modelClass = $this->models[$type];

        foreach ($validated['order'] as $index => $id) {
            $modelClass::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Urutan berhasil diperbarui.',
        ]);
    }
}
