<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useDateId } from '@/Composables/useDateId';

const props = defineProps({
    keywords: {
        type: Array,
        default: () => [],
    },
    sessions: {
        type: Array,
        default: () => [],
    },
    messages: {
        type: Object,
        default: () => ({ data: [] }),
    },
});

const { formatDate, fromNow } = useDateId();
const activeTab = ref('keywords'); // 'keywords' | 'sessions' | 'messages'

// Keyword Modal
const showModal = ref(false);
const editingKeyword = ref(null);
const synonymsInput = ref('');

const form = useForm({
    keyword: '',
    menu_number: '',
    synonyms: [],
    action: 'reply',
    reply_template: '',
    sort_order: 1,
    is_active: true,
});

const openCreateModal = () => {
    editingKeyword.value = null;
    form.reset();
    synonymsInput.value = '';
    form.action = 'reply';
    form.is_active = true;
    showModal.value = true;
};

const openEditModal = (kw) => {
    editingKeyword.value = kw;
    form.keyword = kw.keyword;
    form.menu_number = kw.menu_number || '';
    form.synonyms = kw.synonyms || [];
    synonymsInput.value = (kw.synonyms || []).join(', ');
    form.action = kw.action;
    form.reply_template = kw.reply_template || '';
    form.sort_order = kw.sort_order || 1;
    form.is_active = !!kw.is_active;
    showModal.value = true;
};

