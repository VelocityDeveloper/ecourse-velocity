<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BadgeCheck,
    BookOpen,
    Clock,
    CreditCard,
    Landmark,
    QrCode,
    ShieldCheck,
    ShoppingCart,
    UserRound,
} from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { formatRupiah, levelLabel, paymentMethodLabel } from '@/lib/course';
import catalogRoutes from '@/routes/catalog';
import orderRoutes from '@/routes/orders';
import type { CourseLevel, PaymentMethod } from '@/types';

const props = defineProps<{
    course: {
        id: number;
        title: string;
        thumbnail_url: string | null;
        level: CourseLevel;
        category: { id: number; name: string } | null;
        instructor: { id: number; name: string } | null;
        price: number;
    };
    buyer: { name: string; email: string };
    methods: PaymentMethod[];
    expiryHours: number;
}>();

const form = useForm<{ payment_method: PaymentMethod | '' }>({
    payment_method: props.methods[0] ?? '',
});

const METHOD_HINTS: Record<PaymentMethod, string> = {
    bank_transfer:
        'Transfer ke rekening kami lewat m-banking, ATM, atau teller.',
    qris: 'Pindai kode QRIS dengan aplikasi bank atau e-wallet.',
};

function placeOrder(): void {
    form.post(orderRoutes.store(props.course.id).url);
}
</script>

