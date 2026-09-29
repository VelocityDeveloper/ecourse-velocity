<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Check,
    ChevronDown,
    ExternalLink,
    Inbox,
    MessageCircle,
    Search,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { getInitials } from '@/composables/useInitials';
import { formatDateTime } from '@/lib/course';
import applicationRoutes from '@/routes/admin/instructor-applications';
import instructorRoutes from '@/routes/admin/finance/instructors';
import type {
    InstructorApplicationRow,
    InstructorApplicationStatus,
    Paginated,
} from '@/types';

type StatusFilter = InstructorApplicationStatus | 'all';

const props = defineProps<{
    applications: Paginated<InstructorApplicationRow>;
    counts: Record<InstructorApplicationStatus, number>;
    filters: { status: StatusFilter; search?: string | null };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Instruktur', href: '/dasbor/instruktur' },
            { title: 'Pengajuan', href: '/dasbor/instruktur/pengajuan' },
        ],
    },
});

const STATUS_LABELS: Record<InstructorApplicationStatus, string> = {
    pending: 'Menunggu',
    approved: 'Disetujui',
    rejected: 'Ditolak',
};

const STATUS_CLASSES: Record<InstructorApplicationStatus, string> = {
    pending:
        'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
    approved:
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300',
};

const tabs = computed(() => [
    { value: 'pending', label: 'Menunggu', count: props.counts.pending },
    { value: 'approved', label: 'Disetujui', count: props.counts.approved },
    { value: 'rejected', label: 'Ditolak', count: props.counts.rejected },
    {
        value: 'all',
        label: 'Semua',
        count:
            props.counts.pending +
            props.counts.approved +
            props.counts.rejected,
    },
]);

const search = ref(props.filters.search ?? '');
const expanded = ref<number[]>(
    // A short queue of pending requests is easier to read fully opened.
    props.filters.status === 'pending' && props.applications.data.length <= 3
        ? props.applications.data.map((row) => row.id)
        : [],
);

