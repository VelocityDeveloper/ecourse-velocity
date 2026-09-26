<script setup lang="ts">
import { ListTree } from '@lucide/vue';
import LearnOutline from '@/components/LearnOutline.vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import type { LearningOutline } from '@/types';

defineProps<{
    outline: LearningOutline;
    activeType?: 'lesson' | 'quiz';
    activeId?: number;
}>();
</script>

<template>
    <div
        class="mx-auto grid w-full max-w-site gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[300px_minmax(0,1fr)]"
    >
        <aside
            class="hidden lg:sticky lg:top-24 lg:block lg:max-h-[calc(100vh-7rem)] lg:self-start lg:overflow-y-auto lg:pr-2"
        >
            <LearnOutline
                :outline="outline"
                :active-type="activeType"
                :active-id="activeId"
            />
        </aside>

        <div class="flex min-w-0 flex-col gap-6">
            <Sheet>
                <SheetTrigger as-child>
                    <Button variant="outline" size="sm" class="w-fit lg:hidden">
                        <ListTree class="mr-2 h-4 w-4" />
                        Isi kursus ·
                        {{ outline.progress.percent }}%
                    </Button>
                </SheetTrigger>
                <SheetContent side="left" class="w-80 overflow-y-auto p-4">
                    <SheetHeader class="sr-only">
                        <SheetTitle>Isi kursus</SheetTitle>
                        <SheetDescription>
                            Materi dan kuis dalam kursus ini
                        </SheetDescription>
                    </SheetHeader>
                    <LearnOutline
                        :outline="outline"
                        :active-type="activeType"
                        :active-id="activeId"
                    />
                </SheetContent>
            </Sheet>

            <slot />
        </div>
    </div>
</template>
