<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: Number,
        default: 404,
    },
});

const title = computed(() => {
    return {
        503: 'Layanan Sedang Pemeliharaan',
        500: 'Terjadi Kesalahan Server',
        404: 'Halaman Tidak Ditemukan',
        403: 'Akses Dilarang',
        419: 'Sesi Telah Kedaluwarsa',
    }[props.status] || 'Terjadi Kesalahan';
});

const description = computed(() => {
    return {
        503: 'Platform sedang dalam pemeliharaan rutin. Kami akan segera kembali.',
        500: 'Terjadi kendala pada server kami. Silakan coba kembali nanti.',
        404: 'Halaman yang Anda tuju tidak ditemukan atau telah dipindahkan.',
        403: 'Maaf, Anda tidak memiliki akses untuk membuka halaman ini.',
        419: 'Sesi keamanan Anda telah berakhir. Silakan muat ulang halaman.',
    }[props.status] || 'Terjadi kesalahan yang tidak terduga.';
});
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-cream-50 font-sans text-ink-900 flex items-center justify-center p-6">
        <div class="max-w-md w-full bg-white rounded-3xl border border-line-200 p-8 shadow-sm text-center">
            <span class="num text-5xl font-black text-forest-900 block mb-2">
                {{ status }}
            </span>

            <h1 class="text-xl font-bold text-forest-900 mb-2">
                {{ title }}
            </h1>

            <p class="text-xs sm:text-sm text-ink-600 mb-8 leading-relaxed">
                {{ description }}
            </p>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <Link
                    href="/"
                    class="px-5 py-2.5 rounded-xl bg-forest-900 hover:bg-forest-800 text-white font-bold text-xs shadow-xs transition"
                >
                    Kembali ke Beranda
                </Link>
            </div>
        </div>
    </div>
</template>

