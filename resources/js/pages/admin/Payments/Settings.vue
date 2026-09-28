<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    CircleCheck,
    Clock,
    Eye,
    ImageUp,
    Landmark,
    Plus,
    QrCode,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { oversizedImageError } from '@/lib/imageUpload';
import paymentSettings from '@/routes/admin/payment-settings';
import type { BankAccount } from '@/types';

const props = defineProps<{
    bankAccounts: BankAccount[];
    qrisUrl: string | null;
    qrisName: string | null;
    instructions: string | null;
    expiryHours: number;
    maxBankAccounts: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            {
                title: 'Pengaturan Pembayaran',
                href: '/dasbor/pengaturan-pembayaran',
            },
        ],
    },
});

// Keep in step with the server rule; PHP drops larger files silently.
const MAX_QRIS_MB = 3;

const form = useForm<{
    bank_accounts: BankAccount[];
    qris: File | null;
    remove_qris: boolean;
    qris_name: string;
    instructions: string;
    expiry_hours: number | string;
}>({
    bank_accounts: props.bankAccounts.map((account) => ({ ...account })),
    qris: null,
    remove_qris: false,
    qris_name: props.qrisName ?? '',
    instructions: props.instructions ?? '',
    expiry_hours: props.expiryHours,
});

const qrisPreview = ref<string | null>(props.qrisUrl);
const qrisError = ref<string | null>(null);
const errors = computed(() => form.errors as Record<string, string>);

// Accounts with every field filled in are the ones students will see.
const completeAccounts = computed(() =>
    form.bank_accounts.filter(
        (account) =>
            account.bank.trim() !== '' &&
            account.account_number.trim() !== '' &&
            account.account_name.trim() !== '',
    ),
);
const hasPaymentMethod = computed(
    () => completeAccounts.value.length > 0 || qrisPreview.value !== null,
);

function addAccount(): void {
    form.bank_accounts.push({ bank: '', account_number: '', account_name: '' });
}

function removeAccount(index: number): void {
    form.bank_accounts.splice(index, 1);
}

function pickQris(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    qrisError.value = file ? oversizedImageError(file, MAX_QRIS_MB) : null;

    if (qrisError.value) {
        input.value = '';

        return;
    }

    form.qris = file;
    form.remove_qris = false;
    qrisPreview.value = file ? URL.createObjectURL(file) : props.qrisUrl;
}

function removeQris(): void {
    form.qris = null;
    form.remove_qris = true;
    qrisPreview.value = null;
}

function save(): void {
    form.post(paymentSettings.update().url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.qris = null;
        },
    });
}
</script>

