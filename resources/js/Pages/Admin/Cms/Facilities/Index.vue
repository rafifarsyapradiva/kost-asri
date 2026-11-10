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
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    facilities: {
        type: Array,
        default: () => [],
    },
});

const showModal = ref(false);
const editingFacility = ref(null);

const form = useForm({
    name: '',
    category: 'kamar',
    icon: 'check',
    description: '',
    is_published: true,
});

const openCreateModal = () => {
    editingFacility.value = null;
    form.reset();
    form.category = 'kamar';
    form.is_published = true;
    showModal.value = true;
};

const openEditModal = (fac) => {
    editingFacility.value = fac;
    form.name = fac.name;
    form.category = fac.category;
    form.icon = fac.icon || 'check';
    form.description = fac.description || '';
    form.is_published = !!fac.is_published;
    showModal.value = true;
};

const submitFacility = () => {
    if (editingFacility.value) {
        form.put(`/admin/cms/facilities/${editingFacility.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post('/admin/cms/facilities', {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const deleteFacility = (fac) => {
    if (confirm(`Hapus fasilitas "${fac.name}"?`)) {
        router.delete(`/admin/cms/facilities/${fac.id}`);
    }
};

const handleReorder = (newItems) => {
    const ids = newItems.map((item) => item.id);
    router.post('/admin/cms/facilities/reorder', { ids }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Kelola Fasilitas CMS" />

    <AdminLayout title="CMS: Fasilitas Kamar & Umum">
        <div class="space-y-6">
            <!-- Header bar -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-forest-900">Daftar Fasilitas Kost</h2>
                    <p class="text-xs text-ink-500">Tarik dan geser (drag & drop) untuk mengatur urutan tampil di landing page.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="openCreateModal"
                        class="rounded-xl bg-forest-900 px-4 py-2 text-xs font-semibold text-white hover:bg-forest-800 transition shadow-2xs"
                    >
                        + Tambah Fasilitas Baru
                    </button>
                </div>
            </div>

            <!-- List of facilities -->
            <BentoCard title="Urutan Fasilitas Landing Page">
                <SortableList :items="facilities" @reorder="handleReorder">
                    <template #item="{ item }">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-xs text-forest-900">{{ item.name }}</span>
                                <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase', item.category === 'kamar' ? 'bg-forest-100 text-forest-800' : 'bg-amber-100 text-amber-800']">
                                    {{ item.category === 'kamar' ? 'Fasilitas Kamar' : 'Fasilitas Umum' }}
                                </span>
                                <span v-if="!item.is_published" class="px-2 py-0.5 rounded text-[10px] bg-slate-200 text-slate-700">
                                    Draft
                                </span>
                            </div>
                            <p class="text-[11px] text-ink-500 mt-0.5">{{ item.description || 'Tidak ada deskripsi.' }}</p>
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
                            @click="deleteFacility(item)"
                            class="px-2.5 py-1 rounded bg-red-50 text-red-700 hover:bg-red-100 text-[11px] font-semibold"
                        >
                            Hapus
                        </button>
                    </template>
                </SortableList>
            </BentoCard>

            <!-- Modal Form -->
            <Modal :show="showModal" @close="showModal = false">
                <div class="p-6">
                    <h3 class="text-base font-bold text-forest-900 mb-4">
                        {{ editingFacility ? 'Edit Fasilitas' : 'Tambah Fasilitas Baru' }}
                    </h3>

                    <form @submit.prevent="submitFacility" class="space-y-4 text-xs">
                        <div>
                            <InputLabel value="Nama Fasilitas" />
                            <TextInput v-model="form.name" placeholder="Misal: AC & Kamar Mandi Dalam" required />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div>
                            <InputLabel value="Kategori Fasilitas" />
                            <select v-model="form.category" class="w-full rounded-xl border-line-300 text-xs py-2 px-3 bg-white" required>
                                <option value="kamar">Fasilitas Kamar Pribadi</option>
                                <option value="umum">Fasilitas Umum Bersama</option>
                            </select>
                            <InputError :message="form.errors.category" />
                        </div>

                        <div>
                            <InputLabel value="Nama Ikon Lucide" />
                            <TextInput v-model="form.icon" placeholder="Contoh: wifi, tv, wind, shower-head" />
                        </div>

                        <div>
                            <InputLabel value="Deskripsi Singkat" />
                            <textarea
                                v-model="form.description"
                                rows="2"
                                class="w-full rounded-xl border-line-300 text-xs p-3 focus:ring-forest-500 focus:border-forest-500"
                                placeholder="Penjelasan singkat fasilitas..."
                            ></textarea>
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="fac_published" v-model="form.is_published" class="rounded border-line-300 text-forest-900 focus:ring-forest-500" />
                            <label for="fac_published" class="text-xs text-ink-700 font-medium">Tampilkan di landing page publik</label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3">
                            <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                            <PrimaryButton :disabled="form.processing">Simpan Fasilitas</PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    </AdminLayout>
</template>

