<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import ReservationLayout from '@/Layouts/ReservationLayout.vue';
import RoomClassBadge from '@/Components/RoomClassBadge.vue';
import RupiahText from '@/Components/RupiahText.vue';
import PriceSummaryCard from '@/Components/PriceSummaryCard.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useDateId } from '@/Composables/useDateId';

const props = defineProps({
    draft: {
        type: Object,
        default: () => ({}),
    },
    room: {
        type: Object,
        required: true,
    },
});

const { formatDate } = useDateId();

// Form State
const rentType = ref(props.draft.rent_type || 'monthly');
const checkInDate = ref(props.draft.check_in_date || new Date().toISOString().split('T')[0]);
const durationQty = ref(Number(props.draft.duration_qty || 1));
const prorataOption = ref(props.draft.prorata_option || 'combined');

// Is check-in date exactly the 1st of the month?
const isFirstDayOfMonth = computed(() => {
    if (!checkInDate.value) return false;
    const parts = checkInDate.value.split('-');
    return parseInt(parts[2], 10) === 1;
});

// Quote State
const liveQuote = ref(null);
const isCalculating = ref(false);
const errorMessage = ref(null);

const calculateQuote = async () => {
    isCalculating.value = true;
    errorMessage.value = null;

    try {
        const payload = {
            room_id: props.room.id,
            rent_type: rentType.value,
            check_in_date: checkInDate.value,
            duration_qty: durationQty.value,
            prorata_option: (rentType.value === 'monthly' && !isFirstDayOfMonth.value) ? prorataOption.value : null,
        };

        const response = await axios.post(route('reservation.calculate'), payload);
        liveQuote.value = response.data.quote;
    } catch (err) {
        errorMessage.value = err?.response?.data?.message || 'Gagal menghitung kalkulasi biaya. Periksa durasi dan tanggal sewa.';
    } finally {
        isCalculating.value = false;
    }
};

watch([rentType, checkInDate, durationQty, prorataOption], () => {
    calculateQuote();
});

onMounted(() => {
    calculateQuote();
});

const updateDuration = (delta) => {
    const next = durationQty.value + delta;
    if (next >= 1 && next <= 36) {
        durationQty.value = next;
    }
};

const isSubmitting = ref(false);

