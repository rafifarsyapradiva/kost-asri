<script setup>
import { ref } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import NotificationBell from '@/Components/NotificationBell.vue';
import ToastContainer from '@/Components/ToastContainer.vue';

defineProps({
    title: {
        type: String,
        default: 'Dashboard Admin',
    },
});

const page = usePage();
const isSidebarOpen = ref(false);

const navItems = [
    { label: 'Dashboard', href: '/admin/dashboard', icon: 'home' },
    { label: 'Denah Kamar', href: '/admin/rooms/map', icon: 'map' },
    { label: 'Daftar Kamar', href: '/admin/rooms', icon: 'door' },
    { label: 'Tipe Kamar', href: '/admin/room-types', icon: 'layers' },
    { label: 'Reservasi', href: '/admin/reservations', icon: 'calendar' },
    { label: 'Penyewa', href: '/admin/tenants', icon: 'users' },
    { label: 'Kontrak Sewa', href: '/admin/leases', icon: 'file-text' },
    { label: 'Tagihan', href: '/admin/invoices', icon: 'credit-card' },
    { label: 'Pembayaran', href: '/admin/payments', icon: 'check-circle' },
    { label: 'Laporan', href: '/admin/reports', icon: 'bar-chart' },
    { label: 'Konten CMS', href: '/admin/cms/site-settings', icon: 'layout' },
    { label: 'Chatbot WA', href: '/admin/chatbot', icon: 'message-square' },
    { label: 'Log Notifikasi', href: '/admin/notification-logs', icon: 'bell' },
    { label: 'Pengaturan Sistem', href: '/admin/settings', icon: 'settings' },
    { label: 'Log Aktivitas', href: '/admin/activity-logs', icon: 'activity' },
];

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 font-sans text-ink-900 flex">
        <ToastContainer />

        <!-- Mobile Sidebar Backdrop -->
        <div
            v-if="isSidebarOpen"
            class="fixed inset-0 z-40 bg-ink-950/60 backdrop-blur-xs lg:hidden"
            @click="isSidebarOpen = false"
        />

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 w-64 bg-forest-900 text-white flex flex-col justify-between shadow-xl transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0',
                isSidebarOpen ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <div>
                <!-- Brand Header -->
                <div class="h-16 flex items-center justify-between px-6 border-b border-forest-800">
                    <Link href="/admin/dashboard" class="flex items-center gap-2.5">
                        <div class="h-8 w-8 rounded-xl bg-terracotta-500 text-ink-900 flex items-center justify-center font-black text-base shadow">
                            A
                        </div>
                        <div>
                            <span class="font-extrabold tracking-tight text-white text-base block leading-none">
                                ASRI ERP
                            </span>
                            <span class="text-[10px] text-sage-300 font-medium uppercase tracking-wider">
                                Management Suite
                            </span>
                        </div>
                    </Link>
                    <button
                        type="button"
                        class="lg:hidden text-white hover:text-sage-300 p-1"
                        @click="isSidebarOpen = false"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Navigation List -->
                <nav class="p-3 space-y-1 max-h-[calc(100vh-8rem)] overflow-y-auto">
                    <Link
                        v-for="item in navItems"
                        :key="item.href"
                        :href="item.href"
                        :class="[
                            'flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-semibold transition',
                            page.url.startsWith(item.href)
                                ? 'bg-forest-800 text-white shadow-xs'
                                : 'text-sage-100/80 hover:bg-forest-800/60 hover:text-white'
                        ]"
                    >
                        <span>{{ item.label }}</span>
                    </Link>
                </nav>
            </div>

            <!-- Footer: User Info & Logout -->
            <div class="p-4 border-t border-forest-800 bg-forest-950/40">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 pr-2">
                        <p class="text-xs font-bold text-white truncate">
                            {{ page.props.auth?.user?.name || 'Administrator' }}
                        </p>
                        <p class="text-[10px] text-sage-300 truncate">
                            {{ page.props.auth?.user?.role || 'admin' }}
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="logout"
                        title="Keluar"
                        class="p-2 rounded-lg text-sage-300 hover:text-white hover:bg-forest-800 transition"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Topbar -->
            <header class="h-16 bg-white border-b border-line-200 flex items-center justify-between px-4 sm:px-8 shrink-0">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="lg:hidden p-2 rounded-lg text-ink-600 hover:bg-slate-100"
                        @click="isSidebarOpen = true"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <h1 class="text-lg font-bold text-forest-900 truncate">
                        {{ title }}
                    </h1>
                </div>

                <div class="flex items-center gap-3">
                    <NotificationBell />
                    <a
                        href="/"
                        target="_blank"
                        class="hidden sm:inline-flex items-center gap-1.5 text-xs font-semibold text-ink-600 hover:text-forest-900 border border-line-200 rounded-lg px-2.5 py-1.5 transition"
                    >
                        <span>Lihat Web</span>
                    </a>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>

