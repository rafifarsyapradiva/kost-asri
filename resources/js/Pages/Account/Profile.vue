<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import ReservationLayout from '@/Layouts/ReservationLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    profile: {
        type: Object,
        default: () => ({}),
    },
    masked_nik: {
        type: String,
        default: '',
    },
});

const page = usePage();
const isTenant = computed(() => props.user.role === 'penyewa');
const activeTab = ref('profile'); // 'profile' | 'guardian' | 'testimonial'

// Profile & Guardian Form
const profileForm = useForm({
    nik: '',
    ktp_photo: null,
    gender: props.profile?.gender || 'male',
    birth_date: props.profile?.birth_date || '',
    origin_address: props.profile?.origin_address || '',
    occupation: props.profile?.occupation || '',
    institution_name: props.profile?.institution_name || '',
    guardian_name: props.profile?.guardian_name || '',
    guardian_phone: props.profile?.guardian_phone || '',
    guardian_email: props.profile?.guardian_email || '',
    guardian_relation: props.profile?.guardian_relation || 'Orang Tua',
    emergency_notes: props.profile?.emergency_notes || '',
});

const submitProfile = () => {
    profileForm.post('/akun/profil', {
        preserveScroll: true,
        forceFormData: true,
    });
};

// Testimonial Form (for active tenants)
const testimonialForm = useForm({
    rating: 5,
    review: '',
    origin_info: props.profile?.institution_name || 'Penghuni Kost Asri',
});

const submitTestimonial = () => {
    testimonialForm.post('/penyewa/testimoni', {
        preserveScroll: true,
        onSuccess: () => {
            testimonialForm.reset('review');
        },
    });
};
</script>

