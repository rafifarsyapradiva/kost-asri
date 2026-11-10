<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import RoomClassBadge from '@/Components/RoomClassBadge.vue';
import RupiahText from '@/Components/RupiahText.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { useDateId } from '@/Composables/useDateId';

const props = defineProps({
    reservation: {
        type: Object,
        required: true,
    },
});

const { formatDate } = useDateId();
const invoice = computed(() => props.reservation.invoices?.[0] || null);
const remainingBalance = computed(() => {
    if (!invoice.value) return 0;
    return Math.max(0, (invoice.value.total || invoice.value.total_amount || 0) - (invoice.value.paid_amount || 0));
});

const isFullyPaid = computed(() => {
    return props.reservation.status === 'paid' || (invoice.value && remainingBalance.value === 0);
});

const hasGuardian = computed(() => {
    if (!['weekly', 'monthly'].includes(props.reservation.rent_type)) {
        return true;
    }
    const profile = props.reservation.user?.tenant_profile;
    return Boolean(profile?.guardian_name && profile?.guardian_phone);
});

const canCheckIn = computed(() => isFullyPaid.value && hasGuardian.value);

// Check-in modal
const showCheckInModal = ref(false);
const isCheckingIn = ref(false);

const processCheckIn = () => {
    isCheckingIn.value = true;
    router.post(
        `/admin/reservations/${props.reservation.id}/check-in`,
        {},
        {
            onFinish: () => {
                isCheckingIn.value = false;
                showCheckInModal.value = false;
            },
        }
    );
};

// Cash settlement modal
const showCashModal = ref(false);
const cashForm = useForm({
    amount: remainingBalance.value,
    notes: 'Pelunasan sisa tagihan reservasi saat check-in di front-desk',
});

