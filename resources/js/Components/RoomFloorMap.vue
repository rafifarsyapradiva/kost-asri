<script setup>
import { ref, computed } from 'vue';
import RoomCell from '@/Components/RoomCell.vue';

const props = defineProps({
    rooms: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'selectable', // 'selectable' | 'inspector'
    },
    selectedRoomId: {
        type: [Number, String],
        default: null,
    },
});

const emit = defineEmits(['select', 'inspect']);

const activeFloor = ref('all'); // 'all', '1', '2'

const floor1Rooms = computed(() => {
    return props.rooms.filter((r) => {
        const num = parseInt(r.room_number || r.code, 10);
        return (r.floor === 1 || (num >= 101 && num <= 116));
    }).sort((a, b) => parseInt(a.room_number || a.code, 10) - parseInt(b.room_number || b.code, 10));
});

const floor2Rooms = computed(() => {
    return props.rooms.filter((r) => {
        const num = parseInt(r.room_number || r.code, 10);
        return (r.floor === 2 || (num >= 201 && num <= 216));
    }).sort((a, b) => parseInt(a.room_number || a.code, 10) - parseInt(b.room_number || b.code, 10));
});

const handleRoomClick = (room) => {
    if (props.mode === 'inspector') {
        emit('inspect', room);
    } else {
        emit('select', room);
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- Floor Tabs & Legend -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <!-- Tabs -->
            <div class="inline-flex rounded-xl border border-line-200 bg-slate-50 p-1">
                <button
                    type="button"
                    @click="activeFloor = 'all'"
                    :class="[
                        'rounded-lg px-3.5 py-1.5 text-xs font-semibold transition',
                        activeFloor === 'all'
                            ? 'bg-white text-forest-900 shadow-xs'
                            : 'text-ink-600 hover:text-ink-900'
                    ]"
                >
                    Semua Lantai (32)
                </button>
                <button
                    type="button"
                    @click="activeFloor = '1'"
                    :class="[
                        'rounded-lg px-3.5 py-1.5 text-xs font-semibold transition',
                        activeFloor === '1'
                            ? 'bg-white text-forest-900 shadow-xs'
                            : 'text-ink-600 hover:text-ink-900'
                    ]"
                >
                    Lantai 1 (101–116)
                </button>
                <button
                    type="button"
                    @click="activeFloor = '2'"
                    :class="[
                        'rounded-lg px-3.5 py-1.5 text-xs font-semibold transition',
                        activeFloor === '2'
                            ? 'bg-white text-forest-900 shadow-xs'
                            : 'text-ink-600 hover:text-ink-900'
                    ]"
                >
                    Lantai 2 (201–216)
                </button>
            </div>

            <!-- Legend Status & Class -->
            <div class="flex flex-wrap items-center gap-3 text-xs text-ink-600">
                <div class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-full bg-green-500"></span>
                    <span>Tersedia</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-full bg-yellow-500"></span>
                    <span>Dipesan</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>
                    <span>Terisi</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-full bg-slate-400"></span>
                    <span>Perbaikan</span>
                </div>
                <span class="text-line-200">|</span>
                <span class="border-l-4 border-l-[#6B7280] pl-1.5 font-medium">Standard</span>
                <span class="border-l-4 border-l-[#2563EB] pl-1.5 font-medium">VIP</span>
                <span class="border-l-4 border-l-[#C9A227] pl-1.5 font-medium">Deluxe</span>
            </div>
        </div>

        <!-- Lantai 1 -->
        <div v-if="activeFloor === 'all' || activeFloor === '1'" class="rounded-2xl border border-line-200 bg-white p-5 shadow-xs">
            <div class="mb-4 flex items-center justify-between border-b border-line-100 pb-3">
                <h4 class="text-sm font-bold text-forest-900">
                    Lantai 1 <span class="text-xs font-normal text-ink-500">(16 Kamar: 12 Standard, 4 VIP)</span>
                </h4>
                <span class="text-xs font-semibold text-ink-400">Koridor Lantai 1</span>
            </div>

            <div class="space-y-4">
                <!-- Baris Utara: 101 - 108 -->
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-8 gap-2.5">
                    <RoomCell
                        v-for="room in floor1Rooms.slice(0, 8)"
                        :key="room.id"
                        :room="room"
                        :mode="mode"
                        :is-selected="selectedRoomId == room.id"
                        @click="handleRoomClick"
                    />
                </div>

                <!-- Jalur Koridor -->
                <div class="flex items-center justify-center rounded-lg bg-slate-50 py-1 text-[11px] font-semibold uppercase tracking-widest text-ink-400 border border-dashed border-line-200">
                    Lorong / Koridor Akses
                </div>

                <!-- Baris Selatan: 109 - 116 -->
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-8 gap-2.5">
                    <RoomCell
                        v-for="room in floor1Rooms.slice(8, 16)"
                        :key="room.id"
                        :room="room"
                        :mode="mode"
                        :is-selected="selectedRoomId == room.id"
                        @click="handleRoomClick"
                    />
                </div>
            </div>
        </div>

        <!-- Lantai 2 -->
        <div v-if="activeFloor === 'all' || activeFloor === '2'" class="rounded-2xl border border-line-200 bg-white p-5 shadow-xs">
            <div class="mb-4 flex items-center justify-between border-b border-line-100 pb-3">
                <h4 class="text-sm font-bold text-forest-900">
                    Lantai 2 <span class="text-xs font-normal text-ink-500">(16 Kamar: 4 Standard, 6 VIP, 6 Deluxe)</span>
                </h4>
                <span class="text-xs font-semibold text-ink-400">Koridor Lantai 2</span>
            </div>

            <div class="space-y-4">
                <!-- Baris Utara: 201 - 208 -->
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-8 gap-2.5">
                    <RoomCell
                        v-for="room in floor2Rooms.slice(0, 8)"
                        :key="room.id"
                        :room="room"
                        :mode="mode"
                        :is-selected="selectedRoomId == room.id"
                        @click="handleRoomClick"
                    />
                </div>

                <!-- Jalur Koridor -->
                <div class="flex items-center justify-center rounded-lg bg-slate-50 py-1 text-[11px] font-semibold uppercase tracking-widest text-ink-400 border border-dashed border-line-200">
                    Lorong / Koridor Akses
                </div>

                <!-- Baris Selatan: 209 - 216 -->
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-8 gap-2.5">
                    <RoomCell
                        v-for="room in floor2Rooms.slice(8, 16)"
                        :key="room.id"
                        :room="room"
                        :mode="mode"
                        :is-selected="selectedRoomId == room.id"
                        @click="handleRoomClick"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

