<script setup>
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';

const props = defineProps({
    testimonials: {
        type: Array,
        default: () => [],
    },
});

const togglePublish = (t) => {
    router.patch(`/admin/cms/testimonials/${t.id}/publish`, {}, { preserveScroll: true });
};

const deleteTestimonial = (t) => {
    if (confirm(`Hapus testimoni dari "${t.name}"?`)) {
        router.delete(`/admin/cms/testimonials/${t.id}`);
    }
};
</script>

<template>
    <Head title="Moderasi Testimoni CMS" />

    <AdminLayout title="CMS: Moderasi Testimoni Penghuni">
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-forest-900">Ulasan & Rating Penghuni</h2>
                    <p class="text-xs text-ink-500">Seluruh testimoni baru berstatus pending dan memerlukan persetujuan admin sebelum tayang di landing page.</p>
                </div>
            </div>

            <!-- Testimonials Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <BentoCard v-for="t in testimonials" :key="t.id" :title="t.name">
                    <template #action>
                        <span class="text-amber-500 font-bold text-xs">
                            {{ '★'.repeat(t.rating) }}{{ '☆'.repeat(5 - t.rating) }}
                        </span>
                    </template>

                    <div class="space-y-3 pt-2 text-xs">
                        <span class="text-[11px] text-ink-500 block font-medium">
                            {{ t.origin_info || 'Penyewa Kost Asri' }}
                        </span>

                        <p class="text-ink-700 italic leading-relaxed bg-slate-50 p-3 rounded-xl border border-line-100">
                            "{{ t.content }}"
                        </p>

                        <div class="pt-2 flex items-center justify-between border-t border-line-100">
                            <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase', t.is_published ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800']">
                                {{ t.is_published ? 'Disetujui (Tayang)' : 'Menunggu Moderasi' }}
                            </span>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="togglePublish(t)"
                                    :class="[
                                        'px-2.5 py-1 rounded font-semibold text-[11px] transition',
                                        t.is_published ? 'bg-slate-100 text-ink-700 hover:bg-slate-200' : 'bg-forest-900 text-white hover:bg-forest-800'
                                    ]"
                                >
                                    {{ t.is_published ? 'Sembunyikan' : 'Setujui' }}
                                </button>
                                <button
                                    type="button"
                                    @click="deleteTestimonial(t)"
                                    class="px-2.5 py-1 rounded bg-red-50 text-red-700 hover:bg-red-100 font-semibold text-[11px]"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </BentoCard>

                <div v-if="testimonials.length === 0" class="col-span-full py-12 text-center text-xs text-ink-500">
                    Belum ada kiriman testimoni dari penyewa.
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

