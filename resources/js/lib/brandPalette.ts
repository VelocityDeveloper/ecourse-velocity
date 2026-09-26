import { router } from '@inertiajs/vue3';

/**
 * Keep the brand colours picked in Admin → Pengaturan Situs applied after
 * client-side visits. The first page load gets them from app.blade.php;
 * `success` also covers a form that redirects back to the same page, which
 * does not fire `navigate`.
 */
export function initializeBrandPalette(): void {
    const apply = (paletteCss: string | null | undefined) => {
        const css = paletteCss ?? '';
        let style = document.getElementById('brand-palette');

        if (!style) {
            style = document.createElement('style');
            style.id = 'brand-palette';
            document.head.appendChild(style);
        }

        if (style.textContent !== css) {
            style.textContent = css;
        }
    };

    router.on('navigate', (event) =>
        apply(event.detail.page.props.branding?.paletteCss),
    );
    router.on('success', (event) =>
        apply(event.detail.page.props.branding?.paletteCss),
    );
}