<template>
    <div class="flex flex-col gap-6">
        <Head title="Pengaturan Pembayaran" />

        <Heading
            variant="small"
            title="Pengaturan Pembayaran"
            description="Rekening dan QRIS yang ditampilkan ke siswa saat membeli kursus. Pembayaran dikonfirmasi manual di menu Pesanan."
        />

        <form
            class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_340px]"
            @submit.prevent="save"
        >
            <div class="flex min-w-0 flex-col gap-6">
                <!-- Bank accounts -->
                <section
                    class="overflow-hidden rounded-xl border bg-card"
                    aria-labelledby="bank-heading"
                >
                    <header
                        class="flex flex-wrap items-center justify-between gap-3 border-b p-4 sm:px-5"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <Landmark class="size-4.5" />
                            </span>
                            <div>
                                <h2 id="bank-heading" class="font-semibold">
                                    Rekening bank
                                </h2>
                                <p class="text-sm text-muted-foreground">
                                    Siswa memilih salah satu rekening saat
                                    transfer.
                                </p>
                            </div>
                        </div>
                        <span
                            class="rounded-full bg-muted px-2.5 py-0.5 text-xs font-semibold text-muted-foreground"
                        >
                            {{ form.bank_accounts.length }} /
                            {{ maxBankAccounts }} rekening
                        </span>
                    </header>

                    <div class="flex flex-col gap-3 p-4 sm:p-5">
                        <p
                            v-if="form.bank_accounts.length === 0"
                            class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
                        >
                            Belum ada rekening. Tambahkan minimal satu rekening
                            atau unggah QRIS agar siswa bisa membayar.
                        </p>

                        <div
                            v-for="(account, index) in form.bank_accounts"
                            :key="index"
                            class="flex gap-3 rounded-lg border bg-muted/20 p-3"
                        >
                            <span
                                class="mt-7 flex size-7 shrink-0 items-center justify-center rounded-full bg-background text-xs font-bold text-muted-foreground ring-1 ring-border"
                                aria-hidden="true"
                            >
                                {{ index + 1 }}
                            </span>
                            <div
                                class="grid min-w-0 flex-1 gap-3 sm:grid-cols-[minmax(0,0.7fr)_minmax(0,1fr)_minmax(0,1fr)]"
                            >
                                <div class="grid content-start gap-1.5">
                                    <Label :for="`bank-${index}`">Bank</Label>
                                    <Input
                                        :id="`bank-${index}`"
                                        v-model="account.bank"
                                        placeholder="BCA"
                                        required
                                    />
                                    <InputError
                                        :message="
                                            errors[
                                                `bank_accounts.${index}.bank`
                                            ]
                                        "
                                    />
                                </div>
                                <div class="grid content-start gap-1.5">
                                    <Label :for="`number-${index}`"
                                        >Nomor rekening</Label
                                    >
                                    <Input
                                        :id="`number-${index}`"
                                        v-model="account.account_number"
                                        inputmode="numeric"
                                        placeholder="1234567890"
                                        class="font-mono"
                                        required
                                    />
                                    <InputError
                                        :message="
                                            errors[
                                                `bank_accounts.${index}.account_number`
                                            ]
                                        "
                                    />
                                </div>
                                <div class="grid content-start gap-1.5">
                                    <Label :for="`name-${index}`"
                                        >Atas nama</Label
                                    >
                                    <Input
                                        :id="`name-${index}`"
                                        v-model="account.account_name"
                                        placeholder="PT Velocity Developer"
                                        required
                                    />
                                    <InputError
                                        :message="
                                            errors[
                                                `bank_accounts.${index}.account_name`
                                            ]
                                        "
                                    />
                                </div>
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="mt-5.5 shrink-0 text-muted-foreground hover:text-destructive"
                                :aria-label="`Hapus rekening ${index + 1}`"
                                @click="removeAccount(index)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>

                        <button
                            type="button"
                            class="flex items-center justify-center gap-2 rounded-lg border border-dashed p-3 text-sm font-medium text-muted-foreground transition-colors hover:border-primary/50 hover:text-foreground disabled:pointer-events-none disabled:opacity-60"
                            :disabled="
                                form.bank_accounts.length >= maxBankAccounts
                            "
                            @click="addAccount"
                        >
                            <Plus class="h-4 w-4" />
                            {{
                                form.bank_accounts.length >= maxBankAccounts
                                    ? `Sudah mencapai batas ${maxBankAccounts} rekening`
                                    : 'Tambah rekening'
                            }}
                        </button>
                    </div>
                </section>

                <!-- QRIS -->
                <section
                    class="overflow-hidden rounded-xl border bg-card"
                    aria-labelledby="qris-heading"
                >
                    <header
                        class="flex items-center gap-3 border-b p-4 sm:px-5"
                    >
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <QrCode class="size-4.5" />
                        </span>
                        <div>
                            <h2 id="qris-heading" class="font-semibold">
                                QRIS
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Gambar QRIS statis. Siswa memindainya lalu
                                mengisi nominal sendiri.
                            </p>
                        </div>
                    </header>

                    <div class="grid gap-5 p-4 sm:grid-cols-[176px_1fr] sm:p-5">
                        <label
                            for="qris"
                            class="group relative flex aspect-square cursor-pointer flex-col items-center justify-center gap-2 overflow-hidden rounded-xl border-2 border-dashed bg-muted/30 p-3 text-center transition-colors focus-within:border-primary hover:border-primary/50 max-sm:max-w-44"
                        >
                            <img
                                v-if="qrisPreview"
                                :src="qrisPreview"
                                alt="Pratinjau QRIS"
                                class="size-full rounded-md bg-white object-contain"
                            />
                            <template v-else>
                                <ImageUp
                                    class="size-7 text-muted-foreground transition-colors group-hover:text-primary"
                                />
                                <span class="text-xs font-medium"
                                    >Pilih gambar QRIS</span
                                >
                            </template>
                            <input
                                id="qris"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                class="sr-only"
                                @change="pickQris"
                            />
                        </label>

                        <div class="grid content-start gap-4">
                            <div class="grid gap-1.5">
                                <p class="text-sm font-medium">Gambar QRIS</p>
                                <p class="text-xs text-muted-foreground">
                                    JPG, PNG, atau WebP, maksimal
                                    {{ MAX_QRIS_MB }} MB. Klik kotak di samping
                                    untuk
                                    {{ qrisPreview ? 'mengganti' : 'memilih' }}
                                    gambar.
                                </p>
                                <p
                                    v-if="form.qris"
                                    class="text-xs font-medium text-primary"
                                >
                                    Gambar baru dipilih: {{ form.qris.name }}
                                </p>
                                <InputError
                                    :message="qrisError ?? form.errors.qris"
                                />
                                <div class="flex flex-wrap gap-2 pt-1">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        as-child
                                    >
                                        <label
                                            for="qris"
                                            class="cursor-pointer"
                                        >
                                            <ImageUp class="mr-2 h-4 w-4" />
                                            {{
                                                qrisPreview
                                                    ? 'Ganti gambar'
                                                    : 'Pilih gambar'
                                            }}
                                        </label>
                                    </Button>
                                    <Button
                                        v-if="qrisPreview"
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="removeQris"
                                    >
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Hapus QRIS
                                    </Button>
                                </div>
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="qris-name"
                                    >Nama merchant QRIS</Label
                                >
                                <Input
                                    id="qris-name"
                                    v-model="form.qris_name"
                                    placeholder="Velocity Developer"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Ditampilkan di bawah gambar agar siswa yakin
                                    membayar ke tujuan yang benar.
                                </p>
                                <InputError :message="form.errors.qris_name" />
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Payment terms -->
                <section
                    class="overflow-hidden rounded-xl border bg-card"
                    aria-labelledby="terms-heading"
                >
                    <header
                        class="flex items-center gap-3 border-b p-4 sm:px-5"
                    >
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <Clock class="size-4.5" />
                        </span>
                        <div>
                            <h2 id="terms-heading" class="font-semibold">
                                Ketentuan pembayaran
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Batas waktu bayar dan petunjuk untuk siswa.
                            </p>
                        </div>
                    </header>

                    <div class="grid gap-5 p-4 sm:p-5">
                        <div class="grid gap-1.5">
                            <Label for="expiry">Batas waktu bayar</Label>
                            <div class="relative w-full max-w-48">
                                <Input
                                    id="expiry"
                                    v-model="form.expiry_hours"
                                    type="number"
                                    min="1"
                                    max="720"
                                    class="pr-12"
                                    required
                                />
                                <span
                                    class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-muted-foreground"
                                    >jam</span
                                >
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Pesanan tanpa bukti bayar kedaluwarsa setelah
                                waktu ini (1–720 jam).
                            </p>
                            <InputError :message="form.errors.expiry_hours" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="instructions">
                                Petunjuk tambahan
                                <span class="font-normal text-muted-foreground"
                                    >(opsional)</span
                                >
                            </Label>
                            <Textarea
                                id="instructions"
                                v-model="form.instructions"
                                rows="3"
                                placeholder="Misal: Konfirmasi diproses Senin–Jumat 08.00–17.00 WIB."
                            />
                            <p class="text-xs text-muted-foreground">
                                Tampil di halaman invoice, di bawah cara
                                membayar.
                            </p>
                            <InputError :message="form.errors.instructions" />
                        </div>
                    </div>
                </section>
            </div>

            <!-- What students see on the invoice, updated as the admin types -->
            <aside
                class="hidden rounded-xl border bg-card xl:sticky xl:top-4 xl:block"
                aria-labelledby="preview-heading"
            >
                <header class="flex items-center gap-2 border-b p-4">
                    <Eye class="size-4 text-muted-foreground" />
                    <h2 id="preview-heading" class="text-sm font-semibold">
                        Pratinjau untuk siswa
                    </h2>
                </header>
                <div class="flex flex-col gap-3 p-4">
                    <p
                        v-if="!hasPaymentMethod"
                        class="rounded-lg bg-destructive/10 p-3 text-sm text-destructive"
                    >
                        Belum ada metode pembayaran. Siswa tidak bisa membeli
                        kursus berbayar.
                    </p>

                    <div
                        v-for="(account, index) in completeAccounts"
                        :key="index"
                        class="flex items-center gap-3 rounded-lg border p-3"
                    >
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-surface text-surface-foreground"
                        >
                            <Landmark class="size-4" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-muted-foreground">
                                {{ account.bank }}
                            </p>
                            <p class="truncate font-mono font-semibold">
                                {{ account.account_number }}
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                                a.n. {{ account.account_name }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="qrisPreview"
                        class="flex flex-col items-center gap-1.5 rounded-lg border border-dashed p-3 text-center"
                    >
                        <img
                            :src="qrisPreview"
                            alt=""
                            class="w-full max-w-36 rounded bg-white p-1"
                        />
                        <p v-if="form.qris_name" class="text-xs font-semibold">
                            {{ form.qris_name }}
                        </p>
                    </div>

                    <p
                        v-if="form.instructions.trim()"
                        class="rounded-lg border-l-4 border-primary bg-muted/50 p-2.5 text-xs whitespace-pre-line text-muted-foreground"
                    >
                        {{ form.instructions }}
                    </p>

                    <p
                        v-if="form.expiry_hours"
                        class="flex items-center gap-1.5 text-xs text-muted-foreground"
                    >
                        <Clock class="size-3.5" />
                        Bayar dalam {{ form.expiry_hours }} jam setelah pesanan
                        dibuat.
                    </p>
                </div>
            </aside>

            <!-- Save bar stays in reach on long pages -->
            <div
                class="sticky bottom-0 z-10 -mx-4 flex flex-wrap items-center gap-3 border-t bg-background/95 px-4 py-3 backdrop-blur xl:col-span-2"
            >
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan pengaturan' }}
                </Button>
                <p
                    v-if="form.recentlySuccessful"
                    class="flex items-center gap-1.5 text-sm text-muted-foreground"
                >
                    <CircleCheck class="size-4 text-primary" />
                    Tersimpan.
                </p>
                <p
                    v-else-if="form.isDirty"
                    class="text-sm text-muted-foreground"
                >
                    Ada perubahan yang belum disimpan.
                </p>
            </div>
        </form>
    </div>
</template>
