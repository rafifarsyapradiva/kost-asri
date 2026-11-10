<script setup>
import { computed } from 'vue';

const props = defineProps({
    room: {
        type: Object,
        required: true, // { id, room_number, status, room_type: { name, room_class }, ... }
    },
    mode: {
        type: String,
        default: 'selectable', // 'selectable' | 'inspector'
    },
    isSelected: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['click']);

const roomClass = computed(() => {
    return (props.room.room_type?.room_class || props.room.room_class || 'standard').toLowerCase();
});

const status = computed(() => {
    return (props.room.status || 'available').toLowerCase();
});

const isAvailable = computed(() => status.value === 'available');

const isClickable = computed(() => {
    if (props.mode === 'inspector') return true;
    return isAvailable.value;
});

const classBorderColor = computed(() => {
    switch (roomClass.value) {
        case 'vip': return 'border-l-[#2563EB]';
        case 'deluxe': return 'border-l-[#C9A227]';
        case 'standard':
        default: return 'border-l-[#6B7280]';
    }
});

const statusStyles = computed(() => {
    switch (status.value) {
        case 'available':
            return {
                bg: 'bg-green-50 hover:bg-green-100/80',
                border: 'border-green-300',
                text: 'text-green-900',
                dot: 'bg-green-600',
                label: 'Tersedia',
            };
        case 'booked':
            return {
                bg: 'bg-yellow-50 hover:bg-yellow-100/80',
                border: 'border-yellow-300',
                text: 'text-yellow-900',
                dot: 'bg-yellow-500',
                label: 'Dipesan',
            };
        case 'occupied':
            return {
                bg: 'bg-red-50 hover:bg-red-100/80',
                border: 'border-red-300',
                text: 'text-red-900',
                dot: 'bg-red-600',
                label: 'Terisi',
            };
        case 'maintenance':
        default:
            return {
                bg: 'bg-slate-100 hover:bg-slate-200/80',
                border: 'border-slate-300',
                text: 'text-slate-800',
                dot: 'bg-slate-500',
                label: 'Perbaikan',
            };
    }
});

const ariaLabel = computed(() => {
    const num = props.room.room_number || props.room.code;
    const type = props.room.room_type?.name || roomClass.value;
    return `Kamar ${num}, Tipe ${type}, Status ${statusStyles.value.label}`;
});

const handleClick = () => {
    if (isClickable.value) {
        emit('click', props.room);
    }
};
</script>

<template>
    <button
        type="button"
        :disabled="!isClickable"
        :aria-label="ariaLabel"
        :class="[
            'group relative flex flex-col justify-between rounded-xl border border-l-4 p-3 text-left transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-forest-500 focus-visible:ring-offset-1',
            classBorderColor,
            statusStyles.bg,
            statusStyles.border,
            statusStyles.text,
            isSelected ? 'ring-2 ring-forest-900 ring-offset-2 scale-102 shadow-md' : 'shadow-2xs',
            isClickable ? 'cursor-pointer hover:shadow-sm' : 'cursor-not-allowed opacity-75'
        ]"
        @click="handleClick"
    >
        <!-- Top Bar: Room Number + Status Dot -->
        <div class="flex items-center justify-between w-full">
            <span class="num text-base font-extrabold tracking-tight">
                {{ room.room_number || room.code }}
            </span>
            <span
                :class="['h-2.5 w-2.5 rounded-full shrink-0', statusStyles.dot]"
                :title="statusStyles.label"
            />
        </div>

        <!-- Room Type / Status Label -->
        <div class="mt-2 flex items-center justify-between text-[11px] leading-tight">
            <span class="font-semibold uppercase tracking-wider opacity-85">
                {{ roomClass === 'standard' ? 'STD' : roomClass === 'vip' ? 'VIP' : 'DLX' }}
            </span>
            <span class="font-medium opacity-90">
                {{ statusStyles.label }}
            </span>
        </div>

        <!-- Inspector snippet if in inspector mode -->
        <div v-if="mode === 'inspector' && (room.active_lease || room.current_tenant)" class="mt-1.5 pt-1.5 border-t border-black/10 text-[10px] truncate">
            <span class="font-medium">
                {{ room.active_lease?.tenant?.name || room.current_tenant?.name || 'Penghuni' }}
            </span>
        </div>
    </button>
</template>

