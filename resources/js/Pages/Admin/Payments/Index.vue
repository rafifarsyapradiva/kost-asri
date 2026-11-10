<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import RupiahText from '@/Components/RupiahText.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useDateId } from '@/Composables/useDateId';

const props = defineProps({
    payments: {
        type: Object,
        required: true,
    },
    unpaid_invoices: {
        type: Array,
        default: () => [],
    },
});

const { formatDate } = useDateId();
const showCashModal = ref(false);

const form = useForm({
    invoice_id: '',
    amount: 0,
    notes: 'Penerimaan pembayaran tunai di kasir pengelola kost',
});

const selectedInvoice = computed(() => {
    return props.unpaid_invoices.find((i) => i.id === parseInt(form.invoice_id, 10)) || null;
});

const remainingDue = computed(() => {
    if (!selectedInvoice.value) return 0;
    return Math.max(0, (selectedInvoice.value.total || 0) - (selectedInvoice.value.paid_amount || 0));
});

const openCashModal = (invoice = null) => {
    if (invoice) {
        form.invoice_id = invoice.id;
        form.amount = Math.max(0, (invoice.total || 0) - (invoice.paid_amount || 0));
    } else {
        form.invoice_id = props.unpaid_invoices[0]?.id || '';
        if (props.unpaid_invoices[0]) {
            form.amount = Math.max(0, (props.unpaid_invoices[0].total || 0) - (props.unpaid_invoices[0].paid_amount || 0));
        }
    }
    showCashModal.value = true;
};

const onInvoiceSelectChange = () => {
    if (selectedInvoice.value) {
        form.amount = remainingDue.value;
    }
};

