<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRight, Compass, Receipt } from '@lucide/vue';
import LearningHeader from '@/components/LearningHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    formatDateTime,
    formatRupiah,
    orderStatusVariant,
    publicOrderStatusLabel,
} from '@/lib/course';
import catalogRoutes from '@/routes/catalog';
import orderRoutes from '@/routes/orders';
import type { OrderSummary } from '@/types';

defineProps<{
    orders: OrderSummary[];
}>();
</script>

<template>
    <div>
        <Head title="Pesanan Saya" />

        <LearningHeader
            title="Pesanan Saya"
            description="Riwayat pembelian kursus dan status pembayarannya."
        >
            <template #actions>
                <Link :href="catalogRoutes.index()">
                    <Button variant="secondary" class="rounded-xl font-bold">
                        <Compass class="mr-2 h-4 w-4" />
                        Jelajahi katalog
                    </Button>
                </Link>
            </template>
        </LearningHeader>

        <div
            class="mx-auto flex w-full max-w-site flex-col gap-4 px-4 py-10 sm:px-6"
        >
            <div
                v-if="orders.length === 0"
                class="flex flex-col items-center gap-3 rounded-2xl border border-dashed bg-card p-12 text-center"
            >
                <Receipt class="size-10 text-muted-foreground" />
                <p class="text-sm text-muted-foreground">
                    Anda belum pernah membeli kursus berbayar.
                </p>
            </div>

            <Link
                v-for="order in orders"
                :key="order.number"
                :href="orderRoutes.show(order.number)"
                class="group flex flex-wrap items-center gap-4 rounded-2xl border bg-card p-5 transition-all hover:border-primary/30 hover:shadow-lg hover:shadow-primary/10"
            >
                <span
                    class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                >
                    <Receipt class="size-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-bold group-hover:text-primary">
                        {{ order.course_title }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        <span class="font-mono">{{ order.number }}</span> ·
                        {{ formatDateTime(order.created_at) }}
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="font-bold tabular-nums">{{
                        formatRupiah(order.total)
                    }}</span>
                    <Badge :variant="orderStatusVariant(order.status)">
                        {{ publicOrderStatusLabel(order.status) }}
                    </Badge>
                    <ChevronRight class="size-4 text-muted-foreground" />
                </div>
            </Link>
        </div>
    </div>
</template>
