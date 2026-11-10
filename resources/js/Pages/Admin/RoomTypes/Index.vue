<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import RoomClassBadge from '@/Components/RoomClassBadge.vue';
import RupiahText from '@/Components/RupiahText.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useRupiah } from '@/Composables/useRupiah';

const props = defineProps({
    room_types: {
        type: Array,
        default: () => [],
    },
});

const { formatRupiah } = useRupiah();
const showModal = ref(false);
const editingType = ref(null);

const form = useForm({
    name: '',
    slug: '',
    room_class: 'standard',
    daily_rate: 0,
    weekly_rate: 0,
    monthly_rate: 0,
    size_m2: 12,
    description: '',
    is_published: true,
});

const openEditModal = (type) => {
    editingType.value = type;
    form.name = type.name;
    form.slug = type.slug;
    form.room_class = type.room_class;
    form.daily_rate = type.daily_rate;
    form.weekly_rate = type.weekly_rate;
    form.monthly_rate = type.monthly_rate;
    form.size_m2 = type.size_m2 || 12;
    form.description = type.description || '';
    form.is_published = !!type.is_published;
    showModal.value = true;
};

const submitType = () => {
    if (!editingType.value) return;
    form.put(`/admin/room-types/${editingType.value.id}`, {
        onSuccess: () => {
            showModal.value = false;
        },
    });
};
</script>

<template>
    <Head title="Tarif & Tipe Kamar" />

    <AdminLayout title="Tipe & Tarif Kamar Kost ASRI">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-forest-900">3 Kelas Kamar: Standard, VIP, & Deluxe</h2>
                    <p class="text-xs text-ink-500">Seluruh tarif berlaku untuk 32 kamar dan dihitung murni di server (anti-manipulasi harga klien).</p>
                </div>
                <Link
                    href="/admin/rooms"
                    class="rounded-xl border border-line-200 bg-white px-4 py-2 text-xs font-semibold text-ink-700 hover:bg-slate-50 transition shadow-2xs"
                >
                    ← Kembali ke Daftar Kamar
                </Link>
            </div>

            <!-- Cards Grid for Room Types -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <BentoCard
                    v-for="rt in room_types"
                    :key="rt.id"
                    :title="rt.name"
                    :subtitle="`Ukuran: ${rt.size_m2 || 12} m² • ${rt.rooms?.length || 0} Unit Kamar`"
                >
                    <template #action>
                        <RoomClassBadge :room-class="rt.room_class" />
                    </template>

                    <div class="space-y-4 pt-2 text-xs">
                        <!-- Rates breakdown -->
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-line-200 space-y-2">
                            <div class="flex justify-between items-center py-1 border-b border-line-100">
                                <span class="text-ink-500">Tarif Harian</span>
                                <RupiahText :amount="rt.daily_rate" class="font-bold text-forest-900" />
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-line-100">
                                <span class="text-ink-500">Tarif Mingguan</span>
                                <RupiahText :amount="rt.weekly_rate" class="font-bold text-forest-900" />
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="text-ink-500">Tarif Bulanan</span>
                                <RupiahText :amount="rt.monthly_rate" class="font-extrabold text-sm text-forest-900" />
                            </div>
                        </div>

                        <!-- Description -->
                        <p class="text-ink-600 line-clamp-3 leading-relaxed">
                            {{ rt.description || 'Tidak ada deskripsi spesifik.' }}
                        </p>

                        <!-- Publish Status & Edit Button -->
                        <div class="pt-2 flex items-center justify-between border-t border-line-100">
                            <span :class="['px-2 py-0.5 rounded text-[11px] font-semibold', rt.is_published ? 'bg-green-100 text-green-800' : 'bg-slate-200 text-slate-700']">
                                {{ rt.is_published ? 'Aktif (Publik)' : 'Draft (Nonaktif)' }}
                            </span>
                            <button
                                type="button"
                                @click="openEditModal(rt)"
                                class="px-3 py-1.5 rounded-lg bg-forest-900 text-white font-semibold text-xs hover:bg-forest-800 transition"
                            >
                                Sesuaikan Tarif
                            </button>
                        </div>
                    </div>
                </BentoCard>
            </div>

            <!-- Edit Modal -->
            <Modal :show="showModal" @close="showModal = false">
                <div class="p-6">
                    <h3 class="text-base font-bold text-forest-900 mb-4">
                        Edit Tarif & Detail: {{ editingType?.name }}
                    </h3>

                    <form @submit.prevent="submitType" class="space-y-4 text-xs">
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <InputLabel value="Tarif Harian (Rp)" />
                                <TextInput type="number" v-model="form.daily_rate" min="0" required />
                                <InputError :message="form.errors.daily_rate" />
                            </div>
                            <div>
                                <InputLabel value="Tarif Mingguan (Rp)" />
                                <TextInput type="number" v-model="form.weekly_rate" min="0" required />
                                <InputError :message="form.errors.weekly_rate" />
                            </div>
                            <div>
                                <InputLabel value="Tarif Bulanan (Rp)" />
                                <TextInput type="number" v-model="form.monthly_rate" min="0" required />
                                <InputError :message="form.errors.monthly_rate" />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Deskripsi Fasilitas Kamar" />
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="w-full rounded-xl border-line-300 text-xs p-3 focus:ring-forest-500 focus:border-forest-500"
                            ></textarea>
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="is_published" v-model="form.is_published" class="rounded border-line-300 text-forest-900 focus:ring-forest-500" />
                            <label for="is_published" class="text-xs text-ink-700 font-medium">Tampilkan di halaman publik landing</label>
                        </div>

                        <div class="flex justify-end gap-2 pt-4">
                            <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                            <PrimaryButton :disabled="form.processing">Simpan Tarif</PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    </AdminLayout>
</template>

