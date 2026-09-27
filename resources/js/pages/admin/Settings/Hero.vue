<script setup lang="ts">
import SiteSettingsNav from '@/components/site-settings/SiteSettingsNav.vue';
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import banners from '@/routes/admin/banners';
import BannerSettings from '@/components/site-settings/BannerSettings.vue';
import type { BannerSettingsData } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dashboard' },
            { title: 'Pengaturan Situs', href: '/admin/settings/identitas' },
            { title: 'Hero Beranda', href: '/admin/settings/hero' },
        ],
    },
});

defineProps<{
    banner: BannerSettingsData;
}>();
</script>

<template>
    <Head title="Hero Beranda · Pengaturan Situs" />

    <div class="flex flex-col space-y-6">
        <SiteSettingsNav />

        <Heading
            variant="small"
            title="Hero Beranda"
            description="Teks dan gambar di bagian paling atas beranda, serta banner ajakan di bawahnya"
        />

        <div class="max-w-2xl space-y-8">
            <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-primary/30 bg-primary/5 p-4 text-sm"
            >
                <p>
                    Mencari <strong>slider gambar promo</strong> di bawah hero?
                    Kelola di halaman Banner Promo.
                </p>
                <Link
                    :href="banners.index()"
                    class="font-semibold text-primary underline-offset-4 hover:underline"
                    >Buka Banner Promo →</Link
                >
            </div>
            <BannerSettings v-bind="banner" />
        </div>
    </div>
</template>
