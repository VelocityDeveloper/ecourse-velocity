<script setup lang="ts">
import SiteSettingsNav from '@/components/site-settings/SiteSettingsNav.vue';
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import LogoSettings from '@/components/site-settings/LogoSettings.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            {
                title: 'Pengaturan Situs',
                href: '/dasbor/pengaturan-situs/identitas',
            },
            {
                title: 'Identitas & Logo',
                href: '/dasbor/pengaturan-situs/identitas',
            },
        ],
    },
});

defineProps<{
    logoUrl: string | null;
}>();
</script>

<template>
    <Head title="Identitas & Logo · Pengaturan Situs" />

    <div class="flex flex-col space-y-6">
        <SiteSettingsNav />

        <Heading
            variant="small"
            title="Identitas & Logo"
            description="Logo yang tampil di header, footer, dasbor, dan halaman masuk"
        />

        <div class="max-w-2xl space-y-8">
            <LogoSettings :logo-url="logoUrl" />
        </div>
    </div>
</template>
