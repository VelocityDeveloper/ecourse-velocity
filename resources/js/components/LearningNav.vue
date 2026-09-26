<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookMarked, Bookmark, LayoutGrid, NotebookPen } from '@lucide/vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import learning from '@/routes/learning';
import myCourses from '@/routes/my-courses';

const { isCurrentUrl } = useCurrentUrl();

const tabs = [
    { label: 'Ringkasan', href: learning.dashboard(), Icon: LayoutGrid },
    { label: 'Kursus Saya', href: myCourses.index(), Icon: BookMarked },
    { label: 'Catatan', href: learning.notes(), Icon: NotebookPen },
    { label: 'Markah', href: learning.bookmarks(), Icon: Bookmark },
];
</script>

<template>
    <!-- Tabs along the bottom of the dark LearningHeader band; the active one opens into the page. -->
    <nav
        class="-mx-4 flex gap-1 overflow-x-auto px-4 sm:mx-0 sm:px-0"
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
