<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import ReservationLayout from '@/Layouts/ReservationLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import FileDropzone from '@/Components/FileDropzone.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import RoomClassBadge from '@/Components/RoomClassBadge.vue';
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
    user: {
        type: Object,
        default: () => null,
    },
    profile: {
        type: Object,
        default: () => null,
    },
});

const { formatDate } = useDateId();

const rentType = computed(() => props.draft.rent_type || 'monthly');
const isGuardianRequired = computed(() => ['monthly', 'weekly'].includes(rentType.value));

const form = useForm({
    guardian_name: props.draft.guardian_name || props.profile?.guardian_name || '',
    guardian_phone: props.draft.guardian_phone || props.profile?.guardian_phone || '',
    guardian_email: props.draft.guardian_email || props.profile?.guardian_email || '',
    guardian_relation: props.draft.guardian_relation || props.profile?.guardian_relation || 'Orang Tua',
    privacy_consent: false,
    nik: props.profile?.nik || '',
    ktp_photo: null,
    origin_address: props.profile?.origin_address || '',
    occupation: props.profile?.occupation || '',
    institution_name: props.profile?.institution_name || '',
    gender: props.profile?.gender || 'male',
    birth_date: props.profile?.birth_date ? props.profile.birth_date.split('T')[0] : '',
});

const submitStep3 = () => {
    form.post(route('reservation.step3.store'));
};
</script>

