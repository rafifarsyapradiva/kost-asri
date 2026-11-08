<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import RupiahText from '@/Components/RupiahText.vue';
import { useDateId } from '@/Composables/useDateId';
import { useSnap } from '@/Composables/useSnap';

const props = defineProps({
    invoice: Object,
});

const { formatDate } = useDateId();
const { payWithSnap, isLoading } = useSnap();

const payNow = async () => {
    try {
        const res = await axios.post(`/invoices/${props.invoice.id}/snap-token`);
        const token = res.data.snap_token;
        const result = await payWithSnap(token);
        if (result.status === 'success') {
            router.reload();
        }
    } catch (err) {
        alert('Gagal membuka pembayaran Midtrans: ' + (err.message || 'Error'));
    }
};
</script>

<template>
    <Head :title="`Tagihan #${invoice.number}`" />

    <TenantLayout :title="`Detail Tagihan: ${invoice.number}`">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <Link href="/penyewa/invoices" class="text-xs font-semibold text-forest-700 hover:text-forest-900">
                    ← Kembali ke Tagihan Saya
                </Link>
                <div class="flex items-center gap-2">
                    <a
                        :href="`/invoices/${invoice.id}/pdf`"
                        target="_blank"
                        class="px-3.5 py-2 rounded-xl border border-line-200 bg-white text-xs font-semibold text-ink-700 hover:bg-slate-50 transition shadow-xs"
                    >
                        Cetak PDF
                    </a>
                    <button
                        v-if="invoice.status === 'unpaid' || invoice.status === 'overdue' || invoice.status === 'partial'"
                        type="button"
                        :disabled="isLoading"
                        @click="payNow"
                        class="px-4 py-2 rounded-xl bg-terracotta-500 hover:bg-terracotta-400 text-ink-900 font-bold text-xs shadow-xs transition disabled:opacity-50"
                    >
                        {{ isLoading ? 'Memproses...' : 'Bayar Sekarang' }}
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Info Tagihan -->
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
                            <span class="text-ink-500">Jatuh Tempo</span>
                            <span class="num font-bold text-danger-700">{{ formatDate(invoice.due_date) }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100 items-center">
                            <span class="text-ink-500">Status</span>
                            <StatusBadge :status="invoice.status" />
                        </div>
                    </div>
                </BentoCard>

                <!-- Rincian Biaya -->
                <BentoCard title="Rincian Item">
                    <div class="divide-y divide-line-100 text-xs">
                        <div v-for="item in invoice.items" :key="item.id" class="py-2.5 flex justify-between">
                            <div>
                                <p class="font-medium text-ink-900">{{ item.description }}</p>
                                <p class="text-[10px] text-ink-500 capitalize">{{ item.type }}</p>
                            </div>
                            <RupiahText :amount="item.amount" class="font-bold text-forest-900" />
                        </div>

                        <div class="pt-3 flex justify-between font-bold text-sm">
                            <span>Total:</span>
                            <RupiahText :amount="invoice.total" class="text-forest-900" />
                        </div>
                        <div class="pt-2 flex justify-between font-semibold text-xs text-green-700">
                            <span>Sudah Dibayar:</span>
                            <RupiahText :amount="invoice.paid_amount" />
                        </div>
                        <div class="pt-2 flex justify-between font-bold text-sm text-danger-700">
                            <span>Sisa Tagihan:</span>
                            <RupiahText :amount="invoice.total - invoice.paid_amount" />
                        </div>
                    </div>
                </BentoCard>
            </div>
        </div>
    </TenantLayout>
</template>

