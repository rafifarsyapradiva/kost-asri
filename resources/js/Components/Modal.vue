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
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

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

const maxWidthClass = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
}[props.maxWidth] || 'sm:max-w-md';
</script>

<template>
    <teleport to="body">
        <transition
            enter-active-class="ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-show="show"
                class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
                scroll-region
            >
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-ink-950/60 backdrop-blur-xs transition-opacity"
                    @click="close"
                />

                <!-- Dialog Container -->
                <transition
                    enter-active-class="ease-out duration-200"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="ease-in duration-150"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div
                        v-show="show"
                        :class="[
                            'relative mb-6 transform overflow-hidden rounded-2xl bg-white shadow-xl transition-all sm:mx-auto sm:w-full',
                            maxWidthClass
                        ]"
                    >
                        <!-- Header -->
                        <div v-if="title || $slots.header || closeable" class="flex items-center justify-between border-b border-line-200 px-6 py-4">
                            <slot name="header">
                                <h3 class="text-base font-bold text-forest-900">{{ title }}</h3>
                            </slot>
                            <button
                                v-if="closeable"
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
                        <div class="px-6 py-5">
                            <slot />
                        </div>

                        <!-- Footer -->
                        <div v-if="$slots.footer" class="border-t border-line-200 bg-slate-50 px-6 py-4 flex items-center justify-end gap-3">
                            <slot name="footer" />
                        </div>
                    </div>
                </transition>
            </div>
        </transition>
    </teleport>
</template>

