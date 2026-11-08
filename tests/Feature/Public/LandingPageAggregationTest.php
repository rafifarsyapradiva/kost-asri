<?php

declare(strict_types=1);

use App\Models\Facility;
use App\Models\Faq;
use App\Models\RoomType;

it('renders public landing page aggregating only published sections and room types (BE-018, TST-037)', function () {
    Facility::factory()->create(['name' => 'WiFi 100 Mbps', 'is_published' => true]);
    Facility::factory()->create(['name' => 'Hidden Facility', 'is_published' => false]);
    Faq::factory()->create(['question' => 'Jam malam kost?', 'is_published' => true]);
    Faq::factory()->create(['question' => 'Hidden Faq', 'is_published' => false]);

    $response = $this->get('/');
    $response->assertOk()
        ->assertSee('WiFi 100 Mbps')
        ->assertDontSee('Hidden Facility')
        ->assertSee('Jam malam kost?')
        ->assertDontSee('Hidden Faq');
});

it('renders single room type public detail page (FR-CMS-02)', function () {
    $type = RoomType::factory()->create([
        'name' => 'Kamar Standard Biophilic',
        'slug' => 'kamar-standard-biophilic',
        'is_published' => true,
    ]);

    $this->get('/kamar/kamar-standard-biophilic')
        ->assertOk()
        ->assertSee('Kamar Standard Biophilic');
});

it('aborts 404 for unpublished room type (SEC-008)', function () {
    $type = RoomType::factory()->create([
        'name' => 'Kamar Secret',
        'slug' => 'kamar-secret',
        'is_published' => false,
    ]);

    $this->get('/kamar/kamar-secret')
        ->assertNotFound();
});

it('auto-hides sections when CMS tables have zero published items (BE-018)', function () {
    $response = $this->get('/');
    $response->assertOk();

    $testimonials = $response->viewData('testimonials');
    expect($testimonials)->toBeEmpty();

    $videos = $response->viewData('videos');
    expect($videos)->toBeEmpty();
});
