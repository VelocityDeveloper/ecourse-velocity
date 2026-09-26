<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import AppWordmark from '@/components/AppWordmark.vue';
import { cn } from '@/lib/utils';

// The site logo. When an admin has uploaded one (Admin → Pengaturan Situs) the
// image replaces the whole lockup; otherwise the built-in mark is drawn.
const props = withDefaults(
    defineProps<{
        size?: 'sm' | 'md' | 'lg';
        tile?: 'primary' | 'sidebar' | 'none';
        wordmark?: boolean;
        wordmarkClass?: string;
        class?: string;
    }>(),
    { size: 'md', tile: 'primary', wordmark: false },
);

const page = usePage();
const logoUrl = computed(() => page.props.branding?.logoUrl ?? null);

const IMAGE = {
    sm: 'h-6 max-w-36',
    md: 'h-8 max-w-44',
    lg: 'h-10 max-w-56',
};
const TILE = { sm: 'size-6', md: 'size-8', lg: 'size-9' };
const ICON = { sm: 'size-4', md: 'size-5', lg: 'size-9' };
const TILE_COLOR = {
    primary: 'rounded-md bg-primary text-primary-foreground',
    sidebar: 'rounded-md bg-sidebar-primary text-sidebar-primary-foreground',
    none: 'text-primary',
};
</script>

<template>
    <span :class="cn('flex min-w-0 items-center gap-2', props.class)">
        <img
            v-if="logoUrl"
            :src="logoUrl"
            :alt="page.props.name"
            :class="cn('w-auto object-contain object-left', IMAGE[size])"
        />
        <template v-else>
            <span
                :class="
                    cn(
                        'flex shrink-0 items-center justify-center',
                        TILE[size],
                        TILE_COLOR[tile],
                    )
                "
            >
                <AppLogoIcon :class="ICON[size]" />
            </span>
            <AppWordmark v-if="wordmark" :class="wordmarkClass" />
        </template>
    </span>
</template>
