<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import RupiahText from '@/Components/RupiahText.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { useDateId } from '@/Composables/useDateId';

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    },
});

const { formatDate } = useDateId();

const hasPenalty = computed(() => {
    return props.invoice.items?.some((i) => i.type === 'penalty' || i.type?.value === 'penalty');
});

const isAdjustable = computed(() => {
    return props.invoice.status !== 'paid' && props.invoice.status !== 'void';
});

// Modal states
const showAddItemModal = ref(false);
const showWaiveModal = ref(false);
const showVoidModal = ref(false);

const addItemForm = useForm({
    type: 'discount',
    description: '',
    amount: 0,
});

const waiveForm = useForm({
    reason: '',
});

const voidForm = useForm({
    reason: '',
});

const submitAddItem = () => {
    addItemForm.post(`/admin/invoices/${props.invoice.id}/items`, {
        onSuccess: () => {
            showAddItemModal.value = false;
            addItemForm.reset();
        },
    });
};

const submitWaive = () => {
    waiveForm.post(`/admin/invoices/${props.invoice.id}/waive-penalty`, {
        onSuccess: () => {
            showWaiveModal.value = false;
            waiveForm.reset();
        },
    });
};

const submitVoid = () => {
    voidForm.post(`/admin/invoices/${props.invoice.id}/void`, {
        onSuccess: () => {
            showVoidModal.value = false;
            voidForm.reset();
        },
    });
};
</script>

