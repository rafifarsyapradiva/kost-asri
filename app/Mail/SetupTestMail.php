<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SetupTestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Uji Koneksi Email — ASRI Boarding House',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>Tes koneksi email ASRI Boarding House berhasil dikirim pada ' . e(now()->translatedFormat('d F Y H:i')) . ' WIB.</p>',
        );
    }
}
