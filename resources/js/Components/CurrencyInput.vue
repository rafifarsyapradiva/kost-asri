<script setup>
import { computed } from 'vue';
import { useRupiah } from '@/Composables/useRupiah';

const props = defineProps({
    modelValue: {
        type: [Number, String],
        default: 0,
    },
    placeholder: {
        type: String,
        default: '0',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    required: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);
const { formatRupiah, parseRupiah } = useRupiah();

const displayValue = computed(() => {
    if (!props.modelValue && props.modelValue !== 0) return '';
    return formatRupiah(props.modelValue, false);
});

const handleInput = (event) => {
    const raw = event.target.value;
    const numeric = parseRupiah(raw);
    emit('update:modelValue', numeric);
};
</script>

<template>
    <div class="relative rounded-xl shadow-sm">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
            <span class="text-sm font-semibold text-ink-500">Rp</span>
        </div>
        <input
            type="text"
            :value="displayValue"
            :placeholder="placeholder"
            :disabled="disabled"
            :required="required"
            class="num w-full rounded-xl border border-line-200 bg-white py-2.5 pl-11 pr-3.5 text-sm text-ink-900 shadow-sm transition placeholder:text-ink-500 focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-500/20 disabled:bg-slate-50 disabled:text-ink-500"
            @input="handleInput"
        />
    </div>
</template>

