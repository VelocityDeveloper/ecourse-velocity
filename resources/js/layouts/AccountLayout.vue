<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { BreadcrumbItem } from '@/types';

/**
 * Wraps account pages (settings) in the dashboard for staff and in the public
 * site for everyone else, who has no access to the dashboard.
 */
defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

const page = usePage();
const isStaff = computed(() =>
    ['admin', 'instructor'].includes(page.props.auth.user?.role ?? ''),
);
</script>

<template>
    <AppLayout v-if="isStaff" :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>
    <PublicLayout v-else>
        <div class="mx-auto w-full max-w-6xl px-0 py-4 sm:px-2">
            <slot />
        </div>
    </PublicLayout>
</template>
