<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import RoomClassBadge from '@/Components/RoomClassBadge.vue';
import RupiahText from '@/Components/RupiahText.vue';
import { useDateId } from '@/Composables/useDateId';

const props = defineProps({
    reservations: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ status: '', search: '' }),
    },
});

const { formatDate } = useDateId();
const statusFilter = ref(props.filters.status || '');
const searchQuery = ref(props.filters.search || '');

const applyFilters = () => {
    router.get(
        '/admin/reservations',
        {
            status: statusFilter.value,
            search: searchQuery.value,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
};

let debounceTimer = null;
watch(searchQuery, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 350);
});

watch(statusFilter, applyFilters);
</script>

<template>
    <Head title="Kelola Reservasi" />

    <AdminLayout title="Daftar Reservasi & Check-In">
        <div class="space-y-6">
            <!-- Header actions -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-forest-900">Pemesanan Online & Tamu Walk-in</h2>
                    <p class="text-xs text-ink-500">Check-in hanya dapat diproses apabila tagihan sewa awal telah lunas 100% (BR-06).</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        href="/admin/reservations/create"
                        class="rounded-xl bg-forest-900 px-4 py-2 text-xs font-semibold text-white hover:bg-forest-800 transition shadow-2xs flex items-center gap-1.5"
                    >
                        <span>+ Reservasi Manual Walk-In</span>
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <BentoCard title="Daftar Seluruh Reservasi">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 mb-5">
                    <div class="flex flex-wrap items-center gap-2">
                        <select
                            v-model="statusFilter"
                            class="rounded-xl border-line-300 text-xs py-2 px-3 focus:ring-forest-500 focus:border-forest-500 bg-white"
                        >
                            <option value="">Semua Status Reservasi</option>
                            <option value="pending_payment">Menunggu Pembayaran</option>
                            <option value="dp_paid">DP Terbayar (Belum Lunas)</option>
                            <option value="paid">Lunas 100%</option>
                            <option value="confirmed">Terkonfirmasi (Aktif Huni)</option>
                            <option value="cancelled">Dibatalkan</option>
                            <option value="expired">Kedaluwarsa (Expired)</option>
                        </select>
                    </div>

                    <div class="w-full sm:w-72">
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="Cari kode, nama tamu, atau nomor..."
                            class="w-full rounded-xl border border-line-300 text-xs py-2 px-3 focus:ring-forest-500 focus:border-forest-500 bg-white"
                        />
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line-200 text-xs text-left">
                        <thead class="bg-slate-50 text-ink-500 font-semibold uppercase">
                            <tr>
                                <th class="px-4 py-3">Kode Reservasi</th>
                                <th class="px-4 py-3">Tamu & Kontak</th>
                                <th class="px-4 py-3">Kamar</th>
                                <th class="px-4 py-3">Tgl Rencana Masuk</th>
                                <th class="px-4 py-3">Total Biaya</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line-100">
                            <tr v-for="res in reservations.data" :key="res.id" class="hover:bg-slate-50/60 transition">
                                <td class="px-4 py-3 font-bold text-forest-900 num">
                                    {{ res.code }}
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-bold text-ink-900">{{ res.user?.name || 'Tamu' }}</p>
                                    <p class="text-[11px] text-ink-500 num">{{ res.user?.phone }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <span class="num font-bold text-forest-900">{{ res.room?.code }}</span>
                                        <RoomClassBadge :room-class="res.room?.room_type?.room_class" />
                                    </div>
                                    <span class="text-[10px] text-ink-500 capitalize">{{ res.rent_type }} ({{ res.duration_qty }} {{ res.rent_type === 'daily' ? 'Malam' : (res.rent_type === 'weekly' ? 'Minggu' : 'Bulan') }})</span>
                                </td>
                                <td class="px-4 py-3 num">
                                    {{ formatDate(res.check_in_date) }}
                                </td>
                                <td class="px-4 py-3">
                                    <RupiahText :amount="res.total_amount" class="font-bold text-forest-900 block" />
                                    <span v-if="res.payment_scheme === 'dp'" class="text-[10px] text-amber-700 font-medium">
                                        DP: <RupiahText :amount="res.dp_amount" />
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <StatusBadge :status="res.status" />
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <Link
                                            :href="`/admin/reservations/${res.id}`"
                                            class="px-3 py-1.5 rounded-lg border border-line-200 text-ink-700 hover:bg-slate-100 font-semibold text-[11px] transition shadow-2xs"
                                        >
                                            Detail
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!reservations.data || reservations.data.length === 0">
                                <td colspan="7" class="px-4 py-10 text-center text-ink-500">
                                    Tidak ada data reservasi yang ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="reservations.links && reservations.links.length > 3" class="mt-4 pt-4 border-t border-line-200 flex justify-end gap-1 text-xs">
                    <Component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, idx) in reservations.links"
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
        </div>
    </AdminLayout>
</template>

