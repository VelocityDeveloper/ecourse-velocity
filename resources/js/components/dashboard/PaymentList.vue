<script setup lang="ts">
import { Receipt } from '@lucide/vue';
import PanelCard from '@/components/dashboard/PanelCard.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import { formatRupiah } from '@/lib/course';
import type { RecentPayment } from '@/lib/dashboard';
import { timeAgo } from '@/lib/dashboard';

defineProps<{
    payments: RecentPayment[];
    title: string;
    description?: string;
    href?: string;
}>();
</script>

<template>
    <PanelCard
        :title="title"
        :description="description"
        :href="href"
        link-label="Transaksi"
    >
        <p
            v-if="payments.length === 0"
            class="flex flex-col items-center gap-2 px-4 py-10 text-center text-sm text-muted-foreground"
        >
            <Receipt class="size-6" />
            Belum ada pembayaran.
        </p>
        <ul v-else class="divide-y">
            <li
                v-for="payment in payments"
                :key="payment.id"
                class="flex items-center gap-3 px-4 py-3 sm:px-5"
            >
                <Avatar class="size-8">
                    <AvatarImage
                        v-if="payment.student?.avatar"
                        :src="payment.student.avatar"
                        :alt="payment.student.name"
                    />
                    <AvatarFallback class="text-xs">{{
                        getInitials(payment.student?.name ?? '?')
                    }}</AvatarFallback>
                </Avatar>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium">
                        {{ payment.student?.name ?? 'Siswa' }}
                    </p>
                    <p class="truncate text-xs text-muted-foreground">
                        {{ payment.course_title }}
                    </p>
                </div>
                <div class="shrink-0 text-right">
                    <p class="text-sm font-semibold tabular-nums">
                        {{ formatRupiah(payment.amount) }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ timeAgo(payment.paid_at) }}
                    </p>
                </div>
            </li>
        </ul>
    </PanelCard>
</template>
