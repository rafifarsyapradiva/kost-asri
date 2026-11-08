<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import ReservationLayout from '@/Layouts/ReservationLayout.vue';
import RoomFloorMap from '@/Components/RoomFloorMap.vue';
import RoomClassBadge from '@/Components/RoomClassBadge.vue';
import RupiahText from '@/Components/RupiahText.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useDateId } from '@/Composables/useDateId';

const props = defineProps({
    draft: {
        type: Object,
        default: () => ({}),
    },
    rooms: {
        type: Array,
        default: () => [],
    },
    roomTypes: {
        type: Array,
        default: () => [],
    },
});

const { formatDate } = useDateId();

const todayString = new Date().toISOString().split('T')[0];
const checkInDate = ref(props.draft.check_in_date || todayString);
const selectedRoomId = ref(props.draft.room_id || null);
const selectedClass = ref('all'); // 'all' | 'standard' | 'vip' | 'deluxe'

const roomList = ref([...props.rooms]);
const isLoadingAvailability = ref(false);
const errorMessage = ref(null);

const refreshAvailability = async () => {
    isLoadingAvailability.value = true;
    errorMessage.value = null;

    try {
        const response = await axios.get('/api/availability', {
            params: { check_in: checkInDate.value },
        });
        const f1 = response.data.floor_1 || [];
        const f2 = response.data.floor_2 || [];
        roomList.value = [...f1, ...f2];

        // If previously selected room is no longer available on this new date, unselect it
        if (selectedRoomId.value) {
            const found = roomList.value.find((r) => r.id === selectedRoomId.value);
            if (!found || !found.is_available) {
                selectedRoomId.value = null;
            }
        }
    } catch (err) {
        errorMessage.value = 'Gagal memperbarui status ketersediaan kamar. Silakan coba lagi.';
    } finally {
        isLoadingAvailability.value = false;
    }
};

const handleRoomSelect = (room) => {
    if (!room.is_available && room.status !== 'available') return;
    selectedRoomId.value = room.id;
};

const selectedRoom = computed(() => {
    if (!selectedRoomId.value) return null;
    return roomList.value.find((r) => r.id === selectedRoomId.value) || null;
});

const filteredRooms = computed(() => {
    if (selectedClass.value === 'all') return roomList.value;
    return roomList.value.filter((r) => {
        const c = (r.room_type?.room_class || r.room_class || '').toLowerCase();
        return c === selectedClass.value.toLowerCase();
    });
});

const isSubmitting = ref(false);

