<script setup>
import { Link } from '@inertiajs/vue3';
import ToastContainer from '@/Components/ToastContainer.vue';
import Stepper from '@/Components/Stepper.vue';

defineProps({
    currentStep: {
        type: Number,
        default: 1,
    },
});
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

                <div class="flex items-center gap-3 text-xs font-semibold">
                    <Link
                        v-if="$page.props.auth?.user"
                        :href="route('account.reservations')"
                        class="hidden sm:inline-flex items-center gap-1.5 rounded-lg border border-line-200 bg-white px-3 py-1.5 text-ink-700 hover:bg-slate-50 transition"
                    >
                        <span>Reservasi Saya</span>
                    </Link>

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
            <div v-if="currentStep > 0" class="border-t border-line-100 bg-slate-50/50 py-3 px-4 sm:px-6 lg:px-8">
                <div class="max-w-4xl mx-auto">
                    <Stepper :current-step="currentStep" />
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-28 lg:pb-8">
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

