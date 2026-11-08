<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import NotificationBell from '@/Components/NotificationBell.vue';
import ToastContainer from '@/Components/ToastContainer.vue';

defineProps({
    title: {
        type: String,
        default: 'Portal Penghuni',
    },
});

const page = usePage();

const navItems = [
    { label: 'Beranda', href: '/penyewa/dashboard', icon: 'home' },
    { label: 'Tagihan', href: '/penyewa/invoices', icon: 'credit-card' },
    { label: 'Perpanjangan', href: '/penyewa/extension', icon: 'calendar' },
    { label: 'Profil Saya', href: '/akun/profil', icon: 'user' },
];

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="min-h-screen bg-cream-50 font-sans text-ink-900 flex flex-col pb-16 lg:pb-0">
        <ToastContainer />

        <!-- Top Header (Desktop & Mobile) -->
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-line-200">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center gap-6">
                    <Link href="/penyewa/dashboard" class="flex items-center gap-2.5">
                        <div class="h-8 w-8 rounded-xl bg-forest-900 text-white flex items-center justify-center font-bold text-base shadow">
                            A
                        </div>
                        <div>
                            <span class="font-extrabold tracking-tight text-forest-900 text-base block leading-none">
                                KOST ASRI
                            </span>
                            <span class="text-[10px] text-terracotta-600 font-semibold uppercase tracking-wider">
                                Portal Penghuni
                            </span>
                        </div>
                    </Link>

                    <!-- Desktop Nav -->
                    <nav class="hidden md:flex items-center gap-1">
                        <Link
                            v-for="item in navItems"
                            :key="item.href"
                            :href="item.href"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition',
                                page.url.startsWith(item.href)
                                    ? 'bg-forest-50 text-forest-900'
                                    : 'text-ink-600 hover:text-forest-900 hover:bg-slate-50'
                            ]"
                        >
                            {{ item.label }}
                        </Link>
                    </nav>
                </div>

                <div class="flex items-center gap-3">
                    <NotificationBell />
                    <button
                        type="button"
                        @click="logout"
                        class="text-xs font-semibold text-ink-500 hover:text-danger-700 p-2 transition"
                        title="Keluar"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <slot />
        </main>

        <!-- Mobile Bottom Nav (NFR-07: 360px support) -->
        <nav class="fixed bottom-0 inset-x-0 z-40 bg-white border-t border-line-200 md:hidden flex justify-around items-center h-16 shadow-lg px-2">
            <Link
                v-for="item in navItems"
                :key="item.href"
                :href="item.href"
                :class="[
                    'flex flex-col items-center justify-center flex-1 py-1 text-[11px] font-semibold transition',
                    page.url.startsWith(item.href)
                        ? 'text-forest-900'
                        : 'text-ink-500 hover:text-ink-900'
                ]"
            >
                <!-- Home Icon -->
                <svg v-if="item.icon === 'home'" class="h-5 w-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <!-- Credit Card Icon -->
                <svg v-else-if="item.icon === 'credit-card'" class="h-5 w-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <rect x="2" y="5" width="20" height="14" rx="2" stroke-width="2"/>
                    <line x1="2" y1="10" x2="22" y2="10" stroke-width="2"/>
                </svg>
                <!-- Calendar Icon -->
                <svg v-else-if="item.icon === 'calendar'" class="h-5 w-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="18" rx="2" stroke-width="2"/>
                    <line x1="16" y1="2" x2="16" y2="6" stroke-width="2"/>
                    <line x1="8" y1="2" x2="8" y2="6" stroke-width="2"/>
                    <line x1="3" y1="10" x2="21" y2="10" stroke-width="2"/>
                </svg>
                <!-- User Icon -->
                <svg v-else class="h-5 w-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>{{ item.label }}</span>
            </Link>
        </nav>
    </div>
</template>

