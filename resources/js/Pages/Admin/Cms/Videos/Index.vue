<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    videos: {
        type: Array,
        default: () => [],
    },
});

const showModal = ref(false);
const editingVideo = ref(null);

const form = useForm({
    title: '',
    youtube_url: '',
    description: '',
    is_published: true,
});

const openCreateModal = () => {
    editingVideo.value = null;
    form.reset();
    form.is_published = true;
    showModal.value = true;
};

const openEditModal = (v) => {
    editingVideo.value = v;
    form.title = v.title;
    form.youtube_url = v.youtube_url;
    form.description = v.description || '';
    form.is_published = !!v.is_published;
    showModal.value = true;
};

const submitVideo = () => {
    if (editingVideo.value) {
        form.put(`/admin/cms/videos/${editingVideo.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post('/admin/cms/videos', {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const deleteVideo = (v) => {
    if (confirm(`Hapus video "${v.title}"?`)) {
        router.delete(`/admin/cms/videos/${v.id}`);
    }
};
</script>

<template>
    <Head title="Kelola Video CMS" />

    <AdminLayout title="CMS: Video Tour YouTube">
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-forest-900">Video Tur Kamar Kost ASRI</h2>
                    <p class="text-xs text-ink-500">Video disematkan (lazy load iframe) di landing page untuk kecepatan LCP maksimal.</p>
                </div>
                <button
                    type="button"
                    @click="openCreateModal"
                    class="rounded-xl bg-forest-900 px-4 py-2 text-xs font-semibold text-white hover:bg-forest-800 transition shadow-2xs"
                >
                    + Tambah Video YouTube
                </button>
            </div>

            <!-- Videos Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <BentoCard v-for="video in videos" :key="video.id" :title="video.title">
                    <div class="space-y-3 pt-2 text-xs">
                        <!-- Thumbnail Preview -->
                        <div class="aspect-video w-full rounded-xl overflow-hidden bg-slate-900 relative group">
                            <img
                                :src="video.thumbnail_url"
                                :alt="video.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                            />
                            <a
                                :href="video.youtube_url"
                                target="_blank"
                                class="absolute inset-0 flex items-center justify-center bg-black/40 hover:bg-black/20 text-white transition"
                            >
                                <div class="h-10 w-10 rounded-full bg-red-600 flex items-center justify-center shadow-lg">
                                    ▶
                                </div>
                            </a>
                        </div>

                        <p class="text-ink-600 line-clamp-2 leading-relaxed">
                            {{ video.description || 'Tidak ada deskripsi.' }}
                        </p>

                        <div class="pt-2 flex items-center justify-between border-t border-line-100">
                            <span :class="['px-2 py-0.5 rounded text-[10px] font-bold', video.is_published ? 'bg-green-100 text-green-800' : 'bg-slate-200 text-slate-700']">
                                {{ video.is_published ? 'Tayang' : 'Draft' }}
                            </span>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="openEditModal(video)"
                                    class="px-2.5 py-1 rounded border border-line-200 text-ink-700 hover:bg-slate-100 font-semibold"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    @click="deleteVideo(video)"
                                    class="px-2.5 py-1 rounded bg-red-50 text-red-700 hover:bg-red-100 font-semibold"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </BentoCard>

                <div v-if="videos.length === 0" class="col-span-full py-12 text-center text-xs text-ink-500">
                    Belum ada video YouTube yang ditambahkan.
                </div>
            </div>

            <!-- Modal Form -->
            <Modal :show="showModal" @close="showModal = false">
                <div class="p-6">
                    <h3 class="text-base font-bold text-forest-900 mb-4">
                        {{ editingVideo ? 'Edit Video YouTube' : 'Tambah Video Baru' }}
                    </h3>

                    <form @submit.prevent="submitVideo" class="space-y-4 text-xs">
                        <div>
                            <InputLabel value="Judul Video" />
                            <TextInput v-model="form.title" placeholder="Misal: Room Tour Tipe VIP Lantai 2" required />
                            <InputError :message="form.errors.title" />
                        </div>

                        <div>
                            <InputLabel value="Tautan / URL YouTube" />
                            <TextInput v-model="form.youtube_url" placeholder="https://www.youtube.com/watch?v=XXXX atau https://youtu.be/XXXX" required />
                            <InputError :message="form.errors.youtube_url" />
                        </div>

                        <div>
                            <InputLabel value="Deskripsi Singkat" />
                            <textarea
                                v-model="form.description"
                                rows="2"
                                class="w-full rounded-xl border-line-300 text-xs p-3 focus:ring-forest-500 focus:border-forest-500"
                                placeholder="Jelaskan isi tur video..."
                            ></textarea>
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="vid_published" v-model="form.is_published" class="rounded border-line-300 text-forest-900 focus:ring-forest-500" />
                            <label for="vid_published" class="text-xs text-ink-700 font-medium">Tampilkan di halaman landing</label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3">
                            <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                            <PrimaryButton :disabled="form.processing">Simpan Video</PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    </AdminLayout>
</template>

