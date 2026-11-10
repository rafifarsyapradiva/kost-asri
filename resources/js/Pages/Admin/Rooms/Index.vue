<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import RoomClassBadge from '@/Components/RoomClassBadge.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    rooms: {
        type: Array,
        default: () => [],
    },
});

const filterFloor = ref('all');
const filterStatus = ref('all');
const searchQuery = ref('');

const filteredRooms = computed(() => {
    return props.rooms.filter((room) => {
        if (filterFloor.value !== 'all' && room.floor !== parseInt(filterFloor.value, 10)) {
            return false;
        }
        if (filterStatus.value !== 'all' && room.status !== filterStatus.value) {
            return false;
        }
        if (searchQuery.value.trim() !== '') {
            const q = searchQuery.value.toLowerCase();
            return room.code.toLowerCase().includes(q) || (room.room_type?.name && room.room_type.name.toLowerCase().includes(q));
        }
        return true;
    });
});

// Modal State
const showModal = ref(false);
const editingRoom = ref(null);

const form = useForm({
    code: '',
    floor: 1,
    grid_row: 1,
    grid_col: 1,
    room_type_id: '',
    notes: '',
});

const openCreateModal = () => {
    editingRoom.value = null;
    form.reset();
    form.floor = 1;
    form.grid_row = 1;
    form.grid_col = 1;
    form.room_type_id = props.rooms[0]?.room_type_id || '';
    showModal.value = true;
};

const openEditModal = (room) => {
    editingRoom.value = room;
    form.code = room.code;
    form.floor = room.floor;
    form.grid_row = room.grid_row;
    form.grid_col = room.grid_col;
    form.room_type_id = room.room_type_id;
    form.notes = room.notes || '';
    showModal.value = true;
};

const submitRoom = () => {
    if (editingRoom.value) {
        form.put(`/admin/rooms/${editingRoom.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post('/admin/rooms', {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const toggleMaintenance = (room) => {
    router.patch(`/admin/rooms/${room.id}/toggle-maintenance`, {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Kelola 32 Kamar" />

    <AdminLayout title="Inventaris 32 Kamar Kost ASRI">
        <div class="space-y-6">
            <!-- Header bar -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        href="/admin/rooms/map"
                        class="rounded-xl bg-forest-900 px-4 py-2 text-xs font-semibold text-white hover:bg-forest-800 transition shadow-2xs flex items-center gap-1.5"
                    >
                        <span>Lihat Denah 2 Lantai →</span>
                    </Link>
                    <Link
                        href="/admin/room-types"
                        class="rounded-xl border border-line-200 bg-white px-4 py-2 text-xs font-semibold text-ink-700 hover:bg-slate-50 transition shadow-2xs"
                    >
                        Tarif & Tipe Kamar
                    </Link>
                </div>
            </div>

            <!-- Filters & Search -->
            <BentoCard title="Daftar Unit Kamar">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 mb-5">
                    <div class="flex flex-wrap items-center gap-2">
                        <select
                            v-model="filterFloor"
                            class="rounded-xl border-line-300 text-xs py-2 px-3 focus:ring-forest-500 focus:border-forest-500 bg-white"
                        >
                            <option value="all">Semua Lantai (1 & 2)</option>
                            <option value="1">Lantai 1 (101–116)</option>
                            <option value="2">Lantai 2 (201–216)</option>
                        </select>

                        <select
                            v-model="filterStatus"
                            class="rounded-xl border-line-300 text-xs py-2 px-3 focus:ring-forest-500 focus:border-forest-500 bg-white"
                        >
                            <option value="all">Semua Status</option>
                            <option value="available">Tersedia (Ready)</option>
                            <option value="occupied">Terisi (Occupied)</option>
                            <option value="booked">Dipesan (Booked)</option>
                            <option value="maintenance">Perbaikan (Maintenance)</option>
                        </select>
                    </div>

                    <div class="w-full sm:w-64">
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="Cari kode kamar..."
                            class="w-full rounded-xl border border-line-300 text-xs py-2 px-3 focus:ring-forest-500 focus:border-forest-500 bg-white"
                        />
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line-200 text-xs text-left">
                        <thead class="bg-slate-50 text-ink-500 font-semibold uppercase">
                            <tr>
                                <th class="px-4 py-3">Kode</th>
                                <th class="px-4 py-3">Lantai</th>
                                <th class="px-4 py-3">Tipe & Kelas</th>
                                <th class="px-4 py-3">Grid Posisi</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line-100">
                            <tr v-for="room in filteredRooms" :key="room.id" class="hover:bg-slate-50/60 transition">
                                <td class="px-4 py-3 font-bold text-forest-900 num text-sm">
                                    {{ room.code }}
                                </td>
                                <td class="px-4 py-3 num">
                                    Lantai {{ room.floor }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <RoomClassBadge :room-class="room.room_type?.room_class" />
                                        <span class="font-medium text-ink-900">{{ room.room_type?.name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-ink-500 num">
                                    R{{ room.grid_row }}:C{{ room.grid_col }}
                                </td>
                                <td class="px-4 py-3">
                                    <StatusBadge :status="room.status" />
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="toggleMaintenance(room)"
                                            :class="[
                                                'px-2.5 py-1 rounded text-[11px] font-semibold transition',
                                                room.status === 'maintenance'
                                                    ? 'bg-green-100 text-green-800 hover:bg-green-200'
                                                    : 'bg-slate-100 text-ink-700 hover:bg-slate-200'
                                            ]"
                                        >
                                            {{ room.status === 'maintenance' ? 'Set Siap Huni' : 'Set Maintenance' }}
                                        </button>
                                        <button
                                            type="button"
                                            @click="openEditModal(room)"
                                            class="px-2.5 py-1 rounded border border-line-200 text-ink-700 hover:bg-slate-100 font-semibold text-[11px]"
                                        >
                                            Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredRooms.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-ink-500">
                                    Tidak ada unit kamar yang sesuai kriteria pencarian.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </BentoCard>

            <!-- Edit Modal -->
            <Modal :show="showModal" @close="showModal = false">
                <div class="p-6">
                    <h3 class="text-base font-bold text-forest-900 mb-4">
                        {{ editingRoom ? `Edit Kamar ${editingRoom.code}` : 'Tambah Kamar Baru' }}
                    </h3>

                    <form @submit.prevent="submitRoom" class="space-y-4 text-xs">
                        <div>
                            <InputLabel value="Nomor/Kode Kamar" />
                            <TextInput v-model="form.code" placeholder="Contoh: 101" required />
                            <InputError :message="form.errors.code" />
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <InputLabel value="Lantai" />
                                <select v-model="form.floor" class="w-full rounded-xl border-line-300 text-xs py-2 px-3">
                                    <option :value="1">Lantai 1</option>
                                    <option :value="2">Lantai 2</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Grid Baris" />
                                <TextInput type="number" v-model="form.grid_row" min="1" required />
                            </div>
                            <div>
                                <InputLabel value="Grid Kolom" />
                                <TextInput type="number" v-model="form.grid_col" min="1" required />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Catatan / Info Fasilitas Khusus" />
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                class="w-full rounded-xl border-line-300 text-xs p-3 focus:ring-forest-500 focus:border-forest-500"
                                placeholder="Misal: Posisi dekat jendela taman depan..."
                            ></textarea>
                            <InputError :message="form.errors.notes" />
                        </div>

                        <div class="flex justify-end gap-2 pt-4">
                            <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                            <PrimaryButton :disabled="form.processing">Simpan Perubahan</PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    </AdminLayout>
</template>

