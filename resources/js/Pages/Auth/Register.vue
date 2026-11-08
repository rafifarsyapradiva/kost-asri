<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthLayout title="Daftar Akun Calon Penyewa">
        <Head title="Daftar Akun — Kost ASRI" />

        <!-- Google OAuth Button -->
        <div class="mb-6">
            <a
                :href="route('auth.google.redirect')"
                class="flex w-full items-center justify-center gap-3 rounded-xl border border-line-200 bg-white px-4 py-2.5 text-sm font-semibold text-ink-700 shadow-xs hover:bg-slate-50 transition"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24">
                    <path
                        fill="#4285F4"
                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                    />
                    <path
                        fill="#34A853"
                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                    />
                    <path
                        fill="#FBBC05"
                        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"
                    />
                    <path
                        fill="#EA4335"
                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"
                    />
                </svg>
                <span>Daftar dengan Google</span>
            </a>

            <div class="relative my-6 flex items-center justify-center">
                <div class="w-full border-t border-line-200"></div>
                <span class="absolute bg-white px-3 text-xs uppercase tracking-wider text-ink-400">atau formulir manual</span>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="name" value="Nama Lengkap" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Nama sesuai KTP"
                />
                <InputError class="mt-1.5" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Alamat Email" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="nama@email.com"
                />
                <InputError class="mt-1.5" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="phone" value="Nomor WhatsApp" />
                <TextInput
                    id="phone"
                    type="tel"
                    class="mt-1 block w-full num"
                    v-model="form.phone"
                    required
                    autocomplete="tel"
                    placeholder="08xxxxxxxxxx atau 628xxxxxxxxxx"
                />
                <p class="mt-1 text-[11px] text-ink-500">
                    Notifikasi tagihan & kuitansi resmi akan dikirim via WhatsApp ke nomor ini.
                </p>
                <InputError class="mt-1.5" :message="form.errors.phone" />
            </div>

            <div>
                <InputLabel for="password" value="Kata Sandi" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                    placeholder="Minimal 8 karakter"
                />
                <InputError class="mt-1.5" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Konfirmasi Kata Sandi" />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Ulangi kata sandi"
                />
                <InputError class="mt-1.5" :message="form.errors.password_confirmation" />
            </div>

            <div class="pt-2">
                <PrimaryButton
                    class="w-full justify-center py-2.5 text-sm"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Mendaftarkan...</span>
                    <span v-else>Daftar Sekarang</span>
                </PrimaryButton>
            </div>
        </form>

        <div class="mt-6 text-center text-xs text-ink-600">
            Sudah memiliki akun?
            <Link
                :href="route('login')"
                class="font-bold text-forest-900 hover:underline underline-offset-2 ml-1"
            >
                Masuk di sini
            </Link>
        </div>
    </AuthLayout>
</template>

