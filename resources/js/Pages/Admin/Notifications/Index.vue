<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useTableFilters } from '@/Composables/useTableFilters';
import { useDateId } from '@/Composables/useDateId';

const props = defineProps({
    logs: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ channel: '', status: '', search: '' }),
    },
});

const { filters, resetFilters } = useTableFilters('/admin/notification-logs', props.filters);
const { formatDate, fromNow } = useDateId();

const selectedLog = ref(null);
const showDetailModal = ref(false);

const inspectLog = (log) => {
    selectedLog.value = log;
    showDetailModal.value = true;
};

const resendNotification = (log) => {
    if (confirm(`Kirim ulang notifikasi ke ${log.recipient_address}?`)) {
        router.post(`/admin/notification-logs/${log.id}/resend`, {}, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Log Notifikasi" />

    <AdminLayout title="Log Pengiriman Notifikasi (Fonnte WA & SMTP)">
        <div class="space-y-6">
            <!-- Filter Bar -->
            <BentoCard title="Filter Log Notifikasi">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <TextInput
                        v-model="filters.search"
                        placeholder="Cari penerima, no WA, email..."
                    />
                    <select
                        v-model="filters.channel"
                        class="rounded-xl border border-line-200 bg-white px-3.5 py-2.5 text-xs text-ink-900 focus:border-forest-500 focus:outline-none"
                    >
                        <option value="">Semua Saluran</option>
                        <option value="whatsapp">WhatsApp (Fonnte)</option>
                        <option value="email">Email (SMTP Mailer)</option>
                    </select>
                    <select
                        v-model="filters.status"
                        class="rounded-xl border border-line-200 bg-white px-3.5 py-2.5 text-xs text-ink-900 focus:border-forest-500 focus:outline-none"
                    >
                        <option value="">Semua Status Pengiriman</option>
                        <option value="sent">Terkirim (Sent)</option>
                        <option value="failed">Gagal (Failed)</option>
                        <option value="pending">Antrean (Pending)</option>
                    </select>
                    <button
                        type="button"
                        @click="resetFilters"
                        class="rounded-xl border border-line-200 bg-slate-50 px-4 py-2.5 text-xs font-semibold text-ink-600 hover:bg-slate-100 transition"
                    >
                        Reset Filter
                    </button>
                </div>
            </BentoCard>

            <!-- Table of Logs -->
            <BentoCard title="Riwayat Notifikasi Keluar">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line-200 text-xs text-left">
                        <thead class="bg-slate-50 text-ink-500 font-semibold uppercase">
                            <tr>
                                <th class="px-4 py-3">Saluran</th>
                                <th class="px-4 py-3">Penerima</th>
                                <th class="px-4 py-3">Isi Pesan</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line-100">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-50/60 transition">
                                <td class="px-4 py-3">
                                    <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase', log.channel === 'whatsapp' ? 'bg-green-100 text-green-900' : 'bg-blue-100 text-blue-900']">
                                        {{ log.channel }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-bold text-forest-900">{{ log.recipient_name || '-' }}</p>
                                    <p class="num text-[11px] text-ink-500">{{ log.recipient_address }}</p>
                                </td>
                                <td class="px-4 py-3 max-w-xs truncate text-ink-700">
                                    {{ log.message }}
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase', log.status === 'sent' ? 'bg-green-100 text-green-800' : (log.status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800')]">
                                        {{ log.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 num text-ink-500 whitespace-nowrap">
                                    {{ fromNow(log.created_at) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="inspectLog(log)"
                                            class="px-2.5 py-1 rounded border border-line-200 text-ink-700 hover:bg-slate-100 font-semibold"
                                        >
                                            Detail
                                        </button>
                                        <button
                                            v-if="log.status === 'failed'"
                                            type="button"
                                            @click="resendNotification(log)"
                                            class="px-2.5 py-1 rounded bg-forest-900 text-white hover:bg-forest-800 font-semibold shadow-2xs"
                                        >
                                            Kirim Ulang
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!logs.data || logs.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-ink-500">
                                    Belum ada log notifikasi yang tercatat.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </BentoCard>

            <!-- Inspect Modal -->
            <Modal :show="showDetailModal" @close="showDetailModal = false">
                <div class="p-6">
                    <h3 class="text-base font-bold text-forest-900 mb-4">Inspeksi Log Notifikasi</h3>

                    <div v-if="selectedLog" class="space-y-4 text-xs">
                        <div class="p-3 bg-slate-50 border border-line-200 rounded-xl space-y-1.5">
                            <div class="flex justify-between">
                                <span class="text-ink-500">Penerima:</span>
                                <span class="font-bold text-forest-900">{{ selectedLog.recipient_name }} ({{ selectedLog.recipient_address }})</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-ink-500">Saluran / Status:</span>
                                <span class="uppercase font-bold">{{ selectedLog.channel }} • {{ selectedLog.status }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-ink-500">Waktu Kirim:</span>
                                <span class="num">{{ formatDate(selectedLog.created_at) }}</span>
                            </div>
                        </div>

                        <div>
                            <span class="font-bold text-forest-900 block mb-1">Isi Pesan:</span>
                            <pre class="w-full p-3 bg-slate-100 rounded-xl text-ink-800 font-mono text-[11px] whitespace-pre-wrap leading-relaxed">{{ selectedLog.message }}</pre>
                        </div>

                        <div v-if="selectedLog.response_payload">
                            <span class="font-bold text-forest-900 block mb-1">Respons API Provider:</span>
                            <pre class="w-full p-3 bg-slate-900 text-slate-100 rounded-xl font-mono text-[10px] overflow-x-auto">{{ JSON.stringify(selectedLog.response_payload, null, 2) }}</pre>
                        </div>

                        <div class="flex justify-end pt-3">
                            <SecondaryButton @click="showDetailModal = false">Tutup</SecondaryButton>
                        </div>
                    </div>
                </div>
            </Modal>
        </div>
    </AdminLayout>
</template>

