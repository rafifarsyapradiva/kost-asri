<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import RupiahText from '@/Components/RupiahText.vue';
import { useDateId } from '@/Composables/useDateId';
import { useSnap } from '@/Composables/useSnap';

const props = defineProps({
    lease: Object,
    active_invoice: Object,
    recent_payments: Array,
});

const { formatDate } = useDateId();
const { payWithSnap, isLoading } = useSnap();
</script>

<template>
    <Head title="Beranda Penghuni" />

    <TenantLayout title="Beranda Penghuni">
        <div class="space-y-6">
            <!-- Active Room & Stay Status Bento Card -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Kamar Saya -->
                <BentoCard
                    v-if="lease"
                    title="Kamar Saya"
                    :subtitle="lease.room?.room_type?.name"
                    class="md:col-span-2"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 border-b border-line-100 pb-6 mb-6">
                        <div class="flex items-center gap-4">
                            <div class="h-16 w-16 rounded-2xl bg-forest-900 text-white font-black text-2xl flex items-center justify-center shadow">
                                <span class="num">{{ lease.room?.code }}</span>
                            </div>
                            <div>
                                <span class="text-xs uppercase font-bold text-forest-700 tracking-wider">
                                    Lantai {{ lease.room?.floor }} • {{ lease.room?.room_type?.room_class }}
                                </span>
                                <h3 class="text-xl font-bold text-forest-900 mt-0.5">{{ lease.room?.room_type?.name }}</h3>
                                <p class="text-xs text-ink-500">Mulai sewa: <span class="num font-semibold">{{ formatDate(lease.start_date) }}</span></p>
                            </div>
                        </div>

                        <div class="text-left sm:text-right bg-slate-50 p-4 rounded-xl border border-line-200">
                            <span class="text-xs text-ink-500 block">Sewa Lunas Hingga:</span>
                            <span class="num text-lg font-bold text-forest-900 block mt-0.5">
                                {{ formatDate(lease.paid_until) }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <span class="text-ink-600">Siklus: <strong class="capitalize">{{ lease.rent_type }}</strong></span>
                        <Link href="/penyewa/extension" class="font-bold text-forest-700 hover:text-forest-900">
                            Kelola Perpanjangan Sewa →
                        </Link>
                    </div>
                </BentoCard>

                <BentoCard v-else title="Status Hunian" class="md:col-span-2">
                    <p class="text-xs text-ink-500 py-6 text-center">Anda belum memiliki kontrak sewa aktif.</p>
                </BentoCard>

                <!-- Tagihan Aktif / Bayar Sekarang -->
                <BentoCard title="Tagihan Menunggu">
                    <div v-if="active_invoice" class="space-y-4">
                        <div>
                            <span class="text-xs text-ink-500 block">Nomor Invoice:</span>
                            <span class="num text-xs font-bold text-forest-900">{{ active_invoice.number }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-ink-500 block">Total Tagihan:</span>
                            <RupiahText :amount="active_invoice.total - active_invoice.paid_amount" class="text-xl font-extrabold text-danger-700 block" />
                        </div>
                        <div>
                            <span class="text-xs text-ink-500 block">Jatuh Tempo:</span>
                            <span class="num text-xs font-semibold text-danger-700">{{ formatDate(active_invoice.due_date) }}</span>
                        </div>

                        <Link
                            :href="`/penyewa/invoices/${active_invoice.id}`"
                            class="block w-full text-center py-2.5 rounded-xl bg-terracotta-500 hover:bg-terracotta-400 text-ink-900 font-bold text-xs shadow-xs transition"
                        >
                            Bayar Sekarang
                        </Link>
                    </div>
                    <div v-else class="py-8 text-center text-xs text-ink-500">
                        <div class="h-10 w-10 rounded-full bg-green-50 text-green-600 mx-auto flex items-center justify-center mb-2">
                            ✓
                        </div>
                        Semua tagihan lunas! Tidak ada pembayaran yang tertunda.
                    </div>
                </BentoCard>
            </div>

            <!-- Riwayat Pembayaran Terbaru -->
            <BentoCard title="Riwayat Pembayaran Terbaru">
                <div v-if="!recent_payments || recent_payments.length === 0" class="py-8 text-center text-xs text-ink-500">
                    Belum ada riwayat pembayaran.
                </div>
                <div v-else class="divide-y divide-line-100">
                    <div
                        v-for="p in recent_payments"
                        :key="p.id"
                        class="py-3 flex items-center justify-between text-xs"
                    >
                        <div>
                            <p class="font-bold text-forest-900">Pembayaran Sewa</p>
                            <p class="num text-[11px] text-ink-500">{{ p.paid_at }} • Metode: {{ p.method }}</p>
                        </div>
                        <div class="text-right">
                            <RupiahText :amount="p.amount" class="font-bold text-sm block" />
                            <StatusBadge status="paid" class="mt-0.5" />
                        </div>
                    </div>
                </div>
            </BentoCard>
        </div>
    </TenantLayout>
</template>

