<script setup>
defineProps({
    value: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <label class="block text-xs font-semibold uppercase tracking-wider text-ink-700 mb-1.5">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
        <span v-if="required" class="text-danger-700 ml-0.5">*</span>
    </label>
</template>

