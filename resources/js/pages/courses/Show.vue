<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    FileText,
    ImageIcon,
    Link2,
    ListTree,
    Tag,
    ToggleRight,
    Trash2,
} from '@lucide/vue';
import { computed, ref, useTemplateRef } from 'vue';
import CourseCurriculum from '@/components/CourseCurriculum.vue';
import CourseManageLayout from '@/components/CourseManageLayout.vue';
import CourseSettingsForm from '@/components/CourseSettingsForm.vue';
import type { CourseFormTab } from '@/components/CourseSettingsForm.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    formatDate,
    formatPrice,
    levelLabel,
    statusBadgeVariant,
    statusLabel,
} from '@/lib/course';
import courses from '@/routes/courses';
import enrollmentRoutes from '@/routes/enrollments';
import users from '@/routes/users';
import type {
    CourseDetail,
    CourseFormValues,
    CourseLevel,
    CourseOption,
    CourseStatus,
    LessonContentType,
    MovableItem,
    Section,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Kursus', href: '/dasbor/kursus' },
            { title: 'Detail Kursus', href: '#' },
        ],
    },
});

const props = defineProps<{
    course: CourseDetail;
    sections: Section[];
    statuses: CourseStatus[];
    contentTypes: LessonContentType[];
    movableLessons?: MovableItem[];
    movableQuizzes?: MovableItem[];
    form: CourseFormValues;
    categories: CourseOption[];
    instructors: CourseOption[];
    levels: CourseLevel[];
    isAdmin: boolean;
}>();

type TabKey = 'curriculum' | CourseFormTab;

// `slug` is the ?tab= value, so a tab can be linked to and survives a reload.
// `short` is the label on phones, where every tab shares one row.
const TABS: Array<{
    key: TabKey;
    slug: string;
    label: string;
    short: string;
    icon: unknown;
}> = [
    {
        key: 'curriculum',
        slug: 'kurikulum',
        label: 'Kurikulum',
        short: 'Kurikulum',
        icon: ListTree,
    },
    {
        key: 'info',
        slug: 'informasi',
        label: 'Informasi',
        short: 'Info',
        icon: FileText,
    },
    {
        key: 'price',
        slug: 'harga',
        label: 'Harga & Tingkat',
        short: 'Harga',
        icon: Tag,
    },
    {
        key: 'thumbnail',
        slug: 'sampul',
        label: 'Sampul',
        short: 'Sampul',
        icon: ImageIcon,
    },
    {
        key: 'status',
        slug: 'status',
        label: 'Status',
        short: 'Status',
        icon: ToggleRight,
    },
];

function tabFromUrl(): TabKey {
    const slug =
        typeof window === 'undefined'
            ? null
            : new URLSearchParams(window.location.search).get('tab');

    return TABS.find((tab) => tab.slug === slug)?.key ?? 'curriculum';
}

const activeTab = ref<TabKey>(tabFromUrl());
const settingsForm =
    useTemplateRef<InstanceType<typeof CourseSettingsForm>>('settingsForm');

const formTab = computed<CourseFormTab>(() =>
    activeTab.value === 'curriculum' ? 'info' : activeTab.value,
);

const tabsWithErrors = computed<Set<CourseFormTab>>(
    () => settingsForm.value?.tabsWithErrors ?? new Set<CourseFormTab>(),
);

function selectTab(key: TabKey) {
    activeTab.value = key;

    const url = new URL(window.location.href);
    const slug = TABS.find((tab) => tab.key === key)?.slug;

    if (key === 'curriculum') {
        url.searchParams.delete('tab');
    } else if (slug) {
        url.searchParams.set('tab', slug);
    }

    window.history.replaceState(window.history.state, '', url);
}

function deleteCourse() {
    router.delete(courses.destroy(props.course.slug).url);
}
</script>

