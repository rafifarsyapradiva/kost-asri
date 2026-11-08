<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import KpiStatCard from '@/Components/KpiStatCard.vue';
import RupiahText from '@/Components/RupiahText.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useRupiah } from '@/Composables/useRupiah';

const props = defineProps({
    revenue: Object,
    arrears: Object,
    occupancy: Object,
    filters: Object,
});

const activeTab = ref('revenue'); // revenue, arrears, occupancy
const { formatRupiah } = useRupiah();
</script>

<template>
    <Head title="Laporan & Keuangan" />

    <AdminLayout title="Pusat Laporan & Rekonsiliasi Keuangan">
        <div class="space-y-6">
            <!-- Header Tabs & Export Buttons -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="inline-flex rounded-xl border border-line-200 bg-white p-1 shadow-xs">
                    <button
                        type="button"
                        @click="activeTab = 'revenue'"
                        :class="[
                            'rounded-lg px-4 py-2 text-xs font-semibold transition',
                            activeTab === 'revenue' ? 'bg-forest-900 text-white shadow-xs' : 'text-ink-600 hover:text-ink-900'
                        ]"
                    >
                        Laporan Pendapatan
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'arrears'"
                        :class="[
                            'rounded-lg px-4 py-2 text-xs font-semibold transition',
                            activeTab === 'arrears' ? 'bg-forest-900 text-white shadow-xs' : 'text-ink-600 hover:text-ink-900'
                        ]"
                    >
                        Laporan Tunggakan & Denda
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'occupancy'"
                        :class="[
                            'rounded-lg px-4 py-2 text-xs font-semibold transition',
                            activeTab === 'occupancy' ? 'bg-forest-900 text-white shadow-xs' : 'text-ink-600 hover:text-ink-900'
                        ]"
                    >
                        Laporan Okupansi Kamar
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <a
                        :href="`/admin/reports/export-csv?type=${activeTab}&date_from=${filters.date_from}&date_to=${filters.date_to}`"
                        class="rounded-xl border border-line-200 bg-white px-3.5 py-2 text-xs font-semibold text-ink-700 hover:bg-slate-50 transition shadow-xs flex items-center gap-1.5"
                    >
                        <span>Unduh CSV</span>
                    </a>
                    <a
                        href="/admin/reports/pdf"
                        target="_blank"
                        class="rounded-xl bg-forest-900 px-3.5 py-2 text-xs font-semibold text-white hover:bg-forest-800 transition shadow-xs flex items-center gap-1.5"
                    >
                        <span>Cetak PDF</span>
                    </a>
                </div>
            </div>

            <!-- Tab 1: Revenue -->
            <div v-if="activeTab === 'revenue'" class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <KpiStatCard
                        title="Total Pendapatan Terbayar"
                        :value="formatRupiah(revenue.total || 0)"
                        subtitle="Periode laporan saat ini"
                        badge-text="Sah"
                        badge-type="success"
                    />
                </div>

                <BentoCard title="Rincian Transaksi Pembayaran">
                    <div v-if="!revenue.payments || revenue.payments.length === 0" class="py-8 text-center text-xs text-ink-500">
                        Tidak ada transaksi pada periode ini.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-line-200 text-xs text-left">
                            <thead class="bg-slate-50 text-ink-500 font-semibold uppercase">
                                <tr>
                                    <th class="px-4 py-3">Tanggal</th>
                                    <th class="px-4 py-3">No. Invoice</th>
                                    <th class="px-4 py-3">Penghuni</th>
                                    <th class="px-4 py-3">Metode</th>
                                    <th class="px-4 py-3 text-right">Nominal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line-100">
                                <tr v-for="p in revenue.payments" :key="p.id">
                                    <td class="px-4 py-3 num text-ink-600">{{ p.paid_at }}</td>
                                    <td class="px-4 py-3 font-semibold num text-forest-900">{{ p.invoice?.number }}</td>
                                    <td class="px-4 py-3 font-medium">{{ p.invoice?.user?.name || '-' }}</td>
                                    <td class="px-4 py-3 capitalize text-ink-700">{{ p.method }}</td>
                                    <td class="px-4 py-3 text-right num font-bold text-forest-900">
                                        <RupiahText :amount="p.amount" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </BentoCard>
            </div>

            <!-- Tab 2: Arrears -->
            <div v-else-if="activeTab === 'arrears'" class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <KpiStatCard
                        title="Total Tunggakan Aktif"
                        :value="formatRupiah(arrears.total || 0)"
                        subtitle="Jumlah tagihan belum lunas + denda"
                        badge-text="Arrears"
                        badge-type="danger"
                    />
                </div>

                <BentoCard title="Daftar Tagihan Belum Terbayar">
                    <div v-if="!arrears.invoices || arrears.invoices.length === 0" class="py-8 text-center text-xs text-ink-500">
                        Bebas tunggakan! Semua tagihan telah lunas.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-line-200 text-xs text-left">
                            <thead class="bg-slate-50 text-ink-500 font-semibold uppercase">
                                <tr>
                                    <th class="px-4 py-3">No. Invoice</th>
                                    <th class="px-4 py-3">Penghuni</th>
                                    <th class="px-4 py-3">Jatuh Tempo</th>
                                    <th class="px-4 py-3 text-right">Total</th>
                                    <th class="px-4 py-3 text-right">Sisa Tagihan</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line-100">
                                <tr v-for="inv in arrears.invoices" :key="inv.id">
                                    <td class="px-4 py-3 font-semibold num text-forest-900">{{ inv.number }}</td>
                                    <td class="px-4 py-3 font-medium">{{ inv.user?.name || '-' }}</td>
                                    <td class="px-4 py-3 num text-danger-700">{{ inv.due_date }}</td>
                                    <td class="px-4 py-3 text-right num">
                                        <RupiahText :amount="inv.total" />
                                    </td>
                                    <td class="px-4 py-3 text-right num font-bold text-danger-700">
                                        <RupiahText :amount="inv.total - inv.paid_amount" />
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <StatusBadge :status="inv.status" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </BentoCard>
            </div>

            <!-- Tab 3: Occupancy -->
            <div v-else class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <KpiStatCard
                        title="Tingkat Okupansi Kost"
                        :value="`${occupancy.occupancy_rate}%`"
                        :subtitle="`${occupancy.occupied_rooms} dari ${occupancy.total_rooms} kamar terisi`"
                        badge-text="Kapasitas"
                        badge-type="success"
                    />
                </div>

                <BentoCard title="Okupansi per Kelas Kamar">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div
                            v-for="cls in occupancy.by_class"
                            :key="cls.id"
                            class="p-4 rounded-xl border border-line-200 bg-slate-50/50"
                        >
                            <h4 class="font-bold text-forest-900 text-sm">{{ cls.name }}</h4>
                            <p class="num text-2xl font-black text-forest-900 mt-2">
                                {{ cls.occupied_rooms }} / {{ cls.total_rooms }}
                            </p>
                            <div class="w-full bg-slate-200 rounded-full h-2 mt-3 overflow-hidden">
                                <div
                                    class="bg-forest-900 h-2 rounded-full"
                                    :style="`width: ${cls.total_rooms > 0 ? (cls.occupied_rooms / cls.total_rooms) * 100 : 0}%`"
                                ></div>
                            </div>
                        </div>
                    </div>
                </BentoCard>
            </div>
        </div>
    </AdminLayout>
</template>

