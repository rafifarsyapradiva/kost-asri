<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import RupiahText from '@/Components/RupiahText.vue';
import { useDateId } from '@/Composables/useDateId';

const props = defineProps({
    lease: Object,
});

const { formatDate } = useDateId();

const showCheckoutModal = ref(false);
const overrideCheckout = ref(false);
const checkoutNotes = ref('');
const isProcessing = ref(false);

const showExtendModal = ref(false);
const extendNights = ref(1);

const processCheckout = () => {
    isProcessing.value = true;
    router.post(
        `/admin/leases/${props.lease.id}/check-out`,
        {
            override: overrideCheckout.value,
            notes: checkoutNotes.value,
        },
        {
            onFinish: () => {
                isProcessing.value = false;
                showCheckoutModal.value = false;
            },
        }
    );
};

const processExtend = () => {
    isProcessing.value = true;
    router.post(
        `/admin/leases/${props.lease.id}/extend`,
        {
            extra_nights: extendNights.value,
        },
        {
            onFinish: () => {
                isProcessing.value = false;
                showExtendModal.value = false;
            },
        }
    );
};
</script>

<template>
    <Head :title="`Detail Kontrak #${lease.id}`" />

    <AdminLayout :title="`Kontrak Sewa #${lease.id} — Kamar ${lease.room?.code}`">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <Link href="/admin/leases" class="text-xs font-semibold text-forest-700 hover:text-forest-900">
                    ← Kembali ke Daftar Kontrak
                </Link>
                <div v-if="lease.status === 'active' || lease.status === 'ending'" class="flex items-center gap-2">
                    <button
                        v-if="lease.rent_type === 'daily'"
                        type="button"
                        @click="showExtendModal = true"
                        class="px-4 py-2 rounded-xl border border-forest-900 text-forest-900 font-semibold text-xs hover:bg-forest-50 transition shadow-xs"
                    >
                        + Tambah Hari (Perpanjang)
                    </button>
                    <button
                        type="button"
                        @click="showCheckoutModal = true"
                        class="px-4 py-2 rounded-xl bg-danger-700 text-white font-semibold text-xs hover:bg-danger-800 transition shadow-xs"
                    >
                        Proses Check-Out
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Data Kontrak -->
                <BentoCard title="Rincian Kontrak Sewa">
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Nomor Kamar</span>
                            <span class="num font-bold text-forest-900">Kamar {{ lease.room?.code }} ({{ lease.room?.room_type?.name }})</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Tipe Siklus Sewa</span>
                            <span class="capitalize font-semibold text-ink-900">{{ lease.rent_type }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Tanggal Mulai Sewa</span>
                            <span class="num font-semibold text-ink-900">{{ formatDate(lease.start_date) }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Rencana Selesai</span>
                            <span class="num font-semibold text-ink-900">{{ lease.planned_checkout_date ? formatDate(lease.planned_checkout_date) : 'Aktif Berjalan' }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Lunas Hingga (Paid Until)</span>
                            <span class="num font-bold text-forest-800">{{ formatDate(lease.paid_until) }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100 items-center">
                            <span class="text-ink-500">Status Kontrak</span>
                            <StatusBadge :status="lease.status" />
                        </div>
                    </div>
                </BentoCard>

                <!-- Data Penghuni & Wali -->
                <BentoCard title="Penghuni & Wali">
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Nama Penghuni</span>
                            <span class="font-bold text-forest-900">{{ lease.tenant?.name }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">WhatsApp Penghuni</span>
                            <span class="num font-semibold text-ink-900">{{ lease.tenant?.phone }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Wali / Orang Tua</span>
                            <span class="font-semibold text-ink-900">{{ lease.tenant?.tenant_profile?.guardian_name || '-' }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">WhatsApp Wali</span>
                            <span class="num font-semibold text-ink-900">{{ lease.tenant?.tenant_profile?.guardian_phone || '-' }}</span>
                        </div>
                    </div>
                </BentoCard>
            </div>

            <!-- Daftar Tagihan Terkait -->
            <BentoCard title="Daftar Tagihan & Status Pembayaran">
                <div v-if="!lease.invoices || lease.invoices.length === 0" class="py-8 text-center text-xs text-ink-500">
                    Belum ada tagihan untuk kontrak ini.
                </div>
                <div v-else class="divide-y divide-line-100">
                    <div
                        v-for="inv in lease.invoices"
                        :key="inv.id"
                        class="py-3 flex items-center justify-between text-xs"
                    >
                        <div>
                            <p class="font-bold text-forest-900">{{ inv.number || inv.invoice_number }} ({{ inv.type || inv.invoice_type }})</p>
                            <p class="num text-[11px] text-ink-500">Jatuh Tempo: {{ formatDate(inv.due_date) }}</p>
                        </div>
                        <div class="text-right flex items-center gap-4">
                            <div>
                                <RupiahText :amount="inv.total ?? inv.total_amount ?? 0" class="font-bold block" />
                                <span v-if="inv.paid_amount > 0" class="num text-[10px] text-green-700 block">
                                    Dibayar: <RupiahText :amount="inv.paid_amount" />
                                </span>
                            </div>
                            <StatusBadge :status="inv.status" />
                        </div>
                    </div>
                </div>
            </BentoCard>
        </div>

        <!-- Checkout Confirmation Dialog -->
        <ConfirmDialog
            :show="showCheckoutModal"
            title="Konfirmasi Check-Out Penghuni"
            message="Pastikan kamar telah diperiksa dan kunci telah dikembalikan."
            confirm-text="Check-Out Sekarang"
            :loading="isProcessing"
            @close="showCheckoutModal = false"
            @confirm="processCheckout"
        >
            <div class="mt-4 space-y-3">
                <label class="flex items-center gap-2 text-xs text-ink-800 font-semibold cursor-pointer">
                    <input
                        type="checkbox"
                        v-model="overrideCheckout"
                        class="rounded border-line-200 text-forest-900 focus:ring-forest-500"
                    />
                    <span>Override / Paksa Check-out meskipun masih ada tagihan belum lunas</span>
                </label>
                <textarea
                    v-if="overrideCheckout"
                    v-model="checkoutNotes"
                    placeholder="Wajib masukkan alasan override check-out..."
                    rows="2"
                    class="w-full rounded-xl border border-line-200 p-2.5 text-xs text-ink-900 focus:border-forest-500 focus:outline-none"
                    required
                ></textarea>
            </div>
        </ConfirmDialog>

        <!-- Extend Daily Modal -->
        <Modal :show="showExtendModal" @close="showExtendModal = false">
            <div class="p-6">
                <h3 class="text-base font-bold text-forest-900 mb-2">Perpanjang Sewa Harian</h3>
                <p class="text-xs text-ink-600 mb-4">
                    Kamar {{ lease.room?.code }} — Masukkan jumlah malam tambahan. Invoice perpanjangan (daily_extension) akan otomatis dibuat dan wajib dilunasi di muka.
                </p>

                <form @submit.prevent="processExtend" class="space-y-4 text-xs">
                    <div>
                        <InputLabel value="Jumlah Malam Tambahan" />
                        <TextInput type="number" v-model="extendNights" min="1" max="30" required />
                    </div>

                    <div class="flex justify-end gap-2 pt-3">
                        <SecondaryButton @click="showExtendModal = false">Batal</SecondaryButton>
                        <PrimaryButton :disabled="isProcessing">
                            Buat Tagihan Perpanjangan
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AdminLayout>
</template>

