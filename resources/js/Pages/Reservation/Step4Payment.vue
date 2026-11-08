<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import ReservationLayout from '@/Layouts/ReservationLayout.vue';
import RoomClassBadge from '@/Components/RoomClassBadge.vue';
import RupiahText from '@/Components/RupiahText.vue';
import PriceSummaryCard from '@/Components/PriceSummaryCard.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { useDateId } from '@/Composables/useDateId';
import { useRupiah } from '@/Composables/useRupiah';

const props = defineProps({
    draft: {
        type: Object,
        default: () => ({}),
    },
    room: {
        type: Object,
        required: true,
    },
    quote: {
        type: Object,
        required: true,
    },
    snapClientKey: {
        type: String,
        default: '',
    },
});

const { formatDate } = useDateId();
const { formatRupiah } = useRupiah();

const totalAmount = computed(() => Number(props.quote?.total || 0));
const minDpAmount = computed(() => Number(props.quote?.min_dp || Math.ceil(totalAmount.value * 0.3)));

const form = useForm({
    payment_scheme: 'dp', // 'dp' | 'full'
    preferred_method: 'midtrans', // 'midtrans' | 'cash'
    custom_dp: minDpAmount.value,
    notes: '',
});

const calculatedDp = computed(() => {
    if (form.payment_scheme === 'full') return totalAmount.value;
    const val = Number(form.custom_dp || minDpAmount.value);
    return Math.min(totalAmount.value, Math.max(minDpAmount.value, val));
});

const remainingAtCheckIn = computed(() => {
    if (form.payment_scheme === 'full') return 0;
    return Math.max(0, totalAmount.value - calculatedDp.value);
});

const roomClass = computed(() => props.room?.room_type?.room_class || 'standard');

const conflictError = ref(null);

const submitStep4 = () => {
    conflictError.value = null;

    form.transform((data) => ({
        ...data,
        custom_dp: data.payment_scheme === 'dp' ? calculatedDp.value : null,
    })).post(route('reservation.step4.store'), {
        onError: (errors) => {
            if (errors.room_id || errors.reservation) {
                conflictError.value = errors.room_id || errors.reservation;
            }
        },
    });
};
</script>

