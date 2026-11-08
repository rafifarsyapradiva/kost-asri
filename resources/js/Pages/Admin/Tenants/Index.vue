<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import DataTable from '@/Components/DataTable.vue';
import TextInput from '@/Components/TextInput.vue';
import { useTableFilters } from '@/Composables/useTableFilters';

const props = defineProps({
    tenants: Object,
    filters: Object,
});

const { filters, resetFilters } = useTableFilters('/admin/tenants', props.filters);

const columns = [
    { key: 'name', label: 'Nama Penyewa' },
    { key: 'phone', label: 'WhatsApp / Telepon', isNum: true },
    { key: 'email', label: 'Email' },
    { key: 'role', label: 'Role' },
    { key: 'actions', label: 'Aksi', align: 'right' },
];
</script>

<template>
    <Head title="Daftar Penyewa" />

    <AdminLayout title="Direktori Penyewa Kost">
        <div class="space-y-6">
            <BentoCard title="Filter & Pencarian Penyewa">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <TextInput
                        v-model="filters.search"
                        placeholder="Cari nama, email, nomor WA..."
                    />
                    <select
                        v-model="filters.status"
                        class="rounded-xl border border-line-200 bg-white px-3.5 py-2.5 text-sm text-ink-900 focus:border-forest-500 focus:outline-none"
                    >
                        <option value="">Semua Status Penyewa</option>
                        <option value="active">Penyewa Aktif (Menghuni)</option>
                        <option value="alumni">Alumni Kost</option>
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
                :data="tenants.data"
                :pagination="tenants"
                empty-text="Belum ada data penyewa yang terdaftar."
            >
                <template #cell-name="{ row }">
                    <div class="font-bold text-forest-900">{{ row.name }}</div>
                </template>
                <template #cell-phone="{ row }">
                    <span class="num">{{ row.phone || '-' }}</span>
                </template>
                <template #cell-role="{ row }">
                    <span class="capitalize text-xs font-semibold text-ink-600">{{ row.role.replace('_', ' ') }}</span>
                </template>
                <template #cell-actions="{ row }">
                    <Link
                        :href="`/admin/tenants/${row.id}`"
                        class="rounded-lg bg-forest-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-forest-800 transition"
                    >
                        Detail Profil
                    </Link>
                </template>
            </DataTable>
        </div>
    </AdminLayout>
</template>

