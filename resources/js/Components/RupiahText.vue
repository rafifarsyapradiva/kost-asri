<script setup>
import { computed } from 'vue';
import { useRupiah } from '@/Composables/useRupiah';

const props = defineProps({
    amount: {
        type: [Number, String],
        default: 0,
    },
    prefix: {
        type: Boolean,
        default: true,
    },
});

const { formatRupiah } = useRupiah();

const formatted = computed(() => {
    return formatRupiah(props.amount, props.prefix);
});
</script>

<template>
    <span class="num font-semibold">{{ formatted }}</span>
</template>

