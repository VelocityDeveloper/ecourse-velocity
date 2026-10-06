<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import FooterSettings from '@/components/site-settings/FooterSettings.vue';
import SiteSettingsNav from '@/components/site-settings/SiteSettingsNav.vue';
import type { FooterLayout } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            {
                title: 'Pengaturan Situs',
                href: '/dasbor/pengaturan-situs/identitas',
            },
            { title: 'Footer', href: '/dasbor/pengaturan-situs/footer' },
        ],
    },
});

defineProps<{
    footer: FooterLayout;
    defaults: FooterLayout;
    maxLinks: number;
    contactPhone: string | null;
}>();
</script>

<template>
    <Head title="Footer · Pengaturan Situs" />

    <div class="flex flex-col space-y-6">
        <SiteSettingsNav />

        <Heading
            variant="small"
            title="Footer"
            description="Kolom, tautan, baris bawah, serta tombol WhatsApp dan kembali ke atas yang mengambang"
        />

        <div>
            <FooterSettings
                :footer="footer"
                :defaults="defaults"
                :max-links="maxLinks"
                :contact-phone="contactPhone"
            />
        </div>
    </div>
</template>
