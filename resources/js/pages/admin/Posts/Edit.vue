<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import PostForm from '@/components/PostForm.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import postRoutes from '@/routes/admin/posts';
import type { PostFormValues } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Blog', href: '/dasbor/blog' },
            { title: 'Ubah Artikel', href: '#' },
        ],
    },
});

const props = defineProps<{
    post: PostFormValues;
}>();

function deletePost(): void {
    router.delete(postRoutes.destroy(props.post.slug).url);
}
</script>

<template>
    <Head title="Ubah Artikel" />

    <div class="flex flex-col space-y-6">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Ubah Artikel"
                :description="post.title"
            />

            <Dialog>
                <DialogTrigger as-child>
                    <Button variant="outline" size="sm" class="shrink-0">
                        <Trash2 class="mr-2 h-4 w-4 text-destructive" />
                        Hapus
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus Artikel</DialogTitle>
                        <DialogDescription>
                            Yakin ingin menghapus
                            <strong>{{ post.title }}</strong
                            >? Tindakan ini tidak bisa dibatalkan.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary">Batal</Button>
                        </DialogClose>
                        <Button variant="destructive" @click="deletePost"
                            >Hapus</Button
                        >
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>

        <PostForm :post="post" />
    </div>
</template>
