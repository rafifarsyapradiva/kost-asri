<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import RupiahText from '@/Components/RupiahText.vue';

defineProps({
    tenant: Object,
});
</script>

<template>
    <Head :title="`Profil - ${tenant.name}`" />

    <AdminLayout :title="`Profil Penyewa: ${tenant.name}`">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <Link href="/admin/tenants" class="text-xs font-semibold text-forest-700 hover:text-forest-900">
                    ← Kembali ke Daftar Penyewa
                </Link>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Data Diri Penyewa -->
                <BentoCard title="Informasi Identitas Penyewa">
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Nama Lengkap</span>
                            <span class="font-bold text-forest-900">{{ tenant.name }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Nomor WhatsApp</span>
                            <span class="num font-semibold text-ink-900">{{ tenant.phone }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Email</span>
                            <span class="font-semibold text-ink-900">{{ tenant.email }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">NIK (Masked)</span>
                            <span class="num font-semibold text-ink-900">{{ tenant.tenant_profile?.nik_masked || 'Belum diisi' }}</span>
                        </div>
                    </div>
                </BentoCard>

                <!-- Data Wali -->
                <BentoCard title="Kontak Wali / Orang Tua">
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Nama Wali</span>
                            <span class="font-bold text-forest-900">{{ tenant.tenant_profile?.guardian_name || '-' }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Hubungan</span>
                            <span class="font-semibold text-ink-900">{{ tenant.tenant_profile?.guardian_relation || '-' }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Nomor WhatsApp Wali</span>
                            <span class="num font-semibold text-ink-900">{{ tenant.tenant_profile?.guardian_phone || '-' }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-line-100">
                            <span class="text-ink-500">Email Wali</span>
                            <span class="font-semibold text-ink-900">{{ tenant.tenant_profile?.guardian_email || '-' }}</span>
                        </div>
                    </div>
                </BentoCard>
            </div>

            <!-- Riwayat Kontrak Sewa -->
            <BentoCard title="Riwayat Kontrak Sewa">
                <div v-if="!tenant.leases || tenant.leases.length === 0" class="py-8 text-center text-xs text-ink-500">
                    Belum ada riwayat kontrak sewa.
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="lease in tenant.leases"
                        :key="lease.id"
                        class="p-4 rounded-xl border border-line-200 bg-slate-50/50 flex items-center justify-between text-xs"
                    >
                        <div>
                            <p class="font-bold text-forest-900">
                                Kamar <span class="num">{{ lease.room?.code }}</span> ({{ lease.room?.room_type?.name }})
                            </p>
                            <p class="text-[11px] text-ink-500 mt-0.5">
                                Periode: <span class="num">{{ lease.start_date }}</span> s/d <span class="num">{{ lease.planned_checkout_date || 'Aktif' }}</span>
                            </p>
                        </div>
                        <div class="text-right flex items-center gap-3">
                            <StatusBadge :status="lease.status" />
                            <Link
                                :href="`/admin/leases/${lease.id}`"
                                class="px-3 py-1.5 rounded-lg bg-forest-900 text-white font-semibold text-[11px]"
                            >
                                Detail Kontrak
                            </Link>
                        </div>
                    </div>
                </div>
            </BentoCard>
        </div>
    </AdminLayout>
</template>

