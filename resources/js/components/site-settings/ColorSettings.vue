<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';
import { computed, nextTick, ref, watch } from 'vue';
import SiteSettingController from '@/actions/App/Http/Controllers/Admin/SiteSettingController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

// The main colour setting: swatches, a picker and a preview.
const props = defineProps<{
    name: 'primary_color';
    title: string;
    description: string;
    value: string | null;
    // The built-in colour; saving it stores nothing so app.css keeps applying.
    defaultColor: string;
    presets: { name: string; value: string }[];
}>();

const color = ref(props.value ?? props.defaultColor);

watch(
    () => props.value,
    (value) => {
        color.value = value ?? props.defaultColor;
    },
);

const isValid = computed(() => /^#[0-9a-f]{6}$/i.test(color.value));
const isDirty = computed(
    () =>
        color.value.toLowerCase() !==
        (props.value ?? props.defaultColor).toLowerCase(),
);

const submitted = computed(() =>
    color.value.toLowerCase() === props.defaultColor ? '' : color.value,
);

// The hidden input must hold the new value before the form reads it.
async function resetColor(submit: () => void): Promise<void> {
    color.value = props.defaultColor;
    await nextTick();
    submit();
}
</script>

<template>
    <section class="space-y-4" :aria-labelledby="`${name}-heading`">
        <div>
            <h2 :id="`${name}-heading`" class="font-semibold">{{ title }}</h2>
            <p class="text-sm text-muted-foreground">{{ description }}</p>
        </div>

        <Form
            v-bind="SiteSettingController.update.form()"
            class="space-y-4"
            v-slot="{ errors, processing, submit }"
        >
            <input type="hidden" name="section" value="warna" />
            <div
                class="flex flex-wrap gap-2"
                role="list"
                :aria-label="`Pilihan ${title.toLowerCase()}`"
            >
                <button
                    v-for="preset in presets"
                    :key="preset.value"
                    type="button"
                    role="listitem"
                    class="flex size-9 items-center justify-center rounded-full border-2 border-background ring-1 ring-border transition-transform hover:scale-110"
                    :style="{ backgroundColor: preset.value }"
                    :aria-label="preset.name"
                    :aria-pressed="color.toLowerCase() === preset.value"
                    :title="preset.name"
                    @click="color = preset.value"
                >
                    <Check
                        v-if="color.toLowerCase() === preset.value"
                        class="h-4 w-4 text-white"
                    />
                </button>
            </div>

            <div class="flex flex-wrap items-end gap-3">
                <div class="grid gap-2">
                    <Label :for="`${name}_picker`">Warna lain</Label>
                    <div class="flex items-center gap-2">
                        <input
                            :id="`${name}_picker`"
                            v-model="color"
                            type="color"
                            class="h-9 w-12 cursor-pointer rounded-md border bg-background p-1"
                            aria-label="Pilih warna"
                        />
                        <Input
                            v-model="color"
                            class="w-32 font-mono"
                            maxlength="7"
                            aria-label="Kode warna hex"
                            :placeholder="defaultColor"
                        />
                    </div>
                </div>

                <div
                    class="flex items-center gap-3 rounded-lg border px-4 py-2"
                    aria-hidden="true"
                >
                    <span class="text-xs text-muted-foreground">Pratinjau</span>
                    <span
                        class="rounded-md px-3 py-1.5 text-sm font-medium text-white"
                        :style="{
                            backgroundColor: isValid ? color : undefined,
                        }"
                        >Daftar sekarang</span
                    >
                    <span
                        class="text-sm font-medium"
                        :style="{ color: isValid ? color : undefined }"
                        >Lihat semua</span
                    >
                </div>
            </div>

            <input type="hidden" :name="name" :value="submitted" />
            <InputError :message="errors[name]" />

            <div class="flex flex-wrap items-center gap-3">
                <Button :disabled="processing || !isDirty || !isValid">
                    {{ processing ? 'Menyimpan...' : 'Simpan warna' }}
                </Button>
                <Button
                    v-if="value"
                    type="button"
                    variant="ghost"
                    :disabled="processing"
                    @click="resetColor(submit)"
                >
                    Kembalikan warna bawaan
                </Button>
            </div>
        </Form>
    </section>
</template>
