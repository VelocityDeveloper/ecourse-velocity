<script setup lang="ts">
import SiteSettingsNav from '@/components/site-settings/SiteSettingsNav.vue';
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import ColorSettings from '@/components/site-settings/ColorSettings.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            {
                title: 'Pengaturan Situs',
                href: '/dasbor/pengaturan-situs/identitas',
            },
            { title: 'Warna', href: '/dasbor/pengaturan-situs/warna' },
        ],
    },
});

defineProps<{
    primaryColor: string | null;
}>();

// Swatch colours only: the picked value is stored as data, not used as a class.
const PRIMARY_PRESETS = [
    { name: 'Oranye (bawaan)', value: '#cb450b' },
    { name: 'Biru', value: '#2f57ef' },
    { name: 'Hijau', value: '#16a34a' },
    { name: 'Toska', value: '#0d9488' },
    { name: 'Ungu', value: '#7c3aed' },
    { name: 'Merah muda', value: '#db2777' },
    { name: 'Merah', value: '#dc2626' },
    { name: 'Biru tua', value: '#1e3a8a' },
];
</script>

<template>
    <Head title="Warna · Pengaturan Situs" />

    <div class="flex flex-col space-y-6">
        <SiteSettingsNav />

        <Heading
            variant="small"
            title="Warna"
            description="Warna utama yang dipakai di seluruh situs"
        />

        <div class="max-w-2xl space-y-8">
            <ColorSettings
                name="primary_color"
                title="Warna utama"
                description="Dipakai untuk tombol, tautan, progres, badge, judul yang disorot, dan banner ajakan, termasuk mode gelap. Kecerahannya disesuaikan otomatis agar teks di atasnya tetap mudah dibaca."
                :value="primaryColor"
                default-color="#cb450b"
                :presets="PRIMARY_PRESETS"
            />
        </div>
    </div>
</template>
