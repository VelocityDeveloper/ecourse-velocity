<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ArrowUp, Clock, Mail, MapPin, MessageCircle } from '@lucide/vue';
import { computed } from 'vue';
import SocialIcon from '@/components/SocialIcon.vue';
import type { FooterLayout } from '@/types';

/**
 * A small live picture of the site footer for the settings pages. The part
 * being edited is shown at full strength; the rest is dimmed.
 */
const props = defineProps<{
    brand: {
        description: string;
        address: string | null;
        email: string | null;
        phone: string | null;
        hours: string | null;
        socials: string[];
    };
    layout: FooterLayout;
    focus: 'brand' | 'columns';
    /** Whether the floating chat button has a number to call. */
    hasWhatsapp: boolean;
}>();

const page = usePage();
const appName = computed(() => page.props.name);
const categories = computed(() =>
    (page.props.navCategories ?? []).slice(0, props.layout.categories.limit),
);
const copyright = computed(() =>
    props.layout.copyright
        .replaceAll('{tahun}', String(new Date().getFullYear()))
        .replaceAll('{nama}', appName.value),
);
const columns = computed(() =>
    [
        props.layout.categories.show && categories.value.length > 0
            ? {
                  title: props.layout.categories.title,
                  items: [...categories.value.map((category) => category.name)],
                  more: props.layout.categories.see_all_label,
              }
            : null,
        props.layout.explore.show && props.layout.explore.links.length > 0
            ? {
                  title: props.layout.explore.title,
                  items: props.layout.explore.links.map(
                      (link) => link.label || '(tanpa teks)',
                  ),
                  more: null,
              }
            : null,
        props.layout.account.show
            ? {
                  title: props.layout.account.title,
                  items: ['Masuk', 'Daftar Akun Gratis', 'Lupa Kata Sandi'],
                  more: null,
              }
            : null,
    ].filter((column) => column !== null),
);
const statistics = computed(() => {
    const stats = page.props.visitorStats;

    if (!props.layout.statistics.show) {
        return [];
    }

    return [
        ['Kunjungan hari ini', stats?.today_visits ?? 0],
        ['Pengunjung hari ini', stats?.today_visitors ?? 0],
        ['Total kunjungan', stats?.total_visits ?? 0],
        ['Total pengunjung', stats?.total_visitors ?? 0],
        ...(props.layout.statistics.online
            ? [['Pengunjung online', stats?.online ?? 0] as const]
            : []),
    ] as const;
});
const showWhatsapp = computed(
    () => props.layout.whatsapp.show && props.hasWhatsapp,
);
const onLeft = computed(() => props.layout.floating_position === 'left');
</script>

<template>
    <div
        class="relative overflow-hidden rounded-lg bg-surface pb-14 text-[11px] leading-snug text-surface-foreground"
    >
        <div class="grid gap-4 p-4">
            <div
                class="space-y-2 transition-opacity"
                :class="{ 'opacity-40': focus !== 'brand' }"
            >
                <p class="text-xs font-bold">{{ appName }}</p>
                <p class="text-surface-foreground/65">
                    {{ brand.description }}
                </p>
                <ul class="space-y-1 text-surface-foreground/65">
                    <li v-if="brand.address" class="flex gap-1.5">
                        <MapPin class="mt-px size-3 shrink-0 text-brand" />
                        {{ brand.address }}
                    </li>
                    <li v-if="brand.email" class="flex gap-1.5 break-all">
                        <Mail class="mt-px size-3 shrink-0 text-brand" />
                        {{ brand.email }}
                    </li>
                    <li v-if="brand.phone" class="flex gap-1.5">
                        <MessageCircle
                            class="mt-px size-3 shrink-0 text-brand"
                        />
                        {{ brand.phone }}
                    </li>
                    <li v-if="brand.hours" class="flex gap-1.5">
                        <Clock class="mt-px size-3 shrink-0 text-brand" />
                        {{ brand.hours }}
                    </li>
                </ul>
                <div v-if="brand.socials.length > 0" class="flex gap-1">
                    <span
                        v-for="network in brand.socials"
                        :key="network"
                        class="flex size-6 items-center justify-center rounded bg-surface-muted"
                    >
                        <SocialIcon :network="network" class="size-3" />
                    </span>
                </div>
            </div>

            <div
                class="grid grid-cols-3 gap-3 transition-opacity"
                :class="{ 'opacity-40': focus !== 'columns' }"
            >
                <div
                    v-for="column in columns"
                    :key="column.title"
                    class="min-w-0 space-y-1"
                >
                    <p
                        class="truncate text-[10px] font-bold tracking-wider uppercase"
                    >
                        {{ column.title }}
                    </p>
                    <p
                        v-for="(item, index) in column.items"
                        :key="index"
                        class="truncate text-surface-foreground/65"
                    >
                        {{ item }}
                    </p>
                    <p
                        v-if="column.more"
                        class="truncate font-semibold text-brand"
                    >
                        {{ column.more }} →
                    </p>
                </div>
                <p
                    v-if="columns.length === 0 && statistics.length === 0"
                    class="col-span-3 text-surface-foreground/50 italic"
                >
                    Semua kolom tautan disembunyikan.
                </p>
                <div v-if="statistics.length > 0" class="col-span-3 space-y-1">
                    <p
                        class="truncate text-[10px] font-bold tracking-wider uppercase"
                    >
                        {{ layout.statistics.title }}
                    </p>
                    <dl class="grid grid-cols-2 gap-x-3">
                        <div
                            v-for="[label, value] in statistics"
                            :key="label"
                            class="flex justify-between gap-2 border-b border-surface-muted py-0.5"
                        >
                            <dt class="truncate text-surface-foreground/65">
                                {{ label }}
                            </dt>
                            <dd class="font-bold text-brand tabular-nums">
                                {{ value.toLocaleString('id-ID') }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <div
            class="flex flex-wrap justify-between gap-x-3 gap-y-0.5 border-t border-surface-muted px-4 py-2 text-[10px] text-surface-foreground/55"
            :class="{ 'opacity-40': focus !== 'columns' }"
        >
            <p>{{ copyright }}</p>
            <p>Design by Velocity Developer</p>
        </div>

        <div
            class="absolute bottom-2 flex items-center gap-1.5"
            :class="[
                onLeft ? 'left-3 flex-row-reverse' : 'right-3 flex-row',
                { 'opacity-40': focus !== 'columns' },
            ]"
        >
            <span
                v-if="showWhatsapp"
                class="flex size-8 items-center justify-center rounded-full bg-[#25D366] text-[9px] font-bold text-white shadow"
                >WA</span
            >
            <span
                v-if="layout.back_to_top.show"
                class="flex size-6 items-center justify-center rounded-full bg-background text-foreground shadow"
            >
                <ArrowUp class="size-3" />
            </span>
        </div>
    </div>
</template>