<template>
    <ReservationLayout :current-step="4">
        <Head title="Langkah 4: Pembayaran — Kost ASRI" />

        <div class="space-y-8">
            <!-- Header section -->
            <div class="text-center sm:text-left">
                <span class="text-xs font-bold uppercase tracking-wider text-forest-800">
                    Langkah 4 dari 5
                </span>
                <h1 class="mt-1 text-2xl sm:text-3xl font-extrabold text-forest-900 tracking-tight">
                    Skema & Metode Pembayaran
                </h1>
                <p class="mt-1.5 text-xs text-ink-600 max-w-2xl">
                    Pilih skema pembayaran Down Payment (DP ≥ 30%) atau Lunas 100%, serta metode pembayaran online otomatis via Midtrans Snap atau tunai di lokasi.
                </p>
            </div>

            <!-- Room Conflict or Anti-Hoarding Error Notification -->
            <div
                v-if="conflictError"
                class="rounded-3xl border border-red-300 bg-red-50 p-6 shadow-sm space-y-3"
            >
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-red-100 text-red-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-red-900">Perhatian: Terjadi Kendala Reservasi</h3>
                        <p class="text-xs text-red-800 mt-1 leading-relaxed">
                            {{ conflictError }}
                        </p>
                    </div>
                </div>

                <div class="pt-2 flex flex-wrap gap-3">
                    <Link
                        :href="route('reservation.step1')"
                        class="rounded-xl bg-red-800 px-4 py-2 text-xs font-bold text-white hover:bg-red-900 transition"
                    >
                        Pilih Kamar Lain
                    </Link>
                    <Link
                        :href="route('account.reservations')"
                        class="rounded-xl border border-red-300 bg-white px-4 py-2 text-xs font-bold text-red-900 hover:bg-red-50 transition"
                    >
                        Buka Reservasi Saya
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Left Form Column -->
                <div class="lg:col-span-7 space-y-6">
                    <!-- 1. Pilihan Skema Pembayaran (DP vs Lunas) -->
                    <div class="rounded-3xl border border-line-200 bg-white p-6 shadow-xs space-y-4">
                        <h2 class="text-sm font-bold text-forest-900">
                            1. Skema Pembayaran
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- DP Scheme -->
                            <div
                                @click="form.payment_scheme = 'dp'"
                                :class="[
                                    'cursor-pointer rounded-2xl border-2 p-5 transition space-y-2 relative',
                                    form.payment_scheme === 'dp'
                                        ? 'border-forest-900 bg-forest-50/40 shadow-xs ring-1 ring-forest-900'
                                        : 'border-line-200 hover:border-line-300'
                                ]"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-ink-900">Uang Muka (DP) &ge; 30%</span>
                                    <input
                                        type="radio"
                                        value="dp"
                                        v-model="form.payment_scheme"
                                        class="h-4 w-4 text-forest-900 focus:ring-forest-800 border-line-300"
                                    />
                                </div>
                                <div class="text-base font-black text-forest-900">
                                    <RupiahText :amount="minDpAmount" />
                                </div>
                                <p class="text-[11px] text-ink-500 leading-relaxed">
                                    Kunci kamar Anda sekarang. Sisa pelunasan (<RupiahText :amount="totalAmount - minDpAmount" />) dibayar saat check-in.
                                </p>
                            </div>

                            <!-- Full Scheme -->
                            <div
                                @click="form.payment_scheme = 'full'"
                                :class="[
                                    'cursor-pointer rounded-2xl border-2 p-5 transition space-y-2 relative',
                                    form.payment_scheme === 'full'
                                        ? 'border-forest-900 bg-forest-50/40 shadow-xs ring-1 ring-forest-900'
                                        : 'border-line-200 hover:border-line-300'
                                ]"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-ink-900">Lunas 100%</span>
                                    <input
                                        type="radio"
                                        value="full"
                                        v-model="form.payment_scheme"
                                        class="h-4 w-4 text-forest-900 focus:ring-forest-800 border-line-300"
                                    />
                                </div>
                                <div class="text-base font-black text-forest-900">
                                    <RupiahText :amount="totalAmount" />
                                </div>
                                <p class="text-[11px] text-ink-500 leading-relaxed">
                                    Selesaikan seluruh tagihan di awal. Proses check-in lebih cepat tanpa biaya tambahan saat tiba di kost.
                                </p>
                            </div>
                        </div>

                        <!-- Custom DP Slider/Input if DP selected -->
                        <div
                            v-if="form.payment_scheme === 'dp'"
                            class="rounded-2xl bg-slate-50 p-4 border border-line-200 space-y-3"
                        >
                            <div class="flex items-center justify-between text-xs">
                                <label for="custom_dp" class="font-bold text-ink-800">
                                    Nominal DP yang Ingin Dibayar:
                                </label>
                                <span class="num font-black text-forest-900 text-sm">
                                    {{ formatRupiah(calculatedDp) }}
                                </span>
                            </div>

                            <!-- Range Slider -->
                            <input
                                id="custom_dp"
                                type="range"
                                :min="minDpAmount"
                                :max="totalAmount"
                                :step="10000"
                                v-model.number="form.custom_dp"
                                class="w-full accent-forest-900 h-2 bg-slate-200 rounded-lg cursor-pointer"
                            />

                            <div class="flex items-center justify-between text-[11px] text-ink-400 num">
                                <span>Min: {{ formatRupiah(minDpAmount) }} (30%)</span>
                                <span>Maks: {{ formatRupiah(totalAmount) }} (100%)</span>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Pilihan Metode Pembayaran (Midtrans Snap vs Tunai) -->
                    <div class="rounded-3xl border border-line-200 bg-white p-6 shadow-xs space-y-4">
                        <h2 class="text-sm font-bold text-forest-900">
                            2. Metode Pembayaran
                        </h2>

                        <div class="space-y-3">
                            <!-- Midtrans Online -->
                            <div
                                @click="form.preferred_method = 'midtrans'"
                                :class="[
                                    'cursor-pointer rounded-2xl border-2 p-4 transition flex items-start gap-4',
                                    form.preferred_method === 'midtrans'
                                        ? 'border-forest-900 bg-forest-50/40 shadow-xs ring-1 ring-forest-900'
                                        : 'border-line-200 hover:border-line-300'
                                ]"
                            >
                                <input
                                    type="radio"
                                    value="midtrans"
                                    v-model="form.preferred_method"
                                    class="h-4 w-4 text-forest-900 focus:ring-forest-800 border-line-300 mt-1 shrink-0"
                                />
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-ink-900">Pembayaran Online Otomatis (Midtrans Snap)</span>
                                        <span class="rounded-full bg-green-100 text-green-800 text-[10px] font-bold px-2 py-0.5">
                                            Instan 24/7
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-ink-500 leading-relaxed">
                                        Mendukung QRIS (GoPay, OVO, ShopeePay), Transfer Virtual Account (BCA, Mandiri, BNI, BRI), Kartu Kredit, dan Gerai Minimarket. Pembayaran terkonfirmasi otomatis tanpa upload bukti transfer.
                                    </p>
                                </div>
                            </div>

                            <!-- Cash / Tunai -->
                            <div
                                @click="form.preferred_method = 'cash'"
                                :class="[
                                    'cursor-pointer rounded-2xl border-2 p-4 transition flex items-start gap-4',
                                    form.preferred_method === 'cash'
                                        ? 'border-forest-900 bg-forest-50/40 shadow-xs ring-1 ring-forest-900'
                                        : 'border-line-200 hover:border-line-300'
                                ]"
                            >
                                <input
                                    type="radio"
                                    value="cash"
                                    v-model="form.preferred_method"
                                    class="h-4 w-4 text-forest-900 focus:ring-forest-800 border-line-300 mt-1 shrink-0"
                                />
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-ink-900">Tunai (Cash) ke Penjaga Kost</span>
                                        <span class="rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5">
                                            Hold 24 Jam
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-ink-500 leading-relaxed">
                                        Bayar tunai di lokasi Kost ASRI (Tembalang). Kamar ditahan selama 24 jam menunggu pembayaran dan konfirmasi front-desk penjaga kost.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Catatan Khusus -->
                    <div class="rounded-3xl border border-line-200 bg-white p-6 shadow-xs space-y-3">
                        <label for="notes" class="text-xs font-bold text-forest-900 block">
                            Catatan Tambahan (Opsional)
                        </label>
                        <textarea
                            id="notes"
                            rows="2"
                            v-model="form.notes"
                            maxlength="500"
                            placeholder="Contoh: Perkiraan waktu tiba pukul 16:00 WIB, membawa 1 motor, dll."
                            class="w-full rounded-2xl border border-line-300 bg-white p-3 text-xs text-ink-900 focus:border-forest-800 focus:ring-1 focus:ring-forest-800 transition"
                        />
                        <InputError class="mt-1" :message="form.errors.notes" />
                    </div>

                    <!-- Navigation Action Buttons Desktop/Tablet -->
                    <div class="flex items-center justify-between pt-2">
                        <Link
                            :href="route('reservation.step3')"
                            class="inline-flex items-center gap-2 rounded-xl border border-line-200 bg-white px-5 py-2.5 text-xs font-bold text-ink-700 hover:bg-slate-50 transition"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span>Kembali ke Data Diri</span>
                        </Link>

                        <PrimaryButton
                            @click="submitStep4"
                            :disabled="form.processing"
                            class="px-7 py-3 text-xs font-bold"
                        >
                            <span v-if="form.processing">Membuat Reservasi...</span>
                            <span v-else class="flex items-center gap-2">
                                <span>Konfirmasi & Buat Reservasi</span>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </span>
                        </PrimaryButton>
                    </div>
                </div>

                <!-- Right Sticky Summary Column -->
                <div class="lg:col-span-5">
                    <PriceSummaryCard
                        :quote="quote"
                        :room="room"
                        :rent-type="draft.rent_type"
                        :duration-qty="draft.duration_qty"
                        :check-in-date="draft.check_in_date"
                        :prorata-option="draft.prorata_option"
                        :payment-scheme="form.payment_scheme"
                        :custom-dp="calculatedDp"
                    >
                        <template #action>
                            <PrimaryButton
                                @click="submitStep4"
                                :disabled="form.processing"
                                class="w-full justify-center py-3 text-xs font-bold"
                            >
                                <span v-if="form.processing">Membuat Reservasi...</span>
                                <span v-else>Konfirmasi & Buat Reservasi</span>
                            </PrimaryButton>
                        </template>

                        <template #mobile-action>
                            <PrimaryButton
                                @click="submitStep4"
                                :disabled="form.processing"
                                class="py-2.5 px-4 text-xs font-bold"
                            >
                                Bayar
                            </PrimaryButton>
                        </template>
                    </PriceSummaryCard>
                </div>
            </div>
        </div>
    </ReservationLayout>
</template>

