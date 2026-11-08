<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    phone: props.user.phone || '',
});

const submit = () => {
    form.post(route('account.profile.complete'));
};
</script>

<template>
    <AuthLayout title="Lengkapi Profil Akun">
        <Head title="Lengkapi Profil — Kost ASRI" />

        <div class="mb-5 rounded-2xl bg-amber-50/80 p-4 border border-amber-200/80 text-xs text-amber-900 leading-relaxed">
            <div class="flex items-start gap-2.5">
                <svg class="h-5 w-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <span class="font-bold block mb-0.5">Satu Langkah Lagi!</span>
                    Akun Google Anda <strong>{{ user.email }}</strong> berhasil terhubung. Mohon lengkapi nomor WhatsApp aktif Anda untuk pengiriman invoice resmi, tanda terima, dan konfirmasi reservasi kamar.
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="phone" value="Nomor WhatsApp Aktif" />
                <TextInput
                    id="phone"
                    type="tel"
                    class="mt-1 block w-full num"
                    v-model="form.phone"
                    required
                    autofocus
                    autocomplete="tel"
                    placeholder="08xxxxxxxxxx atau 628xxxxxxxxxx"
                />
                <p class="mt-1.5 text-[11px] text-ink-500">
                    Contoh: 081234567890. Nomor ini akan digunakan oleh Chatbot dan sistem notifikasi resmi Kost ASRI.
                </p>
                <InputError class="mt-1.5" :message="form.errors.phone" />
            </div>

            <div class="pt-2">
                <PrimaryButton
                    class="w-full justify-center py-2.5 text-sm"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Menyimpan...</span>
                    <span v-else>Simpan & Lanjutkan Reservasi</span>
                </PrimaryButton>
            </div>
        </form>
    </AuthLayout>
</template>

