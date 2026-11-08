<script setup>
import { computed } from 'vue';

const props = defineProps({
    roomClass: {
        type: String,
        required: true,
    },
    abbreviated: {
        type: Boolean,
        default: false,
    },
});

const config = computed(() => {
    const rc = (props.roomClass || '').toLowerCase();
    switch (rc) {
        case 'vip':
            return {
                border: 'border-l-4 border-l-[#2563EB]',
                bg: 'bg-blue-100 text-blue-800',
                label: props.abbreviated ? 'VIP' : 'VIP',
            };
        case 'deluxe':
            return {
                border: 'border-l-4 border-l-[#C9A227]',
                bg: 'bg-amber-100 text-amber-900',
                label: props.abbreviated ? 'DLX' : 'Deluxe',
            };
        case 'standard':
        default:
            return {
                border: 'border-l-4 border-l-[#6B7280]',
                bg: 'bg-gray-100 text-gray-800',
                label: props.abbreviated ? 'STD' : 'Standard',
            };
    }
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center rounded-r-md px-2 py-0.5 text-xs font-bold tracking-wider uppercase shadow-xs',
            config.border,
            config.bg
        ]"
    >
        {{ config.label }}
    </span>
</template>

