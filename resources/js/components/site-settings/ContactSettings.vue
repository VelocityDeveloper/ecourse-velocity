<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { CircleCheck, Eye, FileText, Phone, Share2 } from '@lucide/vue';
import { computed } from 'vue';
import SiteSettingController from '@/actions/App/Http/Controllers/Admin/SiteSettingController';
import InputError from '@/components/InputError.vue';
import FooterPreview from '@/components/site-settings/FooterPreview.vue';
import SettingsCard from '@/components/site-settings/SettingsCard.vue';
import SocialIcon from '@/components/SocialIcon.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import siteSettings from '@/routes/admin/settings';

const props = defineProps<{
    values: Record<string, string | null>;
    defaultDescription: string;
}>();

const CONTACT_FIELDS = [
    {
        key: 'contact_address',
        label: 'Alamat',
        placeholder: 'Jl. Contoh No. 1, Bandung, Jawa Barat',
        type: 'text',
        hint: 'Alamat kantor atau lokasi belajar.',
    },
    {
        key: 'contact_email',
        label: 'Email',
        placeholder: 'halo@contoh.id',
        type: 'email',
        hint: 'Pengunjung bisa langsung mengirim email.',
    },
    {
        key: 'contact_phone',
        label: 'Nomor WhatsApp',
        placeholder: '0812 3456 7890',
        type: 'tel',
        hint: 'Menjadi tautan chat WhatsApp di footer.',
    },
    {
        key: 'contact_hours',
        label: 'Jam layanan',
        placeholder: 'Setiap hari, 08.00–17.00 WIB',
        type: 'text',
        hint: 'Kapan pertanyaan biasanya dijawab.',
    },
] as const;

const SOCIAL_FIELDS = [
    { key: 'social_instagram', network: 'instagram', label: 'Instagram' },
    { key: 'social_tiktok', network: 'tiktok', label: 'TikTok' },
    { key: 'social_youtube', network: 'youtube', label: 'YouTube' },
    { key: 'social_facebook', network: 'facebook', label: 'Facebook' },
    { key: 'social_linkedin', network: 'linkedin', label: 'LinkedIn' },
] as const;

const KEYS = [
    'site_description',
    ...CONTACT_FIELDS.map((field) => field.key),
    ...SOCIAL_FIELDS.map((field) => field.key),
];

const form = useForm({
    section: 'kontak',
    ...Object.fromEntries(KEYS.map((key) => [key, props.values[key] ?? ''])),
} as Record<string, string>);

const errors = computed(() => form.errors as Record<string, string>);
const filledSocials = computed(() =>
    SOCIAL_FIELDS.filter((field) => form[field.key].trim() !== ''),
);

const page = usePage();
const preview = computed(() => ({
    description: form.site_description.trim() || props.defaultDescription,
    address: form.contact_address.trim() || null,
    email: form.contact_email.trim() || null,
    phone: form.contact_phone.trim() || null,
    hours: form.contact_hours.trim() || null,
    socials: filledSocials.value.map((field) => field.network),
}));

function submit(): void {
    form.post(SiteSettingController.update.url(), { preserveScroll: true });
}
</script>

<template>
    <form
        class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_340px]"
        @submit.prevent="submit"
    >
        <div class="flex min-w-0 flex-col gap-6">
            <SettingsCard
                :icon="FileText"
                title="Deskripsi situs"
                description="Kalimat singkat di bawah logo footer."
            >
                <div class="grid gap-2">
                    <Label for="site_description" class="sr-only"
                        >Deskripsi situs</Label
                    >
                    <Textarea
                        id="site_description"
                        v-model="form.site_description"
                        :placeholder="defaultDescription"
                        maxlength="300"
                        rows="3"
                    />
                    <p class="text-xs text-muted-foreground">
                        Kosongkan untuk memakai deskripsi bawaan.
                        <span class="tabular-nums"
                            >{{ form.site_description.length }}/300</span
                        >
                    </p>
                    <InputError :message="errors.site_description" />
                </div>
            </SettingsCard>

            <SettingsCard
                :icon="Phone"
                title="Kontak"
                description="Tampil di footer di bawah deskripsi. Yang kosong tidak ditampilkan."
            >
                <div class="grid gap-x-4 gap-y-5 sm:grid-cols-2">
                    <div
                        v-for="field in CONTACT_FIELDS"
                        :key="field.key"
                        class="grid content-start gap-2"
                    >
                        <Label :for="field.key">{{ field.label }}</Label>
                        <Input
                            :id="field.key"
                            v-model="form[field.key]"
                            :type="field.type"
                            :placeholder="field.placeholder"
                        />
                        <p class="text-xs text-muted-foreground">
                            {{ field.hint }}
                        </p>
                        <InputError :message="errors[field.key]" />
                    </div>
                </div>
            </SettingsCard>

            <SettingsCard
                :icon="Share2"
                title="Media sosial"
                description="Tautan lengkap profil. Ikon tampil di footer dan terbuka di tab baru."
            >
                <template #aside>
                    <span
                        class="rounded-full bg-muted px-2.5 py-0.5 text-xs font-semibold text-muted-foreground"
                    >
                        {{ filledSocials.length }} /
                        {{ SOCIAL_FIELDS.length }} terisi
                    </span>
                </template>
                <div class="grid gap-3">
                    <div
                        v-for="field in SOCIAL_FIELDS"
                        :key="field.key"
                        class="grid gap-1.5 sm:grid-cols-[9rem_minmax(0,1fr)] sm:items-center sm:gap-3"
                    >
                        <Label :for="field.key" class="flex items-center gap-2">
                            <span
                                class="flex size-7 items-center justify-center rounded-md bg-muted text-muted-foreground"
                            >
                                <SocialIcon
                                    :network="field.network"
                                    class="size-4"
                                />
                            </span>
                            {{ field.label }}
                        </Label>
                        <Input
                            :id="field.key"
                            v-model="form[field.key]"
                            type="url"
                            :placeholder="`https://${field.network}.com/akun-anda`"
                        />
                        <InputError
                            class="sm:col-start-2"
                            :message="errors[field.key]"
                        />
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
                    :brand="preview"
                    :layout="page.props.site.footer"
                    focus="brand"
                    :has-whatsapp="
                        Boolean(page.props.site.floating_whatsapp_url)
                    "
                />
                <p class="text-xs text-muted-foreground">
                    Bagian yang terang adalah yang diatur di halaman ini. Kolom
                    tautan dan tombol mengambang diatur di tab
                    <Link
                        :href="siteSettings.edit('footer')"
                        class="font-medium text-foreground underline underline-offset-2"
                        >Footer</Link
                    >.
                </p>
            </div>
        </aside>

        <div
            class="sticky bottom-0 z-10 -mx-4 flex flex-wrap items-center gap-3 border-t bg-background/95 px-4 py-3 backdrop-blur xl:col-span-2"
        >
            <Button type="submit" :disabled="form.processing">
                {{
                    form.processing ? 'Menyimpan...' : 'Simpan kontak & sosmed'
                }}
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
