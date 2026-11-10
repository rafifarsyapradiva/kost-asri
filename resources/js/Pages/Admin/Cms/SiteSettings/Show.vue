<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BentoCard from '@/Components/BentoCard.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
});

const about = props.settings.about_us?.value || {};
const contact = props.settings.contact_info?.value || {};
const seo = props.settings.seo_meta?.value || {};

const form = useForm({
    about_title: about.title || 'Tentang Kost Asri Boarding House',
    about_body: about.body || '',
    address: contact.address || 'Jl. Banjarsari Selatan No. 8, Tembalang, Semarang',
    google_maps_embed: contact.google_maps_embed || '',
    phone: contact.phone || '081234567890',
    email: contact.email || 'info@kostasri.id',
    operating_hours: contact.operating_hours || '08:00 - 21:00 WIB',
    meta_title: seo.meta_title || 'Kost Asri Boarding House Tembalang Semarang',
    meta_description: seo.meta_description || 'Kost eksklusif 32 kamar dekat kampus Undip Tembalang dengan fasilitas lengkap.',
    meta_keywords: seo.meta_keywords || 'kost tembalang, kost undip, kost semarang, kost asri',
});

const submit = () => {
    form.post('/admin/cms/site-settings', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Pengaturan Konten & SEO" />

    <AdminLayout title="CMS: Tentang Kami, Kontak & SEO">
        <div class="space-y-6 max-w-4xl">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-forest-900">Profil Publik & Mesin Pencari</h2>
                    <p class="text-xs text-ink-500">Informasi alamat, nomor WhatsApp resmi, dan meta tag SEO untuk Google Search.</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Section 1: About Us -->
                <BentoCard title="Profil Tentang Kami (About Us)">
                    <div class="space-y-4 text-xs">
                        <div>
                            <InputLabel value="Judul Profil" />
                            <TextInput v-model="form.about_title" required />
                            <InputError :message="form.errors.about_title" />
                        </div>

                        <div>
                            <InputLabel value="Deskripsi Profil Kost (Warm Biophilic)" />
                            <textarea
                                v-model="form.about_body"
                                rows="4"
                                class="w-full rounded-xl border-line-300 text-xs p-3 focus:ring-forest-500 focus:border-forest-500 leading-relaxed"
                                placeholder="Jelaskan kenyamanan lingkungan, keamanan, dan keasrian Kost Asri..."
                            ></textarea>
                            <InputError :message="form.errors.about_body" />
                        </div>
                    </div>
                </BentoCard>

                <!-- Section 2: Contact & Operating Hours -->
                <BentoCard title="Kontak & Jam Operasional">
                    <div class="space-y-4 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Nomor WhatsApp Pengelola (Fonnte & Chatbot)" />
                                <TextInput v-model="form.phone" required />
                                <InputError :message="form.errors.phone" />
                            </div>
                            <div>
                                <InputLabel value="Email Pengelola" />
                                <TextInput type="email" v-model="form.email" required />
                                <InputError :message="form.errors.email" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Alamat Lengkap di Tembalang" />
                                <TextInput v-model="form.address" required />
                                <InputError :message="form.errors.address" />
                            </div>
                            <div>
                                <InputLabel value="Jam Pelayanan / Operasional" />
                                <TextInput v-model="form.operating_hours" required />
                                <InputError :message="form.errors.operating_hours" />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Embed Google Maps (Iframe Src)" />
                            <TextInput v-model="form.google_maps_embed" placeholder="https://www.google.com/maps/embed?pb=..." />
                            <InputError :message="form.errors.google_maps_embed" />
                        </div>
                    </div>
                </BentoCard>

                <!-- Section 3: SEO Meta Tags -->
                <BentoCard title="Optimasi Mesin Pencari (SEO & Open Graph)">
                    <div class="space-y-4 text-xs">
                        <div>
                            <InputLabel value="Meta Title (Maks 70 Karakter)" />
                            <TextInput v-model="form.meta_title" required />
                            <InputError :message="form.errors.meta_title" />
                        </div>

                        <div>
                            <InputLabel value="Meta Description (Maks 160 Karakter)" />
                            <textarea
                                v-model="form.meta_description"
                                rows="2"
                                class="w-full rounded-xl border-line-300 text-xs p-3 focus:ring-forest-500 focus:border-forest-500"
                                required
                            ></textarea>
                            <InputError :message="form.errors.meta_description" />
                        </div>

                        <div>
                            <InputLabel value="Kata Kunci (Keywords)" />
                            <TextInput v-model="form.meta_keywords" placeholder="kost tembalang, sewa kost harian semarang..." />
                            <InputError :message="form.errors.meta_keywords" />
                        </div>
                    </div>
                </BentoCard>

                <div class="flex justify-end pt-2">
                    <PrimaryButton :disabled="form.processing">
                        Simpan Semua Pengaturan CMS
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

