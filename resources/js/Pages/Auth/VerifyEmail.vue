<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    status: {
        type: String,
        default: null,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <AuthLayout title="Verifikasi Alamat Email">
        <Head title="Verifikasi Email — Kost ASRI" />

        <div class="mb-4 text-xs leading-relaxed text-ink-600">
            Terima kasih telah mendaftar! Sebelum melanjutkan, mohon verifikasi alamat email Anda dengan mengeklik tautan yang baru saja kami kirimkan. Jika Anda tidak menerima email tersebut, kami dapat mengirimkannya kembali.
        </div>

        <div
            v-if="verificationLinkSent"
            class="mb-4 rounded-xl bg-green-50 p-3 text-xs font-semibold text-green-800 border border-green-200"
        >
            Tautan verifikasi baru telah berhasil dikirim ke alamat email yang Anda daftarkan.
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div class="pt-2">
                <PrimaryButton
                    class="w-full justify-center py-2.5 text-sm"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Mengirim Ulang...</span>
                    <span v-else>Kirim Ulang Email Verifikasi</span>
                </PrimaryButton>
            </div>
        </form>

        <div class="mt-6 flex items-center justify-between text-xs">
            <Link
                :href="route('account.profile.complete')"
                class="font-medium text-forest-800 hover:text-forest-900 underline underline-offset-2"
            >
                Lengkapi Profil
            </Link>

            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="font-semibold text-red-600 hover:text-red-700 underline underline-offset-2"
            >
                Keluar
            </Link>
        </div>
    </AuthLayout>
</template>

