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
    faqs: {
        type: Array,
        default: () => [],
    },
});

const showModal = ref(false);
const editingFaq = ref(null);

const form = useForm({
    question: '',
    answer: '',
    is_published: true,
});

const openCreateModal = () => {
    editingFaq.value = null;
    form.reset();
    form.is_published = true;
    showModal.value = true;
};

const openEditModal = (f) => {
    editingFaq.value = f;
    form.question = f.question;
    form.answer = f.answer;
    form.is_published = !!f.is_published;
    showModal.value = true;
};

const submitFaq = () => {
    if (editingFaq.value) {
        form.put(`/admin/cms/faqs/${editingFaq.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post('/admin/cms/faqs', {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const deleteFaq = (f) => {
    if (confirm(`Hapus pertanyaan "${f.question}"?`)) {
        router.delete(`/admin/cms/faqs/${f.id}`);
    }
};

const handleReorder = (newItems) => {
    const ids = newItems.map((item) => item.id);
    router.post('/admin/cms/faqs/reorder', { ids }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Kelola FAQ CMS" />

    <AdminLayout title="CMS: Pertanyaan Sering Ditanyakan (FAQ)">
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-forest-900">FAQ Landing Page</h2>
                    <p class="text-xs text-ink-500">Tarik untuk mengubah urutan akordion FAQ pada halaman landing.</p>
                </div>
                <button
                    type="button"
                    @click="openCreateModal"
                    class="rounded-xl bg-forest-900 px-4 py-2 text-xs font-semibold text-white hover:bg-forest-800 transition shadow-2xs"
                >
                    + Tambah Tanya Jawab
                </button>
            </div>

            <BentoCard title="Daftar Pertanyaan & Jawaban">
                <SortableList :items="faqs" @reorder="handleReorder">
                    <template #item="{ item }">
                        <div class="max-w-xl">
                            <span class="font-bold text-xs text-forest-900 block">{{ item.question }}</span>
                            <p class="text-[11px] text-ink-500 line-clamp-2 mt-0.5">{{ item.answer }}</p>
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
                            @click="deleteFaq(item)"
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
                        {{ editingFaq ? 'Edit FAQ' : 'Tambah FAQ Baru' }}
                    </h3>

                    <form @submit.prevent="submitFaq" class="space-y-4 text-xs">
                        <div>
                            <InputLabel value="Pertanyaan" />
                            <TextInput v-model="form.question" placeholder="Misal: Apakah biaya sudah termasuk listrik & WiFi?" required />
                            <InputError :message="form.errors.question" />
                        </div>

                        <div>
                            <InputLabel value="Jawaban Lengkap" />
                            <textarea
                                v-model="form.answer"
                                rows="4"
                                class="w-full rounded-xl border-line-300 text-xs p-3 focus:ring-forest-500 focus:border-forest-500"
                                placeholder="Tuliskan jawaban yang jelas dan ramah..."
                                required
                            ></textarea>
                            <InputError :message="form.errors.answer" />
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="faq_published" v-model="form.is_published" class="rounded border-line-300 text-forest-900 focus:ring-forest-500" />
                            <label for="faq_published" class="text-xs text-ink-700 font-medium">Tampilkan di landing page publik</label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3">
                            <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                            <PrimaryButton :disabled="form.processing">Simpan FAQ</PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    </AdminLayout>
</template>

