<script setup>
import { ref, computed } from 'vue';
import RupiahText from '@/Components/RupiahText.vue';
import RoomClassBadge from '@/Components/RoomClassBadge.vue';
import { useDateId } from '@/Composables/useDateId';

const props = defineProps({
    quote: {
        type: Object,
        default: () => null,
    },
    room: {
        type: Object,
        default: () => null,
    },
    rentType: {
        type: String,
        default: 'monthly',
    },
    durationQty: {
        type: Number,
        default: 1,
    },
    checkInDate: {
        type: String,
        default: null,
    },
    prorataOption: {
        type: String,
        default: null,
    },
    paymentScheme: {
        type: String,
        default: 'full', // 'dp' | 'full'
    },
    customDp: {
        type: Number,
        default: null,
    },
    showActionSlot: {
        type: Boolean,
        default: true,
    },
});

const { formatDate } = useDateId();
const isMobileDrawerOpen = ref(false);

const roomClass = computed(() => {
    return props.room?.room_type?.room_class || props.room?.room_class || 'standard';
});

const calculatedDp = computed(() => {
    if (!props.quote) return 0;
    if (props.customDp && props.customDp >= (props.quote.min_dp || 0)) {
        return props.customDp;
    }
    return props.quote.min_dp || Math.ceil((props.quote.total || 0) * 0.3);
});

const remainingAtCheckIn = computed(() => {
    if (!props.quote) return 0;
    if (props.paymentScheme === 'full') return 0;
    return Math.max(0, (props.quote.total || 0) - calculatedDp.value);
});

const rentTypeLabel = computed(() => {
    switch (props.rentType) {
        case 'daily': return `Harian (${props.durationQty} Malam)`;
        case 'weekly': return `Mingguan (${props.durationQty} Minggu)`;
        case 'monthly': return `Bulanan (${props.durationQty} Bulan)`;
        default: return props.rentType;
    }
});
</script>

