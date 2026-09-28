<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ClipboardCheck, ReceiptText } from '@lucide/vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import adminOrders from '@/routes/admin/orders';
import enrollments from '@/routes/enrollments';

// Orders and enrollments share one menu entry; these tabs switch between them.
const TABS = [
    { title: 'Pesanan', href: adminOrders.index(), icon: ReceiptText },
    {
        title: 'User Terdaftar',
        href: enrollments.index(),
        icon: ClipboardCheck,
    },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <nav class="flex gap-1 border-b" aria-label="Pesanan dan pendaftaran">
        <Link
            v-for="tab in TABS"
            :key="toUrl(tab.href)"
            :href="tab.href"
            class="-mb-px inline-flex shrink-0 items-center gap-2 border-b-2 px-3 py-2.5 text-sm whitespace-nowrap transition-colors"
            :class="
                isCurrentOrParentUrl(tab.href)
                    ? 'border-primary font-medium text-foreground'
                    : 'border-transparent text-muted-foreground hover:text-foreground'
            "
            :aria-current="isCurrentOrParentUrl(tab.href) ? 'page' : undefined"
        >
            <component :is="tab.icon" class="size-4" aria-hidden="true" />
            {{ tab.title }}
        </Link>
    </nav>
</template>
