<?php

declare(strict_types=1);

namespace App\Services\Chatbot;

use App\Enums\InvoiceStatus;
use App\Enums\RoomStatus;
use App\Models\Faq;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Support\Money;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\URL;

class ChatbotReplies
{
    /**
     * Menu Utama (MENU).
     */
    public function menu(): string
    {
        return "🏡 *ASRI BOARDING HOUSE — Layanan Informasi*

Silakan balas dengan angka atau kata kunci:
1. *KAMAR* — Ketersediaan kamar hari ini
2. *HARGA* — Daftar tarif sewa
3. *TAGIHAN* — Cek tagihan sewa Anda
4. *BAYAR* — Panduan cara pembayaran
5. *LOKASI* — Alamat & Google Maps
6. *FAQ* — Tanya jawab seputar kost
7. *ADMIN* — Hubungkan ke Penjaga Kost

Ketik *MENU* kapan saja untuk kembali.";
    }

    /**
     * Info Ketersediaan Kamar (1 / KAMAR) (FR-BOT-02).
     */
    public function availability(): string
    {
        $types = RoomType::all();
        $lines = ["🛏️ *Ketersediaan Kamar Hari Ini:*\n"];

        foreach ($types as $type) {
            $count = Room::where('room_type_id', $type->id)
                ->where('status', RoomStatus::Available)
                ->count();
            $lines[] = "• *{$type->name}*: {$count} kamar tersedia";
        }

        $bookingUrl = url('/reservasi');
        $lines[] = "\nPesan kamar secara online langsung di:\n{$bookingUrl}";

        return implode("\n", $lines);
    }

    /**
     * Daftar Tarif Sewa (2 / HARGA).
     */
    public function prices(): string
    {
        $types = RoomType::all();
        $lines = ["🏷️ *Daftar Tarif Sewa ASRI BOARDING HOUSE:*\n"];

        foreach ($types as $type) {
            $lines[] = "*{$type->name}*";
            $lines[] = "• Harian: ".Money::format($type->daily_rate).'/malam';
            $lines[] = "• Mingguan: ".Money::format($type->weekly_rate).'/minggu';
            $lines[] = "• Bulanan: ".Money::format($type->monthly_rate).'/bulan';
            $lines[] = "";
        }

        $lines[] = "Semua kamar fully furnished + kamar mandi dalam + Wi-Fi.";

        return implode("\n", $lines);
    }

    /**
     * Cek Tagihan Penyewa by Nomor HP (3 / TAGIHAN) (FR-BOT-03, SEC-019).
     */
    public function invoiceFor(string $senderPhone): string
    {
        $normalized = PhoneNormalizer::normalize($senderPhone) ?? $senderPhone;

        /** @var User|null $user */
        $user = User::where('phone', $normalized)->first();

        if ($user === null) {
            return "Maaf, nomor WhatsApp Anda ({$normalized}) belum terdaftar sebagai penyewa di ASRI Boarding House.

Jika Anda penghuni kost, pastikan nomor ini sudah dicatat oleh admin, atau ketik *7* untuk berbicara langsung dengan Admin / Penjaga Kost.";
        }

        $unpaidInvoices = Invoice::where('user_id', $user->id)
            ->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Partial, InvoiceStatus::Overdue])
            ->get();

        if ($unpaidInvoices->isEmpty()) {
            return "Halo *{$user->name}*, saat ini Anda tidak memiliki tagihan aktif atau tunggakan. Terima kasih telah tertib!";
        }

        $lines = ["📄 *Tagihan Aktif untuk {$user->name}:*\n"];

        foreach ($unpaidInvoices as $inv) {
            $remaining = $inv->total - $inv->paid_amount;
            $payUrl = URL::temporarySignedRoute('guardian.pay', now()->addDays(3), ['invoice' => $inv->id]);

            $lines[] = "• *{$inv->number}*";
            $lines[] = "  Total: ".Money::format($remaining);
            $lines[] = "  Jatuh tempo: ".($inv->due_date?->format('d M Y') ?? '-');
            $lines[] = "  Bayar online: {$payUrl}";
            $lines[] = "";
        }

        $lines[] = "Pembayaran juga dapat dilakukan secara tunai kepada Penjaga Kost.";

        return implode("\n", $lines);
    }

    /**
     * Panduan Pembayaran (4 / BAYAR).
     */
    public function payInfo(string $senderPhone): string
    {
        return "💳 *Cara Pembayaran ASRI BOARDING HOUSE:*

1. *Online via Midtrans*:
   Mendukung QRIS, BCA VA, BNI VA, Mandiri VA, BRI VA, GoPay, dan Kartu Kredit.
   (Tautan pembayaran online tertera di notifikasi tagihan atau ketik *3*).

2. *Tunai / Cash*:
   Dapat dibayarkan langsung kepada Penjaga Kost di kantor resepsionis. Penjaga kost akan menerbitkan Kwitansi Resmi bermaterai/bernomor KWT/ASRI/...";
    }

    /**
     * Lokasi Kost (5 / LOKASI).
     */
    public function location(): string
    {
        return "📍 *Lokasi ASRI BOARDING HOUSE:*
Jl. Banjarsari Selatan No. 12, Tembalang, Kota Semarang, Jawa Tengah (dekat Kampus UNDIP Tembalang).

Google Maps: https://maps.google.com/?q=ASRI+Boarding+House+Tembalang";
    }

    /**
     * Tanya Jawab / FAQ (6 / FAQ).
     */
    public function faq(): string
    {
        $faqs = Faq::where('is_active', true)
            ->orderBy('sort_order')
            ->take(5)
            ->get();

        if ($faqs->isEmpty()) {
            return "❓ *FAQ ASRI BOARDING HOUSE:*
• Jam malam: Pintu gerbang ditutup pukul 23.00 WIB (penghuni memiliki kunci akses).
• Fasilitas bersama: Dapur bersama, kulkas bersama, dispenser air galon, parkir motor & mobil beratap, CCTV 24 jam.
• Hewan peliharaan: Dilarang membawa hewan peliharaan.";
        }

        $lines = ["❓ *Tanya Jawab (FAQ) Populer:*\n"];
        foreach ($faqs as $idx => $f) {
            $num = $idx + 1;
            $plainAnswer = strip_tags($f->answer);
            $lines[] = "{$num}. *{$f->question}*";
            $lines[] = "   {$plainAnswer}\n";
        }

        return implode("\n", $lines);
    }
}
