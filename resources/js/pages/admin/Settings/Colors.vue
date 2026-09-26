<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import ColorSettings from '@/components/site-settings/ColorSettings.vue';
import { Separator } from '@/components/ui/separator';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dashboard' },
            { title: 'Pengaturan Situs', href: '/admin/settings/identitas' },
            { title: 'Warna', href: '/admin/settings/warna' },
        ],
    },
});

defineProps<{
    primaryColor: string | null;
    surfaceColor: string | null;
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

const SURFACE_PRESETS = [
    { name: 'Navy (bawaan)', value: '#0b1223' },
    { name: 'Hitam', value: '#111111' },
    { name: 'Biru gelap', value: '#0f1e4a' },
    { name: 'Hijau gelap', value: '#0b2a1f' },
    { name: 'Ungu gelap', value: '#1e1036' },
    { name: 'Cokelat gelap', value: '#2a160b' },
];
</script>

<template>
    <Head title="Warna · Pengaturan Situs" />

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Warna"
            description="Warna utama dan warna latar gelap yang dipakai di seluruh situs"
        />

        <div class="max-w-2xl space-y-8">
            <ColorSettings
                name="primary_color"
                title="Warna utama"
                description="Dipakai untuk tombol, tautan, progres, badge, judul yang disorot, dan banner ajakan, termasuk mode gelap. Kecerahannya disesuaikan otomatis agar teks di atasnya tetap mudah dibaca."
                :value="primaryColor"
                default-color="#cb450b"
                :presets="PRIMARY_PRESETS"
                preview="button"
            />
            <Separator />
            <ColorSettings
                name="surface_color"
                title="Warna latar gelap"
                description="Latar header, hero beranda, kartu jalur belajar, dan footer. Teks di atasnya selalu putih, jadi warna yang terlalu terang akan digelapkan otomatis."
                :value="surfaceColor"
                default-color="#0b1223"
                :presets="SURFACE_PRESETS"
                preview="surface"
            />
        </div>
    </div>
</template>
