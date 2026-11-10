<script setup>
import { ref } from 'vue';

const props = defineProps({
    items: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['reorder']);

const draggedIndex = ref(null);

const onDragStart = (index, event) => {
    draggedIndex.value = index;
    event.dataTransfer.effectAllowed = 'move';
};

const onDragOver = (event) => {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
};

const onDrop = (dropIndex, event) => {
    event.preventDefault();
    if (draggedIndex.value === null || draggedIndex.value === dropIndex) return;

    const reordered = [...props.items];
    const [movedItem] = reordered.splice(draggedIndex.value, 1);
    reordered.splice(dropIndex, 0, movedItem);

    draggedIndex.value = null;
    emit('reorder', reordered);
};
</script>

<template>
    <div class="space-y-2">
        <div
            v-for="(item, index) in items"
            :key="item.id || index"
            draggable="true"
            @dragstart="onDragStart(index, $event)"
            @dragover="onDragOver($event)"
            @drop="onDrop(index, $event)"
            class="group flex items-center justify-between p-3 rounded-xl border border-line-200 bg-white hover:border-forest-500 hover:shadow-xs transition cursor-grab active:cursor-grabbing"
        >
            <div class="flex items-center gap-3">
                <span class="text-ink-400 group-hover:text-forest-700 cursor-grab select-none">
                    ⋮⋮
                </span>
                <slot name="item" :item="item" :index="index">
                    <span class="text-xs font-semibold text-ink-900">{{ item.name || item.title }}</span>
                </slot>
            </div>
            <div class="flex items-center gap-2">
                <slot name="actions" :item="item" :index="index"></slot>
            </div>
        </div>
    </div>
</template>

