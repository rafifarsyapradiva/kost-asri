<?php

declare(strict_types=1);

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('it prevents deletion of rooms that have reservations or leases via FK RESTRICT (DB-037)', function () {
    $standardType = RoomType::factory()->standard()->create();
    $room = Room::factory()->for($standardType)->create(['code' => '999']);
    $user = User::factory()->create();

    $reservation = Reservation::factory()->for($user)->for($room)->create();

    expect(fn () => $room->delete())->toThrow(QueryException::class);
});

test('it prevents deletion of invoices that have recorded payments via FK RESTRICT (DB-037)', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->for($user)->create();
    $payment = Payment::factory()->for($invoice)->for($user)->create();

    expect(fn () => $invoice->delete())->toThrow(QueryException::class);
});

test('it cascades deletion of invoice_items when invoice is deleted (DB-037)', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->for($user)->create();
    $item = InvoiceItem::factory()->for($invoice)->create();

    expect(InvoiceItem::where('id', $item->id)->exists())->toBeTrue();

    $invoice->delete();

    expect(InvoiceItem::where('id', $item->id)->exists())->toBeFalse();
});

test('it validates EXPLAIN query plans for critical transactional queries (DB-036)', function () {
    $standardType = RoomType::factory()->standard()->create();
    Room::factory()->for($standardType)->create(['code' => '101']);
    User::factory()->create(['email' => 'admin@asriboardinghouse.id']);

    // 1. Availability query plan check
    $explainAvailability = DB::select(
        "EXPLAIN SELECT * FROM rooms WHERE status != 'maintenance' AND id NOT IN (
            SELECT room_id FROM reservations WHERE status IN ('pending_payment', 'dp_paid', 'paid', 'checked_in')
        )"
    );

    expect(count($explainAvailability))->toBeGreaterThan(0);

    // 2. Room code lookup uses unique index
    $explainRoomCode = DB::select("EXPLAIN SELECT * FROM rooms WHERE code = '101'");
    $roomKey = $explainRoomCode[0]->key ?? $explainRoomCode[0]->possible_keys ?? '';
    expect($roomKey)->toContain('rooms_code_unique');

    // 3. User email lookup uses unique index
    $explainUserEmail = DB::select("EXPLAIN SELECT * FROM users WHERE email = 'admin@asriboardinghouse.id'");
    $userKey = $explainUserEmail[0]->key ?? $explainUserEmail[0]->possible_keys ?? '';
    expect($userKey)->toContain('users_email_unique');
});

test('it verifies tenant NIK encryption integrity in DB and plaintext retrieval (DB-038)', function () {
    $user = User::factory()->create();
    $plainNik = '3374098765432100';

    $profile = TenantProfile::create([
        'user_id' => $user->id,
        'nik' => $plainNik,
    ]);

    // Eloquent decryption
    expect($profile->fresh()->nik)->toBe($plainNik);

    // Raw SQL verification
    $raw = DB::table('tenant_profiles')->where('id', $profile->id)->value('nik');
    expect($raw)->not()->toBe($plainNik)
        ->and(str_starts_with($raw, 'eyJ'))->toBeTrue();
});
