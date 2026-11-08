<?php

declare(strict_types=1);

use App\Models\ChatbotKeyword;
use App\Services\Chatbot\KeywordMatcher;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function () {
    ChatbotKeyword::create([
        'keyword' => 'MENU',
        'menu_number' => null,
        'synonyms' => ['halo', 'hai', 'hi', 'mulai', 'p'],
        'action' => 'menu',
        'sort_order' => 0,
        'is_active' => true,
    ]);

    ChatbotKeyword::create([
        'keyword' => 'KAMAR',
        'menu_number' => '1',
        'synonyms' => ['kosong', 'tersedia', 'ready'],
        'action' => 'availability',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    ChatbotKeyword::create([
        'keyword' => 'HARGA',
        'menu_number' => '2',
        'synonyms' => ['tarif', 'biaya', 'price'],
        'action' => 'prices',
        'sort_order' => 2,
        'is_active' => true,
    ]);

    ChatbotKeyword::create([
        'keyword' => 'TAGIHAN',
        'menu_number' => '3',
        'synonyms' => ['invoice', 'cek tagihan'],
        'action' => 'invoice',
        'sort_order' => 3,
        'is_active' => true,
    ]);

    ChatbotKeyword::create([
        'keyword' => 'ADMIN',
        'menu_number' => '7',
        'synonyms' => ['cs', 'penjaga', 'manusia'],
        'action' => 'handover',
        'sort_order' => 7,
        'is_active' => true,
    ]);
});

it('matches exact keyword case-insensitively and ignores punctuation (TST-013)', function () {
    $matcher = app(KeywordMatcher::class);

    $match1 = $matcher->match('kamar!');
    expect($match1)->not->toBeNull()
        ->and($match1->action)->toBe('availability');

    $match2 = $matcher->match('HARGA');
    expect($match2)->not->toBeNull()
        ->and($match2->action)->toBe('prices');
});

it('matches menu numbers 1 to 7 correctly (TST-013)', function () {
    $matcher = app(KeywordMatcher::class);

    $match1 = $matcher->match('1');
    expect($match1)->not->toBeNull()
        ->and($match1->action)->toBe('availability');

    $match7 = $matcher->match('7');
    expect($match7)->not->toBeNull()
        ->and($match7->action)->toBe('handover');
});

it('matches synonyms accurately (TST-013)', function () {
    $matcher = app(KeywordMatcher::class);

    $match = $matcher->match('halo min');
    expect($match)->not->toBeNull()
        ->and($match->action)->toBe('menu');

    $matchTagihan = $matcher->match('cek tagihan saya');
    expect($matchTagihan)->not->toBeNull()
        ->and($matchTagihan->action)->toBe('invoice');
});

it('falls back to MENU when no keywords or synonyms match (TST-013)', function () {
    $matcher = app(KeywordMatcher::class);

    $match = $matcher->match('xyz random query 123999');
    expect($match)->not->toBeNull()
        ->and($match->keyword)->toBe('MENU');
});