<template>
    <ReservationLayout :current-step="3">
        <Head title="Langkah 3: Data Diri & Wali — Kost ASRI" />

        <div class="space-y-8 max-w-4xl mx-auto">
            <!-- Header section -->
            <div class="text-center sm:text-left">
                <span class="text-xs font-bold uppercase tracking-wider text-forest-800">
                    Langkah 3 dari 5
                </span>
                <h1 class="mt-1 text-2xl sm:text-3xl font-extrabold text-forest-900 tracking-tight">
                    Data Diri & Penanggung Jawab (Wali)
                </h1>
                <p class="mt-1.5 text-xs text-ink-600 max-w-2xl">
                    Lengkapi identitas calon penghuni dan kontak penanggung jawab/wali untuk verifikasi keamanan dan kepatuhan UU PDP.
                </p>
            </div>

            <!-- Selected Room Summary Pill -->
            <div class="rounded-2xl border border-line-200 bg-white p-4 shadow-xs flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="num flex h-10 w-10 items-center justify-center rounded-xl bg-forest-900 text-white font-black text-sm">
                        {{ room.code }}
                    </span>
                    <div>
                        <div class="text-xs font-bold text-ink-900">
                            Kamar {{ room.code }} &bull; {{ room.room_type?.name }} (Lantai {{ room.floor }})
                        </div>
                        <div class="text-[11px] text-ink-500">
                            Sewa {{ rentType }} &bull; Masuk: <span class="font-bold text-forest-900 num">{{ formatDate(draft.check_in_date) }}</span>
                        </div>
                    </div>
                </div>
                <RoomClassBadge :room-class="room.room_type?.room_class" />
            </div>

            <!-- Guest Inline Login Alert (jika belum login) -->
            <div
                v-if="!user"
                class="rounded-3xl border border-amber-200 bg-amber-50/70 p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-amber-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h4 class="text-xs font-bold text-amber-900">Punya Akun Kost ASRI?</h4>
                        <p class="text-[11px] text-amber-800 mt-0.5">
                            Masuk untuk mengisi data diri otomatis dan mempermudah pelacakan invoice.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <a
                        :href="route('auth.google.redirect')"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-line-200 bg-white px-3 py-1.5 text-xs font-semibold text-ink-700 hover:bg-slate-50 transition shadow-xs"
                    >
                        <span>Google</span>
                    </a>
                    <Link
                        :href="route('login')"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-forest-900 px-3.5 py-1.5 text-xs font-bold text-white hover:bg-forest-800 transition shadow-xs"
                    >
                        <span>Masuk</span>
                    </Link>
                </div>
            </div>

            <form @submit.prevent="submitStep3" class="space-y-6">
                <!-- Section 1: Data Identitas Calon Penyewa -->
                <div class="rounded-3xl border border-line-200 bg-white p-6 shadow-xs space-y-4">
                    <h2 class="text-sm font-bold text-forest-900 border-b border-line-100 pb-3 flex items-center gap-2">
                        <span>1. Identitas Calon Penghuni</span>
                        <span v-if="user" class="rounded-full bg-green-100 text-green-800 text-[10px] font-bold px-2 py-0.5">
                            Terotentikasi: {{ user.name }}
                        </span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="nik" value="NIK (Nomor Induk Kependudukan 16 Digit)" />
                            <TextInput
                                id="nik"
                                type="text"
                                maxlength="16"
                                class="mt-1 block w-full num"
                                v-model="form.nik"
                                placeholder="33xxxxxxxxxxxxxx"
                            />
                            <p class="mt-1 text-[11px] text-ink-400">Terenkripsi aman sesuai UU PDP.</p>
                            <InputError class="mt-1.5" :message="form.errors.nik" />
                        </div>

                        <div>
                            <InputLabel for="gender" value="Jenis Kelamin" />
                            <div class="mt-2.5 flex items-center gap-6">
                                <label class="flex items-center gap-2 text-xs text-ink-700 cursor-pointer">
                                    <input
                                        type="radio"
                                        value="male"
                                        v-model="form.gender"
                                        class="h-4 w-4 text-forest-900 focus:ring-forest-800 border-line-300"
                                    />
                                    <span>Laki-laki</span>
                                </label>
                                <label class="flex items-center gap-2 text-xs text-ink-700 cursor-pointer">
                                    <input
                                        type="radio"
                                        value="female"
                                        v-model="form.gender"
                                        class="h-4 w-4 text-forest-900 focus:ring-forest-800 border-line-300"
                                    />
                                    <span>Perempuan</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <InputLabel for="occupation" value="Pekerjaan / Status" />
                            <TextInput
                                id="occupation"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.occupation"
                                placeholder="Mahasiswa / Karyawan / Wiraswasta"
                            />
                            <InputError class="mt-1.5" :message="form.errors.occupation" />
                        </div>

                        <div>
                            <InputLabel for="institution_name" value="Nama Kampus / Kantor" />
                            <TextInput
                                id="institution_name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.institution_name"
                                placeholder="Universitas Diponegoro / PT..."
                            />
                            <InputError class="mt-1.5" :message="form.errors.institution_name" />
                        </div>

                        <div class="sm:col-span-2">
                            <InputLabel for="origin_address" value="Alamat Asal Sesuai KTP" />
                            <textarea
                                id="origin_address"
                                rows="2"
                                v-model="form.origin_address"
                                class="mt-1 block w-full rounded-xl border border-line-300 bg-white p-3 text-xs text-ink-900 focus:border-forest-800 focus:ring-1 focus:ring-forest-800 transition"
                                placeholder="Nama jalan, RT/RW, Kelurahan, Kecamatan, Kota/Kabupaten, Provinsi"
                            />
                            <InputError class="mt-1.5" :message="form.errors.origin_address" />
                        </div>

                        <!-- Upload KTP Dropzone -->
                        <div class="sm:col-span-2">
                            <InputLabel value="Foto KTP Asli (Opsional saat reservasi, wajib saat check-in)" />
                            <div class="mt-1">
                                <FileDropzone
                                    v-model="form.ktp_photo"
                                    label="Pilih atau Tarik Foto KTP"
                                    help-text="Format JPG, PNG, atau WebP (Maks. 2 MB). Gambar dienkripsi di media privat."
                                />
                            </div>
                            <InputError class="mt-1.5" :message="form.errors.ktp_photo" />
                        </div>
                    </div>
                </div>

                <!-- Section 2: Data Wali / Penanggung Jawab (Wajib untuk Mingguan & Bulanan) -->
                <div class="rounded-3xl border border-line-200 bg-white p-6 shadow-xs space-y-4">
                    <div class="border-b border-line-100 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <h2 class="text-sm font-bold text-forest-900 flex items-center gap-2">
                            <span>2. Data Penanggung Jawab / Wali</span>
                            <span
                                :class="[
                                    'rounded-full text-[10px] font-bold px-2 py-0.5',
                                    isGuardianRequired ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-700'
                                ]"
                            >
                                {{ isGuardianRequired ? 'Wajib (Sewa Mingguan & Bulanan)' : 'Opsional (Sewa Harian)' }}
                            </span>
                        </h2>
                        <span class="text-[11px] text-ink-400">Aturan Bisnis BR-28</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="guardian_name" :value="'Nama Lengkap Wali' + (isGuardianRequired ? ' *' : '')" />
                            <TextInput
                                id="guardian_name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.guardian_name"
                                :required="isGuardianRequired"
                                placeholder="Nama orang tua atau wali"
                            />
                            <InputError class="mt-1.5" :message="form.errors.guardian_name" />
                        </div>

                        <div>
                            <InputLabel for="guardian_relation" :value="'Hubungan dengan Penghuni' + (isGuardianRequired ? ' *' : '')" />
                            <select
                                id="guardian_relation"
                                v-model="form.guardian_relation"
                                class="mt-1 block w-full rounded-xl border border-line-300 bg-white px-3.5 py-2.5 text-xs font-medium text-ink-900 focus:border-forest-800 focus:ring-1 focus:ring-forest-800 transition"
                            >
                                <option value="Orang Tua">Orang Tua (Ayah / Ibu)</option>
                                <option value="Saudara Kandung">Saudara Kandung (Kakak / Adik)</option>
                                <option value="Wali Resmi">Wali Resmi / Paman / Bibi</option>
                                <option value="Suami / Istri">Suami / Istri</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                            <InputError class="mt-1.5" :message="form.errors.guardian_relation" />
                        </div>

                        <div>
                            <InputLabel for="guardian_phone" :value="'Nomor WhatsApp Wali' + (isGuardianRequired ? ' *' : '')" />
                            <TextInput
                                id="guardian_phone"
                                type="tel"
                                class="mt-1 block w-full num"
                                v-model="form.guardian_phone"
                                :required="isGuardianRequired"
                                placeholder="08xxxxxxxxxx atau 628xxxxxxxxxx"
                            />
                            <p class="mt-1 text-[11px] text-ink-400">Hanya dihubungi pada kondisi darurat atau eskalasi tagihan.</p>
                            <InputError class="mt-1.5" :message="form.errors.guardian_phone" />
                        </div>

                        <div>
                            <InputLabel for="guardian_email" value="Alamat Email Wali (Opsional)" />
                            <TextInput
                                id="guardian_email"
                                type="email"
                                class="mt-1 block w-full"
                                v-model="form.guardian_email"
                                placeholder="wali@email.com"
                            />
                            <InputError class="mt-1.5" :message="form.errors.guardian_email" />
                        </div>
                    </div>
                </div>

                <!-- Section 3: Persetujuan Privasi UU PDP -->
                <div class="rounded-3xl border border-line-200 bg-white p-6 shadow-xs space-y-4">
                    <h2 class="text-sm font-bold text-forest-900 border-b border-line-100 pb-3">
                        3. Persetujuan & Kepatuhan UU PDP
                    </h2>

                    <div class="flex items-start gap-3">
                        <input
                            id="privacy_consent"
                            type="checkbox"
                            v-model="form.privacy_consent"
                            required
                            class="h-4 w-4 rounded border-line-300 text-forest-900 focus:ring-forest-800 mt-0.5 shrink-0"
                        />
                        <label for="privacy_consent" class="text-xs text-ink-700 leading-relaxed cursor-pointer">
                            Saya menyatakan bahwa seluruh data yang diisi adalah benar dan sah. Saya memberikan izin kepada pengelola <strong>Kost ASRI Boarding House</strong> untuk menyimpan dan memproses data identitas serta kontak penanggung jawab/wali guna keperluan administrasi hunian, keamanan, dan darurat sesuai Undang-Undang Perlindungan Data Pribadi (UU PDP No. 27 Tahun 2022). <span class="text-red-600 font-bold">*</span>
                        </label>
                    </div>
                    <InputError class="mt-1.5" :message="form.errors.privacy_consent" />
                </div>

                <!-- Navigation Action Buttons -->
                <div class="flex items-center justify-between pt-2">
                    <Link
                        :href="route('reservation.step2')"
                        class="inline-flex items-center gap-2 rounded-xl border border-line-200 bg-white px-5 py-2.5 text-xs font-bold text-ink-700 hover:bg-slate-50 transition"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Kembali ke Tipe Sewa</span>
                    </Link>

                    <PrimaryButton
                        type="submit"
                        :disabled="form.processing"
                        class="px-7 py-3 text-xs font-bold"
                    >
                        <span v-if="form.processing">Memvalidasi...</span>
                        <span v-else class="flex items-center gap-2">
                            <span>Lanjut ke Pembayaran</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </span>
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </ReservationLayout>
</template>

