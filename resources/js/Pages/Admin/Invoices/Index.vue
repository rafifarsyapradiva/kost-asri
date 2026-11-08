<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import DataTable from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import RupiahText from '@/Components/RupiahText.vue';
import TextInput from '@/Components/TextInput.vue';
import { useTableFilters } from '@/Composables/useTableFilters';
import { useDateId } from '@/Composables/useDateId';

const props = defineProps({
    invoices: Object,
    filters: Object,
});

const { filters, resetFilters } = useTableFilters('/admin/invoices', props.filters);
const { formatDate } = useDateId();

const columns = [
    { key: 'number', label: 'No. Tagihan' },
    { key: 'tenant', label: 'Penghuni' },
    { key: 'type', label: 'Tipe' },
    { key: 'due_date', label: 'Jatuh Tempo', isNum: true },
    { key: 'total', label: 'Total', align: 'right', isNum: true },
    { key: 'paid_amount', label: 'Dibayar', align: 'right', isNum: true },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Aksi', align: 'right' },
];
</script>

<template>
    <Head title="Manajemen Tagihan" />

    <AdminLayout title="Daftar Tagihan & Status Pembayaran">
        <div class="space-y-6">
            <BentoCard title="Filter Tagihan">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <TextInput
                        v-model="filters.search"
                        placeholder="No. invoice, nama penyewa..."
                    />
                    <select
                        v-model="filters.status"
                        class="rounded-xl border border-line-200 bg-white px-3.5 py-2.5 text-sm text-ink-900 focus:border-forest-500 focus:outline-none"
                    >
                        <option value="">Semua Status Tagihan</option>
                        <option value="unpaid">Belum Lunas (Unpaid)</option>
                        <option value="partial">Sebagian (Partial)</option>
                        <option value="paid">Lunas (Paid)</option>
                        <option value="overdue">Terlambat (Overdue)</option>
                        <option value="void">Batal (Void)</option>
                    </select>
                    <select
                        v-model="filters.type"
                        class="rounded-xl border border-line-200 bg-white px-3.5 py-2.5 text-sm text-ink-900 focus:border-forest-500 focus:outline-none"
                    >
                        <option value="">Semua Tipe Tagihan</option>
                        <option value="monthly">Bulanan</option>
                        <option value="weekly">Mingguan</option>
                        <option value="reservation">Reservasi</option>
                        <option value="settlement">Pelunasan</option>
                        <option value="daily_extension">Perpanjangan Harian</option>
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

            <DataTable
                :columns="columns"
                :data="invoices.data"
                :pagination="invoices"
                empty-text="Belum ada tagihan yang terdaftar."
            >
                <template #cell-number="{ row }">
                    <span class="num font-bold text-forest-900">{{ row.number }}</span>
                </template>
                <template #cell-tenant="{ row }">
                    <div class="font-bold text-forest-900">{{ row.user?.name || '-' }}</div>
                    <div class="num text-[11px] text-ink-500">{{ row.user?.phone }}</div>
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
                            :href="`/admin/invoices/${row.id}`"
                            class="rounded-lg bg-forest-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-forest-800 transition"
                        >
                            Detail
                        </Link>
                    </div>
                </template>
            </DataTable>
        </div>
    </AdminLayout>
</template>

