<script setup>
defineProps({
    title: {
        type: String,
        default: '',
    },
    subtitle: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="rounded-2xl border border-line-200 bg-white p-6 shadow-xs transition hover:shadow-sm">
        <div v-if="title || $slots.header || $slots.action" class="mb-5 flex items-start justify-between gap-4">
            <div>
                <slot name="header">
                    <h3 v-if="title" class="text-base font-bold text-forest-900">{{ title }}</h3>
                    <p v-if="subtitle" class="mt-0.5 text-xs text-ink-500">{{ subtitle }}</p>
                </slot>
            </div>
            <div v-if="$slots.action" class="shrink-0">
                <slot name="action" />
            </div>
        </div>
        <slot />
    </div>
</template>

