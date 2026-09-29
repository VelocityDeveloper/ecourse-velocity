<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    NOTIFICATION_ICONS,
    NOTIFICATION_TONES,
    timeAgo,
} from '@/lib/notifications';
import notificationRoutes from '@/routes/notifications';
import type { AppNotification } from '@/types';

const props = defineProps<{
    notification: AppNotification;
}>();

const emit = defineEmits<{
    opened: [];
}>();

// Opening marks it read on the server, which then sends us to its page.
function open(): void {
    emit('opened');
    router.post(notificationRoutes.open(props.notification.id).url);
}
</script>

<template>
    <button
        type="button"
        class="flex w-full items-start gap-3 px-4 py-3 text-left transition-colors hover:bg-muted/60 focus-visible:bg-muted/60 focus-visible:outline-none"
        :class="{ 'bg-primary/5': !notification.read }"
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
                    notification.read ? 'text-foreground/80' : 'font-semibold'
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
</template>
