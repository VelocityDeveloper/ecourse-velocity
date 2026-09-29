<script setup lang="ts">
import { Link, router, usePage, usePoll } from '@inertiajs/vue3';
import { Bell, CheckCheck } from '@lucide/vue';
import { computed, ref } from 'vue';
import NotificationItem from '@/components/NotificationItem.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import notificationRoutes from '@/routes/notifications';

defineProps<{
    /** Extra classes for the bell button, to match the header it sits in. */
    buttonClass?: string;
}>();

const page = usePage();
const open = ref(false);

const notifications = computed(() => page.props.notifications);
const unread = computed(() => notifications.value?.unread ?? 0);

// New notifications show up without a page load.
usePoll(60_000, { only: ['notifications'] });

function readAll(): void {
    router.post(
        notificationRoutes.readAll().url,
        {},
        { preserveScroll: true, preserveState: true, only: ['notifications'] },
    );
}
</script>

<template>
    <DropdownMenu v-if="notifications" v-model:open="open">
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="relative size-9 shrink-0"
                :class="buttonClass"
                :aria-label="
                    unread > 0
                        ? `Notifikasi, ${unread} belum dibaca`
                        : 'Notifikasi'
                "
                title="Notifikasi"
            >
                <Bell class="size-5" />
                <span
                    v-if="unread > 0"
                    class="absolute -top-0.5 -right-0.5 min-w-4.5 rounded-full bg-primary px-1 text-center text-[10px] leading-4.5 font-semibold text-primary-foreground tabular-nums"
                    >{{ unread > 99 ? '99+' : unread }}</span
                >
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent
            align="end"
            class="w-[calc(100vw-2rem)] max-w-sm p-0"
        >
            <div
                class="flex items-center justify-between gap-2 border-b px-4 py-3"
            >
                <p class="font-semibold">Notifikasi</p>
                <button
                    v-if="unread > 0"
                    type="button"
                    class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                    @click="readAll"
                >
                    <CheckCheck class="size-3.5" />
                    Tandai semua dibaca
                </button>
            </div>
            <div class="max-h-[60vh] overflow-y-auto">
                <p
                    v-if="notifications.recent.length === 0"
                    class="px-4 py-10 text-center text-sm text-muted-foreground"
                >
                    Belum ada notifikasi.
                </p>
                <div v-else class="divide-y">
                    <NotificationItem
                        v-for="item in notifications.recent"
                        :key="item.id"
                        :notification="item"
                        @opened="open = false"
                    />
                </div>
            </div>
            <Link
                :href="notificationRoutes.index()"
                class="block border-t px-4 py-2.5 text-center text-sm font-medium text-primary hover:bg-muted/60"
                @click="open = false"
            >
                Lihat semua notifikasi
            </Link>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
