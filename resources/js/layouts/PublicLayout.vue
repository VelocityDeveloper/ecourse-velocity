<script setup lang="ts">
import PublicFooter from '@/components/PublicFooter.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import type { BreadcrumbItem } from '@/types';

/**
 * Breadcrumbs are accepted so pages shared with the dashboard layout can pass
 * them, but the public site does not display them.
 */
defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <PublicHeader />
        <main class="flex-1">
            <slot />
        </main>
        <PublicFooter />
    </div>
</template>
