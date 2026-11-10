<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import FileDropzone from '@/Components/FileDropzone.vue';

const props = defineProps({
    galleries: {
        type: Array,
        default: () => [],
    },
});

const showModal = ref(false);
const activeTab = ref('all');

const form = useForm({
    title: '',
    category: 'kamar',
    caption: '',
    image: null,
    is_published: true,
});

const openCreateModal = () => {
    form.reset();
    form.category = 'kamar';
    form.is_published = true;
    showModal.value = true;
};

const handleFileSelect = (file) => {
    form.image = file;
};

const submitGallery = () => {
    form.post('/admin/cms/galleries', {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
    });
};

const deleteGallery = (g) => {
    if (confirm(`Hapus foto "${g.title}" dari galeri?`)) {
        router.delete(`/admin/cms/galleries/${g.id}`);
    }
};
</script>

<template>
    <Head title="Kelola Galeri Foto CMS" />

    <AdminLayout title="CMS: Galeri Foto Kost ASRI">
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-forest-900">Galeri Foto Berkualitas Tinggi</h2>
                    <p class="text-xs text-ink-500">Foto otomatis di-optimasi ke format WebP terkompresi untuk LCP cepat.</p>
                </div>
                <button
                    type="button"
                    @click="openCreateModal"
                    class="rounded-xl bg-forest-900 px-4 py-2 text-xs font-semibold text-white hover:bg-forest-800 transition shadow-2xs"
                >
                    + Upload Foto Baru
                </button>
            </div>

            <!-- Category Filter Tabs -->
            <div class="flex items-center gap-2 text-xs">
                <button
                    type="button"
                    @click="activeTab = 'all'"
                    :class="['px-3 py-1.5 rounded-lg font-semibold transition', activeTab === 'all' ? 'bg-forest-900 text-white' : 'bg-white border border-line-200 text-ink-700']"
                >
                    Semua Foto ({{ galleries.length }})
                </button>
                <button
                    type="button"
                    @click="activeTab = 'kamar'"
                    :class="['px-3 py-1.5 rounded-lg font-semibold transition', activeTab === 'kamar' ? 'bg-forest-900 text-white' : 'bg-white border border-line-200 text-ink-700']"
                >
                    Interior Kamar
                </button>
                <button
                    type="button"
                    @click="activeTab = 'fasilitas'"
                    :class="['px-3 py-1.5 rounded-lg font-semibold transition', activeTab === 'fasilitas' ? 'bg-forest-900 text-white' : 'bg-white border border-line-200 text-ink-700']"
                >
                    Fasilitas Bersama
                </button>
                <button
                    type="button"
                    @click="activeTab = 'lingkungan'"
                    :class="['px-3 py-1.5 rounded-lg font-semibold transition', activeTab === 'lingkungan' ? 'bg-forest-900 text-white' : 'bg-white border border-line-200 text-ink-700']"
                >
                    Lingkungan & Gedung
                </button>
            </div>

            <!-- Galleries Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                <div
                    v-for="g in galleries.filter(item => activeTab === 'all' || item.category === activeTab)"
                    :key="g.id"
                    class="group rounded-2xl border border-line-200 bg-white overflow-hidden shadow-2xs flex flex-col justify-between"
                >
                    <div class="aspect-4/3 w-full bg-slate-100 overflow-hidden relative">
                        <img
                            :src="g.image_path.startsWith('http') ? g.image_path : `/storage/${g.image_path}`"
                            :alt="g.title"
                            class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                        />
                        <span class="absolute top-2 left-2 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-black/60 text-white backdrop-blur-xs">
                            {{ g.category }}
                        </span>
                    </div>

                    <div class="p-3 space-y-1 text-xs">
                        <h4 class="font-bold text-forest-900 truncate">{{ g.title }}</h4>
                        <p class="text-[11px] text-ink-500 truncate">{{ g.caption || '-' }}</p>

                        <div class="pt-2 flex items-center justify-between border-t border-line-100">
                            <span :class="['px-1.5 py-0.5 rounded text-[10px] font-semibold', g.is_published ? 'text-green-700 bg-green-50' : 'text-slate-600 bg-slate-100']">
                                {{ g.is_published ? 'Aktif' : 'Draft' }}
                            </span>
                            <button
                                type="button"
                                @click="deleteGallery(g)"
                                class="text-red-600 hover:text-red-800 text-[11px] font-semibold p-1"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="galleries.length === 0" class="col-span-full py-12 text-center text-xs text-ink-500">
                    Belum ada foto dalam galeri.
                </div>
            </div>

            <!-- Modal Upload -->
            <Modal :show="showModal" @close="showModal = false">
                <div class="p-6">
                    <h3 class="text-base font-bold text-forest-900 mb-4">Upload Foto Galeri Baru</h3>

                    <form @submit.prevent="submitGallery" class="space-y-4 text-xs">
                        <div>
                            <InputLabel value="Judul Foto" />
                            <TextInput v-model="form.title" placeholder="Misal: Koridor Lantai 2 Asri" required />
                            <InputError :message="form.errors.title" />
                        </div>

                        <div>
                            <InputLabel value="Kategori Galeri" />
                            <select v-model="form.category" class="w-full rounded-xl border-line-300 text-xs py-2 px-3 bg-white" required>
                                <option value="kamar">Kamar</option>
                                <option value="fasilitas">Fasilitas</option>
                                <option value="lingkungan">Lingkungan</option>
                            </select>
                            <InputError :message="form.errors.category" />
                        </div>

                        <div>
                            <InputLabel value="Unggah Berkas Gambar (JPG, PNG, WebP)" />
                            <input
                                type="file"
                                accept="image/*"
                                @change="(e) => form.image = e.target.files[0]"
                                class="w-full text-xs text-ink-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-forest-50 file:text-forest-900 hover:file:bg-forest-100 cursor-pointer"
                                required
                            />
                            <InputError :message="form.errors.image" />
                        </div>

                        <div>
                            <InputLabel value="Keterangan / Kapsi (Opsional)" />
                            <TextInput v-model="form.caption" placeholder="Keterangan foto..." />
                            <InputError :message="form.errors.caption" />
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="gal_published" v-model="form.is_published" class="rounded border-line-300 text-forest-900 focus:ring-forest-500" />
                            <label for="gal_published" class="text-xs text-ink-700 font-medium">Tampilkan di landing page publik</label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3">
                            <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                            <PrimaryButton :disabled="form.processing">Simpan & Optimasi</PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    </AdminLayout>
</template>

