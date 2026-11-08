<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import RupiahText from '@/Components/RupiahText.vue';
import { useDateId } from '@/Composables/useDateId';

const props = defineProps({
    lease: Object,
});

const { formatDate } = useDateId();

const dailyForm = useForm({
    extra_nights: 1,
});

const monthlyForm = useForm({
    reason: '',
});

const submitDaily = () => {
    dailyForm.post('/penyewa/extension/daily');
};

const toggleWeekly = () => {
    dailyForm.post('/penyewa/extension/weekly-toggle');
};

const submitMonthly = () => {
    monthlyForm.post('/penyewa/extension/monthly-stop');
};
</script>

<template>
    <Head title="Kelola Perpanjangan Sewa" />

    <TenantLayout title="Perpanjangan & Pengakhiran Sewa">
        <div class="space-y-6">
            <BentoCard v-if="!lease" title="Status Sewa">
                <p class="py-6 text-center text-xs text-ink-500">Anda belum memiliki kontrak sewa aktif.</p>
            </BentoCard>

            <template v-else>
                <!-- Overview Kontrak -->
                <BentoCard title="Informasi Kontrak Saat Ini">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                        <div class="p-3.5 rounded-xl border border-line-200 bg-slate-50/50">
                            <span class="text-ink-500 block">Kamar Dihuni</span>
                            <span class="num font-bold text-base text-forest-900 block mt-1">
                                Kamar {{ lease.room?.code }} ({{ lease.room?.room_type?.name }})
                            </span>
                        </div>
                        <div class="p-3.5 rounded-xl border border-line-200 bg-slate-50/50">
                            <span class="text-ink-500 block">Tipe Sewa</span>
                            <span class="capitalize font-bold text-base text-forest-900 block mt-1">
                                Sewa {{ lease.rent_type }}
                            </span>
                        </div>
                        <div class="p-3.5 rounded-xl border border-line-200 bg-slate-50/50">
                            <span class="text-ink-500 block">Dibayar Hingga</span>
                            <span class="num font-bold text-base text-forest-800 block mt-1">
                                {{ formatDate(lease.paid_until) }}
                            </span>
                        </div>
                    </div>
                </BentoCard>

                <!-- Case 1: Sewa Harian (Tambah Hari) -->
                <BentoCard
                    v-if="lease.rent_type === 'daily'"
                    title="Tambah Hari Menginap (Sewa Harian)"
                    subtitle="Perpanjangan sewa harian wajib dibayar di muka sebelum tanggal checkout (BR-07)"
                >
                    <form @submit.prevent="submitDaily" class="space-y-4 max-w-md">
                        <div>
                            <InputLabel value="Jumlah Tambahan Malam" />
                            <TextInput
                                type="number"
                                v-model="dailyForm.extra_nights"
                                min="1"
                                max="30"
                                required
                            />
                            <InputError :message="dailyForm.errors.extra_nights" />
                        </div>

                        <div class="p-3.5 rounded-xl bg-forest-50 text-forest-900 text-xs flex justify-between items-center">
                            <span>Estimasi Biaya Tambahan:</span>
                            <RupiahText
                                :amount="(lease.daily_rate || 0) * (dailyForm.extra_nights || 1)"
                                class="font-bold text-sm"
                            />
                        </div>

                        <PrimaryButton :loading="dailyForm.processing">
                            Buat Tagihan Tambah Hari
                        </PrimaryButton>
                    </form>
                </BentoCard>

                <!-- Case 2: Sewa Mingguan (Auto-renew Toggle) -->
                <BentoCard
                    v-else-if="lease.rent_type === 'weekly'"
                    title="Pengaturan Perpanjangan Mingguan"
                    subtitle="Sewa mingguan otomatis diperpanjang rolling 7 hari (BR-10)"
                >
                    <div class="max-w-xl space-y-4 text-xs text-ink-700">
                        <p>
                            Tagihan sewa mingguan diterbitkan otomatis pada H-2 sebelum periode habis.
                            Jika Anda tidak ingin memperpanjang, Anda dapat menghentikan perpanjangan sebelum jatuh tempo.
                        </p>

                        <div class="pt-2">
                            <DangerButton @click="toggleWeekly">
                                Hentikan Perpanjangan Sewa Mingguan
                            </DangerButton>
                        </div>
                    </div>
                </BentoCard>

                <!-- Case 3: Sewa Bulanan (Pengajuan Berhenti H-7) -->
                <BentoCard
                    v-else
                    title="Pengajuan Berhenti Sewa Bulanan"
                    subtitle="Pemberitahuan berhenti sewa bulanan minimal H-7 sebelum akhir bulan berjalan (BR-33)"
                >
                    <form @submit.prevent="submitMonthly" class="space-y-4 max-w-lg">
                        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs leading-relaxed">
                            ⚠️ Sesuai aturan kost (BR-33), pengajuan berhenti sewa bulanan wajib diajukan minimal 7 hari sebelum akhir bulan berjalan agar kamar dapat disiapkan untuk calon penghuni berikutnya.
                        </div>

                        <div>
                            <InputLabel value="Alasan Berhenti / Pindah (Wajib)" />
                            <textarea
                                v-model="monthlyForm.reason"
                                rows="3"
                                placeholder="Contoh: Lulus kuliah / selesai magang / pindah kota..."
                                class="w-full rounded-xl border border-line-200 p-3 text-xs text-ink-900 focus:border-forest-500 focus:outline-none"
                                required
                            ></textarea>
                            <InputError :message="monthlyForm.errors.reason" />
                        </div>

                        <DangerButton :loading="monthlyForm.processing">
                            Kirim Pengajuan Berhenti Sewa
                        </DangerButton>
                    </form>
                </BentoCard>
            </template>
        </div>
    </TenantLayout>
</template>

