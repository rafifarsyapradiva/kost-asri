<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
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
    places: {
        type: Array,
        default: () => [],
    },
});

const showModal = ref(false);
const editingPlace = ref(null);

const form = useForm({
    name: '',
    category: 'kampus',
    distance_text: '',
    travel_minutes: 5,
    is_published: true,
});

const openCreateModal = () => {
    editingPlace.value = null;
    form.reset();
    form.category = 'kampus';
    form.is_published = true;
    showModal.value = true;
};

const openEditModal = (p) => {
    editingPlace.value = p;
    form.name = p.name;
    form.category = p.category;
    form.distance_text = p.distance_text;
    form.travel_minutes = p.travel_minutes || 5;
    form.is_published = !!p.is_published;
    showModal.value = true;
};

const submitPlace = () => {
    if (editingPlace.value) {
        form.put(`/admin/cms/nearby-places/${editingPlace.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post('/admin/cms/nearby-places', {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const deletePlace = (p) => {
    if (confirm(`Hapus tempat "${p.name}"?`)) {
        router.delete(`/admin/cms/nearby-places/${p.id}`);
    }
};

const handleReorder = (newItems) => {
    const ids = newItems.map((item) => item.id);
    router.post('/admin/cms/nearby-places/reorder', { ids }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Kelola Tempat Sekitar CMS" />

    <AdminLayout title="CMS: Lokasi & Tempat Terdekat">
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-forest-900">Tempat Terdekat Sekitar Tembalang</h2>
                    <p class="text-xs text-ink-500">Jarak tempuh ke kampus (Undip, Polines), rumah sakit, supermarket, dan halte.</p>
                </div>
                <button
                    type="button"
                    @click="openCreateModal"
                    class="rounded-xl bg-forest-900 px-4 py-2 text-xs font-semibold text-white hover:bg-forest-800 transition shadow-2xs"
                >
                    + Tambah Lokasi Terdekat
                </button>
            </div>

            <BentoCard title="Daftar Tempat Terdekat">
                <SortableList :items="places" @reorder="handleReorder">
                    <template #item="{ item }">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-xs text-forest-900">{{ item.name }}</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-800">
                                    {{ item.category }}
                                </span>
                            </div>
                            <p class="text-[11px] text-ink-500 num mt-0.5">
                                Jarak: {{ item.distance_text }} • Waktu Tempuh: ±{{ item.travel_minutes }} Menit
                            </p>
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
                            @click="deletePlace(item)"
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
                        {{ editingPlace ? 'Edit Tempat Terdekat' : 'Tambah Tempat Terdekat' }}
                    </h3>

                    <form @submit.prevent="submitPlace" class="space-y-4 text-xs">
                        <div>
                            <InputLabel value="Nama Tempat" />
                            <TextInput v-model="form.name" placeholder="Misal: Gerbang Utama Kampus Undip Tembalang" required />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <InputLabel value="Kategori Tempat" />
                                <select v-model="form.category" class="w-full rounded-xl border-line-300 text-xs py-2 px-3 bg-white" required>
                                    <option value="kampus">Kampus / Pendidikan</option>
                                    <option value="transportasi">Transportasi / Halte</option>
                                    <option value="kesehatan">RS / Klinik Kesehatan</option>
                                    <option value="belanja">Minimarket / Mall</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Keterangan Jarak" />
                                <TextInput v-model="form.distance_text" placeholder="Contoh: 850 meter" required />
                                <InputError :message="form.errors.distance_text" />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Estimasi Waktu Tempuh (Menit)" />
                            <TextInput type="number" v-model="form.travel_minutes" min="1" required />
                            <InputError :message="form.errors.travel_minutes" />
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="place_published" v-model="form.is_published" class="rounded border-line-300 text-forest-900 focus:ring-forest-500" />
                            <label for="place_published" class="text-xs text-ink-700 font-medium">Tampilkan di landing page publik</label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3">
                            <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                            <PrimaryButton :disabled="form.processing">Simpan Tempat</PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    </AdminLayout>
</template>

