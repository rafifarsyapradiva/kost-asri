<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    columns: {
        type: Array,
        required: true, // [{ key: 'id', label: 'ID', align: 'left'|'right'|'center', isNum: boolean }]
    },
    data: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    emptyText: {
        type: String,
        default: 'Tidak ada data yang ditemukan.',
    },
    pagination: {
        type: Object,
        default: null, // Laravel pagination meta / links
    },
});
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-line-200 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-ink-500">
                    <tr>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            scope="col"
                            :class="[
                                'px-4 py-3.5',
                                col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left'
                            ]"
                        >
                            {{ col.label }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line-200 bg-white text-ink-900">
                    <!-- Loading Skeleton -->
                    <tr v-if="loading" v-for="n in 5" :key="'skeleton-' + n" class="animate-pulse">
                        <td v-for="col in columns" :key="'col-' + col.key" class="px-4 py-3.5">
                            <div class="h-4 rounded bg-slate-200"></div>
                        </td>
                    </tr>

                    <!-- Empty State -->
                    <tr v-else-if="!data || data.length === 0">
                        <td :colspan="columns.length" class="px-4 py-12 text-center text-sm text-ink-500">
                            {{ emptyText }}
                        </td>
                    </tr>

                    <!-- Data Rows -->
                    <tr
                        v-else
                        v-for="(row, index) in data"
                        :key="row.id || index"
                        class="transition hover:bg-slate-50/60"
                    >
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            :class="[
                                'px-4 py-3.5 text-sm',
                                col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left',
                                col.isNum ? 'num' : ''
                            ]"
                        >
                            <slot :name="'cell-' + col.key" :row="row" :value="row[col.key]">
                                {{ row[col.key] !== null && row[col.key] !== undefined ? row[col.key] : '-' }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div
            v-if="pagination && pagination.links && pagination.links.length > 3"
            class="flex items-center justify-between border-t border-line-200 bg-white px-4 py-3 text-xs sm:px-6"
        >
            <div class="text-ink-500">
                Menampilkan <span class="num font-semibold text-ink-900">{{ pagination.from || 0 }}</span> sampai
                <span class="num font-semibold text-ink-900">{{ pagination.to || 0 }}</span> dari
                <span class="num font-semibold text-ink-900">{{ pagination.total || 0 }}</span> data
            </div>
            <div class="flex flex-wrap gap-1">
                <template v-for="(link, i) in pagination.links" :key="i">
                    <span
                        v-if="!link.url"
                        class="num rounded-lg border border-line-200 px-3 py-1.5 text-slate-400 cursor-not-allowed"
                        v-html="link.label"
                    />
                    <Link
                        v-else
                        :href="link.url"
                        :class="[
                            'num rounded-lg border px-3 py-1.5 font-medium transition',
                            link.active
                                ? 'border-forest-900 bg-forest-900 text-white'
                                : 'border-line-200 bg-white text-ink-700 hover:bg-slate-50'
                        ]"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </div>
</template>

