<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import SiteSettingController from '@/actions/App/Http/Controllers/Admin/SiteSettingController';
import InputError from '@/components/InputError.vue';
import SocialIcon from '@/components/SocialIcon.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

defineProps<{
    values: Record<string, string | null>;
    defaultDescription: string;
}>();

const CONTACT_FIELDS = [
    {
        key: 'contact_address',
        label: 'Alamat',
        placeholder: 'Jl. Contoh No. 1, Bandung, Jawa Barat',
        type: 'text',
    },
    {
        key: 'contact_email',
        label: 'Email',
        placeholder: 'halo@contoh.id',
        type: 'email',
    },
    {
        key: 'contact_phone',
        label: 'Nomor WhatsApp',
        placeholder: '0812 3456 7890',
        type: 'tel',
        hint: 'Di footer menjadi tautan ke WhatsApp.',
    },
    {
        key: 'contact_hours',
        label: 'Jam layanan',
        placeholder: 'Setiap hari, 08.00–17.00 WIB',
        type: 'text',
    },
];

const SOCIAL_FIELDS = [
    { key: 'social_instagram', network: 'instagram', label: 'Instagram' },
    { key: 'social_tiktok', network: 'tiktok', label: 'TikTok' },
    { key: 'social_youtube', network: 'youtube', label: 'YouTube' },
    { key: 'social_facebook', network: 'facebook', label: 'Facebook' },
    { key: 'social_linkedin', network: 'linkedin', label: 'LinkedIn' },
];
</script>

<template>
    <Form
        v-bind="SiteSettingController.update.form()"
        class="space-y-8"
        v-slot="{ errors, processing }"
    >
        <input type="hidden" name="section" value="kontak" />

        <section class="space-y-4" aria-labelledby="about-heading">
            <div>
                <h2 id="about-heading" class="font-semibold">
                    Deskripsi situs
                </h2>
                <p class="text-sm text-muted-foreground">
                    Tampil di footer di bawah logo.
                </p>
            </div>
            <div class="grid gap-2">
                <Label for="site_description" class="sr-only"
                    >Deskripsi situs</Label
                >
                <Textarea
                    id="site_description"
                    name="site_description"
                    :default-value="values.site_description ?? ''"
                    :placeholder="defaultDescription"
                    maxlength="300"
                    rows="3"
                />
                <InputError :message="errors.site_description" />
            </div>
        </section>

        <section class="space-y-4" aria-labelledby="contact-heading">
            <div>
                <h2 id="contact-heading" class="font-semibold">Kontak</h2>
                <p class="text-sm text-muted-foreground">
                    Kolom "Hubungi Kami" di footer. Kolom yang kosong tidak
                    ditampilkan.
                </p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div
                    v-for="field in CONTACT_FIELDS"
                    :key="field.key"
                    class="grid content-start gap-2"
                >
                    <Label :for="field.key">{{ field.label }}</Label>
                    <Input
                        :id="field.key"
                        :name="field.key"
                        :type="field.type"
                        :default-value="values[field.key] ?? ''"
                        :placeholder="field.placeholder"
                    />
                    <p v-if="field.hint" class="text-xs text-muted-foreground">
                        {{ field.hint }}
                    </p>
                    <InputError :message="errors[field.key]" />
                </div>
            </div>
        </section>

        <section class="space-y-4" aria-labelledby="social-heading">
            <div>
                <h2 id="social-heading" class="font-semibold">Media sosial</h2>
                <p class="text-sm text-muted-foreground">
                    Isi tautan lengkap profil Anda. Ikon tampil di footer dan
                    terbuka di tab baru.
                </p>
            </div>
            <div class="grid gap-3">
                <div
                    v-for="field in SOCIAL_FIELDS"
                    :key="field.key"
                    class="grid gap-1"
                >
                    <div class="flex items-center gap-3">
                        <Label
                            :for="field.key"
                            class="flex w-32 shrink-0 items-center gap-2"
                        >
                            <SocialIcon
                                :network="field.network"
                                class="size-4 text-muted-foreground"
                            />
                            {{ field.label }}
                        </Label>
                        <Input
                            :id="field.key"
                            :name="field.key"
                            type="url"
                            :default-value="values[field.key] ?? ''"
                            :placeholder="`https://${field.network}.com/akun-anda`"
                        />
                    </div>
                    <InputError class="sm:pl-35" :message="errors[field.key]" />
                </div>
            </div>
        </section>

        <Button :disabled="processing">
            {{ processing ? 'Menyimpan...' : 'Simpan kontak & footer' }}
        </Button>
    </Form>
</template>
