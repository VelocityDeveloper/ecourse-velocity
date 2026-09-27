<script setup lang="ts">
import SiteSettingsNav from '@/components/site-settings/SiteSettingsNav.vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    ExternalLink,
    Eye,
    EyeOff,
    MessageSquareQuote,
    Pencil,
    Plus,
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';
import TestimonialController from '@/actions/App/Http/Controllers/Admin/TestimonialController';
import Heading from '@/components/Heading.vue';
import StarRating from '@/components/StarRating.vue';
import TestimonialFormDialog from '@/components/site-settings/TestimonialFormDialog.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { home } from '@/routes';
import type { AdminTestimonial } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dashboard' },
            { title: 'Pengaturan Situs', href: '/admin/settings/identitas' },
            { title: 'Testimoni', href: '/admin/testimonials' },
        ],
    },
});

defineProps<{
    testimonials: AdminTestimonial[];
}>();

const formOpen = ref(false);
const editing = ref<AdminTestimonial | null>(null);
const deleting = ref<AdminTestimonial | null>(null);

function openCreate(): void {
    editing.value = null;
    formOpen.value = true;
}

function openEdit(item: AdminTestimonial): void {
    editing.value = item;
    formOpen.value = true;
}

function move(item: AdminTestimonial, direction: 'up' | 'down'): void {
    router.post(
        TestimonialController.move(item.id).url,
        { direction },
        { preserveScroll: true },
    );
}

function toggle(item: AdminTestimonial): void {
    router.post(
        TestimonialController.update(item.id).url,
        {
            name: item.name,
            subtitle: item.subtitle ?? '',
            quote: item.quote,
            rating: item.rating,
            is_active: item.is_active ? 0 : 1,
        },
        { preserveScroll: true },
    );
}

function destroy(): void {
    if (!deleting.value) {
        return;
    }

    router.delete(TestimonialController.destroy(deleting.value.id).url, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = null;
        },
    });
}
</script>

<template>
    <Head title="Testimoni · Pengaturan Situs" />

    <div class="flex flex-col space-y-6">
        <SiteSettingsNav />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Testimoni"
                description='Cerita alumni di seksi "Cerita Sukses Alumni" beranda. Selama belum ada testimoni aktif, beranda menampilkan ulasan kursus terbaru.'
            />
            <div class="flex items-center gap-2">
                <a :href="home().url" target="_blank" rel="noopener">
                    <Button variant="outline">
                        <ExternalLink class="mr-2 h-4 w-4" />
                        Lihat di beranda
                    </Button>
                </a>
                <Button @click="openCreate">
                    <Plus class="mr-2 h-4 w-4" />
                    Tambah Testimoni
                </Button>
            </div>
        </div>

        <div
            v-if="testimonials.length === 0"
            class="rounded-lg border border-dashed p-10 text-center"
        >
            <MessageSquareQuote class="mx-auto size-10 text-muted-foreground" />
            <h2 class="mt-3 font-semibold">Belum ada testimoni</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Tambahkan cerita dari alumni. Sampai ada testimoni yang aktif,
                beranda memakai ulasan kursus terbaru.
            </p>
        </div>

        <ul v-else class="space-y-3">
            <li
                v-for="(item, index) in testimonials"
                :key="item.id"
                class="flex flex-col gap-4 rounded-lg border p-4 sm:flex-row sm:items-center"
                :class="item.is_active ? '' : 'bg-muted/40'"
            >
                <Avatar
                    class="size-12 shrink-0 overflow-hidden rounded-full"
                    :class="item.is_active ? '' : 'opacity-60'"
                >
                    <AvatarImage
                        v-if="item.photo_url"
                        :src="item.photo_url"
                        :alt="item.name"
                    />
                    <AvatarFallback
                        class="bg-primary font-semibold text-primary-foreground"
                    >
                        {{ getInitials(item.name) }}
                    </AvatarFallback>
                </Avatar>
                <div class="min-w-0 flex-1 space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-medium">{{ item.display_name }}</span>
                        <Badge
                            :variant="item.is_active ? 'default' : 'outline'"
                        >
                            {{ item.is_active ? 'Active' : 'Hidden' }}
                        </Badge>
                        <StarRating :rating="item.rating" />
                    </div>
                    <p
                        v-if="item.subtitle"
                        class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        {{ item.subtitle }}
                    </p>
                    <p class="line-clamp-2 text-sm text-muted-foreground">
                        “{{ item.quote }}”
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-1">
                    <Button
                        variant="ghost"
                        size="icon"
                        :disabled="index === 0"
                        aria-label="Pindahkan ke atas"
                        @click="move(item, 'up')"
                    >
                        <ArrowUp class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        :disabled="index === testimonials.length - 1"
                        aria-label="Pindahkan ke bawah"
                        @click="move(item, 'down')"
                    >
                        <ArrowDown class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        :aria-label="
                            item.is_active
                                ? 'Sembunyikan dari beranda'
                                : 'Tampilkan di beranda'
                        "
                        @click="toggle(item)"
                    >
                        <EyeOff v-if="item.is_active" class="h-4 w-4" />
                        <Eye v-else class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Ubah testimoni"
                        @click="openEdit(item)"
                    >
                        <Pencil class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Hapus testimoni"
                        @click="deleting = item"
                    >
                        <Trash2 class="h-4 w-4 text-destructive" />
                    </Button>
                </div>
            </li>
        </ul>

        <TestimonialFormDialog v-model:open="formOpen" :testimonial="editing" />

        <Dialog
            :open="deleting !== null"
            @update:open="(value) => !value && (deleting = null)"
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Hapus Testimoni</DialogTitle>
                    <DialogDescription>
                        Hapus testimoni dari
                        <strong>{{ deleting?.name }}</strong
                        >? Tindakan ini tidak bisa dibatalkan.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Batal</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="destroy"
                        >Hapus</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