<template>
    <Head :title="`Invoice #${invoice.number}`" />

    <AdminLayout :title="`Detail Tagihan: ${invoice.number}`">
        <div class="space-y-6">
            <!-- Header bar -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <Link href="/admin/invoices" class="text-xs font-semibold text-forest-700 hover:text-forest-900">
                    ← Kembali ke Daftar Tagihan
                </Link>
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        :href="`/invoices/${invoice.id}/pdf`"
                        target="_blank"
                        class="px-4 py-2 rounded-xl border border-line-200 bg-white text-xs font-semibold text-ink-700 hover:bg-slate-50 transition shadow-2xs flex items-center gap-1.5"
                    >
                        <span>Cetak PDF</span>
                    </a>

                    <!-- Action Modals Triggers -->
                    <template v-if="isAdjustable">
                        <button
                            type="button"
                            @click="showAddItemModal = true"
                            class="px-3.5 py-2 rounded-xl border border-line-200 bg-white text-xs font-semibold text-ink-700 hover:bg-slate-50 transition shadow-2xs"
                        >
                            + Tambah Item / Diskon
                        </button>
                        <button
                            v-if="hasPenalty"
                            type="button"
                            @click="showWaiveModal = true"
                            class="px-3.5 py-2 rounded-xl bg-amber-50 border border-amber-200 text-xs font-bold text-amber-800 hover:bg-amber-100 transition shadow-2xs"
                        >
                            Bebaskan Denda (Waive)
                        </button>
                        <button
                            type="button"
                            @click="showVoidModal = true"
                            class="px-3.5 py-2 rounded-xl bg-red-50 border border-red-200 text-xs font-bold text-red-700 hover:bg-red-100 transition shadow-2xs"
                        >
                            Batalkan Tagihan (Void)
                        </button>
                    </template>
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
            <BentoCard title="Rincian Item Biaya & Penyesuaian">
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
                            <tr
                                v-for="item in invoice.items"
                                :key="item.id"
                                :class="item.type === 'penalty' ? 'bg-red-50/60 text-red-950' : (item.type === 'discount' ? 'bg-green-50/60 text-green-950' : '')"
                            >
                                <td class="px-4 py-3 font-medium">
                                    <div class="flex items-center gap-2">
                                        <span v-if="item.type === 'penalty'" class="text-danger-700 font-bold">⚠️ Denda:</span>
                                        <span v-else-if="item.type === 'discount'" class="text-emerald-700 font-bold">🏷️ Potongan:</span>
                                        <span>{{ item.description }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center capitalize font-semibold">
                                    {{ item.type }}
                                </td>
                                <td class="px-4 py-3 text-center num">{{ item.qty }}</td>
                                <td class="px-4 py-3 text-right font-bold">
                                    <span v-if="item.type === 'discount'">-</span>
                                    <RupiahText :amount="item.amount" />
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="border-t-2 border-line-200 font-bold">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-right uppercase text-ink-600">Total Tagihan:</td>
                                <td class="px-4 py-3 text-right num text-sm text-forest-900">
                                    <RupiahText :amount="invoice.total" />
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-4 py-2 text-right uppercase text-ink-600">Sudah Dibayar:</td>
                                <td class="px-4 py-2 text-right num text-green-700">
                                    <RupiahText :amount="invoice.paid_amount || 0" />
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-4 py-2 text-right uppercase text-ink-600">Sisa Tagihan:</td>
                                <td class="px-4 py-2 text-right num text-sm text-danger-700 font-extrabold">
                                    <RupiahText :amount="Math.max(0, invoice.total - (invoice.paid_amount || 0))" />
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </BentoCard>

            <!-- Modal Add Item -->
            <Modal :show="showAddItemModal" @close="showAddItemModal = false">
                <div class="p-6">
                    <h3 class="text-base font-bold text-forest-900 mb-2">Tambah Item Biaya / Potongan</h3>
                    <p class="text-xs text-ink-600 mb-4">Tambahkan item biaya tambahan atau potongan harga secara resmi ke invoice ini.</p>

                    <form @submit.prevent="submitAddItem" class="space-y-4 text-xs">
                        <div>
                            <InputLabel value="Tipe Penyesuaian" />
                            <select v-model="addItemForm.type" class="w-full rounded-xl border-line-300 text-xs py-2 px-3 bg-white" required>
                                <option value="discount">Diskon / Potongan Harga</option>
                                <option value="laundry">Biaya Laundry</option>
                                <option value="damage_fee">Ganti Rugi Kerusakan</option>
                                <option value="other">Biaya Lain-lain</option>
                            </select>
                            <InputError :message="addItemForm.errors.type" />
                        </div>

                        <div>
                            <InputLabel value="Deskripsi Item" />
                            <TextInput v-model="addItemForm.description" placeholder="Misal: Diskon promo mahasiswa baru" required />
                            <InputError :message="addItemForm.errors.description" />
                        </div>

                        <div>
                            <InputLabel value="Nominal (Rp)" />
                            <TextInput type="number" v-model="addItemForm.amount" min="1" required />
                            <InputError :message="addItemForm.errors.amount" />
                        </div>

                        <div class="flex justify-end gap-2 pt-3">
                            <SecondaryButton @click="showAddItemModal = false">Batal</SecondaryButton>
                            <PrimaryButton :disabled="addItemForm.processing">Simpan Item</PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <!-- Modal Waive Penalty -->
            <Modal :show="showWaiveModal" @close="showWaiveModal = false">
                <div class="p-6">
                    <h3 class="text-base font-bold text-forest-900 mb-2">Bebaskan Denda Keterlambatan (Waive)</h3>
                    <p class="text-xs text-ink-600 mb-4">
                        Pembebasan denda memerlukan pencatatan alasan resmi dari pengelola kost sesuai aturan bisnis.
                    </p>

                    <form @submit.prevent="submitWaive" class="space-y-4 text-xs">
                        <div>
                            <InputLabel value="Alasan Pembebasan Denda (Wajib Diisi)" />
                            <textarea
                                v-model="waiveForm.reason"
                                rows="3"
                                class="w-full rounded-xl border-line-300 text-xs p-3 focus:ring-forest-500 focus:border-forest-500"
                                placeholder="Contoh: Kesepakatan khusus dengan orang tua / wali penghuni..."
                                required
                            ></textarea>
                            <InputError :message="waiveForm.errors.reason" />
                        </div>

                        <div class="flex justify-end gap-2 pt-3">
                            <SecondaryButton @click="showWaiveModal = false">Batal</SecondaryButton>
                            <PrimaryButton :disabled="waiveForm.processing">Konfirmasi Bebaskan Denda</PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <!-- Modal Void Invoice -->
            <Modal :show="showVoidModal" @close="showVoidModal = false">
                <div class="p-6">
                    <h3 class="text-base font-bold text-danger-700 mb-2">Batalkan Tagihan (Void Invoice)</h3>
                    <p class="text-xs text-ink-600 mb-4">
                        Tagihan yang dibatalkan tidak akan dapat ditagihkan lagi dan riwayat pembatalan akan dicatat ke activity logs.
                    </p>

                    <form @submit.prevent="submitVoid" class="space-y-4 text-xs">
                        <div>
                            <InputLabel value="Alasan Pembatalan Tagihan (Wajib)" />
                            <textarea
                                v-model="voidForm.reason"
                                rows="3"
                                class="w-full rounded-xl border-line-300 text-xs p-3 focus:ring-forest-500 focus:border-forest-500"
                                placeholder="Masukkan alasan void invoice..."
                                required
                            ></textarea>
                            <InputError :message="voidForm.errors.reason" />
                        </div>

                        <div class="flex justify-end gap-2 pt-3">
                            <SecondaryButton @click="showVoidModal = false">Batal</SecondaryButton>
                            <DangerButton :disabled="voidForm.processing">Batalkan Tagihan (Void)</DangerButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    </AdminLayout>
</template>