const submitStep1 = () => {
    if (!selectedRoomId.value) {
        errorMessage.value = 'Silakan pilih kamar yang tersedia terlebih dahulu.';
        return;
    }

    isSubmitting.value = true;
    router.post(route('reservation.step1.store'), {
        room_id: selectedRoomId.value,
        check_in_date: checkInDate.value,
    }, {
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <ReservationLayout :current-step="1">
        <Head title="Langkah 1: Pilih Kamar — Kost ASRI" />

        <div class="space-y-8">
            <!-- Header section -->
            <div class="text-center sm:text-left">
                <span class="text-xs font-bold uppercase tracking-wider text-forest-800">
                    Langkah 1 dari 5
                </span>
                <h1 class="mt-1 text-2xl sm:text-3xl font-extrabold text-forest-900 tracking-tight">
                    Pilih Kamar di Denah 2 Lantai
                </h1>
                <p class="mt-1.5 text-xs text-ink-600 max-w-2xl">
                    Tentukan tanggal masuk, lalu klik kamar hijau pada denah interaktif di bawah. Sistem menjamin tidak ada kamar ganda (*anti-double booking*).
                </p>
            </div>

            <!-- Error Banner -->
            <div v-if="errorMessage" class="rounded-2xl bg-red-50 p-4 border border-red-200 text-xs font-semibold text-red-800 flex items-center justify-between">
                <span>{{ errorMessage }}</span>
                <button type="button" @click="errorMessage = null" class="font-bold text-red-600 hover:text-red-800">
                    &times;
                </button>
            </div>

            <!-- Filter Bar: Tanggal Masuk & Kelas Kamar -->
            <div class="rounded-3xl border border-line-200 bg-white p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-5">
                <!-- Input Tanggal Masuk -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <label for="check_in" class="text-xs font-bold text-ink-800 whitespace-nowrap">
                        Rencana Masuk:
                    </label>
                    <div class="relative">
                        <input
                            id="check_in"
                            type="date"
                            v-model="checkInDate"
                            :min="todayString"
                            @change="refreshAvailability"
                            class="rounded-xl border border-line-300 bg-slate-50/50 px-3.5 py-2 text-xs font-bold text-forest-900 num focus:border-forest-800 focus:bg-white focus:ring-1 focus:ring-forest-800 transition"
                        />
                        <span v-if="isLoadingAvailability" class="ml-2 text-[11px] text-ink-400 italic">
                            Memperbarui...
                        </span>
                    </div>
                </div>

                <!-- Filter Kelas Kamar -->
                <div class="flex items-center gap-2 overflow-x-auto pb-1 md:pb-0">
                    <span class="text-xs font-semibold text-ink-500 mr-1 hidden sm:inline">Kelas:</span>
                    <button
                        type="button"
                        @click="selectedClass = 'all'"
                        :class="[
                            'rounded-xl px-3 py-1.5 text-xs font-bold transition shrink-0',
                            selectedClass === 'all'
                                ? 'bg-forest-900 text-white shadow-xs'
                                : 'bg-slate-50 text-ink-600 hover:bg-slate-100'
                        ]"
                    >
                        Semua Kelas (32)
                    </button>
                    <button
                        type="button"
                        @click="selectedClass = 'standard'"
                        :class="[
                            'rounded-xl px-3 py-1.5 text-xs font-bold transition shrink-0',
                            selectedClass === 'standard'
                                ? 'bg-[#6B7280] text-white shadow-xs'
                                : 'bg-slate-50 text-ink-600 hover:bg-slate-100'
                        ]"
                    >
                        Standard (16)
                    </button>
                    <button
                        type="button"
                        @click="selectedClass = 'vip'"
                        :class="[
                            'rounded-xl px-3 py-1.5 text-xs font-bold transition shrink-0',
                            selectedClass === 'vip'
                                ? 'bg-[#2563EB] text-white shadow-xs'
                                : 'bg-slate-50 text-ink-600 hover:bg-slate-100'
                        ]"
                    >
                        VIP (10)
                    </button>
                    <button
                        type="button"
                        @click="selectedClass = 'deluxe'"
                        :class="[
                            'rounded-xl px-3 py-1.5 text-xs font-bold transition shrink-0',
                            selectedClass === 'deluxe'
                                ? 'bg-[#C9A227] text-white shadow-xs'
                                : 'bg-slate-50 text-ink-600 hover:bg-slate-100'
                        ]"
                    >
                        Deluxe (6)
                    </button>
                </div>
            </div>

            <!-- Denah Interaktif 2 Lantai -->
            <RoomFloorMap
                :rooms="filteredRooms"
                :selected-room-id="selectedRoomId"
                mode="selectable"
                @select="handleRoomSelect"
            />

            <!-- Selected Room Summary Banner & Action -->
            <div class="rounded-3xl border border-line-200 bg-white p-6 shadow-sm">
                <div v-if="selectedRoom" class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="flex items-start sm:items-center gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-forest-50 border border-forest-200">
                            <span class="num text-2xl font-black text-forest-900">{{ selectedRoom.code }}</span>
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2.5">
                                <h3 class="text-base font-bold text-forest-900">
                                    Kamar {{ selectedRoom.code }} &bull; {{ selectedRoom.room_type?.name }}
                                </h3>
                                <RoomClassBadge :room-class="selectedRoom.room_type?.room_class" />
                            </div>
                            <p class="text-xs text-ink-500">
                                Lantai {{ selectedRoom.floor }} &bull; Rencana Masuk: <span class="num font-bold text-forest-900">{{ formatDate(checkInDate) }}</span>
                            </p>
                            <!-- Tarif Cepat -->
                            <div class="flex flex-wrap items-center gap-4 pt-1 text-xs">
                                <div>
                                    <span class="text-ink-400">Bulanan: </span>
                                    <span class="font-bold text-forest-900"><RupiahText :amount="selectedRoom.room_type?.monthly_rate" />/bln</span>
                                </div>
                                <span class="text-line-200">|</span>
                                <div>
                                    <span class="text-ink-400">Mingguan: </span>
                                    <span class="font-bold text-forest-900"><RupiahText :amount="selectedRoom.room_type?.weekly_rate" />/mgg</span>
                                </div>
                                <span class="text-line-200">|</span>
                                <div>
                                    <span class="text-ink-400">Harian: </span>
                                    <span class="font-bold text-forest-900"><RupiahText :amount="selectedRoom.room_type?.daily_rate" />/mlm</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="shrink-0 flex items-center justify-end">
                        <PrimaryButton
                            @click="submitStep1"
                            :disabled="isSubmitting"
                            class="px-6 py-3 text-sm font-bold"
                        >
                            <span v-if="isSubmitting">Menyimpan...</span>
                            <span v-else class="flex items-center gap-2">
                                <span>Lanjut ke Tipe Sewa</span>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </span>
                        </PrimaryButton>
                    </div>
                </div>

                <!-- State Belum Pilih Kamar -->
                <div v-else class="flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left py-2">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-amber-50 flex items-center justify-center text-amber-700 shrink-0">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-ink-900">Belum Ada Kamar Terpilih</div>
                            <div class="text-[11px] text-ink-500">Silakan klik salah satu kotak nomor kamar berwarna hijau pada denah lantai 1 atau 2 di atas.</div>
                        </div>
                    </div>

                    <PrimaryButton
                        disabled
                        class="opacity-50 cursor-not-allowed px-5 py-2.5 text-xs"
                    >
                        Pilih Kamar Dahulu
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </ReservationLayout>
</template>

