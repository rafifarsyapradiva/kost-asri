<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ChatbotKeyword;
use Illuminate\Database\Seeder;

class ChatbotKeywordSeeder extends Seeder
{
    public function run(): void
    {
        $keywords = [
            [
                'keyword' => 'MENU',
                'menu_number' => null,
                'synonyms' => ['halo', 'hai', 'hi', 'mulai', 'p', 'bantuan', 'info', 'menu'],
                'action' => 'menu',
                'reply_template' => "Halo! Selamat datang di Layanan Otomatis WhatsApp ASRI BOARDING HOUSE Tembalang 🌿\n\nSilakan balas dengan angka atau kata kunci berikut:\n1. KAMAR - Info ketersediaan kamar hari ini\n2. HARGA - Daftar tarif sewa harian, mingguan & bulanan\n3. TAGIHAN - Cek tagihan aktif Anda\n4. BAYAR - Panduan cara pembayaran\n5. LOKASI - Alamat lengkap & Google Maps\n6. FAQ - Pertanyaan yang sering ditanyakan\n7. ADMIN - Hubungi penjaga kost langsung",
                'sort_order' => 0,
                'is_active' => true,
            ],
            [
                'keyword' => 'KAMAR',
                'menu_number' => '1',
                'synonyms' => ['kosong', 'tersedia', 'ready', 'cek kamar', 'kamar kosong'],
                'action' => 'availability',
                'reply_template' => "Informasi Kamar ASRI Boarding House:\n{dynamic_availability}\n\nPemesanan & denah visual langsung di:\nhttps://asriboardinghouse.id/reservasi",
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'keyword' => 'HARGA',
                'menu_number' => '2',
                'synonyms' => ['tarif', 'biaya', 'price', 'daftar harga', 'sewa'],
                'action' => 'prices',
                'reply_template' => "Daftar Tarif Sewa ASRI Boarding House Tembalang:\n\n1. Standard Room\n   • Harian: Rp 60.000 / malam\n   • Mingguan: Rp 300.000 / minggu\n   • Bulanan: Rp 900.000 / bulan\n\n2. VIP Room (AC + Water Heater)\n   • Harian: Rp 85.000 / malam\n   • Mingguan: Rp 450.000 / minggu\n   • Bulanan: Rp 1.300.000 / bulan\n\n3. Deluxe Room (Balkon + TV + Kulkas)\n   • Harian: Rp 120.000 / malam\n   • Mingguan: Rp 650.000 / minggu\n   • Bulanan: Rp 1.800.000 / bulan\n\nSeluruh tarif sudah termasuk listrik wajar dan air.",
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'keyword' => 'TAGIHAN',
                'menu_number' => '3',
                'synonyms' => ['invoice', 'cek tagihan', 'tagihan saya', 'status sewa'],
                'action' => 'invoice',
                'reply_template' => "{dynamic_invoice}",
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'keyword' => 'BAYAR',
                'menu_number' => '4',
                'synonyms' => ['pembayaran', 'transfer', 'cara bayar', 'rekening'],
                'action' => 'pay',
                'reply_template' => "Cara Pembayaran Sewa ASRI Boarding House:\n\n1. Online via Midtrans (BCA VA, Mandiri, BRI, BNI, QRIS, GoPay)\n   Buka link tagihan pada pesan notifikasi atau dashboard penyewa.\n\n2. Tunai langsung ke Penjaga Kost di kantor resepsionis.\n   Pastikan meminta kwitansi resmi setelah melakukan pembayaran tunai.",
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'keyword' => 'LOKASI',
                'menu_number' => '5',
                'synonyms' => ['alamat', 'maps', 'lokasi kost', 'dimana', 'posisi'],
                'action' => 'location',
                'reply_template' => "Lokasi ASRI BOARDING HOUSE:\nJl. Banjarsari No. 12, Kelurahan Tembalang, Kecamatan Tembalang, Kota Semarang, Jawa Tengah 50275.\n(Hanya 3 menit dari Gerbang Utama Kampus UNDIP Tembalang).\n\nGoogle Maps: https://maps.app.goo.gl/AsriKostTembalang",
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'keyword' => 'FAQ',
                'menu_number' => '6',
                'synonyms' => ['tanya', 'info', 'pertanyaan', 'tanya jawab'],
                'action' => 'faq',
                'reply_template' => "Pertanyaan Umum (FAQ):\n\nQ: Jam malam di kost?\nA: Pintu gerbang utama ditutup pukul 23:00 WIB demi keamanan. Penghuni memiliki akses kunci darurat bila lembur tugas.\n\nQ: Bolehkah membawa tamu menginap?\nA: Tamu menginap wajib melapor ke penjaga dan dikenakan ketentuan sewa harian.\n\nQ: Apakah parkir motor & mobil aman?\nA: Parkir motor beratap di dalam gerbang dan diawasi CCTV 24 jam. Parkir mobil tersedia terbatas.",
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'keyword' => 'ADMIN',
                'menu_number' => '7',
                'synonyms' => ['cs', 'penjaga', 'manusia', 'operator', 'bantuan langsung', 'kontak'],
                'action' => 'handover',
                'reply_template' => "Pesan Anda telah diteruskan ke Admin / Penjaga Kost ASRI. Bot diistirahatkan selama 60 menit untuk nomor ini. Silakan sampaikan pesan atau pertanyaan Anda.",
                'sort_order' => 7,
                'is_active' => true,
            ],
        ];

        foreach ($keywords as $kw) {
            ChatbotKeyword::updateOrCreate(
                ['keyword' => $kw['keyword']],
                $kw
            );
        }
    }
}
