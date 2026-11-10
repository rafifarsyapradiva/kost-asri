<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import RoomFloorMap from '@/Components/RoomFloorMap.vue';
import SlideOverDrawer from '@/Components/SlideOverDrawer.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import RoomClassBadge from '@/Components/RoomClassBadge.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useDateId } from '@/Composables/useDateId';

const props = defineProps({
    floor_1: {
        type: Array,
        default: () => [],
    },
    floor_2: {
        type: Array,
        default: () => [],
    },
});

const { formatDate } = useDateId();
const allRooms = computed(() => [...props.floor_1, ...props.floor_2]);
const selectedRoom = ref(null);
const isDrawerOpen = ref(false);

const handleInspectRoom = (room) => {
    selectedRoom.value = room;
    isDrawerOpen.value = true;
};

const toggleMaintenance = () => {
    if (!selectedRoom.value) return;
    router.patch(
        `/admin/rooms/${selectedRoom.value.id}/toggle-maintenance`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                isDrawerOpen.value = false;
                selectedRoom.value = null;
            },
        }
    );
};
</script>

<template>
    <Head title="Denah Operasional 32 Kamar" />

    <AdminLayout title="Denah Operasional 32 Kamar">
        <div class="space-y-6">
            <!-- Header bar with quick link to room list -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-forest-900">Peta Denah Koridor Lantai 1 & 2</h2>
                    <p class="text-xs text-ink-500">Klik salah satu sel kamar untuk memeriksa detail penghuni, status tagihan, atau aksi operasional.</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        href="/admin/rooms"
                        class="rounded-xl border border-line-200 bg-white px-3.5 py-2 text-xs font-semibold text-ink-700 hover:bg-slate-50 transition shadow-2xs"
                    >
                        Kelola Daftar Kamar →
                    </Link>
                </div>
            </div>

            <!-- Floor Map Bento Card -->
            <BentoCard title="Visualisasi 32 Kamar (2 Lantai)">
                <RoomFloorMap
                    :rooms="allRooms"
                    mode="inspector"
                    :selected-room-id="selectedRoom?.id"
                    @inspect="handleInspectRoom"
                />
            </BentoCard>

            <!-- SlideOverDrawer for Room Details -->
            <SlideOverDrawer
                :show="isDrawerOpen"
                :title="selectedRoom ? `Kamar ${selectedRoom.code}` : 'Detail Kamar'"
                @close="isDrawerOpen = false"
            >
                <div v-if="selectedRoom" class="space-y-6 text-xs">
                    <!-- Room Header Specs -->
                    <div class="p-4 rounded-xl border border-line-200 bg-slate-50 flex items-center justify-between">
                        <div>
                            <span class="text-ink-500 block">Tipe & Kelas:</span>
                            <span class="font-bold text-sm text-forest-900 block mt-0.5">
                                {{ selectedRoom.room_type?.name }}
                            </span>
                            <span class="text-[11px] text-ink-500">
                                Lantai <span class="num font-semibold">{{ selectedRoom.floor }}</span> (Posisi Baris {{ selectedRoom.grid_row }}, Kolom {{ selectedRoom.grid_col }})
                            </span>
                        </div>
                        <div class="text-right space-y-1">
                            <RoomClassBadge :room-class="selectedRoom.room_type?.room_class" />
                            <StatusBadge :status="selectedRoom.status" class="block" />
                        </div>
                    </div>

                    <!-- Maintenance status -->
                    <div v-if="selectedRoom.status === 'maintenance'" class="p-3.5 rounded-xl bg-slate-100 border border-slate-300 text-slate-800 space-y-1">
                        <span class="font-bold block">Kamar Sedang Dalam Pemeliharaan</span>
                        <p class="text-ink-600">{{ selectedRoom.maintenance_note || 'Tidak ada catatan pemeliharaan.' }}</p>
                    </div>

                    <!-- Occupant Details if Occupied -->
                    <div v-if="selectedRoom.occupant" class="space-y-3">
                        <h4 class="font-bold text-forest-900 uppercase tracking-wider text-[11px]">Informasi Penghuni Aktif</h4>
                        <div class="p-4 rounded-xl border border-line-200 bg-white space-y-2.5">
                            <div class="flex justify-between items-center py-1 border-b border-line-100">
                                <span class="text-ink-500">Nama Penghuni</span>
                                <span class="font-bold text-forest-900">{{ selectedRoom.occupant.name }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-line-100">
                                <span class="text-ink-500">Nomor WhatsApp</span>
                                <span class="num font-semibold text-ink-900">{{ selectedRoom.occupant.phone }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-line-100">
                                <span class="text-ink-500">Sewa Lunas Hingga</span>
                                <span class="num font-bold text-forest-800">{{ formatDate(selectedRoom.occupant.paid_until) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="text-ink-500">Status Tagihan</span>
                                <span v-if="selectedRoom.occupant.has_overdue" class="text-danger-700 font-bold">
                                    Ada Tagihan Jatuh Tempo!
                                </span>
                                <span v-else class="text-forest-700 font-semibold">
                                    Semua Tagihan Lancar
                                </span>
                            </div>
                        </div>

                        <!-- Action shortcuts for active occupant -->
                        <div class="grid grid-cols-2 gap-2 pt-2">
                            <Link
                                :href="`/admin/leases/${selectedRoom.occupant.lease_id}`"
                                class="w-full text-center py-2.5 rounded-xl bg-forest-900 text-white font-semibold hover:bg-forest-800 transition"
                            >
                                Detail Kontrak
                            </Link>
                            <Link
                                :href="`/admin/invoices`"
                                class="w-full text-center py-2.5 rounded-xl border border-line-200 bg-white text-ink-800 font-semibold hover:bg-slate-50 transition"
                            >
                                Cek Tagihan
                            </Link>
                        </div>
                    </div>

                    <!-- If room is available -->
                    <div v-else-if="selectedRoom.status === 'available'" class="space-y-4">
                        <div class="p-4 rounded-xl bg-green-50 border border-green-200 text-green-900">
                            <span class="font-bold block">Kamar Kosong & Siap Huni</span>
                            <p class="text-[11px] text-green-800 mt-1">Unit ini siap digunakan untuk tamu reservasi online baru atau tamu walk-in langsung.</p>
                        </div>

                        <Link
                            href="/admin/reservations/create"
                            class="block w-full text-center py-2.5 rounded-xl bg-forest-900 text-white font-bold hover:bg-forest-800 transition shadow-xs"
                        >
                            Buat Reservasi Walk-In untuk Kamar Ini
                        </Link>
                    </div>

                    <!-- Maintenance Toggle Button -->
                    <div class="pt-4 border-t border-line-200">
                        <button
                            type="button"
                            @click="toggleMaintenance"
                            :class="[
                                'w-full py-2.5 rounded-xl font-bold transition text-xs flex items-center justify-center gap-2',
                                selectedRoom.status === 'maintenance'
                                    ? 'bg-forest-900 text-white hover:bg-forest-800'
                                    : 'border border-amber-300 bg-amber-50 text-amber-900 hover:bg-amber-100'
                            ]"
                        >
                            {{ selectedRoom.status === 'maintenance' ? 'Aktifkan Kembali Kamar (Tersedia)' : 'Set Status Perbaikan (Maintenance)' }}
                        </button>
                    </div>
                </div>
            </SlideOverDrawer>
        </div>
    </AdminLayout>
</template>

