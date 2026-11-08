<?php

declare(strict_types=1);

use App\Models\Facility;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\Video;
use App\Support\HtmlSanitizer;

it('sanitizes rich text CMS input removing scripts and event handlers (SEC-014)', function () {
    $dirty = '<p>Pertanyaan aman <script>alert("hack")</script> <a href="javascript:steal()">link</a> <b onclick="evil()">bold</b></p>';
    $clean = HtmlSanitizer::clean($dirty);

    expect($clean)->not->toContain('<script>')
        ->and($clean)->not->toContain('javascript:')
        ->and($clean)->not->toContain('onclick')
        ->and($clean)->toContain('<p>Pertanyaan aman')
        ->and($clean)->toContain('bold');
});

it('allows active tenant to submit testimonial and admin to moderate it (BE-016, FR-TNT-03, FR-CMS-06)', function () {
    $tenant = User::factory()->tenant()->create();
    $this->actingAs($tenant)->post('/penyewa/testimoni', [
        'rating' => 5,
        'review' => 'Kost nyaman dan sangat bersih.',
    ])->assertRedirect();

    $testimonial = Testimonial::where('user_id', $tenant->id)->first();
    expect($testimonial)->not->toBeNull()
        ->and($testimonial->is_published)->toBeFalse();

    $admin = User::factory()->admin()->create();
    actingAsAdmin($admin);
    $this->patch("/admin/cms/testimonials/{$testimonial->id}/publish")
        ->assertRedirect();
    expect($testimonial->fresh()->is_published)->toBeTrue();
});

it('extracts YouTube ID and creates video record (BE-015, FR-CMS-03)', function () {
    $admin = User::factory()->admin()->create();
    actingAsAdmin($admin);

    $response = $this->post('/admin/cms/videos', [
        'title' => 'Tur Kamar Kost Asri 2026',
        'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'is_published' => true,
    ]);

    $response->assertRedirect('/admin/cms/videos');

    $video = Video::first();
    expect($video)->not->toBeNull()
        ->and($video->youtube_id)->toBe('dQw4w9WgXcQ');
});

it('supports generic drag and drop reordering across CMS models (BE-013)', function () {
    $admin = User::factory()->admin()->create();
    actingAsAdmin($admin);

    $f1 = Facility::factory()->create(['sort_order' => 1]);
    $f2 = Facility::factory()->create(['sort_order' => 2]);

    $response = $this->postJson('/admin/cms/facilities/reorder', [
        'order' => [$f2->id, $f1->id],
    ]);

    $response->assertOk();
    expect($f2->fresh()->sort_order)->toBe(1)
        ->and($f1->fresh()->sort_order)->toBe(2);
});