<template>
    <Head title="Profil Saya & Data Wali" />

    <Component :is="isTenant ? TenantLayout : ReservationLayout" :title="'Profil & Data Wali'">
        <div class="space-y-6 max-w-4xl mx-auto">
            <!-- Header bar with tab navigation -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-forest-900">Pengaturan Profil Penghuni</h2>
                    <p class="text-xs text-ink-500">Kelola identitas diri, dokumen KTP terenkripsi, data kontak wali, dan ulasan sewa.</p>
                </div>

                <div class="inline-flex rounded-xl border border-line-200 bg-white p-1 shadow-2xs">
                    <button
                        type="button"
                        @click="activeTab = 'profile'"
                        :class="['rounded-lg px-3.5 py-1.5 text-xs font-semibold transition', activeTab === 'profile' ? 'bg-forest-900 text-white' : 'text-ink-600 hover:text-ink-900']"
                    >
                        Identitas & KTP
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'guardian'"
                        :class="['rounded-lg px-3.5 py-1.5 text-xs font-semibold transition', activeTab === 'guardian' ? 'bg-forest-900 text-white' : 'text-ink-600 hover:text-ink-900']"
                    >
                        Data Wali
                    </button>
                    <button
                        v-if="isTenant"
                        type="button"
                        @click="activeTab = 'testimonial'"
                        :class="['rounded-lg px-3.5 py-1.5 text-xs font-semibold transition', activeTab === 'testimonial' ? 'bg-forest-900 text-white' : 'text-ink-600 hover:text-ink-900']"
                    >
                        Kirim Testimoni
                    </button>
                </div>
            </div>

            <!-- Tab 1: Identitas & Dokumen KTP -->
            <form v-if="activeTab === 'profile'" @submit.prevent="submitProfile" class="space-y-6">
                <BentoCard title="1. Informasi Pribadi & Identitas">
                    <div class="space-y-4 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Nama Lengkap (Sesuai KTP)" />
                                <TextInput :model-value="user.name" disabled class="bg-slate-100 cursor-not-allowed" />
                            </div>
                            <div>
                                <InputLabel value="Nomor WhatsApp" />
                                <TextInput :model-value="user.phone" disabled class="bg-slate-100 cursor-not-allowed num" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Nomor Induk Kependudukan (NIK)" />
                                <TextInput
                                    v-model="profileForm.nik"
                                    :placeholder="masked_nik || 'Masukkan 16 digit NIK baru...'"
                                    maxlength="16"
                                    class="num"
                                />
                                <span v-if="masked_nik" class="text-[10px] text-forest-700 block mt-1">
                                    NIK tersimpan: <strong class="num">{{ masked_nik }}</strong>
                                </span>
                                <InputError :message="profileForm.errors.nik" />
                            </div>

                            <div>
                                <InputLabel value="Jenis Kelamin" />
                                <select v-model="profileForm.gender" class="w-full rounded-xl border-line-300 text-xs py-2 px-3 bg-white">
                                    <option value="male">Laki-laki</option>
                                    <option value="female">Perempuan</option>
                                </select>
                                <InputError :message="profileForm.errors.gender" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Tanggal Lahir" />
                                <TextInput type="date" v-model="profileForm.birth_date" />
                                <InputError :message="profileForm.errors.birth_date" />
                            </div>

                            <div>
                                <InputLabel value="Pekerjaan / Mahasiswa" />
                                <TextInput v-model="profileForm.occupation" placeholder="Misal: Mahasiswa / Karyawan" />
                                <InputError :message="profileForm.errors.occupation" />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Asal Universitas / Instansi Kantor" />
                            <TextInput v-model="profileForm.institution_name" placeholder="Contoh: Universitas Diponegoro (Undip)" />
                            <InputError :message="profileForm.errors.institution_name" />
                        </div>

                        <div>
                            <InputLabel value="Alamat Asal Asli (Sesuai KTP)" />
                            <textarea
                                v-model="profileForm.origin_address"
                                rows="2"
                                class="w-full rounded-xl border-line-300 text-xs p-3 focus:ring-forest-500 focus:border-forest-500"
                                placeholder="Alamat lengkap kota asal..."
                            ></textarea>
                            <InputError :message="profileForm.errors.origin_address" />
                        </div>

                        <!-- KTP Upload -->
                        <div class="p-4 rounded-xl border border-line-200 bg-slate-50 space-y-2">
                            <InputLabel value="Unggah Foto KTP Asli (Terenkripsi Aman)" />
                            <p class="text-[11px] text-ink-500">Berkas gambar akan di-encode ulang menjadi format WebP beresolusi optimal dan disimpan di storage privat (UU PDP).</p>
                            <input
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                @change="(e) => profileForm.ktp_photo = e.target.files[0]"
                                class="w-full text-xs text-ink-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-forest-50 file:text-forest-900 hover:file:bg-forest-100 cursor-pointer"
                            />
                            <InputError :message="profileForm.errors.ktp_photo" />
                        </div>
                    </div>
                </BentoCard>

                <div class="flex justify-end pt-2">
                    <PrimaryButton :disabled="profileForm.processing">
                        Simpan Identitas Diri
                    </PrimaryButton>
                </div>
            </form>

            <!-- Tab 2: Data Wali -->
            <form v-else-if="activeTab === 'guardian'" @submit.prevent="submitProfile" class="space-y-6">
                <BentoCard title="2. Kontak Orang Tua / Wali (Wajib untuk Mingguan & Bulanan)">
                    <div class="space-y-4 text-xs">
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-900 text-[11px] leading-relaxed">
                            Data kontak wali diperlukan untuk konfirmasi darurat, pengiriman salinan tagihan sewa (Signed URL), dan eskalasi jatuh tempo sewa.
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Nama Lengkap Wali" />
                                <TextInput v-model="profileForm.guardian_name" placeholder="Misal: Bapak Gunawan" required />
                                <InputError :message="profileForm.errors.guardian_name" />
                            </div>

                            <div>
                                <InputLabel value="Hubungan Keluarga" />
                                <TextInput v-model="profileForm.guardian_relation" placeholder="Orang Tua / Paman / Kakak Kandung" required />
                                <InputError :message="profileForm.errors.guardian_relation" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Nomor WhatsApp Wali (Aktif)" />
                                <TextInput v-model="profileForm.guardian_phone" placeholder="Contoh: 081234567890" required class="num" />
                                <InputError :message="profileForm.errors.guardian_phone" />
                            </div>

                            <div>
                                <InputLabel value="Email Wali (Opsional)" />
                                <TextInput type="email" v-model="profileForm.guardian_email" placeholder="wali@email.com" />
                                <InputError :message="profileForm.errors.guardian_email" />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Catatan Khusus / Riwayat Medis Darurat (Opsional)" />
                            <textarea
                                v-model="profileForm.emergency_notes"
                                rows="2"
                                class="w-full rounded-xl border-line-300 text-xs p-3 focus:ring-forest-500 focus:border-forest-500"
                                placeholder="Misal: Memiliki riwayat asma / alergi..."
                            ></textarea>
                            <InputError :message="profileForm.errors.emergency_notes" />
                        </div>
                    </div>
                </BentoCard>

                <div class="flex justify-end pt-2">
                    <PrimaryButton :disabled="profileForm.processing">
                        Simpan Data Wali
                    </PrimaryButton>
                </div>
            </form>

            <!-- Tab 3: Testimoni Penghuni -->
            <form v-else-if="activeTab === 'testimonial'" @submit.prevent="submitTestimonial" class="space-y-6">
                <BentoCard title="Bagikan Pengalaman Tinggal di Kost ASRI">
                    <div class="space-y-4 text-xs">
                        <p class="text-ink-600 leading-relaxed">
                            Ulasan Anda sangat berarti bagi calon penghuni berikutnya dan membantu kami menjaga kenyamanan bersama di Kost ASRI Boarding House.
                        </p>

                        <!-- Star Rating Picker -->
                        <div>
                            <InputLabel value="Penilaian Bintang" />
                            <div class="flex items-center gap-2 mt-1">
                                <button
                                    type="button"
                                    v-for="star in 5"
                                    :key="star"
                                    @click="testimonialForm.rating = star"
                                    class="text-2xl transition hover:scale-110"
                                    :class="star <= testimonialForm.rating ? 'text-amber-500' : 'text-slate-300'"
                                >
                                    ★
                                </button>
                                <span class="text-xs font-bold text-forest-900 ml-2">
                                    {{ testimonialForm.rating }} dari 5 Bintang
                                </span>
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Instansi / Asal Kampus Anda" />
                            <TextInput v-model="testimonialForm.origin_info" placeholder="Misal: Mahasiswa Undip 2024" required />
                        </div>

                        <div>
                            <InputLabel value="Ulasan / Kesan & Pesan" />
                            <textarea
                                v-model="testimonialForm.review"
                                rows="4"
                                class="w-full rounded-xl border-line-300 text-xs p-3 focus:ring-forest-500 focus:border-forest-500 leading-relaxed"
                                placeholder="Ceritakan bagaimana kebersihan, fasilitas kamar, WiFi, dan kenyamanan lingkungan Kost Asri..."
                                required
                            ></textarea>
                        </div>
                    </div>
                </BentoCard>

                <div class="flex justify-end pt-2">
                    <PrimaryButton :disabled="testimonialForm.processing">
                        Kirimkan Testimoni
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Component>
</template>

