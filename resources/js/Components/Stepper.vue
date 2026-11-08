<script setup>
defineProps({
    currentStep: {
        type: Number,
        default: 1,
    },
    steps: {
        type: Array,
        default: () => [
            { number: 1, title: 'Pilih Kamar', description: 'Denah 2 Lantai' },
            { number: 2, title: 'Tipe Sewa', description: 'Kalkulator & Prorata' },
            { number: 3, title: 'Data Diri & Wali', description: 'Identitas & KTP' },
            { number: 4, title: 'Pembayaran', description: 'DP / Lunas (Snap)' },
            { number: 5, title: 'Selesai', description: 'Nota & Konfirmasi' },
        ],
    },
});
</script>

<template>
    <div class="w-full">
        <!-- Desktop Stepper (>= 640px) -->
        <nav aria-label="Progress Reservasi" class="hidden sm:block">
            <ol class="flex items-center justify-between">
                <li
                    v-for="(step, idx) in steps"
                    :key="step.number"
                    :class="[
                        'relative flex flex-1 items-center',
                        idx !== steps.length - 1 ? 'pr-4' : ''
                    ]"
                    :aria-current="currentStep === step.number ? 'step' : undefined"
                >
                    <div class="flex items-center gap-3">
                        <!-- Step Circle Node -->
                        <span
                            :class="[
                                'num flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-xs font-black transition',
                                currentStep === step.number
                                    ? 'bg-terracotta-500 text-white ring-4 ring-terracotta-500/20 shadow-xs'
                                    : currentStep > step.number
                                    ? 'bg-forest-900 text-white'
                                    : 'bg-slate-100 text-slate-500 border border-line-200'
                            ]"
                        >
                            <svg v-if="currentStep > step.number" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                            <span v-else>{{ step.number }}</span>
                        </span>

                        <!-- Step Labels -->
                        <div class="flex flex-col">
                            <span
                                :class="[
                                    'text-xs font-bold leading-tight',
                                    currentStep === step.number
                                        ? 'text-forest-900'
                                        : currentStep > step.number
                                        ? 'text-ink-800'
                                        : 'text-ink-400'
                                ]"
                            >
                                {{ step.title }}
                            </span>
                            <span v-if="step.description" class="text-[10px] text-ink-400">
                                {{ step.description }}
                            </span>
                        </div>
                    </div>

                    <!-- Connecting Line -->
                    <div
                        v-if="idx !== steps.length - 1"
                        class="ml-4 flex-1 h-0.5 rounded-full transition"
                        :class="currentStep > step.number ? 'bg-forest-900' : 'bg-line-200'"
                    />
                </li>
            </ol>
        </nav>

        <!-- Mobile Stepper (< 640px) -->
        <div class="sm:hidden rounded-2xl border border-line-200 bg-white p-3.5 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-forest-900">
                    Langkah <span class="num">{{ currentStep }}</span> dari <span class="num">{{ steps.length }}</span>:
                    <span class="ml-1 text-ink-700">{{ steps[currentStep - 1]?.title }}</span>
                </span>
                <span class="num text-[11px] font-semibold text-terracotta-600">
                    {{ Math.round((currentStep / steps.length) * 100) }}%
                </span>
            </div>
            <!-- Progress Bar -->
            <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                <div
                    class="h-full bg-terracotta-500 transition-all duration-300"
                    :style="{ width: `${(currentStep / steps.length) * 100}%` }"
                />
            </div>
        </div>
    </div>
</template>

