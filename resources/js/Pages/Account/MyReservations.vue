<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import ReservationLayout from '@/Layouts/ReservationLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import RoomClassBadge from '@/Components/RoomClassBadge.vue';
import RupiahText from '@/Components/RupiahText.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { useDateId } from '@/Composables/useDateId';
import { useSnap } from '@/Composables/useSnap';

const props = defineProps({
    reservations: {
        type: Array,
        default: () => [],
    },
});

const { formatDate, formatDateTime, formatCountdown } = useDateId();
const { payWithSnap, isLoading: snapLoading } = useSnap();

// Timer tick for active 1s countdown
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

// Cancel Dialog State
const isCancelOpen = ref(false);
const cancellingReservation = ref(null);
const cancelReason = ref('Batal mencari kost di area Tembalang');
const isSubmittingCancel = ref(false);

const openCancelModal = (reservation) => {
    cancellingReservation.value = reservation;
    isCancelOpen.value = true;
};

const handleConfirmCancel = () => {
    if (!cancellingReservation.value) return;
    isSubmittingCancel.value = true;

    router.post(route('account.reservations.cancel', cancellingReservation.value.id), {
        reason: cancelReason.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            isSubmittingCancel.value = false;
            isCancelOpen.value = false;
            cancellingReservation.value = null;
        },
    });
};

// Handle Midtrans Snap Payment
const activePayingInvoiceId = ref(null);
const payErrorMessage = ref(null);

const handlePayNow = async (reservation) => {
    payErrorMessage.value = null;
    const unpaidInvoice = reservation.invoices?.find(
        (inv) => inv.status === 'unpaid' || inv.status === 'partial'
    ) || reservation.invoices?.[0];

    if (!unpaidInvoice) {
        payErrorMessage.value = 'Invoice tagihan tidak ditemukan. Silakan hubungi admin kost.';
        return;
    }

    activePayingInvoiceId.value = unpaidInvoice.id;

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
        payErrorMessage.value = err?.response?.data?.message || err.message || 'Terjadi kesalahan saat memproses pembayaran.';
    } finally {
        activePayingInvoiceId.value = null;
    }
};

const getRemainingBalance = (r) => {
    const total = Number(r.total_amount || 0);
    const dp = Number(r.dp_amount || 0);
    return Math.max(0, total - dp);
};
</script>

