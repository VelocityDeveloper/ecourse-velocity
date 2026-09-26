<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookMarked, Bookmark, LayoutGrid, NotebookPen } from '@lucide/vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import learning from '@/routes/learning';
import myCourses from '@/routes/my-courses';

const { isCurrentUrl } = useCurrentUrl();

const tabs = [
    { label: 'Overview', href: learning.dashboard(), Icon: LayoutGrid },
    { label: 'My Courses', href: myCourses.index(), Icon: BookMarked },
    { label: 'Notes', href: learning.notes(), Icon: NotebookPen },
    { label: 'Bookmarks', href: learning.bookmarks(), Icon: Bookmark },
];
</script>

<template>
    <nav
        class="-mx-4 flex gap-1 overflow-x-auto border-b px-4 sm:mx-0 sm:px-0"
        aria-label="My learning"
    >
        <Link
            v-for="tab in tabs"
            :key="tab.label"
            :href="tab.href"
            class="-mb-px flex shrink-0 items-center gap-2 border-b-2 px-3 py-2.5 text-sm transition-colors"
            :class="
                isCurrentUrl(tab.href)
                    ? 'border-primary font-medium text-foreground'
                    : 'border-transparent text-muted-foreground hover:text-foreground'
            "
            :aria-current="isCurrentUrl(tab.href) ? 'page' : undefined"
        >
            <component :is="tab.Icon" class="h-4 w-4" />
            {{ tab.label }}
        </Link>
    </nav>
</template>