<template>
    <CourseManageLayout :course="course">
        <Head :title="course.title" />

        <div class="flex flex-col space-y-6">
            <div
                class="flex flex-col gap-4 rounded-lg border p-4 sm:flex-row sm:items-center"
            >
                <img
                    v-if="course.thumbnail_url"
                    :src="course.thumbnail_url"
                    :alt="course.title"
                    class="aspect-video w-full rounded-md border object-cover sm:w-44"
                />
                <div
                    v-else
                    class="flex aspect-video w-full items-center justify-center rounded-md border bg-muted text-xs text-muted-foreground sm:w-44"
                >
                    Tanpa sampul
                </div>

                <div class="min-w-0 flex-1 space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge :variant="statusBadgeVariant(course.status)">
                            {{ statusLabel(course.status) }}
                        </Badge>
                        <Badge variant="secondary">
                            {{ levelLabel(course.level) }}
                        </Badge>
                        <Badge variant="outline">
                            {{ formatPrice(course.price) }}
                        </Badge>
                    </div>
                    <h1 class="text-lg leading-snug font-semibold">
                        {{ course.title }}
                    </h1>
                    <dl
                        class="flex flex-wrap gap-x-5 gap-y-1 text-sm text-muted-foreground"
                    >
                        <div class="flex gap-1">
                            <dt>Kategori:</dt>
                            <dd class="text-foreground">
                                {{ course.category?.name ?? '-' }}
                            </dd>
                        </div>
                        <div class="flex gap-1">
                            <dt>Instruktur:</dt>
                            <dd>
                                <Link
                                    v-if="course.instructor?.slug"
                                    :href="users.show(course.instructor.slug)"
                                    class="font-medium text-primary underline-offset-4 hover:underline"
                                >
                                    {{ course.instructor.name }}
                                </Link>
                                <template v-else>-</template>
                            </dd>
                        </div>
                        <div class="flex gap-1">
                            <dt>Siswa:</dt>
                            <dd>
                                <Link
                                    :href="
                                        enrollmentRoutes.index({
                                            query: { course_id: course.id },
                                        })
                                    "
                                    class="inline-flex items-center gap-1 font-medium text-primary underline-offset-4 hover:underline"
                                >
                                    <Link2
                                        class="size-3.5 shrink-0"
                                        aria-hidden="true"
                                    />
                                    {{ course.students_count }} terdaftar
                                </Link>
                            </dd>
                        </div>
                        <div class="flex gap-1">
                            <dt>Diperbarui:</dt>
                            <dd class="text-foreground">
                                {{ formatDate(course.updated_at) }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <Dialog v-if="course.can.delete">
                    <DialogTrigger as-child>
                        <Button variant="outline" size="sm" class="self-start">
                            <Trash2 class="mr-2 h-4 w-4 text-destructive" />
                            Hapus
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Hapus Kursus</DialogTitle>
                            <DialogDescription>
                                Yakin ingin menghapus
                                <strong>{{ course.title }}</strong
                                >? Tindakan ini tidak bisa dibatalkan.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary">Batal</Button>
                            </DialogClose>
                            <Button variant="destructive" @click="deleteCourse"
                                >Hapus</Button
                            >
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>

            <div
                v-if="course.can.update"
                class="flex border-b sm:gap-1"
                role="tablist"
                aria-label="Bagian kursus"
            >
                <template v-for="tab in TABS" :key="tab.key">
                    <button
                        :id="`tab-${tab.key}`"
                        type="button"
                        role="tab"
                        :aria-selected="activeTab === tab.key"
                        class="relative -mb-px flex min-w-0 flex-1 flex-col items-center gap-1 border-b-2 px-1 py-2 text-xs whitespace-nowrap transition-colors sm:flex-none sm:flex-row sm:gap-2 sm:px-3 sm:py-2.5 sm:text-sm"
                        :class="
                            activeTab === tab.key
                                ? 'border-primary font-medium text-foreground'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        "
                        @click="selectTab(tab.key)"
                    >
                        <component
                            :is="tab.icon"
                            class="size-4 shrink-0"
                            aria-hidden="true"
                        />
                        <span class="sm:hidden">{{ tab.short }}</span>
                        <span class="hidden sm:inline">{{ tab.label }}</span>
                        <span
                            v-if="
                                tab.key !== 'curriculum' &&
                                tabsWithErrors.has(tab.key)
                            "
                            class="absolute top-1.5 right-1/4 size-1.5 rounded-full bg-destructive sm:static"
                            aria-label="Ada isian yang salah"
                        />
                    </button>
                </template>
            </div>

            <div
                v-show="activeTab === 'curriculum'"
                role="tabpanel"
                aria-labelledby="tab-curriculum"
                class="space-y-4"
            >
                <div class="rounded-lg border p-4">
                    <h3 class="mb-2 text-sm font-medium">Deskripsi</h3>
                    <p
                        class="text-sm whitespace-pre-line text-muted-foreground"
                    >
                        {{ course.description || 'Belum ada deskripsi.' }}
                    </p>
                </div>

                <CourseCurriculum
                    :course-slug="course.slug"
                    :sections="sections"
                    :content-types="contentTypes"
                    :movable-lessons="movableLessons"
                    :movable-quizzes="movableQuizzes"
                    :can-edit="course.can.update"
                />
            </div>

            <div
                v-if="course.can.update"
                v-show="activeTab !== 'curriculum'"
                role="tabpanel"
                :aria-labelledby="`tab-${formTab}`"
            >
                <CourseSettingsForm
                    ref="settingsForm"
                    :tab="formTab"
                    :course="form"
                    :categories="categories"
                    :instructors="instructors"
                    :levels="levels"
                    :statuses="statuses"
                    :is-admin="isAdmin"
                    @update:tab="selectTab"
                />
            </div>
        </div>
    </CourseManageLayout>
</template>
