<script setup>
import { ref } from 'vue';

const props = defineProps({
    modelValue: {
        type: [File, String, null],
        default: null,
    },
    accept: {
        type: String,
        default: 'image/jpeg,image/png,image/webp',
    },
    maxSizeMb: {
        type: Number,
        default: 2,
    },
    label: {
        type: String,
        default: 'Unggah Berkas',
    },
    helpText: {
        type: String,
        default: 'Format JPG, PNG, atau WebP (Maks. 2 MB)',
    },
});

const emit = defineEmits(['update:modelValue', 'error']);

const isDragging = ref(false);
const previewUrl = ref(typeof props.modelValue === 'string' ? props.modelValue : null);
const errorMessage = ref('');

const handleFile = (file) => {
    errorMessage.value = '';
    if (!file) return;

    if (file.size > props.maxSizeMb * 1024 * 1024) {
        errorMessage.value = `Ukuran file melebihi ${props.maxSizeMb} MB`;
        emit('error', errorMessage.value);
        return;
    }

    if (file.type.startsWith('image/')) {
        previewUrl.value = URL.createObjectURL(file);
    } else {
        previewUrl.value = null;
    }

    emit('update:modelValue', file);
};

const onDrop = (e) => {
    isDragging.value = false;
    const file = e.dataTransfer.files[0];
    handleFile(file);
};

const onFileChange = (e) => {
    const file = e.target.files[0];
    handleFile(file);
};

const clearFile = () => {
    previewUrl.value = null;
    errorMessage.value = '';
    emit('update:modelValue', null);
};
</script>

<template>
    <div class="w-full">
        <div
            v-if="!previewUrl"
            :class="[
                'relative flex flex-col items-center justify-center rounded-2xl border-2 border-dashed px-6 py-8 text-center transition-colors cursor-pointer',
                isDragging ? 'border-forest-500 bg-forest-50/50' : 'border-line-200 hover:border-forest-400 bg-white'
            ]"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="onDrop"
            @click="$refs.fileInput.click()"
        >
            <input
                ref="fileInput"
                type="file"
                :accept="accept"
                class="hidden"
                @change="onFileChange"
            />
            <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-forest-50 text-forest-700">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
            </div>
            <p class="text-sm font-semibold text-forest-900">{{ label }}</p>
            <p class="mt-1 text-xs text-ink-500">{{ helpText }}</p>
        </div>

        <div v-else class="relative rounded-2xl border border-line-200 bg-white p-3 shadow-sm flex items-center gap-4">
            <img
                :src="previewUrl"
                alt="Preview"
                class="h-20 w-20 rounded-xl object-cover border border-line-200"
            />
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-ink-900 truncate">
                    {{ modelValue instanceof File ? modelValue.name : 'Berkas terpilih' }}
                </p>
                <p class="text-xs text-ink-500">
                    {{ modelValue instanceof File ? `${(modelValue.size / 1024).toFixed(0)} KB` : 'Tersimpan' }}
                </p>
            </div>
            <button
                type="button"
                @click="clearFile"
                class="p-2 text-ink-500 hover:text-danger-700 transition-colors"
                title="Hapus berkas"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </div>

        <p v-if="errorMessage" class="mt-1 text-xs font-medium text-danger-700">
            {{ errorMessage }}
        </p>
    </div>
</template>

