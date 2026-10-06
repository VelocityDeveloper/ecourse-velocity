<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';

defineProps<{
    title: string;
    description?: string;
    href?: string;
    linkLabel?: string;
}>();
</script>

<template>
    <section class="flex min-w-0 flex-col rounded-xl border bg-card">
        <header
            class="flex items-start justify-between gap-3 border-b px-4 py-3.5 sm:px-5"
        >
            <div class="min-w-0">
                <h2 class="font-semibold">{{ title }}</h2>
                <p v-if="description" class="text-xs text-muted-foreground">
                    {{ description }}
                </p>
            </div>
            <Link
                v-if="href"
                :href="href"
                class="inline-flex shrink-0 items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                {{ linkLabel ?? 'Semua' }}
                <ArrowRight class="size-3.5" />
            </Link>
        </header>
        <div class="flex-1">
            <slot />
        </div>
    </section>
</template>
