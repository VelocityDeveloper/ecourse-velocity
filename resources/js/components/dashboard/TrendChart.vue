<script setup lang="ts">
import { useElementSize } from '@vueuse/core';
import { computed, ref, useTemplateRef } from 'vue';
import { formatRupiah } from '@/lib/course';
import { bucketLabel, compactRupiah } from '@/lib/dashboard';

/**
 * One measure over time: an area for money, columns for counts. A single
 * series in the brand colour, so the title names it and no legend is needed.
 */
const props = defineProps<{
    title: string;
    points: { bucket: string; value: number }[];
    kind: 'area' | 'bar';
    money?: boolean;
    monthly: boolean;
}>();

const HEIGHT = 220;
const TOP = 12;
const BOTTOM = 24;

const box = useTemplateRef<HTMLElement>('box');
const { width } = useElementSize(box);
const hovered = ref<number | null>(null);

const total = computed(() =>
    props.points.reduce((sum, point) => sum + point.value, 0),
);

function format(value: number, compact = false): string {
    if (!props.money) {
        return value.toLocaleString('id-ID');
    }

    return compact ? compactRupiah(value) : formatRupiah(value);
}

// Round the top of the scale to 1, 2 or 5 × 10ⁿ so the gridlines read cleanly.
const ticks = computed(() => {
    const max = Math.max(...props.points.map((point) => point.value), 0);

    if (max === 0) {
        return [0, 1, 2, 3, 4].map((step) =>
            props.money ? step * 25000 : step,
        );
    }

    const rough = max / 4;
    const power = 10 ** Math.floor(Math.log10(rough));
    const step =
        [1, 2, 2.5, 5, 10].map((m) => m * power).find((s) => s >= rough) ??
        10 * power;

    return [0, 1, 2, 3, 4].map((i) => Math.round(i * step));
});

const left = computed(() => (props.money ? 64 : 36));
const plotWidth = computed(() => Math.max(0, width.value - left.value - 8));
const plotHeight = HEIGHT - TOP - BOTTOM;
const top = computed(() => ticks.value[ticks.value.length - 1] || 1);
const band = computed(() => plotWidth.value / Math.max(props.points.length, 1));

const y = (value: number): number =>
    TOP + plotHeight - (value / top.value) * plotHeight;
const x = (index: number): number => left.value + band.value * (index + 0.5);

const barWidth = computed(() => Math.min(28, Math.max(2, band.value * 0.68)));

function barPath(index: number, value: number): string {
    const w = barWidth.value;
    const x0 = x(index) - w / 2;
    const y0 = y(value);
    const h = TOP + plotHeight - y0;

    if (h <= 0) {
        return '';
    }

    const r = Math.min(4, w / 2, h);

    return `M${x0},${TOP + plotHeight}V${y0 + r}Q${x0},${y0} ${x0 + r},${y0}H${x0 + w - r}Q${x0 + w},${y0} ${x0 + w},${y0 + r}V${TOP + plotHeight}Z`;
}

const line = computed(() =>
    props.points
        .map(
            (point, index) =>
                `${index === 0 ? 'M' : 'L'}${x(index)},${y(point.value)}`,
        )
        .join(''),
);
const area = computed(() =>
    props.points.length === 0
        ? ''
        : `${line.value}L${x(props.points.length - 1)},${TOP + plotHeight}L${x(0)},${TOP + plotHeight}Z`,
);

// About six labels along the bottom, always including the last bucket.
const labelIndexes = computed(() => {
    const count = props.points.length;
    const every = Math.max(1, Math.ceil(count / (width.value < 480 ? 4 : 6)));

    return props.points
        .map((_, index) => index)
        .filter((index) => (count - 1 - index) % every === 0);
});

const tooltip = computed(() => {
    if (hovered.value === null) {
        return null;
    }

    const point = props.points[hovered.value];
    const left = x(hovered.value);

    return {
        label: bucketLabel(point.bucket, props.monthly, true),
        value: format(point.value),
        style: {
            left: `${Math.min(Math.max(left, 70), width.value - 70)}px`,
            top: `${Math.max(y(point.value) - 8, 0)}px`,
        },
    };
});
</script>

