<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import RupiahText from '@/Components/RupiahText.vue';
import { useDateId } from '@/Composables/useDateId';

const props = defineProps({
    invoice: Object,
});

const { formatDate } = useDateId();
</script>

<template>
    <Head :title="`Invoice #${invoice.number}`" />

    <AdminLayout :title="`Detail Tagihan: ${invoice.number}`">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <Link href="/admin/invoices" class="text-xs font-semibold text-forest-700 hover:text-forest-900">
                    ← Kembali ke Daftar Tagihan
                </Link>
                <div class="flex items-center gap-3">
                    <a
                        :href="`/invoices/${invoice.id}/pdf`"
                        target="_blank"
                        class="px-4 py-2 rounded-xl border border-line-200 bg-white text-xs font-semibold text-ink-700 hover:bg-slate-50 transition shadow-xs"
                    >
                        Cetak PDF
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Data Tagihan -->
                <BentoCard title="Informasi Tagihan">
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Nomor Invoice</span>
                            <span class="num font-bold text-forest-900">{{ invoice.number }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Tipe Tagihan</span>
                            <span class="capitalize font-semibold text-ink-900">{{ invoice.type }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Tanggal Terbit</span>
                            <span class="num text-ink-900">{{ formatDate(invoice.issue_date) }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Jatuh Tempo</span>
                            <span class="num font-bold text-danger-700">{{ formatDate(invoice.due_date) }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100 items-center">
                            <span class="text-ink-500">Status Pembayaran</span>
                            <StatusBadge :status="invoice.status" />
                        </div>
                    </div>
                </BentoCard>

                <!-- Data Pembayar -->
                <BentoCard title="Penghuni & Kamar">
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Nama Lengkap</span>
                            <span class="font-bold text-forest-900">{{ invoice.user?.name }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">WhatsApp</span>
                            <span class="num text-ink-900">{{ invoice.user?.phone }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Unit Kamar</span>
                            <span class="num font-bold text-forest-800">
                                {{ invoice.room ? `Kamar ${invoice.room.code}` : '-' }}
                            </span>
                        </div>
                    </div>
                </BentoCard>
            </div>

            <!-- Rincian Item Tagihan -->
            <BentoCard title="Rincian Item Biaya">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line-200 text-xs">
                        <thead class="bg-slate-50 text-ink-500 uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3 text-left">Deskripsi</th>
                                <th class="px-4 py-3 text-center">Tipe</th>
                                <th class="px-4 py-3 text-center">Qty</th>
                                <th class="px-4 py-3 text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line-100">
                            <tr v-for="item in invoice.items" :key="item.id">
                                <td class="px-4 py-3 font-medium text-ink-900">{{ item.description }}</td>
                                <td class="px-4 py-3 text-center capitalize text-ink-600">{{ item.type }}</td>
                                <td class="px-4 py-3 text-center num">{{ item.qty }}</td>
                                <td class="px-4 py-3 text-right num font-semibold">
                                    <RupiahText :amount="item.amount" />
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="border-t-2 border-line-200 bg-slate-50/50 font-bold">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-right text-ink-700">Total Tagihan:</td>
                                <td class="px-4 py-3 text-right text-sm text-forest-900">
                                    <RupiahText :amount="invoice.total" />
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-4 py-2 text-right text-ink-700">Sudah Dibayar:</td>
                                <td class="px-4 py-2 text-right text-xs text-green-700">
                                    <RupiahText :amount="invoice.paid_amount" />
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-right text-ink-700">Sisa Tagihan:</td>
                                <td class="px-4 py-3 text-right text-sm text-danger-700">
                                    <RupiahText :amount="invoice.total - invoice.paid_amount" />
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </BentoCard>
        </div>
    </AdminLayout>
</template>

