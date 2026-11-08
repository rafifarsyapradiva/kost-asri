<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import ReservationLayout from '@/Layouts/ReservationLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import RoomClassBadge from '@/Components/RoomClassBadge.vue';
import RupiahText from '@/Components/RupiahText.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useDateId } from '@/Composables/useDateId';
import { useSnap } from '@/Composables/useSnap';

const props = defineProps({
    reservation: {
        type: Object,
        required: true,
    },
});

const { formatDate, formatDateTime, formatCountdown } = useDateId();
const { payWithSnap, isLoading: snapLoading } = useSnap();

// Timer tick for 1s active countdown
const nowTick = ref(Date.now());
let timerInterval = null;

onMounted(() => {
    timerInterval = setInterval(() => {
        nowTick.value = Date.now();
    }, 1000);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

// Snap payment trigger
const snapErrorMessage = ref(null);

const handlePayNow = async () => {
    snapErrorMessage.value = null;
    const unpaidInvoice = props.reservation.invoices?.find(
        (inv) => inv.status === 'unpaid' || inv.status === 'partial'
    ) || props.reservation.invoices?.[0];

    if (!unpaidInvoice) {
        snapErrorMessage.value = 'Invoice tagihan tidak ditemukan. Silakan hubungi admin kost.';
        return;
    }

    try {
        const response = await axios.post(`/invoices/${unpaidInvoice.id}/snap-token`);
        const token = response.data.snap_token;

        if (!token) {
            throw new Error('Gagal mendapatkan token pembayaran Midtrans.');
        }

        const result = await payWithSnap(token);
        if (result.status === 'success' || result.status === 'pending') {
            router.reload({ preserveScroll: true });
        }
    } catch (err) {
        snapErrorMessage.value = err?.response?.data?.message || err.message || 'Terjadi kesalahan saat menghubungkan ke Midtrans.';
    }
};

const remainingAtCheckIn = computed(() => {
    const total = Number(props.reservation.total_amount || 0);
    const dp = Number(props.reservation.dp_amount || 0);
    if (props.reservation.payment_scheme === 'full') return 0;
    return Math.max(0, total - dp);
});

const isPaid = computed(() => ['paid', 'dp_paid', 'checked_in'].includes(props.reservation.status));
</script>

<template>
    <ReservationLayout :current-step="5">
        <Head title="Langkah 5: Konfirmasi Reservasi — Kost ASRI" />

        <div class="space-y-8 max-w-3xl mx-auto">
            <!-- Success Status Header -->
            <div class="text-center space-y-3">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-green-100 text-green-700 shadow-sm">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <span class="text-xs font-bold uppercase tracking-wider text-forest-800">
                    Langkah 5 dari 5 &bull; Reservasi Berhasil Dibuat
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-forest-900 tracking-tight">
                    Konfirmasi Reservasi Kamar
                </h1>
                <p class="text-xs text-ink-600 max-w-lg mx-auto">
                    Kamar Anda telah berhasil ditahan di sistem Kost ASRI. Simpan kode reservasi di bawah untuk keperluan check-in atau pelunasan.
                </p>
            </div>

            <!-- Error Banner if Snap fails -->
            <div v-if="snapErrorMessage" class="rounded-2xl bg-red-50 p-4 border border-red-200 text-xs font-semibold text-red-800 flex items-center justify-between">
                <span>{{ snapErrorMessage }}</span>
                <button type="button" @click="snapErrorMessage = null" class="font-bold text-red-600 hover:text-red-800">
                    &times;
                </button>
            </div>

            <!-- Main Reservation Card -->
            <div class="rounded-3xl border border-line-200 bg-white p-6 sm:p-8 shadow-sm space-y-6">
                <!-- Reservation Code & Status Bar -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-line-100 pb-5">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-ink-400">Kode Reservasi Resmi</span>
                        <div class="num text-xl sm:text-2xl font-black text-forest-900 tracking-tight">
                            {{ reservation.code }}
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <StatusBadge :status="reservation.status" />
                        <RoomClassBadge
                            v-if="reservation.room?.room_type?.room_class"
                            :room-class="reservation.room.room_type.room_class"
                        />
                    </div>
                </div>

                <!-- 24h Countdown Alert if Pending -->
                <div
                    v-if="reservation.status === 'pending_payment'"
                    class="rounded-2xl bg-amber-50 p-4 border border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-amber-900"
                >
                    <div class="flex items-center gap-2.5">
                        <svg class="h-5 w-5 text-amber-700 shrink-0 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <span class="font-bold block">Selesaikan Pembayaran Sebelum:</span>
                            <span class="text-amber-800">{{ formatDateTime(reservation.expires_at) }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-[11px] font-semibold text-amber-700">Sisa Waktu:</span>
                        <span class="num text-base font-black text-amber-900 bg-white px-3 py-1 rounded-xl border border-amber-200 shadow-2xs">
                            {{ formatCountdown(reservation.expires_at, nowTick) }}
                        </span>
                    </div>
                </div>

                <!-- Details Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 py-2 text-xs">
                    <div class="space-y-1">
                        <span class="font-bold text-ink-400 uppercase tracking-wider text-[10px]">Kamar Pilihan</span>
                        <div class="text-sm font-bold text-ink-900">
                            Kamar {{ reservation.room?.code }} &bull; {{ reservation.room?.room_type?.name }}
                        </div>
                        <div class="text-ink-500">
                            Lantai {{ reservation.room?.floor }} &bull; Tembalang, Semarang
                        </div>
                    </div>

                    <div class="space-y-1">
                        <span class="font-bold text-ink-400 uppercase tracking-wider text-[10px]">Periode Menginap</span>
                        <div class="text-sm font-bold text-ink-900 capitalize">
                            Sewa {{ reservation.rent_type }} ({{ reservation.duration_qty }} {{ reservation.rent_type === 'daily' ? 'Malam' : reservation.rent_type === 'weekly' ? 'Minggu' : 'Bulan' }})
                        </div>
                        <div class="text-ink-500 num">
                            {{ formatDate(reservation.check_in_date) }} s/d {{ formatDate(reservation.check_out_date) }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <span class="font-bold text-ink-400 uppercase tracking-wider text-[10px]">Skema & Metode</span>
                        <div class="text-sm font-bold text-ink-900">
                            {{ reservation.payment_scheme === 'dp' ? 'Uang Muka (DP)' : 'Lunas 100%' }} &bull;
                            {{ reservation.preferred_method === 'cash' ? 'Tunai di Kost' : 'Midtrans Snap' }}
                        </div>
                        <div class="text-ink-500">
                            Dibuat: {{ formatDateTime(reservation.created_at) }}
                        </div>
                    </div>

                    <div class="space-y-1 sm:text-right">
                        <span class="font-bold text-ink-400 uppercase tracking-wider text-[10px]">Total Tagihan Reservasi</span>
                        <div class="text-lg font-black text-forest-900">
                            <RupiahText :amount="reservation.total_amount" />
                        </div>
                        <div v-if="reservation.payment_scheme === 'dp'" class="text-ink-500 text-[11px]">
                            DP: <RupiahText :amount="reservation.dp_amount" class="font-bold text-ink-700" />
                            <span v-if="remainingAtCheckIn > 0" class="block text-amber-700 font-semibold mt-0.5">
                                Sisa pelunasan saat check-in: <RupiahText :amount="remainingAtCheckIn" />
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons & PDF Download -->
                <div class="border-t border-line-100 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <!-- Tombol Bayar Snap jika masih Pending -->
                    <PrimaryButton
                        v-if="reservation.status === 'pending_payment' && reservation.preferred_method !== 'cash'"
                        @click="handlePayNow"
                        :disabled="snapLoading"
                        class="w-full sm:w-auto px-6 py-2.5 text-xs font-bold"
                    >
                        <span v-if="snapLoading">Menghubungkan Midtrans...</span>
                        <span v-else class="flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span>Bayar Sekarang (Midtrans Snap)</span>
                        </span>
                    </PrimaryButton>

                    <div v-else class="text-xs font-semibold text-green-800 flex items-center gap-2">
                        <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Reservasi Siap & Terdaftar</span>
                    </div>

                    <!-- Tombol Unduh PDF Nota Resmi -->
                    <a
                        :href="route('reservations.pdf', reservation.code)"
                        target="_blank"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl border border-line-200 bg-white px-5 py-2.5 text-xs font-bold text-forest-900 shadow-xs hover:bg-slate-50 transition"
                    >
                        <svg class="h-4 w-4 text-forest-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Unduh Nota Reservasi (PDF)</span>
                    </a>
                </div>
            </div>

            <!-- Panduan Check-in Card -->
            <div class="rounded-3xl border border-line-200 bg-white p-6 shadow-xs space-y-4">
                <h3 class="text-sm font-bold text-forest-900 flex items-center gap-2">
                    <svg class="h-4 w-4 text-forest-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Panduan Jam & Prosedur Check-in</span>
                </h3>

                <ul class="space-y-2 text-xs text-ink-600 leading-relaxed list-disc list-inside">
                    <li>Waktu check-in resmi dibuka mulai pukul <strong>14:00 WIB</strong> pada tanggal masuk.</li>
                    <li>Sebelum kunci diserahkan, reservasi <strong>wajib lunas 100%</strong> (sisa DP dapat dilunasi tunai atau QRIS di meja depan/front desk).</li>
                    <li>Harap membawa kartu identitas asli (KTP) untuk verifikasi data diri oleh pengelola kost.</li>
                    <li>Bila membutuhkan bantuan atau tiba di luar jam operasional, silakan hubungi kontak WhatsApp resmi Kost ASRI.</li>
                </ul>

                <div class="pt-3 border-t border-line-100 flex flex-wrap items-center justify-between gap-4">
                    <Link
                        :href="route('account.reservations')"
                        class="text-xs font-bold text-forest-800 hover:underline"
                    >
                        &larr; Lihat Seluruh Reservasi Saya
                    </Link>

                    <Link
                        href="/"
                        class="text-xs font-medium text-ink-500 hover:text-ink-800"
                    >
                        Kembali ke Halaman Utama
                    </Link>
                </div>
            </div>
        </div>
    </ReservationLayout>
</template>