<template>
    <ReservationLayout :current-step="0">
        <Head title="Reservasi Saya — Kost ASRI" />

        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-line-200 pb-5">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-forest-900">
                        Reservasi Saya
                    </h1>
                    <p class="mt-1 text-xs text-ink-500">
                        Pantau status pemesanan kamar, batas countdown pembayaran, dan unduh dokumen nota resmi.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('reservation.step1')"
                        class="inline-flex items-center gap-2 rounded-xl bg-forest-900 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-forest-800 transition"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Pesan Kamar Baru</span>
                    </Link>
                </div>
            </div>

            <!-- Error Notification -->
            <div v-if="payErrorMessage" class="rounded-xl bg-red-50 p-4 border border-red-200 text-xs text-red-800 flex items-center justify-between">
                <span>{{ payErrorMessage }}</span>
                <button type="button" @click="payErrorMessage = null" class="font-bold text-red-600 hover:text-red-800">
                    &times;
                </button>
            </div>

            <!-- Empty State -->
            <div
                v-if="!reservations || reservations.length === 0"
                class="rounded-3xl border border-dashed border-line-300 bg-white p-12 text-center"
            >
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-forest-50 text-forest-800">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </div>
                <h3 class="mt-4 text-base font-bold text-ink-900">
                    Belum Ada Riwayat Reservasi
                </h3>
                <p class="mt-1 text-xs text-ink-500 max-w-sm mx-auto">
                    Anda belum memiliki reservasi aktif di Kost ASRI. Pilih kamar impian Anda sekarang dengan denah interaktif 2 lantai.
                </p>
                <div class="mt-6">
                    <Link
                        :href="route('reservation.step1')"
                        class="inline-flex items-center gap-2 rounded-xl bg-terracotta-500 px-5 py-2.5 text-xs font-bold text-ink-900 shadow-xs hover:bg-terracotta-400 transition"
                    >
                        <span>Mulai Reservasi Kamar</span>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </Link>
                </div>
            </div>

            <!-- Reservations List -->
            <div v-else class="space-y-5">
                <div
                    v-for="res in reservations"
                    :key="res.id"
                    class="rounded-3xl border border-line-200 bg-white p-6 shadow-xs hover:shadow-md transition"
                >
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-line-100 pb-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="num text-base font-black text-forest-900 tracking-tight">
                                {{ res.code }}
                            </span>
                            <StatusBadge :status="res.status" />
                            <RoomClassBadge
                                v-if="res.room?.room_type?.room_class"
                                :room-class="res.room.room_type.room_class"
                            />
                        </div>

                        <!-- 24h Countdown Alert if Pending Payment -->
                        <div
                            v-if="res.status === 'pending_payment'"
                            class="inline-flex items-center gap-2 rounded-xl bg-amber-50 px-3 py-1.5 border border-amber-200 text-xs font-bold text-amber-900"
                        >
                            <svg class="h-4 w-4 text-amber-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Batas Bayar:</span>
                            <span class="num font-black text-amber-700">
                                {{ formatCountdown(res.expires_at, nowTick) }}
                            </span>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 py-4 text-xs">
                        <!-- Detail Kamar -->
                        <div class="space-y-1.5">
                            <span class="font-bold uppercase tracking-wider text-ink-400 text-[10px]">Kamar & Lokasi</span>
                            <div class="text-sm font-bold text-ink-900">
                                Kamar {{ res.room?.code }} &bull; {{ res.room?.room_type?.name }}
                            </div>
                            <div class="text-ink-500">
                                Lantai {{ res.room?.floor }} &bull; Tembalang, Semarang
                            </div>
                        </div>

                        <!-- Periode Sewa -->
                        <div class="space-y-1.5">
                            <span class="font-bold uppercase tracking-wider text-ink-400 text-[10px]">Periode & Durasi</span>
                            <div class="text-sm font-bold text-ink-900 capitalize">
                                Sewa {{ res.rent_type }} ({{ res.duration_qty }} {{ res.rent_type === 'daily' ? 'Malam' : res.rent_type === 'weekly' ? 'Minggu' : 'Bulan' }})
                            </div>
                            <div class="text-ink-500 num">
                                {{ formatDate(res.check_in_date) }} s/d {{ formatDate(res.check_out_date) }}
                            </div>
                        </div>

                        <!-- Rincian Biaya -->
                        <div class="space-y-1.5 md:text-right">
                            <span class="font-bold uppercase tracking-wider text-ink-400 text-[10px]">Total Tagihan</span>
                            <div class="text-base font-black text-forest-900">
                                <RupiahText :amount="res.total_amount" />
                            </div>
                            <div v-if="res.payment_scheme === 'dp'" class="text-ink-500">
                                DP Dibayar: <RupiahText :amount="res.dp_amount" class="font-bold text-ink-700" />
                                <span v-if="getRemainingBalance(res) > 0" class="block text-amber-700 font-semibold mt-0.5">
                                    Sisa pelunasan saat check-in: <RupiahText :amount="getRemainingBalance(res)" />
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line-100 pt-4">
                        <div class="text-[11px] text-ink-500">
                            Dibuat: {{ formatDateTime(res.created_at) }}
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5">
                            <!-- Tombol Bayar Snap jika Pending -->
                            <PrimaryButton
                                v-if="res.status === 'pending_payment'"
                                @click="handlePayNow(res)"
                                :disabled="snapLoading && activePayingInvoiceId !== null"
                                class="text-xs py-2 px-3.5"
                            >
                                <span v-if="snapLoading && activePayingInvoiceId !== null">Menghubungkan Midtrans...</span>
                                <span v-else class="flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span>Bayar Sekarang (Snap)</span>
                                </span>
                            </PrimaryButton>

                            <!-- Tombol Unduh PDF jika sudah DP / Lunas -->
                            <a
                                v-if="res.status === 'paid' || res.status === 'dp_paid'"
                                :href="route('reservations.pdf', res.code)"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-line-200 bg-white px-3.5 py-2 text-xs font-bold text-forest-900 shadow-xs hover:bg-slate-50 transition"
                            >
                                <svg class="h-3.5 w-3.5 text-forest-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Unduh Nota (PDF)</span>
                            </a>

                            <!-- Tombol Batalkan jika Pending -->
                            <DangerButton
                                v-if="res.status === 'pending_payment'"
                                @click="openCancelModal(res)"
                                class="text-xs py-2 px-3"
                            >
                                Batalkan
                            </DangerButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm Cancel Dialog -->
        <ConfirmDialog
            :show="isCancelOpen"
            title="Batalkan Reservasi Kamar?"
            message="Apakah Anda yakin ingin membatalkan reservasi ini? Kamar yang ditahan akan segera dibuka kembali untuk calon penyewa lain."
            confirm-text="Ya, Batalkan Reservasi"
            cancel-text="Kembali"
            type="danger"
            @confirm="handleConfirmCancel"
            @close="isCancelOpen = false"
        />
    </ReservationLayout>
</template>

