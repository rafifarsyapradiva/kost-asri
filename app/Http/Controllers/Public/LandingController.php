<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\NearbyPlace;
use App\Models\ReservationStep;
use App\Models\RoomType;
use App\Models\Testimonial;
use App\Models\Video;
use App\Services\Setting\SettingService;
use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    public function __construct(
        protected SettingService $settingService,
    ) {
    }

    /**
     * Render the public landing page with aggregated published CMS content (BE-018, TST-037).
     */
    public function index(): View
    {
        $facilities = Facility::publishedOrdered()->get();
        $roomTypes = RoomType::where('is_published', true)
            ->orderBy('sort_order')
            ->with('images')
            ->get();
        $videos = Video::publishedOrdered()->get();
        $galleries = Gallery::publishedOrdered()->get();
        $steps = ReservationStep::publishedOrdered()->get();
        $testimonials = Testimonial::publishedOrdered()->get();
        $faqs = Faq::publishedOrdered()->get();
        $nearbyPlaces = NearbyPlace::publishedOrdered()->get();
        $settings = $this->settingService->all();

        return view('public.welcome', compact(
            'facilities',
            'roomTypes',
            'videos',
            'galleries',
            'steps',
            'testimonials',
            'faqs',
            'nearbyPlaces',
            'settings',
        ));
    }

    /**
     * Render single room type detail page (FR-CMS-02).
     */
    public function roomType(string $slug): View
    {
        $roomType = RoomType::where('slug', $slug)
            ->where('is_published', true)
            ->with(['images', 'rooms'])
            ->firstOrFail();

        $settings = $this->settingService->all();

        return view('public.room-detail', compact('roomType', 'settings'));
    }
}
