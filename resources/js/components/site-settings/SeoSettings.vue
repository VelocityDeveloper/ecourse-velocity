<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import {
    CircleCheck,
    ExternalLink,
    Eye,
    Network,
    Hash,
    ImageUp,
    Search,
    ShieldCheck,
    Type,
} from '@lucide/vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import SiteSettingController from '@/actions/App/Http/Controllers/Admin/SiteSettingController';
import InputError from '@/components/InputError.vue';
import SettingsCard from '@/components/site-settings/SettingsCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { oversizedImageError } from '@/lib/imageUpload';

const props = defineProps<{
    values: Record<string, string | null>;
    defaults: { title: string; description: string };
    /** Roughly where search results cut the title and description off. */
    limits: { title: number; description: number };
    imageUrl: string | null;
    /** Shared while no SEO picture is uploaded: the hero photo or the logo. */
    fallbackImageUrl: string | null;
    siteUrl: string;
    sitemapUrl: string;
}>();

// Keep in step with the server rule; PHP drops larger files silently.
const MAX_MB = 3;

const form = useForm({
    section: 'seo',
    seo_title: props.values.seo_title ?? '',
    seo_description: props.values.seo_description ?? '',
    seo_keywords: props.values.seo_keywords ?? '',
    seo_google_verification: props.values.seo_google_verification ?? '',
    seo_image: null as File | null,
    remove_seo_image: false,
});

const errors = computed(() => form.errors as Record<string, string>);

const title = computed(() => form.seo_title.trim() || props.defaults.title);
const description = computed(
    () => form.seo_description.trim() || props.defaults.description,
);
const keywords = computed(() =>
    form.seo_keywords
        .split(',')
        .map((keyword) => keyword.trim())
        .filter((keyword) => keyword !== ''),
);

/** How a length compares with what search results show. */
function lengthTone(length: number, limit: number): string {
    return length > limit ? 'text-amber-600 dark:text-amber-400' : '';
}

// The share picture: the file just picked, the uploaded one, or the fallback.
const inputKey = ref(0);
const fileError = ref<string | null>(null);
const preview = ref<string | null>(null);
const sharedImage = computed(() => {
    if (preview.value) {
        return preview.value;
    }

    if (form.remove_seo_image) {
        return props.fallbackImageUrl;
    }

    return props.imageUrl ?? props.fallbackImageUrl;
});
const usesOwnImage = computed(
    () =>
        preview.value !== null ||
        (props.imageUrl !== null && !form.remove_seo_image),
);

function clearPreview(): void {
    if (preview.value) {
        URL.revokeObjectURL(preview.value);
    }

    preview.value = null;
}

function onImageChange(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    clearPreview();
    fileError.value = file ? oversizedImageError(file, MAX_MB) : null;

    if (fileError.value || !file) {
        form.seo_image = null;
        inputKey.value++;

        return;
    }

    form.seo_image = file;
    form.remove_seo_image = false;
    preview.value = URL.createObjectURL(file);
}

function removeImage(): void {
    clearPreview();
    inputKey.value++;
    form.seo_image = null;
    form.remove_seo_image = true;
}

onBeforeUnmount(clearPreview);

function submit(): void {
    form.post(SiteSettingController.update.url(), {
        preserveScroll: true,
        onSuccess: () => {
            clearPreview();
            inputKey.value++;
            form.seo_image = null;
            form.remove_seo_image = false;
            form.defaults();
        },
    });
}