<template>
    <div>
        <!-- Desktop Sticky Card (>= 1024px) -->
        <div class="hidden lg:block sticky top-24 rounded-3xl border border-line-200 bg-white p-6 shadow-sm">
            <!-- Header -->
            <div class="border-b border-line-100 pb-4">
                <span class="text-[10px] font-bold uppercase tracking-wider text-ink-400">Ringkasan Biaya</span>
                <div class="mt-2 flex items-center justify-between">
                    <div>
                        <div class="text-base font-black text-forest-900">
                            {{ room ? `Kamar ${room.code}` : 'Belum Memilih Kamar' }}
                        </div>
                        <div class="text-xs text-ink-500">
                            {{ room?.room_type?.name || 'Pilih kamar pada denah' }} &bull; Lantai {{ room?.floor || '-' }}
                        </div>
                    </div>
                    <RoomClassBadge v-if="room" :room-class="roomClass" />
                </div>
            </div>

            <!-- Stay Info -->
            <div class="py-4 border-b border-line-100 space-y-2 text-xs">
                <div class="flex items-center justify-between text-ink-600">
                    <span>Tipe Sewa</span>
                    <span class="font-bold text-ink-900">{{ rentTypeLabel }}</span>
                </div>
                <div v-if="checkInDate" class="flex items-center justify-between text-ink-600">
                    <span>Tanggal Masuk</span>
                    <span class="font-bold text-ink-900 num">{{ formatDate(checkInDate) }}</span>
                </div>
                <div v-if="quote?.billed_until" class="flex items-center justify-between text-ink-600">
                    <span>Dibayar Sampai</span>
                    <span class="font-bold text-forest-900 num">{{ formatDate(quote.billed_until) }}</span>
                </div>
                <div v-if="prorataOption && rentType === 'monthly'" class="flex items-center justify-between text-ink-600">
                    <span>Opsi Prorata</span>
                    <span class="font-bold text-forest-800">
                        {{ prorataOption === 'combined' ? 'Opsi 1 (Gabung Awal)' : 'Opsi 2 (Penuh Dulu)' }}
                    </span>
                </div>
            </div>

            <!-- Itemized Breakdown -->
            <div class="py-4 border-b border-line-100 space-y-2 text-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-ink-400">Rincian Komponen</span>
                <div v-if="quote?.items && quote.items.length > 0" class="space-y-1.5 pt-1">
                    <div
                        v-for="(item, idx) in quote.items"
                        :key="idx"
                        class="flex items-start justify-between gap-2"
                    >
                        <span class="text-ink-600 text-[11px] leading-tight">{{ item.description }}</span>
                        <span class="font-semibold text-ink-900 shrink-0">
                            <RupiahText :amount="item.amount" />
                        </span>
                    </div>
                </div>
                <div v-else class="text-xs text-ink-400 italic">
                    Pilih tanggal & kamar untuk kalkulasi otomatis.
                </div>
            </div>

            <!-- Total & Scheme -->
            <div class="pt-4 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-ink-700">Total Tagihan</span>
                    <span class="text-lg font-black text-forest-900">
                        <RupiahText :amount="quote?.total || 0" />
                    </span>
                </div>

                <!-- DP Info if applicable -->
                <div v-if="paymentScheme === 'dp'" class="rounded-2xl bg-amber-50/70 p-3.5 border border-amber-200/60 space-y-1.5 text-xs">
                    <div class="flex items-center justify-between text-amber-900">
                        <span class="font-medium">DP Bayar Sekarang (30%+)</span>
                        <span class="font-black text-amber-800">
                            <RupiahText :amount="calculatedDp" />
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-ink-600 text-[11px] pt-1 border-t border-amber-200/40">
                        <span>Sisa saat Check-in</span>
                        <span class="font-bold text-ink-800">
                            <RupiahText :amount="remainingAtCheckIn" />
                        </span>
                    </div>
                </div>

                <!-- Action Button Slot -->
                <div v-if="showActionSlot" class="pt-2">
                    <slot name="action" />
                </div>
            </div>
        </div>

        <!-- Mobile Sticky Bottom Sheet (< 1024px) -->
        <div class="lg:hidden fixed bottom-0 left-0 right-0 z-30 border-t border-line-200 bg-white/95 backdrop-blur-md px-4 py-3 shadow-lg">
            <div class="max-w-xl mx-auto flex items-center justify-between gap-3">
                <div class="flex flex-col">
                    <button
                        type="button"
                        @click="isMobileDrawerOpen = !isMobileDrawerOpen"
                        class="text-[11px] font-semibold text-forest-800 flex items-center gap-1 hover:underline text-left"
                    >
                        <span>{{ room ? `Kamar ${room.code}` : 'Pilih Kamar' }} &bull; Rincian</span>
                        <svg
                            :class="['h-3 w-3 transition-transform', isMobileDrawerOpen ? 'rotate-180' : '']"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div class="text-base font-black text-forest-900">
                        <RupiahText :amount="paymentScheme === 'dp' ? calculatedDp : (quote?.total || 0)" />
                        <span v-if="paymentScheme === 'dp'" class="text-[10px] font-semibold text-amber-700 ml-1">(DP)</span>
                    </div>
                </div>

                <div class="shrink-0 flex items-center gap-2">
                    <slot name="mobile-action" />
                </div>
            </div>

            <!-- Mobile Expanded Details Drawer -->
            <div
                v-if="isMobileDrawerOpen"
                class="mt-3 border-t border-line-100 pt-3 max-h-64 overflow-y-auto space-y-2 text-xs"
            >
                <div class="flex justify-between text-ink-600">
                    <span>Durasi</span>
                    <span class="font-bold text-ink-900">{{ rentTypeLabel }}</span>
                </div>
                <div v-for="(item, idx) in quote?.items || []" :key="idx" class="flex justify-between text-ink-600 text-[11px]">
                    <span>{{ item.description }}</span>
                    <span class="font-semibold text-ink-900"><RupiahText :amount="item.amount" /></span>
                </div>
                <div v-if="paymentScheme === 'dp'" class="flex justify-between text-amber-800 font-bold pt-1 border-t border-line-100">
                    <span>Sisa Pelunasan Saat Check-in</span>
                    <span><RupiahText :amount="remainingAtCheckIn" /></span>
                </div>
            </div>
        </div>
    </div>
</template>