const submitCash = () => {
    if (!form.invoice_id) return;
    form.post(`/admin/invoices/${form.invoice_id}/cash`, {
        onSuccess: () => {
            showCashModal.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Manajemen Pembayaran" />

    <AdminLayout title="Pusat Pembayaran & Kasir Tunai">
        <div class="space-y-6">
            <!-- Header bar -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-forest-900">Riwayat Pembayaran & Penerimaan Kas</h2>
                    <p class="text-xs text-ink-500">Mencatat pembayaran gateway Midtrans & pelunasan tunai (cash) langsung.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="openCashModal()"
                        class="rounded-xl bg-forest-900 px-4 py-2 text-xs font-semibold text-white hover:bg-forest-800 transition shadow-2xs flex items-center gap-1.5"
                    >
                        <span>+ Catat Pembayaran Tunai (Cash)</span>
                    </button>
                </div>
            </div>

            <!-- Table of Payments -->
            <BentoCard title="Daftar Transaksi Pembayaran">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line-200 text-xs text-left">
                        <thead class="bg-slate-50 text-ink-500 font-semibold uppercase">
                            <tr>
                                <th class="px-4 py-3">Order / ID Transaksi</th>
                                <th class="px-4 py-3">No. Invoice</th>
                                <th class="px-4 py-3">Pembayar</th>
                                <th class="px-4 py-3">Metode</th>
                                <th class="px-4 py-3 text-right">Nominal</th>
                                <th class="px-4 py-3">Waktu Bayar</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-right">Kwitansi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line-100">
                            <tr v-for="pay in payments.data" :key="pay.id" class="hover:bg-slate-50/60 transition">
                                <td class="px-4 py-3 font-mono text-[11px] text-ink-700">
                                    {{ pay.order_id }}
                                </td>
                                <td class="px-4 py-3 font-bold text-forest-900 num">
                                    <Link v-if="pay.invoice" :href="`/admin/invoices/${pay.invoice.id}`" class="hover:underline">
                                        {{ pay.invoice.number }}
                                    </Link>
                                    <span v-else>-</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-semibold text-ink-900">{{ pay.user?.name || '-' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['px-2 py-0.5 rounded text-[11px] font-bold uppercase', pay.method === 'cash' ? 'bg-amber-100 text-amber-900' : 'bg-blue-100 text-blue-900']">
                                        {{ pay.method === 'cash' ? 'Tunai (Cash)' : pay.method }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-forest-900">
                                    <RupiahText :amount="pay.amount" />
                                </td>
                                <td class="px-4 py-3 num text-ink-600">
                                    {{ formatDate(pay.paid_at) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <StatusBadge :status="pay.status" />
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <template v-if="pay.receipt || pay.invoice?.receipts?.[0]">
                                        <a
                                            :href="`/receipts/${pay.receipt?.id || pay.invoice.receipts[0].id}/pdf`"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-slate-100 text-ink-700 hover:bg-slate-200 font-semibold text-[11px]"
                                        >
                                            <span>Kwitansi PDF</span>
                                        </a>
                                    </template>
                                    <span v-else class="text-ink-400 text-[11px]">-</span>
                                </td>
                            </tr>
                            <tr v-if="!payments.data || payments.data.length === 0">
                                <td colspan="8" class="px-4 py-10 text-center text-ink-500">
                                    Belum ada transaksi pembayaran yang tercatat.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="payments.links && payments.links.length > 3" class="mt-4 pt-4 border-t border-line-200 flex justify-end gap-1 text-xs">
                    <Component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, idx) in payments.links"
                        :key="idx"
                        :href="link.url"
                        v-html="link.label"
                        :class="[
                            'px-3 py-1.5 rounded-lg font-medium transition',
                            link.active ? 'bg-forest-900 text-white font-bold' : (link.url ? 'hover:bg-slate-100 text-ink-700' : 'text-ink-400 opacity-50')
                        ]"
                    />
                </div>
            </BentoCard>

            <!-- Modal Record Cash Payment -->
            <Modal :show="showCashModal" @close="showCashModal = false">
                <div class="p-6">
                    <h3 class="text-base font-bold text-forest-900 mb-2">Kasir: Catat Penerimaan Tunai (Cash)</h3>
                    <p class="text-xs text-ink-600 mb-4">
                        Pilih tagihan yang belum lunas. Kwitansi resmi lunas ber-nomor urut akan otomatis diterbitkan oleh server.
                    </p>

                    <form @submit.prevent="submitCash" class="space-y-4 text-xs">
                        <div>
                            <InputLabel value="Pilih Tagihan yang Ditagih" />
                            <select
                                v-model="form.invoice_id"
                                @change="onInvoiceSelectChange"
                                class="w-full rounded-xl border-line-300 text-xs py-2 px-3 bg-white"
                                required
                            >
                                <option value="">-- Pilih Tagihan Belum Lunas --</option>
                                <option v-for="inv in unpaid_invoices" :key="inv.id" :value="inv.id">
                                    {{ inv.number }} — {{ inv.user?.name }} (Kamar {{ inv.room?.code || '-' }}) • Sisa: Rp {{ (inv.total - (inv.paid_amount || 0)).toLocaleString('id-ID') }}
                                </option>
                            </select>
                            <InputError :message="form.errors.invoice_id" />
                        </div>

                        <div v-if="selectedInvoice" class="p-3 bg-slate-50 border border-line-200 rounded-xl space-y-1">
                            <div class="flex justify-between">
                                <span class="text-ink-500">Penghuni:</span>
                                <span class="font-bold text-forest-900">{{ selectedInvoice.user?.name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-ink-500">Sisa Tagihan:</span>
                                <RupiahText :amount="remainingDue" class="font-extrabold text-danger-700" />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Nominal Tunai Diterima (Rp)" />
                            <TextInput type="number" v-model="form.amount" min="1" :max="remainingDue" required />
                            <InputError :message="form.errors.amount" />
                        </div>

                        <div>
                            <InputLabel value="Catatan Kwitansi Kasir" />
                            <TextInput v-model="form.notes" placeholder="Catatan kasir..." />
                            <InputError :message="form.errors.notes" />
                        </div>

                        <div class="flex justify-end gap-2 pt-3">
                            <SecondaryButton @click="showCashModal = false">Batal</SecondaryButton>
                            <PrimaryButton :disabled="form.processing || !form.invoice_id">
                                Simpan Pembayaran & Cetak Kwitansi
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    </AdminLayout>
</template>

