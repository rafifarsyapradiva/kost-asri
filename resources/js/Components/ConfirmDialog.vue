<script setup>
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Konfirmasi Aksi',
    },
    message: {
        type: String,
        default: 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
    },
    confirmText: {
        type: String,
        default: 'Ya, Lanjutkan',
    },
    cancelText: {
        type: String,
        default: 'Batal',
    },
    type: {
        type: String,
        default: 'danger', // danger, warning, primary
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'confirm']);
</script>

<template>
    <Modal :show="show" :title="title" max-width="sm" @close="$emit('close')">
        <div class="text-sm text-ink-700">
            <p>{{ message }}</p>
            <slot />
        </div>

        <template #footer>
            <button
                type="button"
                :disabled="loading"
                @click="$emit('close')"
                class="rounded-xl border border-line-200 bg-white px-4 py-2.5 text-sm font-semibold text-ink-700 shadow-xs hover:bg-slate-50 transition"
            >
                {{ cancelText }}
            </button>

            <DangerButton
                v-if="type === 'danger'"
                :loading="loading"
                @click="$emit('confirm')"
            >
                {{ confirmText }}
            </DangerButton>
            <PrimaryButton
                v-else
                :loading="loading"
                @click="$emit('confirm')"
            >
                {{ confirmText }}
            </PrimaryButton>
        </template>
    </Modal>
</template>

