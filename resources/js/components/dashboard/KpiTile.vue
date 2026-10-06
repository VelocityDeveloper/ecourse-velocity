<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowDownRight, ArrowUpRight, Minus } from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import type { Comparison } from '@/lib/dashboard';
import { change } from '@/lib/dashboard';

const props = defineProps<{
    label: string;
    value: string;
    icon: Component;
    /** Shows the change against the previous period. */
    comparison?: Comparison;
    hint?: string;
    href?: string;
    linkLabel?: string;
}>();

const percent = computed(() =>
    props.comparison ? change(props.comparison) : null,
);
</script>

<template>
    <div
        class="flex min-w-0 flex-col gap-3 rounded-xl border bg-card p-3 sm:p-5"
    >
        <div class="flex items-start justify-between gap-3">
            <p class="text-xs text-muted-foreground sm:text-sm">{{ label }}</p>
            <span
                class="hidden size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary sm:flex"
            >
                <component :is="icon" class="size-4" />
            </span>
        </div>
        <p
            class="truncate text-lg font-bold tracking-tight tabular-nums sm:text-3xl"
        >
            {{ value }}
        </p>
        <div
            class="mt-auto flex flex-wrap items-center gap-x-2 gap-y-1 text-xs"
        >
            <template v-if="comparison">
                <span
                    v-if="percent !== null"
                    class="inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 font-semibold"
                    :class="
                        percent > 0
                            ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                            : percent < 0
                              ? 'bg-destructive/10 text-destructive'
                              : 'bg-muted text-muted-foreground'
                    "
                >
                    <ArrowUpRight v-if="percent > 0" class="size-3" />
                    <ArrowDownRight v-else-if="percent < 0" class="size-3" />
                    <Minus v-else class="size-3" />
                    {{ Math.abs(percent) }}%
                </span>
                <span
                    v-else-if="comparison.current > 0"
                    class="rounded-full bg-muted px-1.5 py-0.5 font-semibold text-muted-foreground"
                    >Baru</span
                >
                <span class="text-muted-foreground">{{
                    hint ??
                    (percent === null
                        ? 'periode sebelumnya kosong'
                        : 'dibanding periode sebelumnya')
                }}</span>
            </template>
            <span v-else-if="hint" class="text-muted-foreground">{{
                hint
            }}</span>
            <Link
                v-if="href"
                :href="href"
                class="ml-auto font-semibold text-primary hover:underline"
                >{{ linkLabel ?? 'Lihat' }} →</Link
            >
        </div>
    </div>
</template>
