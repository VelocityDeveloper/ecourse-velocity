<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Selamat datang kembali',
        description: 'Masuk untuk melanjutkan belajar Anda.',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const canRegister = computed(() => usePage().props.canRegister);
</script>

<template>
    <Head title="Masuk" />

    <div
        v-if="status"
        class="rounded-xl bg-primary/10 p-3 text-center text-sm font-medium"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-5">
            <div class="grid gap-2">
                <Label for="email">Alamat email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    v-focus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="email@example.com"
                    class="h-11 rounded-xl"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">Kata sandi</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm font-semibold text-primary decoration-primary/40"
                        :tabindex="5"
                    >
                        Lupa kata sandi?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Kata sandi"
                    class="h-11 rounded-xl"
                />
                <InputError :message="errors.password" />
            </div>

            <Label
                for="remember"
                class="flex w-fit items-center gap-2.5 font-normal text-muted-foreground"
            >
                <Checkbox id="remember" name="remember" :tabindex="3" />
                Ingat saya
            </Label>

            <Button
                type="submit"
                class="mt-2 h-12 w-full rounded-xl text-base font-bold shadow-lg shadow-primary/25"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                Masuk
            </Button>
        </div>

        <p
            v-if="canRegister"
            class="border-t pt-6 text-center text-sm text-muted-foreground"
        >
            Belum punya akun?
            <TextLink
                :href="register()"
                :tabindex="5"
                class="font-bold text-primary decoration-primary/40"
                >Daftar gratis</TextLink
            >
        </p>
    </Form>
</template>
