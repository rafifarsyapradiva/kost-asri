<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const toasts = ref([]);

const addToast = (type, message) => {
    if (!message) return;
    const id = Date.now() + Math.random();
    toasts.value.push({ id, type, message });

    setTimeout(() => {
        removeToast(id);
    }, 4500);
};

const removeToast = (id) => {
    toasts.value = toasts.value.filter((t) => t.id !== id);
};

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;
        if (flash.success) addToast('success', flash.success);
        if (flash.error) addToast('error', flash.error);
        if (flash.warning) addToast('warning', flash.warning);
        if (flash.info) addToast('info', flash.info);
    },
    { deep: true, immediate: true }
);

defineExpose({ addToast });
</script>

<template>
    <div
        aria-live="assertive"
        class="pointer-events-none fixed inset-0 z-50 flex items-end px-4 py-6 sm:items-start sm:p-6"
    >
        <div class="flex w-full flex-col items-center space-y-3 sm:items-end">
            <transition-group
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    :class="[
                        'pointer-events-auto w-full max-w-sm overflow-hidden rounded-2xl border p-4 shadow-lg backdrop-blur-md',
                        toast.type === 'success' ? 'border-green-300 bg-white/95 text-green-950' :
                        toast.type === 'error' ? 'border-red-300 bg-white/95 text-red-950' :
                        toast.type === 'warning' ? 'border-yellow-300 bg-white/95 text-yellow-950' :
                        'border-line-200 bg-white/95 text-ink-950'
                    ]"
                >
                    <div class="flex items-start gap-3">
                        <div class="shrink-0 pt-0.5">
                            <svg v-if="toast.type === 'success'" class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-else-if="toast.type === 'error'" class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                <line x1="12" y1="8" x2="12" y2="12" stroke-width="2"/>
                                <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2"/>
                            </svg>
                            <svg v-else class="h-5 w-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                <line x1="12" y1="8" x2="12" y2="12" stroke-width="2"/>
                                <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2"/>
                            </svg>
                        </div>
                        <div class="w-0 flex-1">
                            <p class="text-sm font-medium text-ink-900 leading-snug">{{ toast.message }}</p>
                        </div>
                        <button
                            type="button"
                            @click="removeToast(toast.id)"
                            class="inline-flex rounded-md p-1 text-ink-400 hover:text-ink-600 transition"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </transition-group>
        </div>
    </div>
</template>