const submitKeyword = () => {
    form.synonyms = synonymsInput.value
        .split(',')
        .map((s) => s.trim().toLowerCase())
        .filter((s) => s.length > 0);

    if (editingKeyword.value) {
        form.put(`/admin/chatbot/keywords/${editingKeyword.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post('/admin/chatbot/keywords', {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const deleteKeyword = (kw) => {
    if (confirm(`Hapus keyword "${kw.keyword}"?`)) {
        router.delete(`/admin/chatbot/keywords/${kw.id}`);
    }
};

const releaseHandover = (session) => {
    router.post(`/admin/chatbot/sessions/${session.id}/release-handover`, {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Chatbot WhatsApp" />

    <AdminLayout title="Chatbot WhatsApp Rule-Based (Fonnte)">
        <div class="space-y-6">
            <!-- Tabs & Actions -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="inline-flex rounded-xl border border-line-200 bg-white p-1 shadow-2xs">
                    <button
                        type="button"
                        @click="activeTab = 'keywords'"
                        :class="['rounded-lg px-4 py-2 text-xs font-semibold transition', activeTab === 'keywords' ? 'bg-forest-900 text-white shadow-2xs' : 'text-ink-600 hover:text-ink-900']"
                    >
                        Kata Kunci & Menu ({{ keywords.length }})
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'sessions'"
                        :class="['rounded-lg px-4 py-2 text-xs font-semibold transition', activeTab === 'sessions' ? 'bg-forest-900 text-white shadow-2xs' : 'text-ink-600 hover:text-ink-900']"
                    >
                        Sesi & Handover
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'messages'"
                        :class="['rounded-lg px-4 py-2 text-xs font-semibold transition', activeTab === 'messages' ? 'bg-forest-900 text-white shadow-2xs' : 'text-ink-600 hover:text-ink-900']"
                    >
                        Log Percakapan
                    </button>
                </div>

                <button
                    v-if="activeTab === 'keywords'"
                    type="button"
                    @click="openCreateModal"
                    class="rounded-xl bg-forest-900 px-4 py-2 text-xs font-semibold text-white hover:bg-forest-800 transition shadow-2xs"
                >
                    + Tambah Kata Kunci
                </button>
            </div>

            <!-- Tab 1: Keywords -->
            <BentoCard v-if="activeTab === 'keywords'" title="Daftar Menu & Kata Kunci Chatbot">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line-200 text-xs text-left">
                        <thead class="bg-slate-50 text-ink-500 font-semibold uppercase">
                            <tr>
                                <th class="px-4 py-3">Menu</th>
                                <th class="px-4 py-3">Keyword Utama</th>
                                <th class="px-4 py-3">Sinonim / Varian</th>
                                <th class="px-4 py-3">Aksi Chatbot</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line-100">
                            <tr v-for="kw in keywords" :key="kw.id" class="hover:bg-slate-50/60 transition">
                                <td class="px-4 py-3 font-bold text-forest-900 num">
                                    {{ kw.menu_number || '-' }}
                                </td>
                                <td class="px-4 py-3 font-bold text-ink-900">
                                    {{ kw.keyword }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1 max-w-xs">
                                        <span v-for="syn in (kw.synonyms || [])" :key="syn" class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 text-slate-700">
                                            {{ syn }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-100 text-amber-900">
                                        {{ kw.action }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['px-2 py-0.5 rounded text-[10px] font-bold', kw.is_active ? 'bg-green-100 text-green-800' : 'bg-slate-200 text-slate-700']">
                                        {{ kw.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEditModal(kw)"
                                            class="px-2.5 py-1 rounded border border-line-200 text-ink-700 hover:bg-slate-100 font-semibold"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteKeyword(kw)"
                                            class="px-2.5 py-1 rounded bg-red-50 text-red-700 hover:bg-red-100 font-semibold"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </BentoCard>

            <!-- Tab 2: Sessions & Handover -->
            <BentoCard v-else-if="activeTab === 'sessions'" title="Monitor Sesi Chatbot & Handover Pengelola">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line-200 text-xs text-left">
                        <thead class="bg-slate-50 text-ink-500 font-semibold uppercase">
                            <tr>
                                <th class="px-4 py-3">Nomor WhatsApp</th>
                                <th class="px-4 py-3">Status Sesi</th>
                                <th class="px-4 py-3">Interaksi Terakhir</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line-100">
                            <tr v-for="s in sessions" :key="s.id" class="hover:bg-slate-50/60 transition">
                                <td class="px-4 py-3 font-bold text-forest-900 num">
                                    {{ s.phone_number }}
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['px-2.5 py-1 rounded-full text-[10px] font-bold uppercase', s.is_handover ? 'bg-red-100 text-red-800 animate-pulse' : 'bg-green-100 text-green-800']">
                                        {{ s.is_handover ? 'Human Agent (Handover)' : 'Bot Otomatis' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 num text-ink-600">
                                    {{ fromNow(s.updated_at) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        v-if="s.is_handover"
                                        type="button"
                                        @click="releaseHandover(s)"
                                        class="px-3 py-1.5 rounded-lg bg-forest-900 text-white font-semibold text-[11px] hover:bg-forest-800 transition shadow-2xs"
                                    >
                                        Kembalikan ke Bot
                                    </button>
                                    <span v-else class="text-ink-400 text-[11px]">-</span>
                                </td>
                            </tr>
                            <tr v-if="sessions.length === 0">
                                <td colspan="4" class="px-4 py-8 text-center text-ink-500">
                                    Belum ada sesi percakapan aktif.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </BentoCard>

            <!-- Tab 3: Messages Log -->
            <BentoCard v-else-if="activeTab === 'messages'" title="Riwayat Pesan Masuk & Balasan Bot">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line-200 text-xs text-left">
                        <thead class="bg-slate-50 text-ink-500 font-semibold uppercase">
                            <tr>
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-4 py-3">Nomor Pengirim</th>
                                <th class="px-4 py-3">Pesan Diterima</th>
                                <th class="px-4 py-3">Keyword Cocok</th>
                                <th class="px-4 py-3">Balasan Bot</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line-100">
                            <tr v-for="m in messages.data" :key="m.id" class="hover:bg-slate-50/60 transition">
                                <td class="px-4 py-3 num text-ink-500 whitespace-nowrap">
                                    {{ formatDate(m.created_at) }}
                                </td>
                                <td class="px-4 py-3 font-semibold num text-forest-900">
                                    {{ m.phone_number }}
                                </td>
                                <td class="px-4 py-3 font-medium text-ink-900 max-w-xs truncate">
                                    {{ m.incoming_message }}
                                </td>
                                <td class="px-4 py-3">
                                    <span v-if="m.matched_keyword" class="px-2 py-0.5 rounded text-[10px] font-bold bg-forest-100 text-forest-800">
                                        {{ m.matched_keyword.keyword }}
                                    </span>
                                    <span v-else class="text-ink-400 text-[10px]">MENU Fallback</span>
                                </td>
                                <td class="px-4 py-3 text-ink-600 max-w-sm truncate">
                                    {{ m.outgoing_reply }}
                                </td>
                            </tr>
                            <tr v-if="!messages.data || messages.data.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-ink-500">
                                    Belum ada log pesan tercatat.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </BentoCard>

            <!-- Modal Keyword -->
            <Modal :show="showModal" @close="showModal = false">
                <div class="p-6">
                    <h3 class="text-base font-bold text-forest-900 mb-4">
                        {{ editingKeyword ? 'Edit Kata Kunci Chatbot' : 'Tambah Kata Kunci Baru' }}
                    </h3>

                    <form @submit.prevent="submitKeyword" class="space-y-4 text-xs">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <InputLabel value="Kata Kunci Utama" />
                                <TextInput v-model="form.keyword" placeholder="Misal: harga, lokasi, fasilitas" required />
                                <InputError :message="form.errors.keyword" />
                            </div>
                            <div>
                                <InputLabel value="Nomor Menu (1 - 7)" />
                                <TextInput v-model="form.menu_number" placeholder="Contoh: 1" />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Sinonim & Varian (Pisahkan dengan koma)" />
                            <TextInput v-model="synonymsInput" placeholder="biaya, tarif, sewa, berapa" />
                        </div>

                        <div>
                            <InputLabel value="Tipe Aksi Bot" />
                            <select v-model="form.action" class="w-full rounded-xl border-line-300 text-xs py-2 px-3 bg-white" required>
                                <option value="reply">Kirim Template Teks</option>
                                <option value="handover">Alihkan ke Admin (Handover)</option>
                                <option value="menu">Tampilkan Menu Utama</option>
                            </select>
                        </div>

                        <div v-if="form.action === 'reply'">
                            <InputLabel value="Template Pesan Balasan WhatsApp" />
                            <textarea
                                v-model="form.reply_template"
                                rows="5"
                                class="w-full rounded-xl border-line-300 text-xs p-3 focus:ring-forest-500 focus:border-forest-500 font-mono"
                                placeholder="Gunakan *tebal* untuk format WhatsApp..."
                            ></textarea>
                            <InputError :message="form.errors.reply_template" />
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="kw_active" v-model="form.is_active" class="rounded border-line-300 text-forest-900 focus:ring-forest-500" />
                            <label for="kw_active" class="text-xs text-ink-700 font-medium">Aktifkan kata kunci ini</label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3">
                            <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                            <PrimaryButton :disabled="form.processing">Simpan Kata Kunci</PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    </AdminLayout>
</template>