function visit(query: Record<string, unknown>): void {
    router.get(
        applicationRoutes.index().url,
        {
            status: props.filters.status,
            search: search.value || undefined,
            ...query,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function toggle(id: number): void {
    expanded.value = expanded.value.includes(id)
        ? expanded.value.filter((open) => open !== id)
        : [...expanded.value, id];
}

function whatsappUrl(phone: string): string {
    const digits = phone.replace(/\D/g, '').replace(/^0/, '62');

    return `https://wa.me/${digits}`;
}

// The approve / reject dialog.
const decision = ref<{
    application: InstructorApplicationRow;
    action: 'approve' | 'reject';
} | null>(null);
const decisionForm = useForm({ note: '' });

function decide(
    application: InstructorApplicationRow,
    action: 'approve' | 'reject',
): void {
    decisionForm.reset();
    decisionForm.clearErrors();
    decision.value = { application, action };
}

function submitDecision(): void {
    if (!decision.value) {
        return;
    }

    const { application, action } = decision.value;
    const route =
        action === 'approve'
            ? applicationRoutes.approve(application.id)
            : applicationRoutes.reject(application.id);

    decisionForm.post(route.url, {
        preserveScroll: true,
        onSuccess: () => {
            decision.value = null;
        },
    });
}
</script>

<template>
    <div class="flex flex-col space-y-6">
        <Head title="Pengajuan Instruktur" />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Pengajuan Instruktur"
                description="Siswa yang ingin mengajar. Akun baru menjadi instruktur setelah Anda setujui."
            />
            <Link :href="instructorRoutes.index()">
                <Button variant="outline" size="sm">Semua instruktur</Button>
            </Link>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-1 rounded-lg border p-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    type="button"
                    class="flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="
                        filters.status === tab.value
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-muted'
                    "
                    @click="visit({ status: tab.value, page: undefined })"
                >
                    {{ tab.label }}
                    <span
                        class="rounded-full px-1.5 text-xs tabular-nums"
                        :class="
                            filters.status === tab.value
                                ? 'bg-primary-foreground/20'
                                : 'bg-muted'
                        "
                        >{{ tab.count }}</span
                    >
                </button>
            </div>

            <form
                class="relative w-full sm:w-72"
                @submit.prevent="visit({ page: undefined })"
            >
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Cari nama, email, keahlian..."
                    class="pl-9"
                />
            </form>
        </div>

        <div
            v-if="applications.data.length === 0"
            class="flex flex-col items-center gap-2 rounded-lg border border-dashed p-12 text-center"
        >
            <Inbox class="size-8 text-muted-foreground" />
            <p class="font-medium">
                {{
                    filters.status === 'pending'
                        ? 'Tidak ada pengajuan yang menunggu.'
                        : 'Belum ada pengajuan.'
                }}
            </p>
        </div>

        <ul v-else class="space-y-3">
            <li
                v-for="row in applications.data"
                :key="row.id"
                class="rounded-lg border"
            >
                <div class="flex flex-wrap items-center gap-4 p-4">
                    <Avatar class="size-10">
                        <AvatarImage
                            v-if="row.user.avatar"
                            :src="row.user.avatar"
                            :alt="row.user.name"
                        />
                        <AvatarFallback>{{
                            getInitials(row.user.name)
                        }}</AvatarFallback>
                    </Avatar>
                    <div class="min-w-0 flex-1">
                        <p class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold">{{
                                row.user.name
                            }}</span>
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="STATUS_CLASSES[row.status]"
                                >{{ STATUS_LABELS[row.status] }}</span
                            >
                        </p>
                        <p class="truncate text-sm text-muted-foreground">
                            {{ row.headline }} · {{ row.expertise }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ row.user.email }} · diajukan
                            {{ formatDateTime(row.created_at) }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <template v-if="row.status === 'pending'">
                            <Button
                                size="sm"
                                variant="outline"
                                class="text-red-600 hover:text-red-700"
                                @click="decide(row, 'reject')"
                            >
                                <X class="mr-1 size-4" />
                                Tolak
                            </Button>
                            <Button size="sm" @click="decide(row, 'approve')">
                                <Check class="mr-1 size-4" />
                                Setujui
                            </Button>
                        </template>
                        <Link
                            v-else-if="
                                row.status === 'approved' &&
                                row.user.role === 'instructor'
                            "
                            :href="instructorRoutes.show(row.user.slug)"
                        >
                            <Button size="sm" variant="outline"
                                >Lihat instruktur</Button
                            >
                        </Link>
                        <Button
                            size="sm"
                            variant="ghost"
                            :aria-expanded="expanded.includes(row.id)"
                            @click="toggle(row.id)"
                        >
                            Rincian
                            <ChevronDown
                                class="ml-1 size-4 transition-transform"
                                :class="{
                                    'rotate-180': expanded.includes(row.id),
                                }"
                            />
                        </Button>
                    </div>
                </div>

                <div
                    v-if="expanded.includes(row.id)"
                    class="grid gap-4 border-t bg-muted/30 p-4 text-sm md:grid-cols-2"
                >
                    <div>
                        <p class="text-muted-foreground">
                            Pengalaman & keahlian
                        </p>
                        <p class="whitespace-pre-line">{{ row.experience }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">
                            Kursus yang ingin dibuat
                        </p>
                        <p class="whitespace-pre-line">{{ row.motivation }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2 md:col-span-2">
                        <a
                            v-if="row.portfolio_url"
                            :href="row.portfolio_url"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Button size="sm" variant="outline">
                                <ExternalLink class="mr-1 size-4" />
                                Portofolio
                            </Button>
                        </a>
                        <a
                            v-if="row.phone"
                            :href="whatsappUrl(row.phone)"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Button size="sm" variant="outline">
                                <MessageCircle class="mr-1 size-4" />
                                {{ row.phone }}
                            </Button>
                        </a>
                    </div>
                    <p
                        v-if="row.status !== 'pending'"
                        class="text-muted-foreground md:col-span-2"
                    >
                        {{ STATUS_LABELS[row.status] }} oleh
                        {{ row.reviewer?.name ?? '-' }}
                        <template v-if="row.reviewed_at">
                            · {{ formatDateTime(row.reviewed_at) }}</template
                        >
                        <span
                            v-if="row.admin_note"
                            class="mt-1 block text-foreground"
                            >Catatan: {{ row.admin_note }}</span
                        >
                    </p>
                </div>
            </li>
        </ul>

        <div
            v-if="applications.last_page > 1"
            class="flex items-center justify-end gap-2"
        >
            <Button
                variant="outline"
                size="sm"
                :disabled="applications.current_page <= 1"
                @click="visit({ page: applications.current_page - 1 })"
            >
                Sebelumnya
            </Button>
            <span class="text-sm text-muted-foreground">
                Halaman {{ applications.current_page }} dari
                {{ applications.last_page }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="applications.current_page >= applications.last_page"
                @click="visit({ page: applications.current_page + 1 })"
            >
                Berikutnya
            </Button>
        </div>

        <Dialog
            :open="decision !== null"
            @update:open="(open) => !open && (decision = null)"
        >
            <DialogContent v-if="decision">
                <DialogHeader>
                    <DialogTitle>
                        {{
                            decision.action === 'approve'
                                ? 'Setujui pengajuan?'
                                : 'Tolak pengajuan?'
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        <template v-if="decision.action === 'approve'">
                            Akun {{ decision.application.user.name }} akan
                            menjadi instruktur dan bisa membuat kursus.
                        </template>
                        <template v-else>
                            {{ decision.application.user.name }} tetap siswa dan
                            dapat mengajukan kembali. Alasan di bawah
                            ditampilkan kepadanya.
                        </template>
                    </DialogDescription>
                </DialogHeader>

                <form
                    id="decision-form"
                    class="grid gap-2"
                    @submit.prevent="submitDecision"
                >
                    <Label for="note">
                        {{
                            decision.action === 'approve'
                                ? 'Catatan (opsional)'
                                : 'Alasan penolakan'
                        }}
                    </Label>
                    <Textarea
                        id="note"
                        v-model="decisionForm.note"
                        rows="3"
                        maxlength="1000"
                        :required="decision.action === 'reject'"
                        :placeholder="
                            decision.action === 'approve'
                                ? 'Pesan untuk instruktur baru'
                                : 'Contoh: lengkapi portofolio atau contoh materi'
                        "
                    />
                    <InputError :message="decisionForm.errors.note" />
                </form>

                <DialogFooter>
                    <Button variant="outline" @click="decision = null"
                        >Batal</Button
                    >
                    <Button
                        type="submit"
                        form="decision-form"
                        :variant="
                            decision.action === 'approve'
                                ? 'default'
                                : 'destructive'
                        "
                        :disabled="decisionForm.processing"
                    >
                        {{
                            decision.action === 'approve' ? 'Setujui' : 'Tolak'
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