const submitStep2 = () => {
    isSubmitting.value = true;
    router.post(route('reservation.step2.store'), {
        rent_type: rentType.value,
        check_in_date: checkInDate.value,
        duration_qty: durationQty.value,
        prorata_option: (rentType.value === 'monthly' && !isFirstDayOfMonth.value) ? prorataOption.value : null,
    }, {
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <ReservationLayout :current-step="2">
        <Head title="Langkah 2: Tipe & Durasi Sewa — Kost ASRI" />

        <div class="space-y-8">
            <!-- Header section -->
            <div class="text-center sm:text-left">
                <span class="text-xs font-bold uppercase tracking-wider text-forest-800">
                    Langkah 2 dari 5
                </span>
                <h1 class="mt-1 text-2xl sm:text-3xl font-extrabold text-forest-900 tracking-tight">
                    Tipe & Durasi Sewa
                </h1>
                <p class="mt-1.5 text-xs text-ink-600 max-w-2xl">
                    Pilih skema sewa Harian, Mingguan, atau Bulanan. Sistem menghitung rincian prorata secara transparan dan adil.
                </p>
            </div>

            <!-- Error Banner -->
            <div v-if="errorMessage" class="rounded-2xl bg-red-50 p-4 border border-red-200 text-xs font-semibold text-red-800 flex items-center justify-between">
                <span>{{ errorMessage }}</span>
                <button type="button" @click="errorMessage = null" class="font-bold text-red-600 hover:text-red-800">
                    &times;
                </button>
            </div>

            <!-- 2-Column Grid: Form on Left, Sticky Summary on Right -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <div class="lg:col-span-7 space-y-6">
                    <!-- Selected Room Card Info -->
                    <div class="rounded-3xl border border-line-200 bg-white p-5 shadow-xs flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-forest-900 text-white font-black text-lg shadow-xs">
                                {{ room.code }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-ink-900">Kamar {{ room.code }} &bull; {{ room.room_type?.name }}</span>
                                    <RoomClassBadge :room-class="room.room_type?.room_class" />
                                </div>
                                <div class="text-xs text-ink-500">
                                    Lantai {{ room.floor }} &bull; Tanggal Masuk: <span class="font-bold text-forest-900 num">{{ formatDate(checkInDate) }}</span>
                                </div>
                            </div>
                        </div>

                        <Link
                            :href="route('reservation.step1')"
                            class="text-xs font-bold text-forest-800 hover:underline shrink-0"
                        >
                            Ubah Kamar
                        </Link>
                    </div>

                    <!-- 1. Pilihan Tipe Sewa (Cards) -->
                    <div class="rounded-3xl border border-line-200 bg-white p-6 shadow-xs space-y-4">
                        <h2 class="text-sm font-bold text-forest-900">
                            Pilih Tipe Sewa
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <!-- Bulanan -->
                            <button
                                type="button"
                                @click="rentType = 'monthly'; durationQty = 1"
                                :class="[
                                    'rounded-2xl border-2 p-4 text-left transition relative flex flex-col justify-between',
                                    rentType === 'monthly'
                                        ? 'border-forest-900 bg-forest-50/40 shadow-xs ring-1 ring-forest-900'
                                        : 'border-line-200 bg-white hover:border-line-300'
                                ]"
                            >
                                <span class="absolute top-2.5 right-2.5 rounded-full bg-forest-100 text-forest-900 text-[10px] font-extrabold px-2 py-0.5">
                                    Populer
                                </span>
                                <div>
                                    <div class="text-xs font-bold uppercase tracking-wider text-ink-500">Sewa Bulanan</div>
                                    <div class="mt-1 text-sm font-black text-forest-900">
                                        <RupiahText :amount="room.room_type?.monthly_rate" />
                                    </div>
                                    <div class="text-[10px] text-ink-400">/ bulan (siklus tgl 1)</div>
                                </div>
                                <div class="mt-3 text-[11px] text-ink-500">
                                    Cocok untuk mahasiswa & pekerja jangka panjang.
                                </div>
                            </button>

                            <!-- Mingguan -->
                            <button
                                type="button"
                                @click="rentType = 'weekly'; durationQty = 1"
                                :class="[
                                    'rounded-2xl border-2 p-4 text-left transition flex flex-col justify-between',
                                    rentType === 'weekly'
                                        ? 'border-forest-900 bg-forest-50/40 shadow-xs ring-1 ring-forest-900'
                                        : 'border-line-200 bg-white hover:border-line-300'
                                ]"
                            >
                                <div>
                                    <div class="text-xs font-bold uppercase tracking-wider text-ink-500">Sewa Mingguan</div>
                                    <div class="mt-1 text-sm font-black text-forest-900">
                                        <RupiahText :amount="room.room_type?.weekly_rate" />
                                    </div>
                                    <div class="text-[10px] text-ink-400">/ 7 hari rolling</div>
                                </div>
                                <div class="mt-3 text-[11px] text-ink-500">
                                    Fleksibel mingguan dengan perpanjangan H-2.
                                </div>
                            </button>

                            <!-- Harian -->
                            <button
                                type="button"
                                @click="rentType = 'daily'; durationQty = 1"
                                :class="[
                                    'rounded-2xl border-2 p-4 text-left transition flex flex-col justify-between',
                                    rentType === 'daily'
                                        ? 'border-forest-900 bg-forest-50/40 shadow-xs ring-1 ring-forest-900'
                                        : 'border-line-200 bg-white hover:border-line-300'
                                ]"
                            >
                                <div>
                                    <div class="text-xs font-bold uppercase tracking-wider text-ink-500">Sewa Harian</div>
                                    <div class="mt-1 text-sm font-black text-forest-900">
                                        <RupiahText :amount="room.room_type?.daily_rate" />
                                    </div>
                                    <div class="text-[10px] text-ink-400">/ malam</div>
                                </div>
                                <div class="mt-3 text-[11px] text-ink-500">
                                    Transit, tes kampus, atau kunjungan singkat.
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- 2. Durasi Sewa -->
                    <div class="rounded-3xl border border-line-200 bg-white p-6 shadow-xs flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-ink-500">
                                Durasi Sewa
                            </h3>
                            <div class="mt-1 text-sm font-bold text-ink-900">
                                Berapa {{ rentType === 'daily' ? 'Malam' : rentType === 'weekly' ? 'Minggu' : 'Bulan' }} Rencana Menginap?
                            </div>
                        </div>

                        <!-- Stepper Counter Buttons -->
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="updateDuration(-1)"
                                :disabled="durationQty <= 1"
                                class="h-9 w-9 rounded-xl border border-line-300 bg-slate-50 flex items-center justify-center text-ink-700 font-black hover:bg-slate-100 disabled:opacity-40 transition"
                            >
                                &minus;
                            </button>
                            <span class="num text-base font-black text-forest-900 min-w-10 text-center">
                                {{ durationQty }}
                            </span>
                            <button
                                type="button"
                                @click="updateDuration(1)"
                                :disabled="durationQty >= 36"
                                class="h-9 w-9 rounded-xl border border-line-300 bg-slate-50 flex items-center justify-center text-ink-700 font-black hover:bg-slate-100 disabled:opacity-40 transition"
                            >
                                +
                            </button>
                        </div>
                    </div>

                    <!-- 3. Komparasi Visual Prorata (Jika Bulanan & Tidak Masuk Tgl 1) -->
                    <div
                        v-if="rentType === 'monthly' && !isFirstDayOfMonth"
                        class="rounded-3xl border border-line-200 bg-white p-6 shadow-xs space-y-4"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-extrabold text-forest-900">
                                    Pilihan Opsi Prorata Bulan Masuk
                                </h3>
                                <p class="text-xs text-ink-500">
                                    Karena Anda masuk di pertengahan bulan (tgl <span class="num font-bold">{{ checkInDate.split('-')[2] }}</span>), pilih bagaimana tagihan diselaraskan ke tanggal 1:
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                            <!-- Opsi 1: Gabung di Awal -->
                            <div
                                @click="prorataOption = 'combined'"
                                :class="[
                                    'cursor-pointer rounded-2xl border-2 p-5 transition space-y-3 relative',
                                    prorataOption === 'combined'
                                        ? 'border-forest-900 bg-forest-50/30 ring-2 ring-forest-900/20'
                                        : 'border-line-200 hover:border-line-300'
                                ]"
                            >
                                <span class="rounded-full bg-forest-900 text-white text-[10px] font-black px-2.5 py-0.5 inline-block">
                                    Rekomendasi (Opsi 1)
                                </span>
                                <div>
                                    <h4 class="text-sm font-bold text-forest-900">Gabung di Awal</h4>
                                    <p class="text-[11px] text-ink-600 mt-1 leading-relaxed">
                                        Prorata sisa hari bulan ini + 1 bulan penuh berikutnya dibayar saat reservasi.
                                    </p>
                                </div>

                                <div class="rounded-xl bg-white p-3 border border-line-100 space-y-1.5 text-[11px]">
                                    <div class="flex justify-between text-ink-600">
                                        <span>Bulan Masuk (Prorata):</span>
                                        <span class="font-bold text-ink-900">Dihitung harian</span>
                                    </div>
                                    <div class="flex justify-between text-ink-600">
                                        <span>Bulan Berikutnya:</span>
                                        <span class="font-bold text-green-700">Bebas Tagihan (Rp 0)</span>
                                    </div>
                                    <div class="flex justify-between text-forest-900 font-bold pt-1 border-t border-line-100">
                                        <span>Invoice Rutin Pertama:</span>
                                        <span>2 bulan berikutnya</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Opsi 2: Penuh Dulu -->
                            <div
                                @click="prorataOption = 'full_first'"
                                :class="[
                                    'cursor-pointer rounded-2xl border-2 p-5 transition space-y-3 relative',
                                    prorataOption === 'full_first'
                                        ? 'border-forest-900 bg-forest-50/30 ring-2 ring-forest-900/20'
                                        : 'border-line-200 hover:border-line-300'
                                ]"
                            >
                                <span class="rounded-full bg-slate-200 text-slate-800 text-[10px] font-black px-2.5 py-0.5 inline-block">
                                    Opsi 2
                                </span>
                                <div>
                                    <h4 class="text-sm font-bold text-forest-900">Penuh Dulu</h4>
                                    <p class="text-[11px] text-ink-600 mt-1 leading-relaxed">
                                        Bayar 1 bulan penuh di awal (menutup 30 hari dari tgl masuk).
                                    </p>
                                </div>

                                <div class="rounded-xl bg-white p-3 border border-line-100 space-y-1.5 text-[11px]">
                                    <div class="flex justify-between text-ink-600">
                                        <span>Tagihan Reservasi:</span>
                                        <span class="font-bold text-ink-900">1 Bulan Penuh</span>
                                    </div>
                                    <div class="flex justify-between text-ink-600">
                                        <span>Tanggal 1 Pertama:</span>
                                        <span class="font-bold text-slate-600">Dilewati (Rp 0)</span>
                                    </div>
                                    <div class="flex justify-between text-forest-900 font-bold pt-1 border-t border-line-100">
                                        <span>Invoice Tgl 1 Berikutnya:</span>
                                        <span>Kekurangan + 1 Bln</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notice Masuk Tanggal 1 -->
                    <div
                        v-if="rentType === 'monthly' && isFirstDayOfMonth"
                        class="rounded-2xl bg-forest-50 p-4 border border-forest-200 text-xs text-forest-900 flex items-center gap-3"
                    >
                        <svg class="h-5 w-5 text-forest-800 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Anda masuk pada tanggal 1. Tidak ada biaya prorata, tagihan reservasi tepat 1 bulan penuh normal.</span>
                    </div>

                    <!-- Navigation Action Buttons Desktop/Tablet -->
                    <div class="flex items-center justify-between pt-2">
                        <Link
                            :href="route('reservation.step1')"
                            class="inline-flex items-center gap-2 rounded-xl border border-line-200 bg-white px-4 py-2.5 text-xs font-bold text-ink-700 hover:bg-slate-50 transition"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span>Kembali ke Denah Kamar</span>
                        </Link>

                        <PrimaryButton
                            @click="submitStep2"
                            :disabled="isSubmitting || isCalculating"
                            class="px-6 py-2.5 text-xs font-bold"
                        >
                            <span v-if="isSubmitting">Menyimpan...</span>
                            <span v-else class="flex items-center gap-2">
                                <span>Lanjut ke Data Diri</span>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </span>
                        </PrimaryButton>
                    </div>
                </div>

                <!-- Right Column: Sticky Price Summary Card -->
                <div class="lg:col-span-5">
                    <PriceSummaryCard
                        :quote="liveQuote"
                        :room="room"
                        :rent-type="rentType"
                        :duration-qty="durationQty"
                        :check-in-date="checkInDate"
                        :prorata-option="prorataOption"
                        payment-scheme="full"
                    >
                        <template #action>
                            <PrimaryButton
                                @click="submitStep2"
                                :disabled="isSubmitting || isCalculating"
                                class="w-full justify-center py-3 text-xs font-bold"
                            >
                                <span v-if="isSubmitting">Menyimpan...</span>
                                <span v-else>Lanjut ke Data Diri</span>
                            </PrimaryButton>
                        </template>

                        <template #mobile-action>
                            <PrimaryButton
                                @click="submitStep2"
                                :disabled="isSubmitting || isCalculating"
                                class="py-2.5 px-4 text-xs font-bold"
                            >
                                Lanjut
                            </PrimaryButton>
                        </template>
                    </PriceSummaryCard>
                </div>
            </div>
        </div>
    </ReservationLayout>
</template>

