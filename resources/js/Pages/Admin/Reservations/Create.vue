<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import RupiahText from '@/Components/RupiahText.vue';
import RoomClassBadge from '@/Components/RoomClassBadge.vue';
import { useRupiah } from '@/Composables/useRupiah';

const props = defineProps({
    available_rooms: {
        type: Array,
        default: () => [],
    },
    users: {
        type: Array,
        default: () => [],
    },
});

const { formatRupiah } = useRupiah();
const isGuestNew = ref(true);

const form = useForm({
    user_id: null,
    name: '',
    phone: '',
    email: '',
    room_id: props.available_rooms[0]?.id || '',
    rent_type: 'monthly',
    check_in_date: new Date().toISOString().split('T')[0],
    duration_qty: 1,
    prorata_option: 'combined',
    payment_scheme: 'full',
    preferred_method: 'cash',
    source: 'admin',
    custom_dp: null,
    notes: 'Reservasi walk-in via admin front-desk',
    pay_now_cash: true,
});

const selectedRoom = computed(() => {
    return props.available_rooms.find((r) => r.id === form.room_id) || null;
});

const isMonthlyEntryAfterFirst = computed(() => {
    if (form.rent_type !== 'monthly' || !form.check_in_date) return false;
    const day = parseInt(form.check_in_date.split('-')[2], 10);
    return day > 1;
});

const quote = ref(null);
const isCalculating = ref(false);

const calculatePrice = async () => {
    if (!form.room_id || !form.check_in_date) return;
    isCalculating.value = true;
    try {
        const resp = await axios.post('/reservasi/hitung', {
            room_id: form.room_id,
            rent_type: form.rent_type,
            check_in_date: form.check_in_date,
            duration_qty: form.duration_qty,
            prorata_option: isMonthlyEntryAfterFirst.value ? form.prorata_option : null,
        });
        quote.value = resp.data;
        if (form.payment_scheme === 'dp') {
            form.custom_dp = resp.data.min_dp;
        }
    } catch (e) {
        quote.value = null;
    } finally {
        isCalculating.value = false;
    }
};

watch(
    () => [form.room_id, form.rent_type, form.check_in_date, form.duration_qty, form.prorata_option],
    () => {
        calculatePrice();
    },
    { immediate: true }
);

const submit = () => {
    if (isGuestNew.value) {
        form.user_id = null;
    } else {
        form.name = '';
        form.phone = '';
        form.email = '';
    }

    form.post('/admin/reservations', {
        onSuccess: () => {
            // Redirected by Inertia
        },
    });
};
</script>

