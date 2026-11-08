<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    status: {
        type: String,
        default: null,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <AuthLayout title="Atur Ulang Kata Sandi">
        <Head title="Lupa Kata Sandi — Kost ASRI" />

        <div class="mb-4 text-xs leading-relaxed text-ink-600">
            Lupa kata sandi Anda? Masukkan alamat email yang terdaftar. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi baru Anda melalui email.
        </div>

        <div v-if="status" class="mb-4 rounded-xl bg-green-50 p-3 text-xs font-semibold text-green-800 border border-green-200">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="email" value="Alamat Email Terdaftar" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="nama@email.com"
                />
                <InputError class="mt-1.5" :message="form.errors.email" />
            </div>

            <div class="pt-2">
                <PrimaryButton
                    class="w-full justify-center py-2.5 text-sm"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Mengirim Tautan...</span>
                    <span v-else>Kirim Tautan Atur Ulang</span>
                </PrimaryButton>
            </div>
        </form>

        <div class="mt-6 text-center text-xs text-ink-600">
            Ingat kata sandi Anda?
            <Link
                :href="route('login')"
                class="font-bold text-forest-900 hover:underline underline-offset-2 ml-1"
            >
                Kembali ke halaman masuk
            </Link>
        </div>
    </AuthLayout>
</template>

