<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    CircleCheck,
    KeyRound,
    LockKeyhole,
    LogIn,
    RefreshCw,
    ShieldCheck,
    Timer,
    UserPlus,
} from '@lucide/vue';
import SiteSettingController from '@/actions/App/Http/Controllers/Admin/SiteSettingController';
import SettingsCard from '@/components/site-settings/SettingsCard.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useCaptchaImage } from '@/composables/useCaptchaImage';

const props = defineProps<{
    captcha: { login: boolean; register: boolean };
    loginAttempts: number;
}>();

const form = useForm({
    section: 'keamanan',
    captcha: { ...props.captcha },
});

const FORMS = [
    {
        key: 'login',
        label: 'Form masuk',
        description: 'Halaman /masuk, sebelum kata sandi diperiksa.',
        Icon: LogIn,
    },
    {
        key: 'register',
        label: 'Form daftar',
        description: 'Halaman /daftar, mencegah pendaftaran akun palsu massal.',
        Icon: UserPlus,
    },
] as const;

// The sample picture uses the registration code, which an admin never needs.
const { src: sampleSrc, refresh: refreshSample } = useCaptchaImage('register');

function submit(): void {
    form.post(SiteSettingController.update.url(), {
        preserveScroll: true,
        onSuccess: () => form.defaults(),
    });
}
</script>

<template>
    <form
        class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_360px]"
        @submit.prevent="submit"
    >
        <div class="flex min-w-0 flex-col gap-6">
            <SettingsCard
                :icon="ShieldCheck"
                title="Kode keamanan (captcha gambar)"
                description="Pengunjung mengetik 5 angka dari sebuah gambar sebelum masuk atau mendaftar, sehingga program otomatis (bot) tidak bisa menebak kata sandi atau membuat akun palsu."
            >
                <div class="divide-y rounded-lg border">
                    <Label
                        v-for="item in FORMS"
                        :key="item.key"
                        class="flex cursor-pointer items-center gap-3 p-3 font-normal"
                    >
                        <component
                            :is="item.Icon"
                            class="size-4 shrink-0 text-muted-foreground"
                        />
                        <span class="min-w-0 flex-1">
                            <span class="block font-medium">{{
                                item.label
                            }}</span>
                            <span class="block text-xs text-muted-foreground">{{
                                item.description
                            }}</span>
                        </span>
                        <span
                            class="text-xs font-medium"
                            :class="
                                form.captcha[item.key]
                                    ? 'text-primary'
                                    : 'text-muted-foreground'
                            "
                            >{{
                                form.captcha[item.key] ? 'Aktif' : 'Mati'
                            }}</span
                        >
                        <Checkbox v-model="form.captcha[item.key]" />
                    </Label>
                </div>
                <p class="text-xs text-muted-foreground">
                    Setiap kode hanya berlaku untuk satu kali kirim dan 10
                    menit. Bila salah, gambar otomatis diganti.
                </p>
            </SettingsCard>

            <SettingsCard
                :icon="LockKeyhole"
                title="Perlindungan yang selalu aktif"
                description="Bekerja otomatis, tidak perlu diatur."
            >
                <ul class="grid gap-3 text-sm">
                    <li class="flex gap-3">
                        <Timer
                            class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                        />
                        <span
                            >Batas percobaan masuk:
                            <strong>{{ loginAttempts }} kali per menit</strong>
                            untuk tiap email dari satu alamat IP. Setelah itu
                            harus menunggu.</span
                        >
                    </li>
                    <li class="flex gap-3">
                        <KeyRound
                            class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                        />
                        <span
                            >Kata sandi disimpan teracak (bcrypt), tidak bisa
                            dibaca siapa pun termasuk admin.</span
                        >
                    </li>
                    <li class="flex gap-3">
                        <ShieldCheck
                            class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                        />
                        <span
                            >Setiap form dilindungi token CSRF, dan akun yang
                            ditangguhkan tidak bisa masuk.</span
                        >
                    </li>
                </ul>
            </SettingsCard>
        </div>

        <aside class="flex flex-col gap-4 xl:sticky xl:top-4">
            <section class="rounded-xl border bg-card p-4">
                <p class="mb-3 text-sm font-semibold">Contoh gambar</p>
                <div class="flex items-center gap-2">
                    <img
                        v-if="sampleSrc"
                        :src="sampleSrc"
                        width="168"
                        height="56"
                        alt="Contoh gambar kode keamanan"
                        class="h-14 w-auto rounded-lg border bg-white"
                    />
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        aria-label="Ganti contoh gambar"
                        @click="refreshSample"
                    >
                        <RefreshCw class="size-4" />
                    </Button>
                </div>
                <p class="mt-3 text-xs text-muted-foreground">
                    Angka dibuat acak di server dan setiap angka dimiringkan
                    serta diberi garis pengganggu, sehingga sulit dibaca
                    program.
                </p>
            </section>
        </aside>

        <div
            class="sticky bottom-0 z-10 -mx-4 flex flex-wrap items-center gap-3 border-t bg-background/95 px-4 py-3 backdrop-blur xl:col-span-2"
        >
            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Menyimpan...' : 'Simpan keamanan' }}
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
