<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CheckCheck } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import NotificationItem from '@/components/NotificationItem.vue';
import { Button } from '@/components/ui/button';
import notificationRoutes from '@/routes/notifications';
import type { AppNotification, Paginated } from '@/types';
import { usePage } from '@inertiajs/vue3';

const props = defineProps<{
    items: Paginated<AppNotification>;
    filter: 'all' | 'unread';
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Notifikasi', href: '/notifikasi' },
        ],
    },
});

const page = usePage();
const unread = computed(() => page.props.notifications?.unread ?? 0);

function show(filter: 'all' | 'unread', number?: number): void {
    router.get(
        notificationRoutes.index().url,
        {
            filter: filter === 'unread' ? 'unread' : undefined,
            page: number,
        },
        { preserveState: true },
    );
}

function readAll(): void {
    router.post(notificationRoutes.readAll().url, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Notifikasi" />

    <div class="mx-auto flex w-full max-w-3xl flex-col space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Notifikasi"
                description="Kabar tentang pembayaran, penjualan, pengajuan, dan penarikan dana."
            />
            <Button
                v-if="unread > 0"
                variant="outline"
                size="sm"
                @click="readAll"
            >
                <CheckCheck class="mr-2 size-4" />
                Tandai semua dibaca
            </Button>
        </div>

        <div class="flex gap-2">
            <Button
                size="sm"
                :variant="props.filter === 'all' ? 'default' : 'outline'"
                @click="show('all')"
            >
                Semua
            </Button>
            <Button
                size="sm"
                :variant="props.filter === 'unread' ? 'default' : 'outline'"
                @click="show('unread')"
            >
                Belum dibaca
                <span
                    v-if="unread > 0"
                    class="ml-1.5 rounded-full bg-background/20 px-1.5 text-xs tabular-nums"
                    >{{ unread }}</span
                >
            </Button>
        </div>

        <div class="overflow-hidden rounded-lg border">
            <p
                v-if="items.data.length === 0"
                class="px-4 py-12 text-center text-sm text-muted-foreground"
            >
                {{
                    filter === 'unread'
                        ? 'Semua notifikasi sudah dibaca.'
                        : 'Belum ada notifikasi.'
                }}
            </p>
            <div v-else class="divide-y">
                <NotificationItem
                    v-for="item in items.data"
                    :key="item.id"
                    :notification="item"
                />
            </div>
        </div>

        <div
            v-if="items.last_page > 1"
            class="flex items-center justify-end gap-2"
        >
            <Button
                variant="outline"
                size="sm"
                :disabled="items.current_page <= 1"
                @click="show(filter, items.current_page - 1)"
            >
                Sebelumnya
            </Button>
            <span class="text-sm text-muted-foreground">
                Halaman {{ items.current_page }} dari
                {{ items.last_page }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="items.current_page >= items.last_page"
                @click="show(filter, items.current_page + 1)"
            >
                Berikutnya
            </Button>
        </div>
    </div>
</template>