<template>
    <Head title="Buat Reservasi Walk-In" />

    <AdminLayout title="Formulir Reservasi Manual Walk-In (Front-Desk)">
        <div class="space-y-6 max-w-4xl">
            <div class="flex items-center justify-between">
                <Link href="/admin/reservations" class="text-xs font-semibold text-forest-700 hover:text-forest-900">
                    ← Kembali ke Daftar Reservasi
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Section 1: Guest Information -->
                <BentoCard title="1. Identitas Tamu Walk-In">
                    <div class="space-y-4 text-xs">
                        <div class="flex items-center gap-4 border-b border-line-200 pb-3">
                            <label class="flex items-center gap-2 cursor-pointer font-medium text-ink-800">
                                <input type="radio" :value="true" v-model="isGuestNew" class="text-forest-900 focus:ring-forest-500" />
                                <span>Tamu Baru (Input Cepat)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer font-medium text-ink-800">
                                <input type="radio" :value="false" v-model="isGuestNew" class="text-forest-900 focus:ring-forest-500" />
                                <span>Penyewa Terdaftar (Akun Lama)</span>
                            </label>
                        </div>

                        <div v-if="isGuestNew" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <InputLabel value="Nama Lengkap Tamu" />
                                <TextInput v-model="form.name" placeholder="Misal: Dimas Ramadhan" required />
                                <InputError :message="form.errors.name" />
                            </div>
                            <div>
                                <InputLabel value="Nomor WhatsApp (Aktif)" />
                                <TextInput v-model="form.phone" placeholder="Contoh: 081234567890" required />
                                <InputError :message="form.errors.phone" />
                            </div>
                            <div>
                                <InputLabel value="Email (Opsional)" />
                                <TextInput type="email" v-model="form.email" placeholder="nama@email.com" />
                                <InputError :message="form.errors.email" />
                            </div>
                        </div>

                        <div v-else>
                            <InputLabel value="Pilih Akun Penyewa" />
                            <select v-model="form.user_id" class="w-full rounded-xl border-line-300 text-xs py-2 px-3">
                                <option value="">-- Pilih Akun Penyewa --</option>
                                <option v-for="u in users" :key="u.id" :value="u.id">
                                    {{ u.name }} ({{ u.phone }} - {{ u.email }})
                                </option>
                            </select>
                            <InputError :message="form.errors.user_id" />
                        </div>
                    </div>
                </BentoCard>

                <!-- Section 2: Room Selection & Rent Type -->
                <BentoCard title="2. Pemilihan Unit Kamar & Durasi Sewa">
                    <div class="space-y-4 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Pilih Kamar yang Tersedia" />
                                <select v-model="form.room_id" class="w-full rounded-xl border-line-300 text-xs py-2 px-3 bg-white" required>
                                    <option v-for="r in available_rooms" :key="r.id" :value="r.id">
                                        Kamar {{ r.code }} (Lantai {{ r.floor }} - {{ r.room_type?.name }})
                                    </option>
                                </select>
                                <InputError :message="form.errors.room_id" />
                            </div>

                            <div>
                                <InputLabel value="Tipe Sewa" />
                                <select v-model="form.rent_type" class="w-full rounded-xl border-line-300 text-xs py-2 px-3 bg-white" required>
                                    <option value="monthly">Sewa Bulanan</option>
                                    <option value="weekly">Sewa Mingguan (7 Hari)</option>
                                    <option value="daily">Sewa Harian</option>
                                </select>
                                <InputError :message="form.errors.rent_type" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Tanggal Rencana Masuk (Check-In)" />
                                <TextInput type="date" v-model="form.check_in_date" required />
                                <InputError :message="form.errors.check_in_date" />
                            </div>

                            <div>
                                <InputLabel :value="form.rent_type === 'daily' ? 'Durasi (Malam)' : (form.rent_type === 'weekly' ? 'Durasi (Minggu)' : 'Durasi (Bulan)')" />
                                <TextInput type="number" v-model="form.duration_qty" min="1" required />
                                <InputError :message="form.errors.duration_qty" />
                            </div>
                        </div>

                        <!-- Prorata choice if monthly entry > 1st -->
                        <div v-if="isMonthlyEntryAfterFirst" class="p-4 rounded-xl bg-forest-50 border border-forest-200 space-y-3">
                            <span class="font-bold text-forest-900 block">
                                Tamu masuk pada pertengahan bulan (bukan tanggal 1) — Pilih Skema Prorata:
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label :class="['p-3 rounded-xl border cursor-pointer transition', form.prorata_option === 'combined' ? 'border-forest-900 bg-white shadow-2xs' : 'border-line-200 bg-white/60']">
                                    <div class="flex items-center gap-2">
                                        <input type="radio" value="combined" v-model="form.prorata_option" class="text-forest-900 focus:ring-forest-500" />
                                        <span class="font-bold text-forest-900">Opsi 1: Gabung (Combined)</span>
                                    </div>
                                    <p class="text-[11px] text-ink-600 mt-1">Bayar sisa hari bulan ini + bulan depan penuh langsung. Tagihan berikutnya tgl 1 dua bulan lagi.</p>
                                </label>

                                <label :class="['p-3 rounded-xl border cursor-pointer transition', form.prorata_option === 'full_first' ? 'border-forest-900 bg-white shadow-2xs' : 'border-line-200 bg-white/60']">
                                    <div class="flex items-center gap-2">
                                        <input type="radio" value="full_first" v-model="form.prorata_option" class="text-forest-900 focus:ring-forest-500" />
                                        <span class="font-bold text-forest-900">Opsi 2: Penuh Dulu (Full First)</span>
                                    </div>
                                    <p class="text-[11px] text-ink-600 mt-1">Bayar 1 bulan penuh di muka. Penyesuaian prorata dilakukan pada invoice tgl 1 bulan berikutnya.</p>
                                </label>
                            </div>
                        </div>
                    </div>
                </BentoCard>

                <!-- Section 3: Cost Quote & Cash Payment -->
                <BentoCard title="3. Rincian Biaya Server & Kasir Pembayaran">
                    <div class="space-y-4 text-xs">
                        <div v-if="quote" class="p-4 rounded-xl bg-slate-50 border border-line-200 space-y-2">
                            <div class="flex justify-between items-center py-1 border-b border-line-100">
                                <span class="text-ink-500">Total Biaya Reservasi</span>
                                <RupiahText :amount="quote.total" class="font-extrabold text-forest-900 text-base" />
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-line-100">
                                <span class="text-ink-500">Batas Minimal DP (30%)</span>
                                <RupiahText :amount="quote.min_dp" class="font-bold text-amber-700" />
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="text-ink-500">Masa Sewa Terhitung Hingga</span>
                                <span class="font-semibold text-ink-800 num">{{ quote.billed_until }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Skema Pembayaran" />
                                <select v-model="form.payment_scheme" class="w-full rounded-xl border-line-300 text-xs py-2 px-3 bg-white">
                                    <option value="full">Lunas 100%</option>
                                    <option value="dp">Uang Muka (DP Minimal 30%)</option>
                                </select>
                            </div>

                            <div v-if="form.payment_scheme === 'dp'">
                                <InputLabel value="Nominal DP Custom (Rp)" />
                                <TextInput type="number" v-model="form.custom_dp" :min="quote?.min_dp" required />
                            </div>
                        </div>

                        <!-- Cash Checkbox -->
                        <div class="p-4 rounded-xl bg-green-50 border border-green-200 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <input type="checkbox" id="pay_now_cash" v-model="form.pay_now_cash" class="h-4 w-4 rounded border-line-300 text-forest-900 focus:ring-forest-500" />
                                <label for="pay_now_cash" class="font-bold text-green-950 cursor-pointer">
                                    Tamu membayar tunai (Cash) langsung di kasir front-desk sekarang
                                </label>
                            </div>
                            <span class="text-[11px] text-green-800 font-medium">Kwitansi lunas terbit otomatis</span>
                        </div>

                        <div>
                            <InputLabel value="Catatan Tambahan (Opsional)" />
                            <TextInput v-model="form.notes" placeholder="Catatan internal pengelola..." />
                        </div>
                    </div>
                </BentoCard>

                <div class="flex justify-end gap-3">
                    <Link
                        href="/admin/reservations"
                        class="px-5 py-2.5 rounded-xl border border-line-200 bg-white text-ink-700 font-semibold text-xs hover:bg-slate-50 transition"
                    >
                        Batal
                    </Link>
                    <PrimaryButton :disabled="form.processing || isCalculating">
                        Buat Reservasi & Proses
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