const submitCashPayment = () => {
    if (!invoice.value) return;
    cashForm.post(`/admin/invoices/${invoice.value.id}/cash`, {
        onSuccess: () => {
            showCashModal.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`Reservasi ${reservation.code}`" />

    <AdminLayout :title="`Detail Reservasi #${reservation.code}`">
        <div class="space-y-6">
            <!-- Header bar -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <Link href="/admin/reservations" class="text-xs font-semibold text-forest-700 hover:text-forest-900">
                    ← Kembali ke Daftar Reservasi
                </Link>
                <div class="flex items-center gap-3">
                    <a
                        :href="`/reservations/${reservation.code}/pdf`"
                        target="_blank"
                        class="px-4 py-2 rounded-xl border border-line-200 bg-white text-xs font-semibold text-ink-700 hover:bg-slate-50 transition shadow-2xs flex items-center gap-1.5"
                    >
                        <span>Unduh Nota PDF</span>
                    </a>

                    <!-- Check-in button -->
                    <button
                        v-if="reservation.status !== 'confirmed' && reservation.status !== 'cancelled' && reservation.status !== 'expired'"
                        type="button"
                        :disabled="!canCheckIn"
                        @click="showCheckInModal = true"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold transition shadow-2xs flex items-center gap-1.5',
                            canCheckIn
                                ? 'bg-forest-900 text-white hover:bg-forest-800'
                                : 'bg-slate-200 text-slate-400 cursor-not-allowed'
                        ]"
                        :title="!isFullyPaid ? 'Check-in terkunci sebelum pembayaran lunas 100%' : (!hasGuardian ? 'Check-in terkunci: data wali belum lengkap (BR-28)' : 'Proses check-in penghuni')"
                    >
                        <span>{{ canCheckIn ? 'Proses Check-In' : (!isFullyPaid ? '🔒 Check-In Terkunci (Belum Lunas)' : '🔒 Check-In Terkunci (Data Wali Belum Lengkap)') }}</span>
                    </button>
                </div>
            </div>

            <!-- Lock Alert if not fully paid -->
            <div v-if="!isFullyPaid && reservation.status !== 'cancelled' && reservation.status !== 'expired'" class="p-4 rounded-xl border border-amber-300 bg-amber-50 text-amber-900 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
                <div>
                    <span class="font-bold block text-sm">🔒 Check-in Terkunci (Aturan BR-06)</span>
                    <p class="mt-0.5">Penghuni belum melunasi tagihan awal. Sisa yang harus dibayar: <strong><RupiahText :amount="remainingBalance" /></strong>.</p>
                </div>
                <button
                    v-if="invoice"
                    type="button"
                    @click="showCashModal = true"
                    class="px-3.5 py-2 rounded-lg bg-amber-800 text-white font-bold hover:bg-amber-900 transition shadow-2xs shrink-0"
                >
                    Terima Pelunasan Tunai (Cash)
                </button>
            </div>

            <!-- Lock Alert if paid but missing guardian for weekly/monthly -->
            <div v-if="isFullyPaid && !hasGuardian && reservation.status !== 'confirmed' && reservation.status !== 'cancelled' && reservation.status !== 'expired'" class="p-4 rounded-xl border border-amber-300 bg-amber-50 text-amber-900 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
                <div>
                    <span class="font-bold block text-sm">⚠️ Data Wali Belum Lengkap (Aturan BR-28)</span>
                    <p class="mt-0.5">Sewa mingguan dan bulanan wajib memiliki data wali lengkap (nama & nomor WhatsApp) sebelum check-in.</p>
                </div>
                <Link
                    :href="`/admin/tenants/${reservation.user?.id}`"
                    class="px-3.5 py-2 rounded-lg bg-amber-800 text-white font-bold hover:bg-amber-900 transition shadow-2xs shrink-0"
                >
                    Lengkapi Profil Wali
                </Link>
            </div>

            <!-- Bento Info Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Tamu & Kamar -->
                <BentoCard title="Informasi Pemesan & Unit Kamar">
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Nama Tamu</span>
                            <span class="font-bold text-forest-900">{{ reservation.user?.name }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Nomor WhatsApp</span>
                            <span class="num font-semibold text-ink-900">{{ reservation.user?.phone }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Email</span>
                            <span class="font-semibold text-ink-900">{{ reservation.user?.email }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Kamar Dipesan</span>
                            <div class="text-right">
                                <span class="num font-bold text-forest-900">Kamar {{ reservation.room?.code }}</span>
                                <span class="text-ink-500 block">Lantai {{ reservation.room?.floor }} • {{ reservation.room?.room_type?.name }}</span>
                            </div>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Tipe Sewa</span>
                            <span class="capitalize font-semibold text-ink-900">{{ reservation.rent_type }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Rencana Check-In</span>
                            <span class="num font-bold text-ink-900">{{ formatDate(reservation.check_in_date) }}</span>
                        </div>
                        <div class="flex justify-between py-2 items-center">
                            <span class="text-ink-500">Status Reservasi</span>
                            <StatusBadge :status="reservation.status" />
                        </div>
                    </div>
                </BentoCard>

                <!-- Rincian Biaya & Invoice -->
                <BentoCard title="Rincian Biaya & Status Pembayaran">
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Total Biaya Sewa</span>
                            <RupiahText :amount="reservation.total_amount" class="font-bold text-forest-900" />
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Skema Pembayaran</span>
                            <span class="uppercase font-semibold text-ink-900">{{ reservation.payment_scheme }}</span>
                        </div>
                        <div v-if="reservation.payment_scheme === 'dp'" class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Nominal Uang Muka (DP)</span>
                            <RupiahText :amount="reservation.dp_amount" class="font-semibold text-amber-800" />
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Jumlah Sudah Dibayar</span>
                            <RupiahText :amount="invoice?.paid_amount || 0" class="font-bold text-forest-700" />
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Sisa Tagihan</span>
                            <RupiahText :amount="remainingBalance" class="font-extrabold text-danger-700 text-sm" />
                        </div>
                        <div v-if="invoice" class="pt-2 flex justify-between items-center">
                            <span class="text-ink-500">No. Invoice Terkait:</span>
                            <Link :href="`/admin/invoices/${invoice.id}`" class="font-bold text-forest-700 hover:underline num">
                                {{ invoice.number }} →
                            </Link>
                        </div>
                    </div>
                </BentoCard>
            </div>

            <!-- Check-in Confirmation Modal -->
            <Modal :show="showCheckInModal" @close="showCheckInModal = false">
                <div class="p-6">
                    <h3 class="text-base font-bold text-forest-900 mb-2">Konfirmasi Check-In Penghuni</h3>
                    <p class="text-xs text-ink-600 mb-5 leading-relaxed">
                        Anda akan memproses check-in untuk tamu <strong>{{ reservation.user?.name }}</strong> ke <strong>Kamar {{ reservation.room?.code }}</strong>.
                        Tindakan ini akan mengaktifkan kontrak sewa (Lease), mengubah status kamar menjadi <em>Occupied</em> (Terisi), serta mengalihkan status reservasi menjadi <em>Confirmed</em>.
                    </p>

                    <div v-if="$page.props.errors?.guardian" class="mb-4 p-3 rounded-lg bg-danger-50 border border-danger-200 text-danger-800 text-xs font-semibold">
                        {{ $page.props.errors.guardian }}
                    </div>

                    <div class="flex justify-end gap-2">
                        <SecondaryButton @click="showCheckInModal = false">Batal</SecondaryButton>
                        <PrimaryButton :disabled="isCheckingIn" @click="processCheckIn">
                            Konfirmasi & Check-In
                        </PrimaryButton>
                    </div>
                </div>
            </Modal>

            <!-- Cash Settlement Modal -->
            <Modal :show="showCashModal" @close="showCashModal = false">
                <div class="p-6">
                    <h3 class="text-base font-bold text-forest-900 mb-2">Catat Pelunasan Tunai (Front-Desk)</h3>
                    <p class="text-xs text-ink-600 mb-4">
                        Terima uang tunai langsung dari tamu di front-desk. Kwitansi resmi lunas akan otomatis diterbitkan.
                    </p>

                    <form @submit.prevent="submitCashPayment" class="space-y-4 text-xs">
                        <div>
                            <InputLabel value="Nominal Pembayaran Tunai (Rp)" />
                            <TextInput type="number" v-model="cashForm.amount" :max="remainingBalance" required />
                            <InputError :message="cashForm.errors.amount" />
                        </div>

                        <div>
                            <InputLabel value="Catatan Kwitansi" />
                            <TextInput v-model="cashForm.notes" />
                            <InputError :message="cashForm.errors.notes" />
                        </div>

                        <div class="flex justify-end gap-2 pt-3">
                            <SecondaryButton @click="showCashModal = false">Batal</SecondaryButton>
                            <PrimaryButton :disabled="cashForm.processing">
                                Simpan & Terbitkan Kwitansi
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    </AdminLayout>
</template>

