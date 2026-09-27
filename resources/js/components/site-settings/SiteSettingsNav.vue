<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Contact,
    GalleryHorizontal,
    Image,
    MessageSquareQuote,
    Palette,
    Stamp,
} from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import banners from '@/routes/admin/banners';
import siteSettings from '@/routes/admin/settings';
import testimonials from '@/routes/admin/testimonials';

const { isCurrentUrl } = useCurrentUrl();

// Every Pengaturan Situs page, shown as tabs instead of a sidebar submenu.
const tabs = [
    {
        label: 'Identitas & Logo',
        href: siteSettings.edit('identitas'),
        Icon: Stamp,
    },
    { label: 'Warna', href: siteSettings.edit('warna'), Icon: Palette },
    { label: 'Hero Beranda', href: siteSettings.edit('hero'), Icon: Image },
    { label: 'Banner Promo', href: banners.index(), Icon: GalleryHorizontal },
    {
        label: 'Testimoni',
        href: testimonials.index(),
        Icon: MessageSquareQuote,
    },
    {
        label: 'Kontak & Footer',
        href: siteSettings.edit('kontak'),
        Icon: Contact,
    },
];
</script>

<template>
    <div class="flex flex-col gap-4">
        <Heading
            variant="small"
            title="Pengaturan Situs"
            description="Identitas, warna, beranda, dan footer situs publik"
        />
        <nav
            class="-mx-1 flex gap-1 overflow-x-auto border-b px-1"
            aria-label="Pengaturan Situs"
        >
            <Link
                v-for="tab in tabs"
                :key="tab.label"
                :href="tab.href"
                class="-mb-px flex shrink-0 items-center gap-2 border-b-2 px-3 py-2.5 text-sm font-medium whitespace-nowrap transition-colors"
                :class="
                    isCurrentUrl(tab.href)
                        ? 'border-primary text-foreground'
                        : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground'
                "
                :aria-current="isCurrentUrl(tab.href) ? 'page' : undefined"
            >
                <component
                    :is="tab.Icon"
                    class="size-4"
                    :class="isCurrentUrl(tab.href) ? 'text-primary' : ''"
                />
                {{ tab.label }}
            </Link>
        </nav>
    </div>
</template>
