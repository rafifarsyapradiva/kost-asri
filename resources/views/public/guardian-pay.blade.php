<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Tagihan Sewa — ASRI Boarding House</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="{{ $snapUrl }}" data-client-key="{{ $clientKey }}"></script>
    <style>
        .num { font-variant-numeric: tabular-nums; }
    </style>
</head>
<body class="bg-stone-50 text-stone-800 antialiased min-h-screen flex flex-col justify-center items-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-stone-200 overflow-hidden">
        <div class="bg-[#1B4332] text-white p-6 text-center">
            <h1 class="text-xl font-bold tracking-tight">ASRI BOARDING HOUSE</h1>
            <p class="text-xs text-stone-300 mt-1">Portal Pembayaran Aman untuk Wali & Keluarga</p>
        </div>

        <div class="p-6 space-y-5">
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-xs text-amber-800 leading-relaxed">
                Tautan pembayaran ini bersifat rahasia dan berbatas waktu. Seluruh data pribadi penghuni telah disamarkan demi perlindungan privasi.
            </div>

            <div class="space-y-3 text-sm divide-y divide-stone-100">
                <div class="flex justify-between items-center pt-2">
                    <span class="text-stone-500">No. Invoice</span>
                    <span class="font-semibold num">{{ $invoice->number }}</span>
                </div>
                <div class="flex justify-between items-center pt-2">
                    <span class="text-stone-500">Penghuni</span>
                    <span class="font-medium">{{ $maskedName }}</span>
                </div>
                <div class="flex justify-between items-center pt-2">
                    <span class="text-stone-500">Kamar</span>
                    <span class="font-medium num">{{ $roomCode }} ({{ $roomTypeName }})</span>
                </div>
                <div class="flex justify-between items-center pt-2">
                    <span class="text-stone-500">Jatuh Tempo</span>
                    <span class="font-medium num">{{ $invoice->due_date?->format('d M Y') ?? '-' }}</span>
                </div>
                <div class="flex justify-between items-center pt-3 text-base">
                    <span class="font-bold text-stone-900">Total Tagihan</span>
                    <span class="font-extrabold text-[#1B4332] text-xl num">{{ $formattedTotal }}</span>
                </div>
            </div>

            @if ($invoice->status->value === 'paid')
                <div class="bg-emerald-50 text-emerald-800 p-4 rounded-xl text-center font-bold">
                    ✓ Tagihan ini sudah LUNAS. Terima kasih!
                </div>
            @elseif ($invoice->status->value === 'void')
                <div class="bg-stone-100 text-stone-600 p-4 rounded-xl text-center font-medium">
                    Tagihan ini telah dibatalkan (void). Silakan hubungi pengelola kost.
                </div>
            @else
                <button id="pay-button" onclick="payNow()" class="w-full bg-[#E07A5F] hover:bg-[#c96a51] text-white font-bold py-3 px-4 rounded-xl shadow transition duration-150 flex items-center justify-center gap-2">
                    <span>Bayar Sekarang via Midtrans</span>
                </button>
            @endif

            <p class="text-center text-xs text-stone-400">
                Mendukung QRIS, GoPay, ShopeePay, Virtual Account BCA, Mandiri, BNI, BRI, & Kartu Kredit.
            </p>
        </div>
    </div>

    <script>
        async function payNow() {
            const btn = document.getElementById('pay-button');
            btn.disabled = true;
            btn.innerText = 'Memuat sistem pembayaran...';

            try {
                const response = await fetch("{{ URL::temporarySignedRoute('guardian.snap-token', now()->addHour(), ['invoice' => $invoice->id]) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (data.snap_token) {
                    window.snap.pay(data.snap_token, {
                        onSuccess: function(result) {
                            window.location.reload();
                        },
                        onPending: function(result) {
                            window.location.reload();
                        },
                        onError: function(result) {
                            alert('Pembayaran gagal atau dibatalkan.');
                            btn.disabled = false;
                            btn.innerText = 'Bayar Sekarang via Midtrans';
                        },
                        onClose: function() {
                            btn.disabled = false;
                            btn.innerText = 'Bayar Sekarang via Midtrans';
                        }
                    });
                } else {
                    alert('Gagal membuat token pembayaran.');
                    btn.disabled = false;
                    btn.innerText = 'Bayar Sekarang via Midtrans';
                }
            } catch (err) {
                alert('Terjadi kesalahan saat menghubungi server pembayaran.');
                btn.disabled = false;
                btn.innerText = 'Bayar Sekarang via Midtrans';
            }
        }
    </script>
</body>
</html>

