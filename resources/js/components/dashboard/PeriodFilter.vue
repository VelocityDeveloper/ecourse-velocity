<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CalendarRange } from '@lucide/vue';
import { dashboard } from '@/routes';
import type { DashboardPeriod } from '@/lib/dashboard';

defineProps<{ period: DashboardPeriod }>();

function choose(key: string): void {
    router.get(
        dashboard().url,
        { periode: key === '30h' ? undefined : key },
        { preserveScroll: true, preserveState: true },
    );
}
</script>

<template>
    <div class="flex items-center gap-2">
        <CalendarRange class="hidden size-4 text-muted-foreground sm:block" />
        <div
            class="inline-flex rounded-lg bg-muted p-1"
            role="radiogroup"
            aria-label="Periode"
        >
            <button
                v-for="option in period.options"
                :key="option.key"
                type="button"
                role="radio"
                :aria-checked="period.key === option.key"
                class="rounded-md px-2.5 py-1 text-xs font-medium whitespace-nowrap transition sm:px-3 sm:text-sm"
                :class="
                    period.key === option.key
                        ? 'bg-background shadow-sm'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="choose(option.key)"
            >
                {{ option.label }}
            </button>
        </div>
    </div>
</template>
