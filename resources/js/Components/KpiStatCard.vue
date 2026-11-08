<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
    badgeText: {
        type: String,
        default: '',
    },
    badgeType: {
        type: String,
        default: 'neutral', // success, warning, danger, neutral
    },
});
</script>

<template>
    <div class="rounded-2xl border border-line-200 bg-white p-5 shadow-xs transition hover:shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-ink-500">{{ title }}</span>
            <div v-if="$slots.icon" class="flex h-9 w-9 items-center justify-center rounded-xl bg-forest-50 text-forest-700">
                <slot name="icon" />
            </div>
        </div>

        <div class="mt-3 flex items-baseline justify-between gap-2">
            <div class="num text-2xl font-bold tracking-tight text-forest-900">
                {{ value }}
            </div>
            <span
                v-if="badgeText"
                :class="[
                    'num inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold',
                    badgeType === 'success' ? 'bg-green-100 text-green-800' :
                    badgeType === 'warning' ? 'bg-yellow-100 text-yellow-800' :
                    badgeType === 'danger' ? 'bg-red-100 text-red-800' :
                    'bg-slate-100 text-slate-700'
                ]"
            >
                {{ badgeText }}
            </span>
        </div>

        <p v-if="subtitle" class="mt-2 text-xs text-ink-500">
            {{ subtitle }}
        </p>
    </div>
</template>

