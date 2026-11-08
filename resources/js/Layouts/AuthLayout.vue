<script setup>
import { Link } from '@inertiajs/vue3';
import ToastContainer from '@/Components/ToastContainer.vue';

defineProps({
    title: {
        type: String,
        default: 'Masuk',
    },
});
</script>

<template>
    <div class="min-h-screen bg-cream-50 font-sans text-ink-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <ToastContainer />

        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <Link href="/" class="inline-block group">
                <div class="flex items-center justify-center gap-2">
                    <div class="h-10 w-10 rounded-2xl bg-forest-900 flex items-center justify-center text-cream-50 font-extrabold text-xl shadow-md group-hover:bg-forest-700 transition">
                        A
                    </div>
                    <span class="text-2xl font-bold tracking-tight text-forest-900">
                        KOST ASRI
                    </span>
                </div>
            </Link>
            <h2 class="mt-4 text-center text-xl font-bold tracking-tight text-ink-900">
                {{ title }}
            </h2>
            <p class="mt-1 text-center text-xs text-ink-500">
                Hunian Tenang & Nyaman 32 Kamar di Tembalang, Semarang
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
            <div class="rounded-3xl border border-line-200 bg-white px-6 py-8 shadow-sm sm:px-10">
                <slot />
            </div>

            <div class="mt-6 text-center text-xs text-ink-400">
                &copy; {{ new Date().getFullYear() }} ASRI BOARDING HOUSE. Seluruh hak cipta dilindungi.
            </div>
        </div>
    </div>
</template>

