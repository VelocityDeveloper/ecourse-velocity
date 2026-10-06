<script setup lang="ts">
import SiteSettingsNav from '@/components/site-settings/SiteSettingsNav.vue';
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import ContactSettings from '@/components/site-settings/ContactSettings.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            {
                title: 'Pengaturan Situs',
                href: '/dasbor/pengaturan-situs/identitas',
            },
            {
                title: 'Kontak & Sosmed',
                href: '/dasbor/pengaturan-situs/kontak',
            },
        ],
    },
});

defineProps<{
    values: Record<string, string | null>;
    defaultDescription: string;
}>();
</script>

<template>
    <Head title="Kontak & Sosmed · Pengaturan Situs" />

    <div class="flex flex-col space-y-6">
        <SiteSettingsNav />

        <Heading
            variant="small"
            title="Kontak & Sosmed"
            description="Deskripsi, kontak, dan media sosial yang tampil di kolom pertama footer"
        />

        <div>
            <ContactSettings
                :values="values"
                :default-description="defaultDescription"
            />
        </div>
    </div>
</template>
