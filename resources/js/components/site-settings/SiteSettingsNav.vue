<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Contact,
    CreditCard,
    GalleryHorizontal,
    Image,
    MessageSquareQuote,
    Palette,
    Stamp,
} from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import banners from '@/routes/admin/banners';
import paymentSettings from '@/routes/admin/payment-settings';
import siteSettings from '@/routes/admin/settings';
import testimonials from '@/routes/admin/testimonials';

const { isCurrentUrl } = useCurrentUrl();

// Every Pengaturan Situs page, shown as tabs instead of a sidebar submenu.
// `short` is the label below xl, where the tabs are boxes in a grid.
const tabs = [
    {
        label: 'Identitas & Logo',
        short: 'Identitas',
        href: siteSettings.edit('identitas'),
        Icon: Stamp,
    },
    {
        label: 'Warna',
        short: 'Warna',
        href: siteSettings.edit('warna'),
        Icon: Palette,
    },
    {
        label: 'Hero Beranda',
        short: 'Hero',
        href: siteSettings.edit('hero'),
        Icon: Image,
    },
    {
        label: 'Banner Promo',
        short: 'Banner',
        href: banners.index(),
        Icon: GalleryHorizontal,
    },
    {
        label: 'Testimoni',
        short: 'Testimoni',
        href: testimonials.index(),
        Icon: MessageSquareQuote,
    },
    {
        label: 'Kontak & Footer',
        short: 'Kontak',
        href: siteSettings.edit('kontak'),
        Icon: Contact,
    },
    {
        label: 'Pembayaran',
        short: 'Pembayaran',
        href: paymentSettings.edit(),
        Icon: CreditCard,
    },
];
</script>

<template>
    <div class="flex flex-col gap-4">
        <Heading
            variant="small"
            title="Pengaturan Situs"
            description="Identitas, warna, beranda, footer, dan pembayaran"
        />
        <!-- Phones: a 4 + 3 grid of boxes, tablets one row of seven. Wide screens: one underlined row. -->
        <nav
            class="grid grid-cols-4 gap-1 rounded-lg bg-muted p-1 sm:grid-cols-7 xl:flex xl:rounded-none xl:border-b xl:bg-transparent xl:p-0"
            aria-label="Pengaturan Situs"
        >
            <Link
                v-for="tab in tabs"
                :key="tab.label"
                :href="tab.href"
                class="flex min-w-0 flex-col items-center gap-1 rounded-md px-1 py-2 text-xs font-medium whitespace-nowrap transition-colors xl:-mb-px xl:flex-row xl:gap-2 xl:rounded-none xl:border-b-2 xl:px-3 xl:py-2.5 xl:text-sm"
                :class="
                    isCurrentUrl(tab.href)
                        ? 'bg-background text-foreground shadow-sm xl:border-primary xl:bg-transparent xl:shadow-none'
                        : 'text-muted-foreground hover:text-foreground xl:border-transparent xl:hover:border-border'
                "
                :aria-current="isCurrentUrl(tab.href) ? 'page' : undefined"
            >
                <component
                    :is="tab.Icon"
                    class="size-4 shrink-0"
                    :class="isCurrentUrl(tab.href) ? 'text-primary' : ''"
                />
                <span class="xl:hidden">{{ tab.short }}</span>
                <span class="hidden xl:inline">{{ tab.label }}</span>
            </Link>
        </nav>
    </div>
</template>
