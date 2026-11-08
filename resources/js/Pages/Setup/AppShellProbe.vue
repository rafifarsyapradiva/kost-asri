<script setup>
defineProps({
    kostName: {
        type: String,
        default: 'ASRI BOARDING HOUSE',
    },
});
</script>

<template>
    <main class="mx-auto max-w-4xl p-6">
        <section class="rounded-2xl border border-line-200 bg-white p-6 shadow-xs">
            <h1 class="text-2xl font-bold text-forest-900">{{ kostName }}</h1>
            <p class="mt-2 text-sm text-ink-600">
                Inertia v2 + Vue 3 + Ziggy shell siap digunakan (<span class="num">32</span> kamar).
            </p>
        </section>
    </main>
</template>

