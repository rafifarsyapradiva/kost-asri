<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Services\Setting\SettingService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('retrieves cached settings and auto-flushes on update (BE-006)', function () {
    Setting::create(['key' => 'test_tax', 'value' => '10', 'type' => 'int', 'group' => 'general']);
    $service = app(SettingService::class);

    expect($service->get('test_tax'))->toBe(10)
        ->and(setting('test_tax'))->toBe(10);

    $service->set('test_tax', 12, 'int');
    expect($service->get('test_tax'))->toBe(12)
        ->and($service->all()['test_tax'])->toBe(12);
});
