<script setup>
import { onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    maxWidth: {
        type: String,
        default: 'md', // sm, md, lg, xl, 2xl
    },
});

const emit = defineEmits(['close']);

const close = () => emit('close');

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

watch(
    () => props.show,
    (val) => {
        if (val) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = null;
        }
    }
);

const widthClass = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
}[props.maxWidth] || 'max-w-md';
</script>

<template>
    <teleport to="body">
        <transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-show="show" class="fixed inset-0 z-50 overflow-hidden">
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-ink-950/60 backdrop-blur-xs transition-opacity"
                    @click="close"
                />

                <div class="fixed inset-y-0 right-0 flex pl-10 max-w-full">
                    <transition
                        enter-active-class="transform transition ease-in-out duration-300"
                        enter-from-class="translate-x-full"
                        enter-to-class="translate-x-0"
                        leave-active-class="transform transition ease-in-out duration-200"
                        leave-from-class="translate-x-0"
                        leave-to-class="translate-x-full"
                    >
                        <div
                            v-show="show"
                            :class="['w-screen bg-white shadow-2xl flex flex-col', widthClass]"
                        >
                            <!-- Header -->
                            <div class="flex items-center justify-between border-b border-line-200 px-6 py-4">
                                <slot name="header">
                                    <h3 class="text-base font-bold text-forest-900">{{ title }}</h3>
                                </slot>
                                <button
                                    type="button"
                                    @click="close"
                                    class="rounded-lg p-1.5 text-ink-500 hover:bg-slate-100 hover:text-ink-900 transition-colors"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Body -->
                            <div class="relative flex-1 overflow-y-auto px-6 py-6">
                                <slot />
                            </div>

                            <!-- Footer -->
                            <div v-if="$slots.footer" class="border-t border-line-200 bg-slate-50 px-6 py-4 flex items-center justify-end gap-3">
                                <slot name="footer" />
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
        </transition>
    </teleport>
</template>

