<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import RupiahText from '@/Components/RupiahText.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    tenant: Object,
});

const showKtpModal = ref(false);
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
                        <div v-if="tenant.tenant_profile?.id" class="pt-2 flex justify-end">
                            <button
                                type="button"
                                @click="showKtpModal = true"
                                class="px-3 py-1.5 rounded-lg border border-forest-800 bg-forest-50 text-forest-900 font-bold hover:bg-forest-100 transition shadow-2xs"
                            >
                                🔒 Lihat Dokumen KTP (Privat)
                            </button>
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

        <!-- Private KTP Photo Modal -->
        <Modal :show="showKtpModal" @close="showKtpModal = false">
            <div class="p-6">
                <div class="flex items-center justify-between mb-3 border-b border-line-200 pb-3">
                    <h3 class="text-base font-bold text-forest-900">Dokumen KTP: {{ tenant.name }}</h3>
                    <span class="text-[11px] font-semibold text-danger-700 bg-red-50 px-2.5 py-1 rounded-md border border-red-200">
                        UU PDP Terlindungi
                    </span>
                </div>

                <div class="space-y-4">
                    <div class="rounded-xl border border-line-200 bg-slate-100 overflow-hidden flex items-center justify-center p-2 min-h-60">
                        <img
                            v-if="tenant.tenant_profile?.id"
                            :src="`/admin/tenants/${tenant.tenant_profile.id}/ktp`"
                            alt="Foto KTP Pribadi"
                            class="max-h-96 w-auto object-contain rounded-lg shadow-xs"
                            @error="(e) => e.target.parentElement.innerHTML = '<span class=\'text-xs text-ink-500 py-10\'>Foto KTP belum diunggah atau tidak tersedia di disk privat.</span>'"
                        />
                    </div>

                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-[11px] text-amber-900 leading-relaxed">
                        Dokumen ini disimpan terenkripsi di disk privat <code>storage/app/private/ktp/</code>. Akses diaudit secara ketat dan dilarang untuk disebarluaskan ke pihak ketiga.
                    </div>

                    <div class="flex justify-end pt-2">
                        <SecondaryButton @click="showKtpModal = false">Tutup Viewer</SecondaryButton>
                    </div>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>

