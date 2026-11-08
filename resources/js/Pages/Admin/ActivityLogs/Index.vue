<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import DataTable from '@/Components/DataTable.vue';
import { useDateId } from '@/Composables/useDateId';

const props = defineProps({
    logs: Object,
});

const { formatDateTime } = useDateId();

const columns = [
    { key: 'created_at', label: 'Waktu', isNum: true },
    { key: 'actor', label: 'Aktor / Admin' },
    { key: 'action', label: 'Aksi' },
    { key: 'description', label: 'Deskripsi' },
    { key: 'ip_address', label: 'IP Address', isNum: true },
];
</script>

<template>
    <Head title="Log Aktivitas Admin" />

    <AdminLayout title="Jejak Audit & Log Aktivitas Admin (NFR-09)">
        <div class="space-y-6">
            <DataTable
                :columns="columns"
                :data="logs.data"
                :pagination="logs"
                empty-text="Belum ada catatan aktivitas admin."
            >
                <template #cell-created_at="{ row }">
                    <span class="num text-xs text-ink-600">{{ formatDateTime(row.created_at) }}</span>
                </template>
                <template #cell-actor="{ row }">
                    <div class="font-bold text-forest-900">{{ row.actor?.name || 'Sistem' }}</div>
                </template>
                <template #cell-action="{ row }">
                    <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-mono font-semibold text-ink-700">
                        {{ row.action }}
                    </span>
                </template>
                <template #cell-description="{ row }">
                    <span class="text-xs text-ink-800">{{ row.description }}</span>
                </template>
                <template #cell-ip_address="{ row }">
                    <span class="num text-xs text-ink-500 font-mono">{{ row.ip_address }}</span>
                </template>
            </DataTable>
        </div>
    </AdminLayout>
</template>

