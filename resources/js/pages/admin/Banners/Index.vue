<script setup lang="ts">
import SiteSettingsNav from '@/components/site-settings/SiteSettingsNav.vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    ExternalLink,
    Eye,
    EyeOff,
    ImagePlus,
    Pencil,
    Plus,
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';
import BannerController from '@/actions/App/Http/Controllers/Admin/BannerController';
import Heading from '@/components/Heading.vue';
import BannerFormDialog from '@/components/site-settings/BannerFormDialog.vue';
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
import type { AdminBanner } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dashboard' },
            { title: 'Pengaturan Situs', href: '/admin/settings/identitas' },
            { title: 'Banner Promo', href: '/admin/banners' },
        ],
    },
});

defineProps<{
    banners: AdminBanner[];
}>();

const formOpen = ref(false);
const editing = ref<AdminBanner | null>(null);
const deleting = ref<AdminBanner | null>(null);

function openCreate(): void {
    editing.value = null;
    formOpen.value = true;
}

function openEdit(banner: AdminBanner): void {
    editing.value = banner;
    formOpen.value = true;
}

function move(banner: AdminBanner, direction: 'up' | 'down'): void {
    router.post(
        BannerController.move(banner.id).url,
        { direction },
        { preserveScroll: true },
    );
}

function toggle(banner: AdminBanner): void {
    router.post(
        BannerController.update(banner.id).url,
        {
            title: banner.title,
            link_url: banner.link_url ?? '',
            is_active: banner.is_active ? 0 : 1,
        },
        { preserveScroll: true },
    );
}

function destroy(): void {
    if (!deleting.value) {
        return;
    }

    router.delete(BannerController.destroy(deleting.value.id).url, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = null;
        },
    });
}
</script>

<template>
    <Head title="Banner Promo · Pengaturan Situs" />

    <div class="flex flex-col space-y-6">
        <SiteSettingsNav />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Banner Promo"
                description="Slider gambar di beranda, tepat di bawah hero. Hanya banner yang aktif yang tampil, dengan urutan sama seperti di sini."
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
                    Tambah Banner
                </Button>
            </div>
        </div>

        <div
            v-if="banners.length === 0"
            class="rounded-lg border border-dashed p-10 text-center"
        >
            <ImagePlus class="mx-auto size-10 text-muted-foreground" />
            <h2 class="mt-3 font-semibold">Belum ada banner</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Tambahkan banner pertama; slider muncul di beranda setelah ada
                banner yang aktif.
            </p>
        </div>

        <ul v-else class="space-y-3">
            <li
                v-for="(banner, index) in banners"
                :key="banner.id"
                class="flex flex-col gap-4 rounded-lg border p-3 sm:flex-row sm:items-center"
                :class="banner.is_active ? '' : 'bg-muted/40'"
            >
                <img
                    :src="banner.image_url"
                    :alt="banner.title"
                    class="aspect-[3/1] w-full shrink-0 rounded-md border object-cover sm:w-56"
                    :class="banner.is_active ? '' : 'opacity-60'"
                />
                <div class="min-w-0 flex-1 space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-medium">{{ banner.title }}</span>
                        <Badge
                            :variant="banner.is_active ? 'default' : 'outline'"
                        >
                            {{ banner.is_active ? 'Active' : 'Hidden' }}
                        </Badge>
                    </div>
                    <p class="truncate text-sm text-muted-foreground">
                        {{ banner.link_url || 'Tanpa tautan' }}
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-1">
                    <Button
                        variant="ghost"
                        size="icon"
                        :disabled="index === 0"
                        aria-label="Pindahkan ke atas"
                        @click="move(banner, 'up')"
                    >
                        <ArrowUp class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        :disabled="index === banners.length - 1"
                        aria-label="Pindahkan ke bawah"
                        @click="move(banner, 'down')"
                    >
                        <ArrowDown class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        :aria-label="
                            banner.is_active
                                ? 'Sembunyikan dari beranda'
                                : 'Tampilkan di beranda'
                        "
                        @click="toggle(banner)"
                    >
                        <EyeOff v-if="banner.is_active" class="h-4 w-4" />
                        <Eye v-else class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Ubah banner"
                        @click="openEdit(banner)"
                    >
                        <Pencil class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Hapus banner"
                        @click="deleting = banner"
                    >
                        <Trash2 class="h-4 w-4 text-destructive" />
                    </Button>
                </div>
            </li>
        </ul>

        <BannerFormDialog v-model:open="formOpen" :banner="editing" />

        <Dialog
            :open="deleting !== null"
            @update:open="(value) => !value && (deleting = null)"
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Hapus Banner</DialogTitle>
                    <DialogDescription>
                        Hapus banner <strong>{{ deleting?.title }}</strong
                        >? Gambarnya ikut terhapus dan tindakan ini tidak bisa
                        dibatalkan.
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
