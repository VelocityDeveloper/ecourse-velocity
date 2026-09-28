<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookMarked,
    Bookmark,
    Heart,
    LayoutGrid,
    NotebookPen,
    Receipt,
} from '@lucide/vue';
import { onMounted, useTemplateRef } from 'vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import learning from '@/routes/learning';
import myCourses from '@/routes/my-courses';
import orders from '@/routes/orders';

const { isCurrentUrl } = useCurrentUrl();

const tabs = [
    { label: 'Ringkasan', href: learning.dashboard(), Icon: LayoutGrid },
    { label: 'Kursus Saya', href: myCourses.index(), Icon: BookMarked },
    { label: 'Catatan', href: learning.notes(), Icon: NotebookPen },
    { label: 'Markah', href: learning.bookmarks(), Icon: Bookmark },
    { label: 'Wishlist', href: learning.wishlist(), Icon: Heart },
    { label: 'Pesanan', href: orders.index(), Icon: Receipt },
];

// On phones the tabs scroll sideways; bring the current one into view.
const nav = useTemplateRef<HTMLElement>('nav');

onMounted(() => {
    const current = nav.value?.querySelector<HTMLElement>(
        '[aria-current="page"]',
    );

    if (nav.value && current) {
        nav.value.scrollLeft =
            current.offsetLeft -
            nav.value.clientWidth / 2 +
            current.clientWidth / 2;
    }
});
</script>

<template>
    <!-- Tabs along the bottom of the dark LearningHeader band; the active one opens into the page. -->
    <nav
        ref="nav"
        class="-mx-4 scrollbar-none flex gap-1 overflow-x-auto px-4 sm:mx-0 sm:px-0"
        aria-label="Belajar Saya"
    >
        <Link
            v-for="tab in tabs"
            :key="tab.label"
            :href="tab.href"
            class="flex shrink-0 items-center gap-2 rounded-t-xl px-4 py-3 text-sm font-bold transition-colors"
            :class="
                isCurrentUrl(tab.href)
                    ? 'bg-background text-foreground'
                    : 'text-surface-foreground/70 hover:bg-surface-foreground/10 hover:text-surface-foreground'
            "
            :aria-current="isCurrentUrl(tab.href) ? 'page' : undefined"
        >
            <component
                :is="tab.Icon"
                class="h-4 w-4"
                :class="isCurrentUrl(tab.href) ? 'text-primary' : ''"
            />
            {{ tab.label }}
        </Link>
    </nav>
</template>
