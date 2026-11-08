<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import KpiStatCard from '@/Components/KpiStatCard.vue';
import RupiahText from '@/Components/RupiahText.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useRupiah } from '@/Composables/useRupiah';
import { useDateId } from '@/Composables/useDateId';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    today_schedule: {
        type: Object,
        default: () => ({ check_ins: [], check_outs: [] }),
    },
    upcoming_due_invoices: {
        type: Array,
        default: () => [],
    },
    recent_reservations: {
        type: Array,
        default: () => [],
    },
    cron_health: {
        type: Object,
        default: () => ({}),
    },
});

const { formatRupiah } = useRupiah();
const { formatDate, fromNow } = useDateId();
</script>

<template>
    <Head title="Dashboard Admin" />

    <AdminLayout title="Dashboard Eksekutif">
        <div class="space-y-8">
            <!-- Bento Row 1: Top 4 KPI Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <KpiStatCard
                    title="Tingkat Okupansi"
                    :value="`${stats.occupancy_rate}%`"
                    :subtitle="`${stats.occupied_rooms} dari ${stats.total_rooms} kamar terisi`"
                    :badge-text="stats.occupancy_rate >= 80 ? 'Optimal' : 'Tersedia'"
                    :badge-type="stats.occupancy_rate >= 80 ? 'success' : 'neutral'"
                />

                <KpiStatCard
                    title="Pendapatan Bulan Ini"
                    :value="formatRupiah(stats.revenue_mtd?.total || 0)"
                    :subtitle="`Cash: ${formatRupiah(stats.revenue_mtd?.cash || 0)} | Midtrans: ${formatRupiah(stats.revenue_mtd?.midtrans || 0)}`"
                    badge-text="MTD"
                    badge-type="success"
                />

                <KpiStatCard
                    title="Total Tunggakan"
                    :value="formatRupiah(stats.arrears_total || 0)"
                    subtitle="Tagihan belum terbayar & denda"
                    :badge-text="stats.arrears_total > 0 ? 'Perlu Ditagih' : 'Nol'"
                    :badge-type="stats.arrears_total > 0 ? 'danger' : 'success'"
                />

                <KpiStatCard
                    title="Kamar Siap Huni"
                    :value="`${stats.available_rooms} Kamar`"
                    :subtitle="`Dipesan: ${stats.booked_rooms} | Perbaikan: ${stats.maintenance_rooms}`"
                    badge-text="Ready"
                    badge-type="success"
                />
            </div>

            <!-- Bento Row 2: Jadwal Hari Ini & Status Kamar -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Check-in & Check-out Hari Ini -->
                <BentoCard
                    title="Jadwal Hari Ini"
                    subtitle="Daftar calon penghuni masuk & check-out"
                    class="lg:col-span-2"
                >
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Check-ins -->
                        <div class="rounded-xl border border-line-100 bg-slate-50/50 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-forest-800">
                                    Check-In Hari Ini ({{ today_schedule.check_ins.length }})
                                </h4>
                            </div>
                            <div v-if="today_schedule.check_ins.length === 0" class="py-6 text-center text-xs text-ink-500">
                                Tidak ada jadwal check-in hari ini.
                            </div>
                            <div v-else class="space-y-2.5">
                                <div
                                    v-for="r in today_schedule.check_ins"
                                    :key="r.id"
                                    class="p-2.5 rounded-lg bg-white border border-line-200 flex items-center justify-between text-xs"
                                >
                                    <div>
                                        <p class="font-bold text-forest-900">{{ r.user?.name || 'Tamu' }}</p>
                                        <p class="text-[11px] text-ink-500">Kamar <span class="num font-semibold">{{ r.room?.code }}</span> ({{ r.room?.room_type?.name }})</p>
                                    </div>
                                    <Link
                                        :href="`/admin/reservations`"
                                        class="px-2.5 py-1 rounded bg-forest-900 text-white font-semibold text-[11px]"
                                    >
                                        Proses
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Check-outs -->
                        <div class="rounded-xl border border-line-100 bg-slate-50/50 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-terracotta-700">
                                    Check-Out Hari Ini ({{ today_schedule.check_outs.length }})
                                </h4>
                            </div>
                            <div v-if="today_schedule.check_outs.length === 0" class="py-6 text-center text-xs text-ink-500">
                                Tidak ada jadwal check-out hari ini.
                            </div>
                            <div v-else class="space-y-2.5">
                                <div
                                    v-for="l in today_schedule.check_outs"
                                    :key="l.id"
                                    class="p-2.5 rounded-lg bg-white border border-line-200 flex items-center justify-between text-xs"
                                >
                                    <div>
                                        <p class="font-bold text-forest-900">{{ l.tenant?.name || 'Penghuni' }}</p>
                                        <p class="text-[11px] text-ink-500">Kamar <span class="num font-semibold">{{ l.room?.code }}</span></p>
                                    </div>
                                    <Link
                                        :href="`/admin/leases`"
                                        class="px-2.5 py-1 rounded bg-slate-100 text-ink-800 font-semibold text-[11px] hover:bg-slate-200"
                                    >
                                        Check-out
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </BentoCard>

                <!-- Status 32 Kamar -->
                <BentoCard
                    title="Ringkasan 32 Kamar"
                    subtitle="Status seluruh unit lantai 1 & 2"
                >
                    <template #action>
                        <Link href="/admin/rooms/map" class="text-xs font-bold text-forest-700 hover:text-forest-900">
                            Buka Denah →
                        </Link>
                    </template>

                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-green-50 border border-green-200 text-green-900">
                            <div class="flex items-center gap-2 text-xs font-semibold">
                                <span class="h-2.5 w-2.5 rounded-full bg-green-600"></span>
                                <span>Tersedia (Siap Huni)</span>
                            </div>
                            <span class="num font-bold text-sm">{{ stats.available_rooms }}</span>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-red-50 border border-red-200 text-red-900">
                            <div class="flex items-center gap-2 text-xs font-semibold">
                                <span class="h-2.5 w-2.5 rounded-full bg-red-600"></span>
                                <span>Terisi (Aktif Huni)</span>
                            </div>
                            <span class="num font-bold text-sm">{{ stats.occupied_rooms }}</span>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-yellow-50 border border-yellow-200 text-yellow-900">
                            <div class="flex items-center gap-2 text-xs font-semibold">
                                <span class="h-2.5 w-2.5 rounded-full bg-yellow-600"></span>
                                <span>Dipesan (Pending / DP)</span>
                            </div>
                            <span class="num font-bold text-sm">{{ stats.booked_rooms }}</span>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-100 border border-slate-300 text-slate-800">
                            <div class="flex items-center gap-2 text-xs font-semibold">
                                <span class="h-2.5 w-2.5 rounded-full bg-slate-500"></span>
                                <span>Perbaikan / Maintenance</span>
                            </div>
                            <span class="num font-bold text-sm">{{ stats.maintenance_rooms }}</span>
                        </div>
                    </div>
                </BentoCard>
            </div>

            <!-- Bento Row 3: Tagihan Jatuh Tempo & Reservasi Terbaru -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Tagihan Jatuh Tempo -->
                <BentoCard
                    title="Tagihan Jatuh Tempo & Overdue"
                    subtitle="Perlu penanganan atau follow-up WA"
                >
                    <div v-if="upcoming_due_invoices.length === 0" class="py-10 text-center text-xs text-ink-500">
                        Tidak ada tagihan yang mendekati jatuh tempo.
                    </div>
                    <div v-else class="divide-y divide-line-100">
                        <div
                            v-for="inv in upcoming_due_invoices"
                            :key="inv.id"
                            class="py-3 flex items-center justify-between text-xs"
                        >
                            <div>
                                <p class="font-bold text-forest-900">{{ inv.tenant?.name || 'Penyewa' }}</p>
                                <p class="text-[11px] text-ink-500">
                                    {{ inv.invoice_number }} • Jatuh Tempo: <span class="num font-semibold">{{ formatDate(inv.due_date) }}</span>
                                </p>
                            </div>
                            <div class="text-right">
                                <RupiahText :amount="inv.total_amount - inv.paid_amount" class="text-sm font-bold text-danger-700 block" />
                                <StatusBadge :status="inv.status" class="mt-0.5" />
                            </div>
                        </div>
                    </div>
                </BentoCard>

                <!-- Reservasi Terbaru -->
                <BentoCard
                    title="Reservasi Terbaru"
                    subtitle="Pemesanan online & walk-in terbaru"
                >
                    <div v-if="recent_reservations.length === 0" class="py-10 text-center text-xs text-ink-500">
                        Belum ada data reservasi.
                    </div>
                    <div v-else class="divide-y divide-line-100">
                        <div
                            v-for="res in recent_reservations"
                            :key="res.id"
                            class="py-3 flex items-center justify-between text-xs"
                        >
                            <div>
                                <p class="font-bold text-forest-900">{{ res.user?.name || 'Calon Penyewa' }}</p>
                                <p class="text-[11px] text-ink-500">
                                    Kamar <span class="num font-semibold">{{ res.room?.code }}</span> • {{ res.code }}
                                </p>
                            </div>
                            <div class="text-right">
                                <StatusBadge :status="res.status" class="block mb-1" />
                                <span class="text-[10px] text-ink-400 num">{{ fromNow(res.created_at) }}</span>
                            </div>
                        </div>
                    </div>
                </BentoCard>
            </div>

            <!-- Bento Row 4: Status Kesehatan Cronjob Otomatis -->
            <BentoCard
                title="Lampu Pemantauan Cronjob Otomatis"
                subtitle="Status eksekusi 7 command scheduler Laravel"
            >
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div
                        v-for="(timestamp, key) in cron_health"
                        :key="key"
                        class="p-3 rounded-xl border border-line-200 bg-slate-50/50"
                    >
                        <div class="flex items-center gap-1.5 mb-1">
                            <span :class="['h-2 w-2 rounded-full', timestamp ? 'bg-green-500' : 'bg-yellow-500']"></span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-ink-700 truncate">
                                {{ key.replace('_', ' ') }}
                            </span>
                        </div>
                        <p class="num text-[11px] font-medium text-ink-600 truncate">
                            {{ timestamp ? fromNow(timestamp) : 'Belum jalan' }}
                        </p>
                    </div>
                </div>
            </BentoCard>
        </div>
    </AdminLayout>
</template>

