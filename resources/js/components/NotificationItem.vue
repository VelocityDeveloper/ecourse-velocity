<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import {
    NOTIFICATION_ICONS,
    NOTIFICATION_TONES,
    timeAgo,
} from '@/lib/notifications';
import notificationRoutes from '@/routes/notifications';
import type { AppNotification } from '@/types';

const props = defineProps<{
    notification: AppNotification;
    /** Offer a delete button once the notification has been read. */
    deletable?: boolean;
}>();

const emit = defineEmits<{
    opened: [];
}>();

// Opening marks it read on the server, which then sends us to its page.
function open(): void {
    emit('opened');
    router.post(notificationRoutes.open(props.notification.id).url);
}

const deleting = ref(false);

function remove(): void {
    deleting.value = true;
    router.delete(notificationRoutes.destroy(props.notification.id).url, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
        },
    });
}
</script>

<template>
    <div class="group relative">
        <button
            type="button"
            class="flex w-full items-start gap-3 px-4 py-3 text-left transition-colors hover:bg-muted/60 focus-visible:bg-muted/60 focus-visible:outline-none"
            :class="{
                'bg-primary/5': !notification.read,
                'pr-12': deletable && notification.read,
            }"
            @click="open"
        >
            <span
                class="flex size-9 shrink-0 items-center justify-center rounded-full"
                :class="NOTIFICATION_TONES[notification.tone]"
            >
                <component
                    :is="
                        NOTIFICATION_ICONS[notification.kind] ??
                        NOTIFICATION_ICONS.info
                    "
                    class="size-4"
                />
            </span>
            <span class="min-w-0 flex-1">
                <span
                    class="block text-sm"
                    :class="
                        notification.read
                            ? 'text-foreground/80'
                            : 'font-semibold'
                    "
                    >{{ notification.title }}</span
                >
                <span
                    v-if="notification.body"
                    class="line-clamp-2 block text-xs text-muted-foreground"
                    >{{ notification.body }}</span
                >
                <span class="mt-0.5 block text-[11px] text-muted-foreground">{{
                    timeAgo(notification.created_at)
                }}</span>
            </span>
            <span
                v-if="!notification.read"
                class="mt-1.5 size-2 shrink-0 rounded-full bg-primary"
                aria-label="Belum dibaca"
            />
        </button>
        <button
            v-if="deletable && notification.read"
            type="button"
            class="absolute top-2.5 right-2.5 flex size-8 items-center justify-center rounded-md text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:opacity-50"
            aria-label="Hapus notifikasi"
            title="Hapus notifikasi"
            :disabled="deleting"
            @click="remove"
        >
            <Trash2 class="size-4" />
        </button>
    </div>
</template>