const page = usePage();
const domain = computed(() => props.siteUrl.replace(/^https?:\/\//, ''));
const host = computed(() => domain.value.replace(/\/$/, ''));
</script>

<template>
    <form
        class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_360px]"
        @submit.prevent="submit"
    >
        <div class="flex min-w-0 flex-col gap-6">
            <p
                class="rounded-lg border bg-muted/40 p-3 text-sm text-muted-foreground"
            >
                Berlaku untuk halaman depan
                <span class="font-medium text-foreground">{{ siteUrl }}</span
                >, yaitu judul dan deskripsi di hasil pencarian Google, serta
                judul, deskripsi, dan gambar saat tautannya dibagikan di
                WhatsApp, Facebook, atau X. Isian yang kosong memakai bawaan.
            </p>

            <SettingsCard
                :icon="Type"
                title="Judul & deskripsi"
                description="Teks biru dan ringkasan di bawahnya pada hasil pencarian Google."
            >
                <div class="grid gap-5">
                    <div class="grid gap-2">
                        <Label for="seo_title">Judul halaman</Label>
                        <Input
                            id="seo_title"
                            v-model="form.seo_title"
                            :placeholder="defaults.title"
                            maxlength="70"
                        />
                        <p
                            class="flex flex-wrap justify-between gap-x-3 text-xs text-muted-foreground"
                        >
                            <span
                                >Sebutkan nama situs dan apa yang ditawarkan,
                                kata terpenting di depan.</span
                            >
                            <span
                                class="tabular-nums"
                                :class="lengthTone(title.length, limits.title)"
                                >{{ title.length }}/{{ limits.title }}</span
                            >
                        </p>
                        <InputError :message="errors.seo_title" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="seo_description">Deskripsi</Label>
                        <Textarea
                            id="seo_description"
                            v-model="form.seo_description"
                            :placeholder="defaults.description"
                            maxlength="300"
                            rows="3"
                        />
                        <p
                            class="flex flex-wrap justify-between gap-x-3 text-xs text-muted-foreground"
                        >
                            <span
                                >Kosong = deskripsi situs di tab Kontak &amp;
                                Sosmed. Idealnya 120–{{
                                    limits.description
                                }}
                                karakter.</span
                            >
                            <span
                                class="tabular-nums"
                                :class="
                                    lengthTone(
                                        description.length,
                                        limits.description,
                                    )
                                "
                                >{{ description.length }}/{{
                                    limits.description
                                }}</span
                            >
                        </p>
                        <InputError :message="errors.seo_description" />
                    </div>
                </div>
            </SettingsCard>

            <SettingsCard
                :icon="Hash"
                title="Kata kunci"
                description="Pisahkan dengan koma. Google tidak lagi memakainya untuk peringkat, tetapi beberapa mesin pencari lain masih membacanya."
            >
                <div class="grid gap-2">
                    <Label for="seo_keywords" class="sr-only">Kata kunci</Label>
                    <Input
                        id="seo_keywords"
                        v-model="form.seo_keywords"
                        placeholder="kursus online, belajar programming, kelas desain"
                        maxlength="255"
                    />
                    <ul
                        v-if="keywords.length > 0"
                        class="flex flex-wrap gap-1.5"
                        aria-label="Kata kunci"
                    >
                        <li
                            v-for="(keyword, index) in keywords"
                            :key="index"
                            class="rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium"
                        >
                            {{ keyword }}
                        </li>
                    </ul>
                    <InputError :message="errors.seo_keywords" />
                </div>
            </SettingsCard>

            <SettingsCard
                :icon="ImageUp"
                title="Gambar saat dibagikan"
                description="Muncul di pratinjau tautan WhatsApp, Facebook, dan X. Ukuran terbaik 1200 × 630 piksel."
            >
                <template #aside>
                    <span
                        class="rounded-full bg-muted px-2.5 py-0.5 text-xs font-semibold text-muted-foreground"
                    >
                        {{ usesOwnImage ? 'Gambar sendiri' : 'Otomatis' }}
                    </span>
                </template>
                <div class="grid gap-4 sm:grid-cols-[220px_minmax(0,1fr)]">
                    <div
                        class="flex aspect-[1200/630] items-center justify-center overflow-hidden rounded-lg border bg-muted"
                    >
                        <img
                            v-if="sharedImage"
                            :src="sharedImage"
                            alt="Gambar yang dibagikan"
                            class="size-full object-cover"
                        />
                        <span v-else class="text-xs text-muted-foreground"
                            >Tanpa gambar</span
                        >
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="seo_image">Berkas gambar</Label>
                        <Input
                            id="seo_image"
                            :key="inputKey"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            @change="onImageChange"
                        />
                        <p class="text-xs text-muted-foreground">
                            PNG, JPG, atau WebP, maksimal {{ MAX_MB }} MB. Tanpa
                            gambar sendiri, dipakai foto hero beranda (atau logo
                            bila belum ada).
                        </p>
                        <Button
                            v-if="usesOwnImage"
                            type="button"
                            variant="link"
                            size="sm"
                            class="h-auto justify-start p-0 text-xs text-destructive"
                            @click="removeImage"
                        >
                            Hapus gambar, pakai otomatis
                        </Button>
                        <InputError :message="fileError ?? errors.seo_image" />
                    </div>
                </div>
            </SettingsCard>

            <SettingsCard
                :icon="ShieldCheck"
                title="Verifikasi Google Search Console"
                description="Untuk mendaftarkan situs di Search Console agar bisa memantau pencarian dan mengirim sitemap."
            >
                <div class="grid gap-2">
                    <Label for="seo_google_verification">Kode verifikasi</Label>
                    <Input
                        id="seo_google_verification"
                        v-model="form.seo_google_verification"
                        placeholder='Kode, atau tempel seluruh <meta name="google-site-verification" ...>'
                    />
                    <p class="text-xs text-muted-foreground">
                        Di Search Console pilih metode verifikasi
                        <span class="font-medium text-foreground">Tag HTML</span
                        >, salin tagnya ke sini, simpan, lalu klik Verifikasi di
                        Search Console.
                    </p>
                    <InputError :message="errors.seo_google_verification" />
                </div>
            </SettingsCard>

            <SettingsCard
                :icon="Network"
                title="Sitemap & robots.txt"
                description="Dibuat otomatis: beranda, katalog, kategori, kursus terbit, artikel blog, dan profil instruktur. Langsung diperbarui saat ada kursus atau artikel baru."
            >
                <div class="grid gap-3">
                    <div
                        class="flex flex-wrap items-center justify-between gap-2 rounded-lg border bg-muted/40 px-3 py-2"
                    >
                        <code class="text-sm break-all">{{ sitemapUrl }}</code>
                        <a
                            :href="sitemapUrl"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                        >
                            Buka <ExternalLink class="size-3.5" />
                        </a>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Setelah verifikasi berhasil, buka menu
                        <span class="font-medium text-foreground">Sitemap</span>
                        di Search Console lalu kirim alamat di atas. Alamat ini
                        juga sudah dicantumkan di
                        <a
                            :href="`${siteUrl}robots.txt`"
                            target="_blank"
                            rel="noopener"
                            class="font-medium text-foreground underline underline-offset-2"
                            >robots.txt</a
                        >, yang juga menutup halaman dasbor, belajar, dan akun
                        dari mesin pencari.
                    </p>
                </div>
            </SettingsCard>
        </div>

        <aside
            class="flex flex-col gap-4 xl:sticky xl:top-4"
            aria-label="Pratinjau"
        >
            <section class="rounded-xl border bg-card">
                <header class="flex items-center gap-2 border-b p-4">
                    <Search class="size-4 text-muted-foreground" />
                    <h2 class="text-sm font-semibold">Di hasil Google</h2>
                </header>
                <div class="p-4">
                    <div class="rounded-lg border bg-white p-3 text-left">
                        <div class="flex items-center gap-2">
                            <span
                                class="flex size-7 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#f1f3f4]"
                            >
                                <img src="/favicon.svg" alt="" class="size-4" />
                            </span>
                            <span class="min-w-0 leading-tight">
                                <span
                                    class="block truncate text-xs text-[#202124]"
                                    >{{ page.props.name }}</span
                                >
                                <span
                                    class="block truncate text-[11px] text-[#4d5156]"
                                    >{{ siteUrl }}</span
                                >
                            </span>
                        </div>
                        <p
                            class="mt-1.5 line-clamp-2 text-base leading-snug text-[#1a0dab]"
                        >
                            {{ title }}
                        </p>
                        <p
                            class="mt-0.5 line-clamp-2 text-xs leading-relaxed text-[#4d5156]"
                        >
                            {{ description }}
                        </p>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border bg-card">
                <header class="flex items-center gap-2 border-b p-4">
                    <Eye class="size-4 text-muted-foreground" />
                    <h2 class="text-sm font-semibold">Saat dibagikan</h2>
                </header>
                <div class="p-4">
                    <div
                        class="overflow-hidden rounded-lg border bg-[#f0f2f5] text-left"
                    >
                        <div
                            v-if="sharedImage"
                            class="aspect-[1200/630] bg-muted"
                        >
                            <img
                                :src="sharedImage"
                                alt=""
                                class="size-full object-cover"
                            />
                        </div>
                        <div class="space-y-0.5 px-3 py-2">
                            <p
                                class="truncate text-[11px] text-[#65676b] uppercase"
                            >
                                {{ host }}
                            </p>
                            <p
                                class="line-clamp-2 text-sm leading-snug font-semibold text-[#050505]"
                            >
                                {{ title }}
                            </p>
                            <p class="line-clamp-1 text-xs text-[#65676b]">
                                {{ description }}
                            </p>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-muted-foreground">
                        WhatsApp dan Facebook menyimpan pratinjau lama beberapa
                        waktu; perubahan bisa baru terlihat belakangan.
                    </p>
                </div>
            </section>
        </aside>

        <div
            class="sticky bottom-0 z-10 -mx-4 flex flex-wrap items-center gap-3 border-t bg-background/95 px-4 py-3 backdrop-blur xl:col-span-2"
        >
            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Menyimpan...' : 'Simpan SEO' }}
            </Button>
            <p
                v-if="form.recentlySuccessful"
                class="flex items-center gap-1.5 text-sm text-muted-foreground"
            >
                <CircleCheck class="size-4 text-primary" />
                Tersimpan.
            </p>
            <p v-else-if="form.isDirty" class="text-sm text-muted-foreground">
                Ada perubahan yang belum disimpan.
            </p>
        </div>
    </form>
</template>
