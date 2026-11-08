<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    settings: Object,
    credentials: Object,
});

const form = useForm({
    kost_name: props.settings.kost_name || 'ASRI Boarding House',
    penalty_percent: props.settings.penalty_percent || 10,
    dp_percent: props.settings.dp_percent || 30,
    due_date_monthly: props.settings.due_date_monthly || 10,
    daily_deposit: props.settings.daily_deposit || 50000,
});

const submit = () => {
    form.post('/admin/settings');
};
</script>

<template>
    <Head title="Pengaturan Sistem" />

    <AdminLayout title="Pengaturan Sistem & Kredensial API">
        <div class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Operational Parameters Form -->
                <BentoCard title="Parameter Operasional Kost">
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <InputLabel value="Nama Kost / Brand" />
                            <TextInput v-model="form.kost_name" />
                            <InputError :message="form.errors.kost_name" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Persentase DP Minimal (%)" />
                                <TextInput type="number" v-model="form.dp_percent" min="10" max="100" />
                                <InputError :message="form.errors.dp_percent" />
                            </div>
                            <div>
                                <InputLabel value="Denda Keterlambatan (%)" />
                                <TextInput type="number" v-model="form.penalty_percent" min="0" max="100" />
                                <InputError :message="form.errors.penalty_percent" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Tanggal Jatuh Tempo Bulanan" />
                                <TextInput type="number" v-model="form.due_date_monthly" min="1" max="28" />
                                <InputError :message="form.errors.due_date_monthly" />
                            </div>
                            <div>
                                <InputLabel value="Deposit Harian (Rp)" />
                                <TextInput type="number" v-model="form.daily_deposit" min="0" />
                                <InputError :message="form.errors.daily_deposit" />
                            </div>
                        </div>

                        <div class="pt-2">
                            <PrimaryButton :loading="form.processing">
                                Simpan Parameter
                            </PrimaryButton>
                        </div>
                    </form>
                </BentoCard>

                <!-- Masked Integration Credentials (SEC-024) -->
                <BentoCard
                    title="Status Kredensial Integrasi (Masked)"
                    subtitle="Kunci API pihak ketiga ditampilkan tersensor demi keamanan (NFR-02)"
                >
                    <div class="space-y-3.5 text-xs">
                        <div class="p-3.5 rounded-xl border border-line-200 bg-slate-50/50">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold text-forest-900">Midtrans Server Key</span>
                                <span class="num text-green-700 font-semibold bg-green-50 px-2 py-0.5 rounded border border-green-200">Terkoneksi</span>
                            </div>
                            <span class="num text-ink-600 font-mono text-[11px]">{{ credentials.midtrans_server_key }}</span>
                        </div>

                        <div class="p-3.5 rounded-xl border border-line-200 bg-slate-50/50">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold text-forest-900">Midtrans Client Key</span>
                                <span class="num text-green-700 font-semibold bg-green-50 px-2 py-0.5 rounded border border-green-200">Terkoneksi</span>
                            </div>
                            <span class="num text-ink-600 font-mono text-[11px]">{{ credentials.midtrans_client_key }}</span>
                        </div>

                        <div class="p-3.5 rounded-xl border border-line-200 bg-slate-50/50">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold text-forest-900">Fonnte WhatsApp Token</span>
                                <span class="num text-green-700 font-semibold bg-green-50 px-2 py-0.5 rounded border border-green-200">Terkoneksi</span>
                            </div>
                            <span class="num text-ink-600 font-mono text-[11px]">{{ credentials.fonnte_token }}</span>
                        </div>

                        <div class="p-3.5 rounded-xl border border-line-200 bg-slate-50/50">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold text-forest-900">Google OAuth Secret</span>
                                <span class="num text-green-700 font-semibold bg-green-50 px-2 py-0.5 rounded border border-green-200">Terkoneksi</span>
                            </div>
                            <span class="num text-ink-600 font-mono text-[11px]">{{ credentials.google_client_secret }}</span>
                        </div>
                    </div>
                </BentoCard>
            </div>
        </div>
    </AdminLayout>
</template>

