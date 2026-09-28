<script setup lang="ts">
import { computed, ref } from 'vue';
import { formatRupiah } from '@/lib/course';

// Daily new enrollments or revenue as a bar chart, drawn with plain elements.
const props = defineProps<{
    days: Array<{ date: string; enrollments: number; revenue: number }>;
}>();

type Metric = 'enrollments' | 'revenue';

const METRICS: Array<{ key: Metric; label: string }> = [
    { key: 'enrollments', label: 'Pendaftar' },
    { key: 'revenue', label: 'Pendapatan' },
];

const metric = ref<Metric>('enrollments');
const hovered = ref<number | null>(null);

const values = computed(() => props.days.map((day) => day[metric.value]));
const max = computed(() => Math.max(1, ...values.value));
const total = computed(() => values.value.reduce((sum, v) => sum + v, 0));

function format(value: number): string {
    return metric.value === 'revenue' ? formatRupiah(value) : `${value}`;
}

function dayLabel(date: string, withMonth = true): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString('id-ID', {
        day: 'numeric',
        ...(withMonth ? { month: 'short' } : {}),
    });
}

const shown = computed(() => {
    const index = hovered.value ?? props.days.length - 1;

    return { day: props.days[index], value: values.value[index] };
});
</script>

<template>
    <div
        class="flex flex-col rounded-xl border bg-card p-5 text-card-foreground"
    >
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="font-semibold">Aktivitas 30 hari terakhir</h2>
                <p class="text-sm text-muted-foreground">
                    Total {{ format(total) }}
                    {{ metric === 'enrollments' ? 'pendaftar baru' : '' }}
                </p>
            </div>
            <div class="flex gap-1 rounded-lg bg-muted p-1" role="tablist">
                <button
                    v-for="option in METRICS"
                    :key="option.key"
                    type="button"
                    role="tab"
                    :aria-selected="metric === option.key"
                    class="rounded-md px-3 py-1 text-sm transition-colors"
                    :class="
                        metric === option.key
                            ? 'bg-background font-medium shadow-sm'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="metric = option.key"
                >
                    {{ option.label }}
                </button>
            </div>
        </div>

        <p class="mt-4 text-sm">
            <span class="text-muted-foreground">{{
                shown.day ? dayLabel(shown.day.date) : ''
            }}</span>
            <span class="ml-2 font-semibold">{{
                format(shown.value ?? 0)
            }}</span>
        </p>

        <div
            class="mt-2 flex min-h-40 flex-1 items-end gap-[3px]"
            @mouseleave="hovered = null"
        >
            <div
                v-for="(day, index) in days"
                :key="day.date"
                class="group flex h-full min-w-0 flex-1 items-end"
                @mouseenter="hovered = index"
            >
                <div
                    class="w-full rounded-t-sm transition-colors"
                    :class="
                        hovered === index
                            ? 'bg-primary'
                            : 'bg-primary/35 group-hover:bg-primary/60'
                    "
                    :style="{
                        height: `${Math.max((values[index] / max) * 100, values[index] > 0 ? 4 : 1.5)}%`,
                    }"
                    :title="`${dayLabel(day.date)}: ${format(values[index])}`"
                />
            </div>
        </div>
        <div class="mt-2 flex justify-between text-xs text-muted-foreground">
            <span>{{ days[0] ? dayLabel(days[0].date) : '' }}</span>
            <span>{{
                days[Math.floor(days.length / 2)]
                    ? dayLabel(days[Math.floor(days.length / 2)].date)
                    : ''
            }}</span>
            <span>Hari ini</span>
        </div>
    </div>
</template>
