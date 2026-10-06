<script setup lang="ts">
import { RefreshCw, ShieldCheck } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCaptchaImage } from '@/composables/useCaptchaImage';

/**
 * The picture of digits a login or registration form asks for. Each code
 * answers one submission, so the form calls refresh() after every failed attempt.
 */
const props = defineProps<{
    form: 'login' | 'register';
    error?: string;
    tabindex?: number;
}>();

const { src, failed, refresh } = useCaptchaImage(props.form);

defineExpose({ refresh });
</script>

<template>
    <div class="grid gap-2">
        <Label for="captcha" class="flex items-center gap-1.5">
            <ShieldCheck class="size-4 text-primary" />
            Kode keamanan
        </Label>
        <div class="flex items-stretch gap-2">
            <img
                v-if="src"
                :src="src"
                width="168"
                height="56"
                alt="Gambar kode keamanan berisi 5 angka"
                class="h-11 w-[132px] shrink-0 rounded-xl border bg-white object-cover select-none"
                draggable="false"
            />
            <span
                v-else
                class="flex h-11 w-[132px] shrink-0 items-center justify-center rounded-xl border bg-muted px-2 text-center text-[11px] leading-tight text-muted-foreground"
                >{{
                    failed ? 'Gambar gagal dimuat, ketuk ulang' : 'Memuat…'
                }}</span
            >
            <button
                type="button"
                class="flex size-11 shrink-0 items-center justify-center rounded-xl border text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                title="Ganti gambar"
                aria-label="Ganti gambar kode keamanan"
                @click="refresh"
            >
                <RefreshCw class="size-4" />
            </button>
            <Input
                id="captcha"
                name="captcha"
                required
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="5"
                autocomplete="off"
                autocapitalize="off"
                spellcheck="false"
                placeholder="5 angka"
                :tabindex="tabindex"
                class="h-11 min-w-0 flex-1 rounded-xl text-center font-mono text-lg tracking-[0.3em] placeholder:font-sans placeholder:text-sm placeholder:tracking-normal"
            />
        </div>
        <InputError :message="error" />
    </div>
</template>
