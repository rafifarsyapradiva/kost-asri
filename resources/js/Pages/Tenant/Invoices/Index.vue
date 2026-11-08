<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import DataTable from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import RupiahText from '@/Components/RupiahText.vue';
import { useDateId } from '@/Composables/useDateId';

const props = defineProps({
    invoices: Object,
});

const { formatDate } = useDateId();

const columns = [
    { key: 'number', label: 'No. Tagihan' },
    { key: 'type', label: 'Tipe' },
    { key: 'due_date', label: 'Jatuh Tempo', isNum: true },
    { key: 'total', label: 'Total', align: 'right', isNum: true },
    { key: 'paid_amount', label: 'Dibayar', align: 'right', isNum: true },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Aksi', align: 'right' },
];
</script>

<template>
    <Head title="Daftar Tagihan Saya" />

    <TenantLayout title="Tagihan & Riwayat Pembayaran">
        <div class="space-y-6">
            <DataTable
                :columns="columns"
                :data="invoices.data"
                :pagination="invoices"
                empty-text="Belum ada tagihan sewa."
            >
                <template #cell-number="{ row }">
                    <span class="num font-bold text-forest-900">{{ row.number }}</span>
                </template>
                <template #cell-type="{ row }">
                    <span class="capitalize text-xs font-semibold text-ink-700">{{ row.type }}</span>
                </template>
                <template #cell-due_date="{ row }">
                    <span class="num text-xs">{{ formatDate(row.due_date) }}</span>
                </template>
                <template #cell-total="{ row }">
                    <RupiahText :amount="row.total" class="font-bold text-sm" />
                </template>
                <template #cell-paid_amount="{ row }">
                    <RupiahText :amount="row.paid_amount" class="text-xs text-green-700" />
                </template>
                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" />
                </template>
                <template #cell-actions="{ row }">
                    <div class="flex items-center justify-end gap-2">
                        <a
                            :href="`/invoices/${row.id}/pdf`"
                            target="_blank"
                            class="rounded-lg border border-line-200 p-1.5 text-ink-600 hover:bg-slate-100 transition"
                            title="Unduh PDF"
                        >
                            PDF
                        </a>
                        <Link
                            :href="`/penyewa/invoices/${row.id}`"
                            class="rounded-lg bg-forest-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-forest-800 transition"
                        >
                            Detail
                        </Link>
                    </div>
                </template>
            </DataTable>
        </div>
    </TenantLayout>
</template>

