<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import DataTable from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TextInput from '@/Components/TextInput.vue';
import { useTableFilters } from '@/Composables/useTableFilters';

const props = defineProps({
    leases: Object,
    filters: Object,
});

const { filters, resetFilters } = useTableFilters('/admin/leases', props.filters);

const columns = [
    { key: 'tenant', label: 'Penghuni' },
    { key: 'room', label: 'Kamar', isNum: true },
    { key: 'rent_type', label: 'Siklus' },
    { key: 'period', label: 'Periode Sewa', isNum: true },
    { key: 'paid_until', label: 'Dibayar Hingga', isNum: true },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Aksi', align: 'right' },
];
</script>

<template>
    <Head title="Kontrak Sewa" />

    <AdminLayout title="Manajemen Kontrak Sewa (Leases)">
        <div class="space-y-6">
            <BentoCard title="Filter Kontrak">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <TextInput
                        v-model="filters.search"
                        placeholder="Cari penghuni, no kamar..."
                    />
                    <select
                        v-model="filters.status"
                        class="rounded-xl border border-line-200 bg-white px-3.5 py-2.5 text-sm text-ink-900 focus:border-forest-500 focus:outline-none"
                    >
                        <option value="">Semua Status Kontrak</option>
                        <option value="active">Aktif (Berjalan)</option>
                        <option value="ending">Segera Berakhir</option>
                        <option value="completed">Selesai (Check-Out)</option>
                    </select>
                    <select
                        v-model="filters.rent_type"
                        class="rounded-xl border border-line-200 bg-white px-3.5 py-2.5 text-sm text-ink-900 focus:border-forest-500 focus:outline-none"
                    >
                        <option value="">Semua Tipe Sewa</option>
                        <option value="monthly">Bulanan</option>
                        <option value="weekly">Mingguan</option>
                        <option value="daily">Harian</option>
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
                :data="leases.data"
                :pagination="leases"
                empty-text="Belum ada kontrak sewa ditemukan."
            >
                <template #cell-tenant="{ row }">
                    <div class="font-bold text-forest-900">{{ row.tenant?.name || 'Penghuni' }}</div>
                    <div class="num text-[11px] text-ink-500">{{ row.tenant?.phone }}</div>
                </template>
                <template #cell-room="{ row }">
                    <span class="num font-bold">Kamar {{ row.room?.code }}</span>
                </template>
                <template #cell-rent_type="{ row }">
                    <span class="capitalize text-xs font-semibold text-ink-700">{{ row.rent_type }}</span>
                </template>
                <template #cell-period="{ row }">
                    <span class="num text-xs">{{ row.start_date }} s/d {{ row.planned_checkout_date || 'Aktif' }}</span>
                </template>
                <template #cell-paid_until="{ row }">
                    <span class="num text-xs font-semibold text-forest-800">{{ row.paid_until || '-' }}</span>
                </template>
                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" />
                </template>
                <template #cell-actions="{ row }">
                    <Link
                        :href="`/admin/leases/${row.id}`"
                        class="rounded-lg bg-forest-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-forest-800 transition"
                    >
                        Detail
                    </Link>
                </template>
            </DataTable>
        </div>
    </AdminLayout>
</template>

