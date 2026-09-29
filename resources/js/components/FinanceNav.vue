<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Banknote,
    LayoutDashboard,
    Presentation,
    ReceiptText,
} from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import finance from '@/routes/admin/finance';
import financeInstructors from '@/routes/admin/finance/instructors';
import orders from '@/routes/admin/orders';
import withdrawals from '@/routes/admin/withdrawals';

const page = usePage();
const { isCurrentUrl } = useCurrentUrl();

const isAdmin = computed(() => page.props.auth.user?.role === 'admin');

// Every Keuangan page, shown as tabs instead of a sidebar submenu.
// `prefix` also marks a tab current on its detail pages.
const tabs = computed(() => {
    const withdrawalTab = {
        label: isAdmin.value ? 'Penarikan Dana' : 'Saldo & Penarikan',
        href: withdrawals.index(),
        prefix: withdrawals.index().url,
        Icon: Banknote,
        badge: page.props.pendingWithdrawals,
    };
    const shared = [
        {
            label: 'Pesanan',
            href: orders.index(),
            prefix: orders.index().url,
            Icon: ReceiptText,
            badge: page.props.pendingOrders,
        },
    ];

    if (!isAdmin.value) {
        return [withdrawalTab, ...shared];
    }

    return [
        {
            label: 'Ringkasan',
            href: finance.index(),
            prefix: null,
            Icon: LayoutDashboard,
            badge: 0,
        },
        {
            label: 'Instruktur',
            href: financeInstructors.index(),
            prefix: financeInstructors.index().url,
            Icon: Presentation,
            badge: 0,
        },
        ...shared,
        withdrawalTab,
    ];
});

function isCurrent(tab: (typeof tabs.value)[number]): boolean {
    if (isCurrentUrl(tab.href)) {
        return true;
    }

    const path = page.url.split('?')[0];

    return tab.prefix !== null && path.startsWith(`${tab.prefix}/`);
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <Heading
            variant="small"
            title="Keuangan"
            :description="
                isAdmin
                    ? 'Penjualan, pendapatan instruktur, dan penarikan dana dalam satu tempat'
                    : 'Saldo, pesanan, dan penarikan dana kursus Anda'
            "
        />
        <!-- Phones: a grid of boxes. Wide screens: one underlined row. -->
        <nav
            class="grid gap-1 rounded-lg bg-muted p-1 lg:flex lg:rounded-none lg:border-b lg:bg-transparent lg:p-0"
            :class="isAdmin ? 'grid-cols-2 sm:grid-cols-4' : 'grid-cols-2'"
            aria-label="Keuangan"
        >
            <Link
                v-for="tab in tabs"
                :key="tab.label"
                :href="tab.href"
                class="relative flex min-w-0 flex-col items-center gap-1 rounded-md px-1 py-2 text-xs font-medium whitespace-nowrap transition-colors lg:-mb-px lg:flex-row lg:gap-2 lg:rounded-none lg:border-b-2 lg:px-3 lg:py-2.5 lg:text-sm"
                :class="
                    isCurrent(tab)
                        ? 'bg-background text-foreground shadow-sm lg:border-primary lg:bg-transparent lg:shadow-none'
                        : 'text-muted-foreground hover:text-foreground lg:border-transparent lg:hover:border-border'
                "
                :aria-current="isCurrent(tab) ? 'page' : undefined"
            >
                <component
                    :is="tab.Icon"
                    class="size-4 shrink-0"
                    :class="isCurrent(tab) ? 'text-primary' : ''"
                />
                <span class="truncate">{{ tab.label }}</span>
                <span
                    v-if="tab.badge > 0"
                    class="absolute top-1 right-1 rounded-full bg-primary px-1.5 text-[10px] leading-4 font-semibold text-primary-foreground tabular-nums lg:static"
                    >{{ tab.badge }}</span
                >
            </Link>
        </nav>
    </div>
</template>
