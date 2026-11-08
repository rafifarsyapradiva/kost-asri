<script setup>
import { Link } from '@inertiajs/vue3';
import ToastContainer from '@/Components/ToastContainer.vue';

defineProps({
    currentStep: {
        type: Number,
        default: 1,
    },
});

const steps = [
    { number: 1, title: 'Pilih Kamar' },
    { number: 2, title: 'Tipe Sewa' },
    { number: 3, title: 'Data Diri & Wali' },
    { number: 4, title: 'Pembayaran' },
    { number: 5, title: 'Selesai' },
];
</script>

<template>
    <div class="min-h-screen bg-cream-50 font-sans text-ink-900 flex flex-col justify-between">
        <ToastContainer />

        <!-- Header -->
        <header class="sticky top-0 z-40 border-b border-line-200 bg-white/95 backdrop-blur-md">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-xl bg-forest-900 flex items-center justify-center text-white font-bold text-base shadow">
                        A
                    </div>
                    <span class="text-lg font-bold tracking-tight text-forest-900">
                        KOST ASRI
                    </span>
                </Link>

                <div class="flex items-center gap-4 text-xs font-semibold">
                    <span class="hidden sm:inline text-ink-500">Butuh bantuan?</span>
                    <a
                        href="https://wa.me/6281234567890?text=Halo%20Admin%20Kost%20ASRI,%20saya%20ingin%20bertanya%20tentang%20reservasi"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-line-200 bg-slate-50 px-3 py-1.5 text-forest-800 hover:bg-slate-100 transition"
                    >
                        <span>WhatsApp Kost</span>
                    </a>
                </div>
            </div>

            <!-- Stepper Progress Bar -->
            <div class="border-t border-line-100 bg-slate-50/50 py-2.5 px-4 overflow-x-auto">
                <div class="max-w-3xl mx-auto flex items-center justify-between min-w-[500px]">
                    <div
                        v-for="s in steps"
                        :key="s.number"
                        class="flex items-center gap-2"
                    >
                        <span
                            :class="[
                                'num flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold transition',
                                currentStep === s.number
                                    ? 'bg-terracotta-500 text-ink-900 ring-2 ring-terracotta-500/30'
                                    : currentStep > s.number
                                    ? 'bg-forest-900 text-white'
                                    : 'bg-slate-200 text-slate-600'
                            ]"
                        >
                            <svg v-if="currentStep > s.number" class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                            <span v-else>{{ s.number }}</span>
                        </span>
                        <span
                            :class="[
                                'text-xs font-medium',
                                currentStep === s.number
                                    ? 'font-bold text-forest-900'
                                    : currentStep > s.number
                                    ? 'text-forest-800'
                                    : 'text-slate-500'
                            ]"
                        >
                            {{ s.title }}
                        </span>
                        <div v-if="s.number < 5" class="h-0.5 w-8 bg-line-200 ml-2" />
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="border-t border-line-200 bg-white py-6 text-center text-xs text-ink-400">
            <div class="max-w-6xl mx-auto px-4">
                &copy; {{ new Date().getFullYear() }} ASRI BOARDING HOUSE. Seluruh hak cipta dilindungi.
            </div>
        </footer>
    </div>
</template>

