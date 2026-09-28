/**
 * Copy text to the clipboard, returning whether it worked.
 *
 * `navigator.clipboard` only exists on secure origins (HTTPS or localhost), so on a
 * plain-HTTP address such as the LAN dev server it is undefined. There we fall back
 * to selecting a hidden textarea and running the legacy copy command.
 */
export async function copyText(text: string): Promise<boolean> {
    if (window.isSecureContext && navigator.clipboard) {
        try {
            await navigator.clipboard.writeText(text);

            return true;
        } catch {
            // Permission denied: try the fallback below.
        }
    }

    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.top = '0';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();

    try {
        return document.execCommand('copy');
    } catch {
        return false;
    } finally {
        textarea.remove();
    }
}
