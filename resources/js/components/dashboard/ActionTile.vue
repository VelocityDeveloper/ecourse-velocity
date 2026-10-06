<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CircleCheck } from '@lucide/vue';
import type { Component } from 'vue';

defineProps<{
    label: string;
    count: number;
    href: string;
    icon: Component;
    detail?: string;
}>();
</script>

<template>
    <Link
        :href="href"
        class="group flex items-center gap-3 rounded-xl border bg-card p-3 transition hover:border-primary/40 hover:bg-primary/5 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
    >
        <span
            class="flex size-10 shrink-0 items-center justify-center rounded-lg"
            :class="
                count > 0
                    ? 'bg-amber-500/15 text-amber-700 dark:text-amber-400'
                    : 'bg-muted text-muted-foreground'
            "
        >
            <component :is="count > 0 ? icon : CircleCheck" class="size-5" />
        </span>
        <span class="min-w-0">
            <span
                class="block text-xl leading-tight font-bold tabular-nums"
                :class="{ 'text-muted-foreground': count === 0 }"
                >{{ count }}</span
            >
            <span
                class="line-clamp-2 block text-xs leading-snug text-muted-foreground"
                >{{ label }}</span
            >
            <span
                v-if="detail && count > 0"
                class="block truncate text-xs font-medium"
                >{{ detail }}</span
            >
        </span>
    </Link>
</template>
