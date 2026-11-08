<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        default: '',
    },
});

const config = computed(() => {
    const s = (props.status || '').toLowerCase();
    switch (s) {
        // Success
        case 'available':
            return {
                bg: 'bg-green-100 border-green-500 text-green-900',
                icon: 'check',
                label: props.label || 'Tersedia',
            };
        case 'paid':
            return {
                bg: 'bg-green-100 border-green-500 text-green-900',
                icon: 'check',
                label: props.label || 'Lunas',
            };
        case 'active':
            return {
                bg: 'bg-green-100 border-green-500 text-green-900',
                icon: 'check',
                label: props.label || 'Aktif',
            };
        case 'settled':
            return {
                bg: 'bg-green-100 border-green-500 text-green-900',
                icon: 'check',
                label: props.label || 'Lunas',
            };

        // Warning
        case 'booked':
            return {
                bg: 'bg-yellow-100 border-yellow-500 text-yellow-900',
                icon: 'clock',
                label: props.label || 'Dipesan',
            };
        case 'partial':
            return {
                bg: 'bg-yellow-100 border-yellow-500 text-yellow-900',
                icon: 'clock',
                label: props.label || 'Sebagian',
            };
        case 'dp_paid':
            return {
                bg: 'bg-yellow-100 border-yellow-500 text-yellow-900',
                icon: 'clock',
                label: props.label || 'DP Terbayar',
            };
        case 'pending':
        case 'pending_payment':
            return {
                bg: 'bg-yellow-100 border-yellow-500 text-yellow-900',
                icon: 'clock',
                label: props.label || 'Menunggu',
            };
        case 'ending':
            return {
                bg: 'bg-yellow-100 border-yellow-500 text-yellow-900',
                icon: 'clock',
                label: props.label || 'Segera Berakhir',
            };

        // Danger
        case 'occupied':
            return {
                bg: 'bg-red-100 border-red-500 text-red-900',
                icon: 'alert',
                label: props.label || 'Terisi',
            };
        case 'overdue':
            return {
                bg: 'bg-red-100 border-red-500 text-red-900',
                icon: 'alert',
                label: props.label || 'Terlambat',
            };
        case 'failed':
            return {
                bg: 'bg-red-100 border-red-500 text-red-900',
                icon: 'alert',
                label: props.label || 'Gagal',
            };
        case 'cancelled':
            return {
                bg: 'bg-red-100 border-red-500 text-red-900',
                icon: 'alert',
                label: props.label || 'Dibatalkan',
            };

        // Neutral
        case 'maintenance':
            return {
                bg: 'bg-slate-100 border-slate-500 text-slate-800',
                icon: 'wrench',
                label: props.label || 'Perbaikan',
            };
        case 'void':
            return {
                bg: 'bg-slate-100 border-slate-500 text-slate-800',
                icon: 'minus',
                label: props.label || 'Void',
            };
        case 'expired':
            return {
                bg: 'bg-slate-100 border-slate-500 text-slate-800',
                icon: 'minus',
                label: props.label || 'Kedaluwarsa',
            };
        case 'completed':
            return {
                bg: 'bg-slate-100 border-slate-500 text-slate-800',
                icon: 'check',
                label: props.label || 'Selesai',
            };
        default:
            return {
                bg: 'bg-slate-100 border-slate-400 text-slate-800',
                icon: 'minus',
                label: props.label || props.status,
            };
    }
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold shadow-xs',
            config.bg
        ]"
    >
        <!-- Check Icon -->
        <svg v-if="config.icon === 'check'" class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
        </svg>
        <!-- Clock Icon -->
        <svg v-else-if="config.icon === 'clock'" class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke-width="2.5" />
            <polyline points="12 6 12 12 16 14" stroke-width="2.5" />
        </svg>
        <!-- Alert Icon -->
        <svg v-else-if="config.icon === 'alert'" class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke-width="2.5" />
            <line x1="12" y1="8" x2="12" y2="12" stroke-width="2.5" />
            <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2.5" />
        </svg>
        <!-- Wrench Icon -->
        <svg v-else-if="config.icon === 'wrench'" class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z" />
        </svg>
        <!-- Minus Icon -->
        <svg v-else class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke-width="2.5" />
            <line x1="8" y1="12" x2="16" y2="12" stroke-width="2.5" />
        </svg>

        <span>{{ config.label }}</span>
    </span>
</template>

