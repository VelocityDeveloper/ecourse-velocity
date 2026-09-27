<script setup lang="ts">
import CardCarousel from '@/components/CardCarousel.vue';
import TestimonialCard from '@/components/TestimonialCard.vue';
import type { HomeTestimonial } from '@/types';

defineProps<{
    items: HomeTestimonial[];
}>();
</script>

<template>
    <CardCarousel
        :items="items"
        :per-view-large="3"
        label="Testimoni alumni"
        item-name="Testimoni"
    >
        <template #default="{ item }">
            <TestimonialCard :item="item" class="w-full" />
        </template>
    </CardCarousel>
</template>
