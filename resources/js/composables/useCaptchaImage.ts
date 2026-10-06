import { onBeforeUnmount, onMounted, ref } from 'vue';
import { captcha } from '@/routes';

/**
 * A fresh picture of digits for the form, as an object URL.
 *
 * The picture is fetched as an AJAX request rather than an <img src>, so the
 * server does not remember it as the "previous page" a failed login returns to.
 */
export function useCaptchaImage(form: 'login' | 'register') {
    const src = ref<string | null>(null);
    const failed = ref(false);
    let latest = 0;

    async function refresh(): Promise<void> {
        const request = ++latest;

        try {
            const response = await fetch(
                captcha.url(form, { query: { v: Date.now() } }),
                {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                },
            );

            if (!response.ok) {
                throw new Error(String(response.status));
            }

            const url = URL.createObjectURL(await response.blob());

            // A slower earlier request must not replace a newer picture.
            if (request !== latest) {
                URL.revokeObjectURL(url);

                return;
            }

            release();
            src.value = url;
            failed.value = false;
        } catch {
            if (request === latest) {
                failed.value = true;
            }
        }
    }

    function release(): void {
        if (src.value) {
            URL.revokeObjectURL(src.value);
        }
    }

    onMounted(refresh);
    onBeforeUnmount(release);

    return { src, failed, refresh };
}
