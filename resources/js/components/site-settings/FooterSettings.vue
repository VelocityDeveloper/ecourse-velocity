<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpToLine,
    ChartNoAxesColumn,
    CircleCheck,
    Eye,
    LayoutList,
    Link2,
    MessageCircle,
    PanelBottom,
    Plus,
    RotateCcw,
    Trash2,
    UserRound,
} from '@lucide/vue';
import { computed } from 'vue';
import SiteSettingController from '@/actions/App/Http/Controllers/Admin/SiteSettingController';
import InputError from '@/components/InputError.vue';
import FooterPreview from '@/components/site-settings/FooterPreview.vue';
import SettingsCard from '@/components/site-settings/SettingsCard.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import siteSettings from '@/routes/admin/settings';
import type { FooterLayout } from '@/types';

const props = defineProps<{
    footer: FooterLayout;
    defaults: FooterLayout;
    maxLinks: number;
    /** The WhatsApp number under Kontak, used when the floating one is left empty. */
    contactPhone: string | null;
}>();

const POSITIONS = [
    { value: 'left', label: 'Kiri bawah' },
    { value: 'right', label: 'Kanan bawah' },
] as const;

const clone = (layout: FooterLayout): FooterLayout =>
    JSON.parse(JSON.stringify(layout)) as FooterLayout;

const form = useForm({ section: 'footer', footer: clone(props.footer) });

const errors = computed(() => form.errors as Record<string, string>);
const links = computed(() => form.footer.explore.links);

function addLink(): void {
    links.value.push({ label: '', url: '' });
}

function removeLink(index: number): void {
    links.value.splice(index, 1);
}

function moveLink(index: number, step: -1 | 1): void {
    const [link] = links.value.splice(index, 1);
    links.value.splice(index + step, 0, link);
}

function resetLinks(): void {
    form.footer.explore.links = clone(props.defaults).explore.links;
}

function resetAll(): void {
    form.footer = clone(props.defaults);
}

function submit(): void {
    form.post(SiteSettingController.update.url(), { preserveScroll: true });
}

const year = new Date().getFullYear();

const page = usePage();
const brand = computed(() => ({
    ...page.props.site,
    socials: page.props.site.socials.map((social) => social.network),
}));
const hasWhatsapp = computed(
    () =>
        form.footer.whatsapp.number.trim() !== '' ||
        Boolean(props.contactPhone),
);
</script>

