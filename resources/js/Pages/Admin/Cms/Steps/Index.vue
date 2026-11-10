<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import SortableList from '@/Components/SortableList.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    steps: {
        type: Array,
        default: () => [],
    },
});

const showModal = ref(false);
const editingStep = ref(null);

const form = useForm({
    step_number: 1,
    title: '',
    description: '',
    icon: 'check',
    is_published: true,
});

const openCreateModal = () => {
    editingStep.value = null;
    form.reset();
    form.step_number = (props.steps.length || 0) + 1;
    form.is_published = true;
    showModal.value = true;
};

const openEditModal = (s) => {
    editingStep.value = s;
    form.step_number = s.step_number;
    form.title = s.title;
    form.description = s.description;
    form.icon = s.icon || 'check';
    form.is_published = !!s.is_published;
    showModal.value = true;
};

const submitStep = () => {
    if (editingStep.value) {
        form.put(`/admin/cms/steps/${editingStep.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post('/admin/cms/steps', {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const deleteStep = (s) => {
    if (confirm(`Hapus langkah reservasi "${s.title}"?`)) {
        router.delete(`/admin/cms/steps/${s.id}`);
    }
};

const handleReorder = (newItems) => {
    const ids = newItems.map((item) => item.id);
    router.post('/admin/cms/steps/reorder', { ids }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Kelola 5 Langkah Reservasi" />

    <AdminLayout title="CMS: 5 Langkah Alur Reservasi">
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-forest-900">Alur Reservasi di Landing Page</h2>
                    <p class="text-xs text-ink-500">Panduan langkah calon penyewa memesan kamar hingga check-in.</p>
                </div>
                <button
                    type="button"
                    @click="openCreateModal"
                    class="rounded-xl bg-forest-900 px-4 py-2 text-xs font-semibold text-white hover:bg-forest-800 transition shadow-2xs"
                >
                    + Tambah Langkah Baru
                </button>
            </div>

            <BentoCard title="Daftar Langkah Alur Reservasi">
                <SortableList :items="steps" @reorder="handleReorder">
                    <template #item="{ item }">
                        <div class="flex items-center gap-3">
                            <span class="h-6 w-6 rounded-full bg-forest-900 text-white font-bold text-[11px] flex items-center justify-center num">
                                {{ item.step_number }}
                            </span>
                            <div>
                                <span class="font-bold text-xs text-forest-900">{{ item.title }}</span>
                                <p class="text-[11px] text-ink-500 leading-snug">{{ item.description }}</p>
                            </div>
                        </div>
                    </template>
                    <template #actions="{ item }">
                        <button
                            type="button"
                            @click="openEditModal(item)"
                            class="px-2.5 py-1 rounded border border-line-200 text-ink-700 hover:bg-slate-100 text-[11px] font-semibold"
                        >
                            Edit
                        </button>
                        <button
                            type="button"
                            @click="deleteStep(item)"
                            class="px-2.5 py-1 rounded bg-red-50 text-red-700 hover:bg-red-100 text-[11px] font-semibold"
                        >
                            Hapus
                        </button>
                    </template>
                </SortableList>
            </BentoCard>

            <Modal :show="showModal" @close="showModal = false">
                <div class="p-6">
                    <h3 class="text-base font-bold text-forest-900 mb-4">
                        {{ editingStep ? 'Edit Langkah Reservasi' : 'Tambah Langkah Baru' }}
                    </h3>

                    <form @submit.prevent="submitStep" class="space-y-4 text-xs">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <InputLabel value="Nomor Urut Langkah" />
                                <TextInput type="number" v-model="form.step_number" min="1" required />
                                <InputError :message="form.errors.step_number" />
                            </div>
                            <div>
                                <InputLabel value="Nama Ikon" />
                                <TextInput v-model="form.icon" placeholder="check, search, credit-card" />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Judul Langkah" />
                            <TextInput v-model="form.title" placeholder="Misal: Pilih Kamar di Denah" required />
                            <InputError :message="form.errors.title" />
                        </div>

                        <div>
                            <InputLabel value="Deskripsi Lengkap" />
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="w-full rounded-xl border-line-300 text-xs p-3 focus:ring-forest-500 focus:border-forest-500"
                                placeholder="Jelaskan apa yang harus dilakukan tamu..."
                                required
                            ></textarea>
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="step_published" v-model="form.is_published" class="rounded border-line-300 text-forest-900 focus:ring-forest-500" />
                            <label for="step_published" class="text-xs text-ink-700 font-medium">Tampilkan di halaman landing</label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3">
                            <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                            <PrimaryButton :disabled="form.processing">Simpan Langkah</PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    </AdminLayout>
</template>

