<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
            { title: 'Dasbor', href: '/dashboard' },
            { title: 'Pengaturan Pembayaran', href: '/admin/payment-settings' },
        ],
    },
});

const MAX_QRIS_BYTES = 3 * 1024 * 1024;

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

function addAccount(): void {
    form.bank_accounts.push({ bank: '', account_number: '', account_name: '' });
}

function removeAccount(index: number): void {
    form.bank_accounts.splice(index, 1);
}

function pickQris(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    qrisError.value = null;

    if (file && file.size > MAX_QRIS_BYTES) {
        qrisError.value = 'Ukuran gambar QRIS maksimal 3 MB.';

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
    <div class="flex flex-col space-y-6">
        <Head title="Pengaturan Pembayaran" />

        <Heading
            variant="small"
            title="Pengaturan Pembayaran"
            description="Rekening dan QRIS yang ditampilkan ke siswa saat membeli kursus. Pembayaran dikonfirmasi manual di menu Pesanan."
        />

        <form class="flex max-w-3xl flex-col gap-6" @submit.prevent="save">
            <section class="space-y-4 rounded-lg border p-4">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <h3 class="font-medium">Rekening bank</h3>
                        <p class="text-sm text-muted-foreground">
                            Maksimal {{ maxBankAccounts }} rekening.
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="form.bank_accounts.length >= maxBankAccounts"
                        @click="addAccount"
                    >
                        <Plus class="mr-2 h-4 w-4" />
                        Tambah rekening
                    </Button>
                </div>

                <p
                    v-if="form.bank_accounts.length === 0"
                    class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
                >
                    Belum ada rekening. Tambahkan minimal satu rekening atau
                    unggah QRIS agar siswa bisa membayar.
                </p>

                <div
                    v-for="(account, index) in form.bank_accounts"
                    :key="index"
                    class="grid gap-3 rounded-lg bg-muted/30 p-3 sm:grid-cols-[140px_1fr_1fr_auto]"
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
                            :message="errors[`bank_accounts.${index}.bank`]"
                        />
                    </div>
                    <div class="grid content-start gap-1.5">
                        <Label :for="`number-${index}`">Nomor rekening</Label>
                        <Input
                            :id="`number-${index}`"
                            v-model="account.account_number"
                            inputmode="numeric"
                            placeholder="1234567890"
                            required
                        />
                        <InputError
                            :message="
                                errors[`bank_accounts.${index}.account_number`]
                            "
                        />
                    </div>
                    <div class="grid content-start gap-1.5">
                        <Label :for="`name-${index}`">Atas nama</Label>
                        <Input
                            :id="`name-${index}`"
                            v-model="account.account_name"
                            placeholder="PT Velocity Developer"
                            required
                        />
                        <InputError
                            :message="
                                errors[`bank_accounts.${index}.account_name`]
                            "
                        />
                    </div>
                    <div class="flex items-start pt-6">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            aria-label="Hapus rekening"
                            @click="removeAccount(index)"
                        >
                            <Trash2 class="h-4 w-4 text-destructive" />
                        </Button>
                    </div>
                </div>
            </section>

            <section class="space-y-4 rounded-lg border p-4">
                <div>
                    <h3 class="font-medium">QRIS</h3>
                    <p class="text-sm text-muted-foreground">
                        Unggah gambar QRIS statis toko Anda. Siswa memindainya
                        lalu mengisi nominal sendiri.
                    </p>
                </div>
                <div class="flex flex-wrap items-start gap-4">
                    <div
                        class="flex size-40 items-center justify-center overflow-hidden rounded-lg border bg-muted text-xs text-muted-foreground"
                    >
                        <img
                            v-if="qrisPreview"
                            :src="qrisPreview"
                            alt="Pratinjau QRIS"
                            class="size-full object-contain"
                        />
                        <span v-else>Belum ada QRIS</span>
                    </div>
                    <div class="grid flex-1 content-start gap-2">
                        <Label for="qris">Gambar QRIS</Label>
                        <Input
                            id="qris"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            @change="pickQris"
                        />
                        <p class="text-xs text-muted-foreground">
                            JPG, PNG, atau WebP, maksimal 3 MB.
                        </p>
                        <InputError :message="qrisError ?? form.errors.qris" />
                        <Button
                            v-if="qrisPreview"
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="w-fit text-destructive"
                            @click="removeQris"
                        >
                            Hapus QRIS
                        </Button>
                        <Label for="qris-name" class="mt-2"
                            >Nama merchant QRIS</Label
                        >
                        <Input
                            id="qris-name"
                            v-model="form.qris_name"
                            placeholder="Velocity Developer"
                        />
                        <InputError :message="form.errors.qris_name" />
                    </div>
                </div>
            </section>

            <section class="grid gap-4 rounded-lg border p-4 sm:grid-cols-2">
                <div class="grid content-start gap-2 sm:col-span-2">
                    <Label for="instructions"
                        >Petunjuk tambahan (opsional)</Label
                    >
                    <Textarea
                        id="instructions"
                        v-model="form.instructions"
                        rows="3"
                        placeholder="Misal: Konfirmasi diproses Senin–Jumat 08.00–17.00 WIB."
                    />
                    <InputError :message="form.errors.instructions" />
                </div>
                <div class="grid content-start gap-2">
                    <Label for="expiry">Batas waktu bayar (jam)</Label>
                    <Input
                        id="expiry"
                        v-model="form.expiry_hours"
                        type="number"
                        min="1"
                        max="720"
                        required
                    />
                    <p class="text-xs text-muted-foreground">
                        Pesanan tanpa bukti bayar kedaluwarsa setelah waktu ini.
                    </p>
                    <InputError :message="form.errors.expiry_hours" />
                </div>
            </section>

            <div>
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan pengaturan' }}
                </Button>
            </div>
        </form>
    </div>
</template>
