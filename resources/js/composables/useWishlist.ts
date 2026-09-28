import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { login } from '@/routes';
import catalogRoutes from '@/routes/catalog';

// Courses whose heart was just pressed, so a double click cannot send two requests.
const pending = ref(new Set<number>());

/**
 * The student's wishlist, read from the shared `wishlistCourseIds` prop.
 *
 * Guests may press the heart too; it takes them to the login page.
 * Students and instructors can use it; admins never see it, since they do not learn.
 * An instructor's own courses have no heart: see `canSave`.
 */
export function useWishlist() {
    const page = usePage();

    const ids = computed(() => new Set(page.props.wishlistCourseIds ?? []));
    const canUse = computed(() => {
        const role = page.props.auth.user?.role;

        return (
            role === undefined || role === 'student' || role === 'instructor'
        );
    });

    function canSave(instructorId: number | null | undefined): boolean {
        return (
            canUse.value &&
            (instructorId == null || instructorId !== page.props.auth.user?.id)
        );
    }

    function isWishlisted(courseId: number): boolean {
        return ids.value.has(courseId);
    }

    function isPending(courseId: number): boolean {
        return pending.value.has(courseId);
    }

    function toggle(course: { id: number; slug: string }): void {
        const courseId = course.id;

        if (!page.props.auth.user) {
            router.visit(login());

            return;
        }

        if (pending.value.has(courseId)) {
            return;
        }

        const route = isWishlisted(courseId)
            ? catalogRoutes.wishlist.destroy(course.slug)
            : catalogRoutes.wishlist.store(course.slug);

        pending.value.add(courseId);
        router.visit(route, {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => pending.value.delete(courseId),
        });
    }

    return { canUse, canSave, isWishlisted, isPending, toggle };
}