<template>
    <div class="flex flex-col gap-4 rounded-xl border bg-card p-4 sm:p-5">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <h2 class="font-semibold">{{ title }}</h2>
                <p class="text-2xl font-bold tracking-tight tabular-nums">
                    {{ format(total) }}
                </p>
            </div>
            <slot name="aside" />
        </div>

        <div
            ref="box"
            class="relative"
            :style="{ height: `${HEIGHT}px` }"
            @mouseleave="hovered = null"
        >
            <svg
                v-if="width > 0"
                :width="width"
                :height="HEIGHT"
                class="overflow-visible"
                aria-hidden="true"
            >
                <g>
                    <template v-for="tick in ticks" :key="tick">
                        <line
                            :x1="left"
                            :x2="left + plotWidth"
                            :y1="y(tick)"
                            :y2="y(tick)"
                            class="stroke-border"
                            :stroke-dasharray="tick === 0 ? undefined : '3 4'"
                        />
                        <text
                            :x="left - 8"
                            :y="y(tick)"
                            text-anchor="end"
                            dominant-baseline="middle"
                            class="fill-muted-foreground text-[11px] tabular-nums"
                        >
                            {{ format(tick, true) }}
                        </text>
                    </template>
                </g>

                <g v-if="kind === 'bar'">
                    <path
                        v-for="(point, index) in points"
                        :key="point.bucket"
                        :d="barPath(index, point.value)"
                        class="transition-opacity"
                        :class="
                            hovered === null || hovered === index
                                ? 'fill-primary'
                                : 'fill-primary opacity-40'
                        "
                    />
                </g>
                <g v-else>
                    <path :d="area" class="fill-primary/10" />
                    <path
                        :d="line"
                        class="fill-none stroke-primary"
                        stroke-width="2"
                        stroke-linejoin="round"
                        stroke-linecap="round"
                    />
                    <template v-if="hovered !== null">
                        <line
                            :x1="x(hovered)"
                            :x2="x(hovered)"
                            :y1="TOP"
                            :y2="TOP + plotHeight"
                            class="stroke-muted-foreground/50"
                            stroke-dasharray="3 3"
                        />
                        <circle
                            :cx="x(hovered)"
                            :cy="y(points[hovered].value)"
                            r="5"
                            class="fill-primary stroke-card"
                            stroke-width="2"
                        />
                    </template>
                </g>

                <text
                    v-for="index in labelIndexes"
                    :key="`label-${index}`"
                    :x="x(index)"
                    :y="HEIGHT - 6"
                    text-anchor="middle"
                    class="fill-muted-foreground text-[11px]"
                >
                    {{ bucketLabel(points[index].bucket, monthly) }}
                </text>

                <!-- Hit areas: a full-height column per bucket, wider than the mark. -->
                <rect
                    v-for="(point, index) in points"
                    :key="`hit-${point.bucket}`"
                    :x="left + band * index"
                    :y="TOP"
                    :width="band"
                    :height="plotHeight"
                    fill="transparent"
                    @mouseenter="hovered = index"
                />
            </svg>

            <div
                v-if="tooltip"
                class="pointer-events-none absolute z-10 -translate-x-1/2 -translate-y-full rounded-lg border bg-popover px-3 py-2 text-xs whitespace-nowrap text-popover-foreground shadow-md"
                :style="tooltip.style"
            >
                <p class="text-muted-foreground">{{ tooltip.label }}</p>
                <p class="font-semibold tabular-nums">{{ tooltip.value }}</p>
            </div>
        </div>

        <table class="sr-only">
            <caption>
                {{
                    title
                }}
            </caption>
            <thead>
                <tr>
                    <th scope="col">{{ monthly ? 'Bulan' : 'Tanggal' }}</th>
                    <th scope="col">Nilai</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="point in points" :key="`row-${point.bucket}`">
                    <td>{{ bucketLabel(point.bucket, monthly, true) }}</td>
                    <td>{{ format(point.value) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
