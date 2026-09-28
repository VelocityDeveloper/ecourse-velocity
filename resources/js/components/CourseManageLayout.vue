<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ChartNoAxesColumn,
    GraduationCap,
    LayoutDashboard,
    Star,
} from '@lucide/vue';
import { computed } from 'vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import courses from '@/routes/courses';
import type { NavItem } from '@/types';

// The course's own menu, shared by every page that manages one course.
const props = defineProps<{
    course: { slug: string; title: string };
}>();

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

const items = computed<Array<NavItem & { exact?: boolean }>>(() => [
    {
        title: 'Ringkasan',
        href: courses.show(props.course.slug),
        icon: LayoutDashboard,
        exact: true,
    },
    {
        title: 'Progres Siswa',
        href: courses.progress.index(props.course.slug),
        icon: ChartNoAxesColumn,
    },
    {
        title: 'Buku Nilai',
        href: courses.grades.index(props.course.slug),
        icon: GraduationCap,
    },
    {
        title: 'Ulasan',
        href: courses.reviews.index(props.course.slug),
        icon: Star,
    },
]);

function isActive(item: NavItem & { exact?: boolean }): boolean {
    return item.exact
        ? isCurrentUrl(item.href)
        : isCurrentOrParentUrl(item.href);
}
</script>

<template>
    <div class="flex flex-col gap-6 lg:flex-row lg:gap-8">
        <aside class="lg:w-52 lg:shrink-0">
            <div class="lg:sticky lg:top-4">
                <p class="text-xs text-muted-foreground">Kelola kursus</p>
                <p class="mt-0.5 mb-3 line-clamp-2 font-semibold">
                    {{ course.title }}
                </p>
                <nav
                    class="grid grid-cols-2 gap-1 sm:grid-cols-4 lg:flex lg:flex-col"
                    aria-label="Menu kursus"
                >
                    <Link
                        v-for="item in items"
                        :key="toUrl(item.href)"
                        :href="item.href"
                        class="flex min-w-0 items-center gap-2 rounded-md px-3 py-2 text-sm whitespace-nowrap transition-colors hover:bg-muted"
                        :class="
                            isActive(item)
                                ? 'bg-muted font-medium text-foreground'
                                : 'text-muted-foreground'
                        "
                        :aria-current="isActive(item) ? 'page' : undefined"
                    >
                        <component
                            :is="item.icon"
                            class="size-4"
                            aria-hidden="true"
                        />
                        {{ item.title }}
                    </Link>
                </nav>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <slot />
        </div>
    </div>
</template>
