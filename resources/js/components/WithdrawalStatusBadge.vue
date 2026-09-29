<script setup lang="ts">
import type { WithdrawalStatus } from '@/types';

defineProps<{
    status: WithdrawalStatus;
}>();

const LABELS: Record<WithdrawalStatus, string> = {
    pending: 'Menunggu transfer',
    paid: 'Sudah ditransfer',
    rejected: 'Ditolak',
    cancelled: 'Dibatalkan',
};

const CLASSES: Record<WithdrawalStatus, string> = {
    pending:
        'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
    paid: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300',
    cancelled: 'bg-muted text-muted-foreground',
};
</script>

<template>
    <span
        class="inline-block rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap"
        :class="CLASSES[status]"
        >{{ LABELS[status] }}</span
    >
</template>
