<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\NotificationChannel;
use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // 1. Tagihan Bulanan / Mingguan Terbit
            [
                'key' => 'invoice_issued',
                'name' => 'Pemberitahuan Tagihan Terbit',
                'channel' => NotificationChannel::WhatsApp,
                'subject' => null,
                'body' => "Halo {nama}, tagihan sewa kamar {kode_kamar} ({kelas}) periode {periode} telah terbit.\nNo. Invoice : {nomor_invoice}\nTotal       : {total}\nJatuh tempo : {tanggal_jatuh_tempo}\nBayar online: {link_bayar}\nAtau bayar tunai ke penjaga kost.\n— ASRI BOARDING HOUSE, Tembalang",
                'available_placeholders' => ['nama', 'kode_kamar', 'kelas', 'periode', 'nomor_invoice', 'total', 'tanggal_jatuh_tempo', 'link_bayar'],
            ],
            [
                'key' => 'invoice_issued',
                'name' => 'Tagihan Sewa Kamar ASRI Boarding House',
                'channel' => NotificationChannel::Email,
                'subject' => 'Tagihan Sewa Kamar {kode_kamar} Periode {periode} — No. {nomor_invoice}',
                'body' => "Yth. {nama},\n\nTagihan sewa kamar Anda di ASRI Boarding House telah terbit dengan rincian sebagai berikut:\n\n• Nomor Invoice: {nomor_invoice}\n• Kamar: {kode_kamar} ({kelas})\n• Periode: {periode}\n• Total Tagihan: {total}\n• Jatuh Tempo: {tanggal_jatuh_tempo}\n\nSilakan melakukan pembayaran secara online melalui tautan berikut:\n{link_bayar}\n\nTerima kasih atas kerja sama Anda.\n\nSalam hangat,\nPengelola ASRI Boarding House Tembalang",
                'available_placeholders' => ['nama', 'kode_kamar', 'kelas', 'periode', 'nomor_invoice', 'total', 'tanggal_jatuh_tempo', 'link_bayar'],
            ],

            // 2. Peringatan Ke Wali (Siklus 2)
            [
                'key' => 'guardian_warning',
                'name' => 'Peringatan Jatuh Tempo ke Wali',
                'channel' => NotificationChannel::WhatsApp,
                'subject' => null,
                'body' => "Yth. Bapak/Ibu {nama_wali},\nKami informasikan tagihan sewa {nama} kamar {kode_kamar} periode {periode} sebesar {total} telah melewati jatuh tempo {tanggal_jatuh_tempo}.\nIni adalah PERINGATAN. Keterlambatan berikutnya akan dikenakan denda 10%.\nTerima kasih — ASRI BOARDING HOUSE",
                'available_placeholders' => ['nama_wali', 'nama', 'kode_kamar', 'periode', 'total', 'tanggal_jatuh_tempo', 'link_bayar'],
            ],
            [
                'key' => 'guardian_warning',
                'name' => 'Surat Peringatan Tagihan Sewa ke Wali',
                'channel' => NotificationChannel::Email,
                'subject' => 'Pemberitahuan Keterlambatan Tagihan Sewa {nama} — Kamar {kode_kamar}',
                'body' => "Yth. Bapak/Ibu {nama_wali},\n\nBersama surat ini kami informasikan bahwa tagihan sewa atas nama putra/putri Bapak/Ibu, {nama}, untuk kamar {kode_kamar} periode {periode} dengan nominal {total} belum kami terima hingga batas tanggal {tanggal_jatuh_tempo}.\n\nPemberitahuan ini merupakan peringatan tahap kedua tanpa denda. Apabila pembayaran belum diselesaikan, siklus berikutnya akan diberlakukan ketentuan denda 10% sesuai peraturan sewa.\n\nPembayaran dapat diselesaikan secara langsung melalui:\n{link_bayar}\n\nHormat kami,\nPengelola ASRI Boarding House",
                'available_placeholders' => ['nama_wali', 'nama', 'kode_kamar', 'periode', 'total', 'tanggal_jatuh_tempo', 'link_bayar'],
            ],

            // 3. Denda ke Wali (Siklus 3+ / Mingguan)
            [
                'key' => 'guardian_penalty',
                'name' => 'Pemberitahuan Denda ke Wali',
                'channel' => NotificationChannel::WhatsApp,
                'subject' => null,
                'body' => "Yth. Bapak/Ibu {nama_wali},\nTagihan sewa {nama} kamar {kode_kamar} periode {periode} terlambat dibayar.\nDenda 10%   : {nominal_denda}\nTotal kini  : {total}\nMohon segera diselesaikan melalui {link_bayar} atau tunai ke penjaga kost.\n— ASRI BOARDING HOUSE",
                'available_placeholders' => ['nama_wali', 'nama', 'kode_kamar', 'periode', 'nominal_denda', 'total', 'link_bayar'],
            ],
            [
                'key' => 'guardian_penalty',
                'name' => 'Pemberitahuan Penerapan Denda Keterlambatan',
                'channel' => NotificationChannel::Email,
                'subject' => 'Pemberitahuan Denda Keterlambatan Sewa Kamar {kode_kamar} ({nama})',
                'body' => "Yth. Bapak/Ibu {nama_wali},\n\nKami menginformasikan bahwa tagihan sewa kamar {kode_kamar} atas nama {nama} untuk periode {periode} telah melewati batas toleransi keterlambatan.\n\nSesuai ketentuan, denda keterlambatan sebesar 10% ({nominal_denda}) telah ditambahkan, sehingga total tagihan saat ini adalah {total}.\n\nMohon kerja sama Bapak/Ibu untuk menyelesaikan tagihan tersebut melalui tautan:\n{link_bayar}\n\nTerima kasih,\nASRI BOARDING HOUSE",
                'available_placeholders' => ['nama_wali', 'nama', 'kode_kamar', 'periode', 'nominal_denda', 'total', 'link_bayar'],
            ],

            // 4. Reservasi Dibuat
            [
                'key' => 'reservation_created',
                'name' => 'Konfirmasi Reservasi Baru',
                'channel' => NotificationChannel::WhatsApp,
                'subject' => null,
                'body' => "Halo {nama}, reservasi Anda di ASRI Boarding House berhasil dibuat!\nNo. Reservasi: {kode_reservasi}\nKamar         : {kode_kamar} ({kelas})\nCheck-in      : {tanggal_checkin}\nBatas Bayar   : 24 Jam (kamar di-hold)\nLink Bayar    : {link_bayar}\nTerima kasih!",
                'available_placeholders' => ['nama', 'kode_reservasi', 'kode_kamar', 'kelas', 'tanggal_checkin', 'link_bayar'],
            ],
            [
                'key' => 'reservation_created',
                'name' => 'Nota Reservasi Kamar ASRI Boarding House',
                'channel' => NotificationChannel::Email,
                'subject' => 'Konfirmasi Reservasi Kamar {kode_kamar} — No. {kode_reservasi}',
                'body' => "Yth. {nama},\n\nTerima kasih telah memilih ASRI Boarding House. Reservasi kamar Anda telah berhasil dibuat dengan kode: {kode_reservasi}.\n\nKamar ditahan untuk Anda selama 24 jam. Segera selesaikan pembayaran DP/Lunas melalui:\n{link_bayar}\n\nSalam,\nASRI Boarding House",
                'available_placeholders' => ['nama', 'kode_reservasi', 'kode_kamar', 'kelas', 'tanggal_checkin', 'link_bayar'],
            ],

            // 5. Pembayaran Sukses
            [
                'key' => 'payment_success',
                'name' => 'Konfirmasi Pembayaran Sukses',
                'channel' => NotificationChannel::WhatsApp,
                'subject' => null,
                'body' => "Pembayaran Anda sebesar {total} untuk invoice {nomor_invoice} telah BERHASIL diverifikasi.\nKwitansi resmi telah diterbitkan.\nTerima kasih — ASRI BOARDING HOUSE",
                'available_placeholders' => ['nama', 'total', 'nomor_invoice', 'kode_kamar'],
            ],
            [
                'key' => 'payment_success',
                'name' => 'Kwitansi Pembayaran Sah',
                'channel' => NotificationChannel::Email,
                'subject' => 'Pembayaran Berhasil — Invoice {nomor_invoice}',
                'body' => "Yth. {nama},\n\nPembayaran sebesar {total} untuk invoice {nomor_invoice} telah berhasil kami terima. Terlampir bukti kwitansi sah pembayaran Anda.\n\nTerima kasih,\nASRI BOARDING HOUSE",
                'available_placeholders' => ['nama', 'total', 'nomor_invoice', 'kode_kamar'],
            ],
        ];

        foreach ($templates as $tpl) {
            NotificationTemplate::updateOrCreate(
                ['key' => $tpl['key'], 'channel' => $tpl['channel']],
                $tpl
            );
        }
    }
}
