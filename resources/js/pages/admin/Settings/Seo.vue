<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import SeoSettings from '@/components/site-settings/SeoSettings.vue';
import SiteSettingsNav from '@/components/site-settings/SiteSettingsNav.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            {
                title: 'Pengaturan Situs',
                href: '/dasbor/pengaturan-situs/identitas',
            },
            { title: 'SEO', href: '/dasbor/pengaturan-situs/seo' },
        ],
    },
});

defineProps<{
    values: Record<string, string | null>;
    defaults: { title: string; description: string };
    limits: { title: number; description: number };
    imageUrl: string | null;
    fallbackImageUrl: string | null;
    siteUrl: string;
    sitemapUrl: string;
}>();
</script>

<template>
    <Head title="SEO · Pengaturan Situs" />

    <div class="flex flex-col space-y-6">
        <SiteSettingsNav />

        <Heading
            variant="small"
            title="SEO"
            description="Tampilan halaman depan di hasil pencarian Google dan saat dibagikan, verifikasi Search Console, dan sitemap. SEO tiap kursus dan artikel diatur di formnya masing-masing."
        />

        <div>
            <SeoSettings
                :values="values"
                :defaults="defaults"
                :limits="limits"
                :image-url="imageUrl"
                :fallback-image-url="fallbackImageUrl"
                :site-url="siteUrl"
                :sitemap-url="sitemapUrl"
            />
        </div>
    </div>
</template>