<template>
    <div
        class="mx-auto flex w-full max-w-site flex-col gap-6 px-4 py-10 sm:px-6"
    >
        <Head :title="`Checkout · ${course.title}`" />

        <Link
            :href="catalogRoutes.show(course.id)"
            class="flex w-fit items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
        >
            <ArrowLeft class="h-4 w-4" />
            Kembali ke kursus
        </Link>

        <div class="flex items-start gap-4">
            <span
                class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-lg shadow-primary/25"
            >
                <ShoppingCart class="size-6" />
            </span>
            <div class="space-y-1">
                <p class="text-sm font-semibold text-primary">Checkout</p>
                <h1 class="text-3xl font-bold tracking-tight">
                    Periksa pesanan Anda
                </h1>
                <p class="text-muted-foreground">
                    Invoice dibuat setelah Anda menekan
                    <span class="font-semibold text-foreground"
                        >Buat pesanan</span
                    >.
                </p>
            </div>
        </div>

        <form
            class="grid items-start gap-6 lg:grid-cols-[1fr_360px]"
            @submit.prevent="placeOrder"
        >
            <div class="flex flex-col gap-6">
                <section
                    class="flex gap-4 rounded-2xl border bg-gradient-to-br from-primary/5 via-card to-card p-5 shadow-sm"
                >
                    <div
                        class="aspect-video w-36 shrink-0 overflow-hidden rounded-xl bg-muted shadow-sm sm:w-48"
                    >
                        <img
                            v-if="course.thumbnail_url"
                            :src="course.thumbnail_url"
                            :alt="course.title"
                            class="size-full object-cover"
                        />
                        <div
                            v-else
                            class="flex size-full items-center justify-center text-muted-foreground"
                        >
                            <BookOpen class="size-8" />
                        </div>
                    </div>
                    <div class="min-w-0 space-y-1">
                        <p
                            v-if="course.category"
                            class="text-[11px] font-bold tracking-wider text-primary uppercase"
                        >
                            {{ course.category.name }}
                        </p>
                        <p class="font-bold">{{ course.title }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ levelLabel(course.level) }}
                            <template v-if="course.instructor">
                                · {{ course.instructor.name }}</template
                            >
                        </p>
                        <p class="text-lg font-bold text-primary">
                            {{ formatRupiah(course.price) }}
                        </p>
                    </div>
                </section>

                <section
                    class="space-y-4 rounded-2xl border bg-card p-5 shadow-sm"
                >
                    <h2 class="flex items-center gap-2 text-lg font-bold">
                        <span
                            class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <CreditCard class="size-4" />
                        </span>
                        Metode pembayaran
                    </h2>
                    <p
                        v-if="methods.length === 0"
                        class="rounded-xl border border-dashed p-4 text-sm text-muted-foreground"
                    >
                        Metode pembayaran belum tersedia. Silakan hubungi kami.
                    </p>
                    <div
                        v-else
                        class="grid gap-3 sm:grid-cols-2"
                        role="radiogroup"
                    >
                        <label
                            v-for="method in methods"
                            :key="method"
                            class="relative flex cursor-pointer items-start gap-3 rounded-xl border-2 p-4 transition-all"
                            :class="
                                form.payment_method === method
                                    ? 'border-primary bg-primary/5 shadow-md shadow-primary/10'
                                    : 'border-border hover:border-primary/40 hover:bg-muted/40'
                            "
                        >
                            <input
                                v-model="form.payment_method"
                                type="radio"
                                name="payment_method"
                                :value="method"
                                class="sr-only"
                            />
                            <span
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl"
                                :class="
                                    form.payment_method === method
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-muted text-muted-foreground'
                                "
                            >
                                <Landmark
                                    v-if="method === 'bank_transfer'"
                                    class="size-5"
                                />
                                <QrCode v-else class="size-5" />
                            </span>
                            <span class="min-w-0">
                                <span class="block font-semibold">{{
                                    paymentMethodLabel(method)
                                }}</span>
                                <span
                                    class="block text-sm text-muted-foreground"
                                    >{{ METHOD_HINTS[method] }}</span
                                >
                            </span>
                            <BadgeCheck
                                v-if="form.payment_method === method"
                                class="absolute top-3 right-3 size-5 text-primary"
                            />
                        </label>
                    </div>
                    <InputError :message="form.errors.payment_method" />
                </section>

                <section
                    class="flex items-center gap-4 rounded-2xl border bg-card p-5 shadow-sm"
                >
                    <span
                        class="flex size-11 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground"
                    >
                        <UserRound class="size-5" />
                    </span>
                    <div class="min-w-0 text-sm">
                        <p class="text-muted-foreground">Ditagihkan kepada</p>
                        <p class="font-semibold">{{ buyer.name }}</p>
                        <p class="truncate text-muted-foreground">
                            {{ buyer.email }}
                        </p>
                    </div>
                </section>
            </div>

            <aside
                class="overflow-hidden rounded-2xl border bg-card shadow-sm lg:sticky lg:top-24"
            >
                <div class="h-1.5 bg-primary" />
                <div class="flex flex-col gap-4 p-6">
                    <h2 class="font-bold">Ringkasan</h2>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Harga kursus</dt>
                            <dd class="tabular-nums">
                                {{ formatRupiah(course.price) }}
                            </dd>
                        </div>
                        <div
                            class="flex items-center justify-between gap-4 border-t border-dashed pt-3"
                        >
                            <dt class="font-semibold">Total</dt>
                            <dd
                                class="text-2xl font-bold text-primary tabular-nums"
                            >
                                {{ formatRupiah(course.price) }}
                            </dd>
                        </div>
                    </dl>
                    <Button
                        type="submit"
                        size="lg"
                        class="h-12 w-full rounded-xl text-base font-bold shadow-lg shadow-primary/25"
                        :disabled="form.processing || !form.payment_method"
                    >
                        {{
                            form.processing
                                ? 'Membuat invoice...'
                                : 'Buat pesanan'
                        }}
                    </Button>
                    <ul
                        class="space-y-2 rounded-xl bg-muted/50 p-3 text-xs text-muted-foreground"
                    >
                        <li class="flex items-start gap-2">
                            <Clock class="size-4 shrink-0 text-primary" />
                            Bayar dalam {{ expiryHours }} jam setelah invoice
                            dibuat.
                        </li>
                        <li class="flex items-start gap-2">
                            <ShieldCheck class="size-4 shrink-0 text-primary" />
                            Akses kursus terbuka setelah pembayaran dikonfirmasi
                            admin.
                        </li>
                    </ul>
                </div>
            </aside>
        </form>
    </div>
</template>
