<script setup lang="ts">
import { Download } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

/**
 * A payment or transfer proof in a popup, so checking it never leaves the page.
 * Images show as they are; a PDF opens in the browser's own viewer inside it.
 */
const open = defineModel<boolean>('open', { required: true });

defineProps<{
    url: string | null;
    title: string;
    description?: string;
    isPdf?: boolean;
}>();
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="flex max-h-[95vh] flex-col gap-4 sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <div
                v-if="url"
                class="min-h-0 flex-1 overflow-auto rounded-lg border bg-muted"
            >
                <iframe
                    v-if="isPdf"
                    :src="url"
                    :title="title"
                    class="h-[70vh] w-full"
                />
                <img
                    v-else
                    :src="url"
                    :alt="title"
                    class="mx-auto max-h-[70vh] w-auto object-contain"
                />
            </div>

            <DialogFooter class="gap-2">
                <a v-if="url" :href="url" download>
                    <Button variant="outline" class="w-full sm:w-auto">
                        <Download class="mr-2 size-4" />
                        Unduh
                    </Button>
                </a>
                <Button @click="open = false">Tutup</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