<template>
    <form
        class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_340px]"
        @submit.prevent="submit"
    >
        <div class="flex min-w-0 flex-col gap-6">
            <p
                class="rounded-lg border bg-muted/40 p-3 text-sm text-muted-foreground"
            >
                Logo, deskripsi, kontak, dan media sosial di kolom pertama
                footer diatur di tab
                <Link
                    :href="siteSettings.edit('kontak')"
                    class="font-medium text-foreground underline underline-offset-2"
                    >Kontak &amp; Sosmed</Link
                >. Halaman ini mengatur kolom lainnya, baris bawah, dan tombol
                mengambang.
            </p>

            <h3
                class="-mb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Kolom footer
            </h3>

            <SettingsCard
                v-model:show="form.footer.categories.show"
                :icon="LayoutList"
                title="Kolom kategori"
                description="Kategori yang punya kursus terbit, diambil otomatis."
            >
                <div class="grid gap-4 sm:grid-cols-[1fr_8rem_1fr]">
                    <div class="grid content-start gap-2">
                        <Label for="categories-title">Judul kolom</Label>
                        <Input
                            id="categories-title"
                            v-model="form.footer.categories.title"
                            :placeholder="defaults.categories.title"
                            maxlength="40"
                        />
                        <InputError
                            :message="errors['footer.categories.title']"
                        />
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="categories-limit">Jumlah</Label>
                        <Input
                            id="categories-limit"
                            v-model.number="form.footer.categories.limit"
                            type="number"
                            min="1"
                            max="12"
                        />
                        <InputError
                            :message="errors['footer.categories.limit']"
                        />
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="categories-see-all"
                            >Teks "lihat semua"</Label
                        >
                        <Input
                            id="categories-see-all"
                            v-model="form.footer.categories.see_all_label"
                            :placeholder="defaults.categories.see_all_label"
                            maxlength="40"
                        />
                        <InputError
                            :message="errors['footer.categories.see_all_label']"
                        />
                    </div>
                </div>
            </SettingsCard>

            <SettingsCard
                v-model:show="form.footer.explore.show"
                :icon="Link2"
                title="Kolom tautan"
                description="Daftar tautan bebas, misalnya halaman kursus, blog, atau situs lain."
            >
                <template #aside>
                    <span
                        class="rounded-full bg-muted px-2.5 py-0.5 text-xs font-semibold text-muted-foreground tabular-nums"
                    >
                        {{ links.length }} / {{ maxLinks }} tautan
                    </span>
                </template>

                <div class="grid gap-2 sm:max-w-xs">
                    <Label for="explore-title">Judul kolom</Label>
                    <Input
                        id="explore-title"
                        v-model="form.footer.explore.title"
                        :placeholder="defaults.explore.title"
                        maxlength="40"
                    />
                    <InputError :message="errors['footer.explore.title']" />
                </div>

                <div class="space-y-2">
                    <div
                        class="hidden grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)_7.5rem] gap-2 px-2 text-xs font-medium text-muted-foreground sm:grid"
                    >
                        <span>Teks tautan</span>
                        <span>Alamat</span>
                        <span class="sr-only">Aksi</span>
                    </div>
                    <p
                        v-if="links.length === 0"
                        class="rounded-lg border border-dashed px-4 py-6 text-center text-sm text-muted-foreground"
                    >
                        Belum ada tautan.
                    </p>
                    <ol v-else class="space-y-2">
                        <li
                            v-for="(link, index) in links"
                            :key="index"
                            class="grid gap-2 rounded-lg border bg-muted/30 p-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)_7.5rem] sm:items-start"
                        >
                            <div class="grid gap-1">
                                <Label
                                    :for="`link-label-${index}`"
                                    class="sr-only"
                                    >Teks tautan {{ index + 1 }}</Label
                                >
                                <Input
                                    :id="`link-label-${index}`"
                                    v-model="link.label"
                                    class="bg-background"
                                    placeholder="Teks tautan"
                                    maxlength="40"
                                />
                                <InputError
                                    :message="
                                        errors[
                                            `footer.explore.links.${index}.label`
                                        ]
                                    "
                                />
                            </div>
                            <div class="grid gap-1">
                                <Label
                                    :for="`link-url-${index}`"
                                    class="sr-only"
                                    >Alamat tautan {{ index + 1 }}</Label
                                >
                                <Input
                                    :id="`link-url-${index}`"
                                    v-model="link.url"
                                    class="bg-background"
                                    placeholder="/kursus atau https://..."
                                    maxlength="255"
                                />
                                <InputError
                                    :message="
                                        errors[
                                            `footer.explore.links.${index}.url`
                                        ]
                                    "
                                />
                            </div>
                            <div class="flex justify-end gap-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    :disabled="index === 0"
                                    aria-label="Naikkan"
                                    @click="moveLink(index, -1)"
                                >
                                    <ArrowUp class="size-4" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    :disabled="index === links.length - 1"
                                    aria-label="Turunkan"
                                    @click="moveLink(index, 1)"
                                >
                                    <ArrowDown class="size-4" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Hapus tautan"
                                    @click="removeLink(index)"
                                >
                                    <Trash2 class="size-4 text-destructive" />
                                </Button>
                            </div>
                        </li>
                    </ol>
                    <InputError :message="errors['footer.explore.links']" />
                    <div class="flex flex-wrap items-center gap-2">
                        <Button
                            v-if="links.length < maxLinks"
                            type="button"
                            variant="outline"
                            size="sm"
                            class="border-dashed"
                            @click="addLink"
                        >
                            <Plus class="mr-2 size-4" />
                            Tambah tautan
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="resetLinks"
                        >
                            <RotateCcw class="mr-2 size-4" />
                            Tautan bawaan
                        </Button>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Alamat di situs ini diawali "/" (contoh /kursus atau
                        /#testimonials). Tautan ke situs lain ditulis lengkap
                        dengan https:// dan terbuka di tab baru.
                    </p>
                </div>
            </SettingsCard>

            <SettingsCard
                v-model:show="form.footer.account.show"
                :icon="UserRound"
                title="Kolom akun"
                description="Isinya otomatis: Masuk/Daftar untuk pengunjung, menu akun untuk yang sudah masuk."
            >
                <div class="grid gap-2 sm:max-w-xs">
                    <Label for="account-title">Judul kolom</Label>
                    <Input
                        id="account-title"
                        v-model="form.footer.account.title"
                        :placeholder="defaults.account.title"
                        maxlength="40"
                    />
                    <InputError :message="errors['footer.account.title']" />
                </div>
            </SettingsCard>

            <SettingsCard
                v-model:show="form.footer.statistics.show"
                :icon="ChartNoAxesColumn"
                title="Kolom statistik pengunjung"
                description="Seperti statistik velocity-addons: kunjungan & pengunjung hari ini, totalnya, dan yang sedang online. Dihitung otomatis dari halaman publik; bot dan halaman dasbor tidak dihitung."
            >
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid content-start gap-2">
                        <Label for="statistics-title">Judul kolom</Label>
                        <Input
                            id="statistics-title"
                            v-model="form.footer.statistics.title"
                            :placeholder="defaults.statistics.title"
                            maxlength="40"
                        />
                        <InputError
                            :message="errors['footer.statistics.title']"
                        />
                    </div>
                    <Label
                        class="flex items-start gap-2 self-end rounded-lg border p-3 font-normal"
                    >
                        <Checkbox
                            v-model="form.footer.statistics.online"
                            class="mt-0.5"
                        />
                        <span>
                            <span class="block font-medium"
                                >Tampilkan pengunjung online</span
                            >
                            <span class="text-xs text-muted-foreground"
                                >Pengunjung yang membuka halaman dalam 5 menit
                                terakhir.</span
                            >
                        </span>
                    </Label>
                </div>
            </SettingsCard>

            <h3
                class="-mb-2 pt-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Bagian bawah
            </h3>

            <SettingsCard
                :icon="PanelBottom"
                title="Baris hak cipta"
                description="Satu baris di bagian paling bawah footer, berdampingan dengan kredit “Design by Velocity Developer”."
            >
                <div class="grid gap-2">
                    <Label for="copyright">Teks hak cipta</Label>
                    <Input
                        id="copyright"
                        v-model="form.footer.copyright"
                        :placeholder="defaults.copyright"
                        maxlength="150"
                    />
                    <p class="text-xs text-muted-foreground">
                        {tahun} diganti tahun berjalan ({{ year }}), {nama}
                        diganti nama situs.
                    </p>
                    <InputError :message="errors['footer.copyright']" />
                </div>
            </SettingsCard>

            <SettingsCard
                :icon="MessageCircle"
                title="Tombol mengambang"
                description="Selalu terlihat di pojok bawah setiap halaman situs."
            >
                <div class="grid gap-2">
                    <span class="text-sm font-medium">Letak</span>
                    <div
                        class="inline-flex w-fit rounded-lg bg-muted p-1"
                        role="radiogroup"
                        aria-label="Letak tombol mengambang"
                    >
                        <button
                            v-for="position in POSITIONS"
                            :key="position.value"
                            type="button"
                            role="radio"
                            :aria-checked="
                                form.footer.floating_position === position.value
                            "
                            class="rounded-md px-4 py-1.5 text-sm font-medium transition"
                            :class="
                                form.footer.floating_position === position.value
                                    ? 'bg-background shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                            @click="
                                form.footer.floating_position = position.value
                            "
                        >
                            {{ position.label }}
                        </button>
                    </div>
                    <InputError :message="errors['footer.floating_position']" />
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="space-y-4 rounded-lg border p-4">
                        <Label class="flex items-center gap-2 font-medium">
                            <Checkbox v-model="form.footer.whatsapp.show" />
                            <MessageCircle class="size-4 text-[#25D366]" />
                            Tombol WhatsApp
                        </Label>
                        <template v-if="form.footer.whatsapp.show">
                            <div class="grid gap-2">
                                <Label for="whatsapp-number"
                                    >Nomor WhatsApp</Label
                                >
                                <Input
                                    id="whatsapp-number"
                                    v-model="form.footer.whatsapp.number"
                                    type="tel"
                                    :placeholder="
                                        contactPhone ?? '0812 3456 7890'
                                    "
                                    maxlength="30"
                                />
                                <p class="text-xs text-muted-foreground">
                                    <template v-if="contactPhone">
                                        Kosongkan untuk memakai nomor di Kontak
                                        &amp; Sosmed ({{ contactPhone }}).
                                    </template>
                                    <template v-else>
                                        Tombol baru tampil setelah nomor diisi.
                                    </template>
                                </p>
                                <InputError
                                    :message="errors['footer.whatsapp.number']"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="whatsapp-message"
                                    >Pesan pembuka (opsional)</Label
                                >
                                <Textarea
                                    id="whatsapp-message"
                                    v-model="form.footer.whatsapp.message"
                                    rows="2"
                                    maxlength="200"
                                    placeholder="Halo, saya ingin bertanya tentang kursus."
                                />
                                <p class="text-xs text-muted-foreground">
                                    Sudah terisi di kolom chat saat WhatsApp
                                    terbuka.
                                </p>
                                <InputError
                                    :message="errors['footer.whatsapp.message']"
                                />
                            </div>
                        </template>
                    </div>

                    <div class="space-y-4 rounded-lg border p-4">
                        <Label class="flex items-center gap-2 font-medium">
                            <Checkbox v-model="form.footer.back_to_top.show" />
                            <ArrowUpToLine
                                class="size-4 text-muted-foreground"
                            />
                            Ikon kembali ke atas
                        </Label>
                        <template v-if="form.footer.back_to_top.show">
                            <div class="grid gap-2">
                                <Label for="back-to-top">Keterangan ikon</Label>
                                <Input
                                    id="back-to-top"
                                    v-model="form.footer.back_to_top.label"
                                    :placeholder="defaults.back_to_top.label"
                                    maxlength="40"
                                />
                                <InputError
                                    :message="
                                        errors['footer.back_to_top.label']
                                    "
                                />
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Ikon bulat paling pojok, di sebelah luar tombol
                                WhatsApp. Muncul setelah halaman digulir;
                                keterangannya tampil saat ikon disorot.
                            </p>
                        </template>
                    </div>
                </div>
            </SettingsCard>
        </div>

        <aside
            class="hidden rounded-xl border bg-card xl:sticky xl:top-4 xl:block"
            aria-label="Pratinjau footer"
        >
            <header class="flex items-center gap-2 border-b p-4">
                <Eye class="size-4 text-muted-foreground" />
                <h2 class="text-sm font-semibold">Pratinjau footer</h2>
            </header>
            <div class="space-y-3 p-4">
                <FooterPreview
                    :brand="brand"
                    :layout="form.footer"
                    focus="columns"
                    :has-whatsapp="hasWhatsapp"
                />
                <p class="text-xs text-muted-foreground">
                    Berubah langsung saat Anda mengetik. Bagian yang redup
                    diatur di tab Kontak &amp; Sosmed.
                </p>
            </div>
        </aside>

        <div
            class="sticky bottom-0 z-10 -mx-4 flex flex-wrap items-center gap-3 border-t bg-background/95 px-4 py-3 backdrop-blur xl:col-span-2"
        >
            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Menyimpan...' : 'Simpan footer' }}
            </Button>
            <Button type="button" variant="ghost" @click="resetAll">
                <RotateCcw class="mr-2 size-4" />
                Kembalikan semua ke bawaan
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
