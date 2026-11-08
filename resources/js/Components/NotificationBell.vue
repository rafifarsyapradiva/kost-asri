<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const isOpen = ref(false);
const notifications = ref([]);
const loading = ref(false);

const unreadCount = computed(() => {
    return page.props.unreadNotificationsCount || 0;
});

const toggleDropdown = async () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        await fetchNotifications();
    }
};

const fetchNotifications = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/notifications');
        notifications.value = res.data.data || [];
    } catch (err) {
        // fallback
    } finally {
        loading.value = false;
    }
};

const markAllAsRead = async () => {
    try {
        await axios.post('/notifications/read-all');
        notifications.value = notifications.value.map((n) => ({ ...n, read_at: new Date().toISOString() }));
        router.reload({ only: ['unreadNotificationsCount'] });
    } catch (err) {
        // ignore
    }
};

const closeDropdown = (e) => {
    if (!e.target.closest('#notification-bell-container')) {
        isOpen.value = false;
    }
};

onMounted(() => document.addEventListener('click', closeDropdown));
onUnmounted(() => document.removeEventListener('click', closeDropdown));
</script>

<template>
    <div id="notification-bell-container" class="relative">
        <button
            type="button"
            @click="toggleDropdown"
            class="relative rounded-xl p-2 text-ink-500 hover:bg-slate-100 hover:text-ink-900 transition focus:outline-none"
            aria-label="Notifikasi"
        >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span
                v-if="unreadCount > 0"
                class="num absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-danger-700 text-[10px] font-bold text-white shadow"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-show="isOpen"
                class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl border border-line-200 bg-white py-2 shadow-xl z-50 overflow-hidden"
            >
                <div class="flex items-center justify-between border-b border-line-200 px-4 py-3">
                    <h4 class="text-sm font-bold text-forest-900">Notifikasi</h4>
                    <button
                        v-if="unreadCount > 0"
                        type="button"
                        @click="markAllAsRead"
                        class="text-xs font-semibold text-forest-700 hover:text-forest-900"
                    >
                        Tandai semua dibaca
                    </button>
                </div>

                <div class="max-h-80 overflow-y-auto divide-y divide-line-100">
                    <div v-if="loading" class="p-6 text-center text-xs text-ink-500">
                        Memuat notifikasi...
                    </div>
                    <div v-else-if="notifications.length === 0" class="p-8 text-center text-xs text-ink-500">
                        Belum ada notifikasi baru.
                    </div>
                    <div
                        v-else
                        v-for="item in notifications"
                        :key="item.id"
                        :class="[
                            'px-4 py-3 text-xs transition',
                            !item.read_at ? 'bg-forest-50/40' : 'bg-white'
                        ]"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-semibold text-ink-900">{{ item.data?.title || 'Informasi' }}</p>
                            <span class="num text-[10px] text-ink-400 shrink-0">{{ item.created_at_human || '' }}</span>
                        </div>
                        <p class="mt-1 text-ink-600 leading-normal">{{ item.data?.message || '' }}</p>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

