<script setup lang="ts">
import { Star } from '@lucide/vue';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        rating: number | null;
        size?: 'sm' | 'md';
    }>(),
    { size: 'sm' },
);

const rounded = computed(() => Math.round(props.rating ?? 0));
const iconClass = computed(() =>
    props.size === 'md' ? 'h-5 w-5' : 'h-3.5 w-3.5',
);
</script>

<template>
    <span
        class="inline-flex items-center gap-0.5"
        role="img"
        :aria-label="
            rating === null ? 'Not rated yet' : `Rated ${rating} out of 5`
        "
    >
        <Star
            v-for="index in 5"
            :key="index"
            :class="[
                iconClass,
                index <= rounded
                    ? 'fill-current text-chart-4'
                    : 'text-muted-foreground/40',
            ]"
        />
    </span>
</template>
