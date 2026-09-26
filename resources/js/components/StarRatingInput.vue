<script setup lang="ts">
import { Star } from '@lucide/vue';
import { ref } from 'vue';

const rating = defineModel<number>({ required: true });
const hovered = ref<number | null>(null);

const LABELS = ['Poor', 'Fair', 'Good', 'Very good', 'Excellent'];
</script>

<template>
    <div class="flex items-center gap-3">
        <div
            class="flex items-center gap-1"
            role="radiogroup"
            aria-label="Your rating"
            @mouseleave="hovered = null"
        >
            <button
                v-for="value in 5"
                :key="value"
                type="button"
                role="radio"
                :aria-checked="rating === value"
                :aria-label="`${value} star${value > 1 ? 's' : ''} – ${LABELS[value - 1]}`"
                class="rounded-sm p-0.5 transition-transform hover:scale-110 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                @mouseenter="hovered = value"
                @click="rating = value"
            >
                <Star
                    class="h-6 w-6"
                    :class="
                        value <= (hovered ?? rating)
                            ? 'fill-current text-chart-4'
                            : 'text-muted-foreground/40'
                    "
                />
            </button>
        </div>
        <span class="text-sm text-muted-foreground">
            {{ LABELS[(hovered ?? rating) - 1] ?? 'Choose a rating' }}
        </span>
    </div>
</template>
