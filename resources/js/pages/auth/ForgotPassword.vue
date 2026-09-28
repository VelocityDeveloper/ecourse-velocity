<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Lupa kata sandi',
        description:
            'Masukkan email akun Anda, kami kirimkan tautan untuk mengatur ulang kata sandi.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Lupa kata sandi" />

    <div
        v-if="status"
        class="rounded-xl bg-primary/10 p-3 text-center text-sm font-medium"
    >
        {{ status }}
    </div>

    <div class="space-y-6">
        <Form v-bind="email.form()" v-slot="{ errors, processing }">
            <div class="grid gap-2">
                <Label for="email">Alamat email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="off"
                    v-focus
                    placeholder="email@example.com"
                    class="h-11 rounded-xl"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    class="h-12 w-full rounded-xl text-base font-bold shadow-lg shadow-primary/25"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                >
                    <Spinner v-if="processing" />
                    Kirim tautan atur ulang kata sandi
                </Button>
            </div>
        </Form>

        <div class="space-x-1 text-center text-sm text-muted-foreground">
            <span>Atau, kembali ke</span>
            <TextLink :href="login()">halaman masuk</TextLink>
        </div>
    </div>
</template>
