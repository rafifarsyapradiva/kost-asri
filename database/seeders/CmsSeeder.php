<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\NearbyPlace;
use App\Models\ReservationStep;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Models\Video;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Facilities
        $facilities = [
            ['name' => 'WiFi Kecepatan Tinggi 100 Mbps', 'icon' => 'wifi', 'description' => 'Akses internet cepat dedicated di seluruh koridor dan kamar.', 'category' => 'general', 'sort_order' => 1],
            ['name' => 'Dapur Bersama Lengkap', 'icon' => 'utensils', 'description' => 'Dilengkapi kompor gas, kulkas bersama, dispenser air galon gratis, dan microwave.', 'category' => 'general', 'sort_order' => 2],
            ['name' => 'CCTV 24 Jam & Security Key', 'icon' => 'shield-check', 'description' => 'Keamanan terpantau kamera CCTV di area publik 24 jam penuh.', 'category' => 'general', 'sort_order' => 3],
            ['name' => 'Area Parkir Motor Beratap', 'icon' => 'bike', 'description' => 'Parkir luas, teduh berkanopi, dan terlindung dari hujan serta panas matahari.', 'category' => 'general', 'sort_order' => 4],
            ['name' => 'Ruang Santai & Co-Working Space', 'icon' => 'users', 'description' => 'Ruang komunal berkonsep Warm Biophilic dengan tanaman asri untuk belajar kelompok.', 'category' => 'general', 'sort_order' => 5],
            ['name' => 'Area Jemur Pakaian Luas', 'icon' => 'sun', 'description' => 'Area jemur beratap transparan di lantai atas dengan sirkulasi angin optimal.', 'category' => 'general', 'sort_order' => 6],
        ];

        foreach ($facilities as $fac) {
            Facility::updateOrCreate(['name' => $fac['name']], $fac);
        }

        // 2. Galleries
        $galleries = [
            ['title' => 'Tampak Depan ASRI Boarding House', 'category' => 'environment', 'image_path' => 'cms/gallery/depan.jpg', 'sort_order' => 1],
            ['title' => 'Koridor Biophilic Hijau Lantai 1', 'category' => 'facility', 'image_path' => 'cms/gallery/koridor-lt1.jpg', 'sort_order' => 2],
            ['title' => 'Kamar VIP Interior & Meja Belajar', 'category' => 'room', 'image_path' => 'cms/gallery/kamar-vip.jpg', 'sort_order' => 3],
            ['title' => 'Balkon Terbuka Kamar Deluxe Lantai 2', 'category' => 'room', 'image_path' => 'cms/gallery/balkon-deluxe.jpg', 'sort_order' => 4],
            ['title' => 'Dapur Bersama Bersih & Higienis', 'category' => 'facility', 'image_path' => 'cms/gallery/dapur.jpg', 'sort_order' => 5],
        ];

        foreach ($galleries as $gal) {
            Gallery::updateOrCreate(['title' => $gal['title']], $gal);
        }

        // 3. Videos
        Video::updateOrCreate(
            ['youtube_id' => 'dQw4w9WgXcQ'],
            [
                'title' => 'Room Tour & Suasana Lingkungan ASRI Boarding House Tembalang',
                'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'youtube_id' => 'dQw4w9WgXcQ',
                'thumbnail_url' => 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
                'description' => 'Simak tur singkat 32 kamar, fasilitas umum, dan suasana asri tenang di sekitar kost dekat kampus UNDIP Tembalang.',
                'sort_order' => 1,
                'is_published' => true,
            ]
        );

        // 4. Reservation Steps (5 Langkah PRD §7.1)
        $steps = [
            ['step_number' => 1, 'title' => 'Pilih Kamar & Tanggal', 'description' => 'Pilih tanggal rencana masuk dan klik nomor kamar hijau (tersedia) langsung pada denah interaktif 2 lantai.', 'icon' => 'door-open', 'sort_order' => 1],
            ['step_number' => 2, 'title' => 'Tentukan Tipe Sewa', 'description' => 'Pilih sewa Harian, Mingguan, atau Bulanan (dengan opsi perhitungan prorata transparan). Rincian biaya dihitung otomatis.', 'icon' => 'calendar-range', 'sort_order' => 2],
            ['step_number' => 3, 'title' => 'Lengkapi Data Diri & Wali', 'description' => 'Masuk dengan Google atau akun Anda, lengkapi identitas KTP dan nomor darurat wali demi keamanan kontrak.', 'icon' => 'user-check', 'sort_order' => 3],
            ['step_number' => 4, 'title' => 'Pembayaran Fleksibel', 'description' => 'Pilih skema pembayaran DP (minimal 30%) atau Lunas melalui Virtual Account Midtrans (otomatis) atau tunai ke penjaga.', 'icon' => 'credit-card', 'sort_order' => 4],
            ['step_number' => 5, 'title' => 'Konfirmasi & Check-In', 'description' => 'Dapatkan Nota Reservasi resmi berformat PDF dan notifikasi instan WhatsApp. Kamar siap Anda huni sesuai jadwal.', 'icon' => 'file-check', 'sort_order' => 5],
        ];

        foreach ($steps as $st) {
            ReservationStep::updateOrCreate(['step_number' => $st['step_number']], $st);
        }

        // 5. Testimonials
        $testimonials = [
            [
                'name' => 'Dimas Arya Pratama',
                'origin_info' => 'Mahasiswa Teknik Sipil UNDIP Angkatan 2023',
                'rating' => 5,
                'content' => 'Kost paling tenang di area Tembalang. Sirkulasi udaranya sejuk, internetnya sangat stabil buat kuliah daring dan ngerjain tugas, serta penjaganya ramah dan siap membantu 24 jam.',
                'moderation_status' => 'approved',
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'name' => 'Nabila Putri Santoso',
                'origin_info' => 'Mahasiswi Kedokteran UNDIP',
                'rating' => 5,
                'content' => 'Lokasinya sangat strategis dekat RSND dan kampus. Sistem tagihannya transparan dan bisa bayar online QRIS/BCA VA tanpa repot transfer manual atau konfirmasi bolak-balik.',
                'moderation_status' => 'approved',
                'sort_order' => 2,
                'is_published' => true,
            ],
            [
                'name' => 'Reza Pahlevi',
                'origin_info' => 'Karyawan Swasta di Banyumanik',
                'rating' => 5,
                'content' => 'Saya ambil tipe VIP lantai 1. Fasilitas air panas dan AC bekerja optimal, kebersihan koridor terjaga tiap pagi. Parkiran motor juga aman dan berkanopi luas.',
                'moderation_status' => 'approved',
                'sort_order' => 3,
                'is_published' => true,
            ],
        ];

        foreach ($testimonials as $tst) {
            Testimonial::updateOrCreate(['name' => $tst['name']], $tst);
        }

        // 6. FAQs
        $faqs = [
            ['question' => 'Apakah tarif sewa bulanan sudah termasuk biaya listrik dan air?', 'answer' => 'Ya, seluruh tarif sewa di ASRI Boarding House sudah mencakup pemakaian air bersih dan listrik standar wajar (lampu, kipas angin/AC, laptop, charger HP).', 'sort_order' => 1],
            ['question' => 'Bagaimana sistem pembayaran sewa dapat dilakukan?', 'answer' => 'Pembayaran dapat dilakukan melalui gerbang pembayaran Midtrans (BCA VA, Mandiri, BNI, BRI, QRIS, GoPay) atau tunai langsung ke kasir/penjaga kost dengan bukti kwitansi resmi.', 'sort_order' => 2],
            ['question' => 'Berapa persen minimal Uang Muka (DP) untuk pemesanan kamar?', 'answer' => 'Minimal DP adalah 30% dari total tagihan awal reservasi. Kamar akan di-hold selama 24 jam sejak reservasi dibuat hingga pembayaran terkonfirmasi.', 'sort_order' => 3],
            ['question' => 'Bagaimana jika saya masuk kost di pertengahan bulan (bulanan)?', 'answer' => 'Kami menyediakan 2 opsi perhitungan prorata: Opsi 1 (Gabung) membayar prorata sisa hari bulan berjalan ditambah bulan berikutnya, atau Opsi 2 (Penuh Dulu) membayar 1 bulan penuh di muka dengan penyesuaian di tanggal 1 berikutnya.', 'sort_order' => 4],
            ['question' => 'Apakah pintu gerbang memiliki batas jam malam?', 'answer' => 'Gerbang utama ditutup pukul 23:00 WIB demi ketertiban bersama. Namun penghuni yang memiliki kegiatan kampus lembur dapat mengakses melalui pintu samping khusus dengan melapor ke penjaga.', 'sort_order' => 5],
            ['question' => 'Apakah diperbolehkan membawa kendaraan roda empat (mobil)?', 'answer' => 'Tersedia tempat parkir mobil terbatas dengan sistem reservasi slot khusus. Mohon konfirmasi ketersediaan slot parkir mobil ke penjaga kost terlebih dahulu.', 'sort_order' => 6],
        ];

        foreach ($faqs as $fq) {
            Faq::updateOrCreate(['question' => $fq['question']], $fq);
        }

        // 7. Nearby Places
        $places = [
            ['name' => 'Universitas Diponegoro (Gerbang Utama Tembalang)', 'category' => 'campus', 'distance_text' => '650 m', 'travel_minutes' => 3, 'sort_order' => 1],
            ['name' => 'Politeknik Negeri Semarang (Polines)', 'category' => 'campus', 'distance_text' => '1.2 km', 'travel_minutes' => 5, 'sort_order' => 2],
            ['name' => 'Rumah Sakit Nasional Diponegoro (RSND)', 'category' => 'health', 'distance_text' => '1.5 km', 'travel_minutes' => 6, 'sort_order' => 3],
            ['name' => 'Indomaret & Alfamart Banjarsari', 'category' => 'minimarket', 'distance_text' => '100 m', 'travel_minutes' => 1, 'sort_order' => 4],
            ['name' => 'Pusat Kuliner & Kafe Mahasiswa Banjarsari', 'category' => 'culinary', 'distance_text' => '250 m', 'travel_minutes' => 2, 'sort_order' => 5],
        ];

        foreach ($places as $pl) {
            NearbyPlace::updateOrCreate(['name' => $pl['name']], $pl);
        }

        // 8. Site Settings
        $siteSettings = [
            [
                'key' => 'about_us',
                'group' => 'about',
                'value' => [
                    'title' => 'Hunian Kost Asri, Nyaman & Berkonsep Biophilic di Tembalang',
                    'lead' => 'ASRI BOARDING HOUSE adalah hunian kost eksklusif 2 lantai dengan total 32 kamar tidur pilihan yang dirancang khusus untuk kenyamanan mahasiswa dan profesional muda di kawasan Tembalang Semarang.',
                    'vision' => 'Mewujudkan ekosistem tempat tinggal yang tenang, asri, sehat, dan mendukung produktivitas belajar serta istirahat dengan integrasi teknologi reservasi digital transparan.',
                    'values' => [
                        'Kenyamanan Maksimal & Udara Asri',
                        'Keamanan Terjamin 24 Jam Non-stop',
                        'Transparansi Billing & Dukungan Pembayaran Digital',
                        'Lokasi Strategis Sangat Dekat Kampus UNDIP',
                    ],
                ],
            ],
            [
                'key' => 'contact_info',
                'group' => 'contact',
                'value' => [
                    'phone' => '6281234567890',
                    'whatsapp' => '6281234567890',
                    'email' => 'info@asriboardinghouse.id',
                    'operational_hours' => 'Senin – Minggu: 07:00 – 21:00 WIB',
                    'address' => 'Jl. Banjarsari No. 12, Kelurahan Tembalang, Kecamatan Tembalang, Kota Semarang, Jawa Tengah 50275',
                    'maps_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3959.610582509146!2d110.4357321!3d-7.0549421!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e708c02c6107a61%3A0x6bcfad0964fb4958!2sUniversitas%20Diponegoro!5e0!3m2!1sid!2sid!4v1715000000000!5m2!1sid!2sid',
                    'latitude' => -7.054942,
                    'longitude' => 110.435732,
                ],
            ],
            [
                'key' => 'seo_meta',
                'group' => 'seo',
                'value' => [
                    'meta_title' => 'ASRI BOARDING HOUSE — Kost Eksklusif 32 Kamar Dekat Kampus UNDIP Tembalang',
                    'meta_description' => 'Kost nyaman dan asri di Tembalang Semarang. 32 kamar 2 lantai pilihan (Standard, VIP, Deluxe), fasilitas lengkap, WiFi 100 Mbps, denah visual interaktif, reservasi online instan.',
                    'og_image' => 'images/og-kost-asri.jpg',
                ],
            ],
        ];

        foreach ($siteSettings as $ss) {
            SiteSetting::updateOrCreate(
                ['key' => $ss['key']],
                ['group' => $ss['group'], 'value' => $ss['value'], 'updated_at' => now()]
            );
        }
    }
}
