<?php

namespace App\Actions;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Strip anything the rich text editor should never have produced.
 *
 * The editor runs in the browser, so its output is user input: it is only
 * trusted once it has been through this allow list on the server.
 */
class SanitizeLessonContent
{
    /**
     * The elements the lesson editor is allowed to emit.
     *
     * @var list<string>
     */
    private const array ALLOWED_ELEMENTS = [
        'p', 'br', 'strong', 'em', 'u', 's', 'code', 'pre',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li', 'blockquote', 'hr',
    ];

    /**
     * Clean the given HTML, returning null when nothing meaningful is left.
     */
    public function __invoke(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $clean = trim($this->sanitizer()->sanitize($html));

        return $this->isEmpty($clean) ? null : $clean;
    }

    /**
     * Build the sanitizer with the lesson content allow list.
     */
    private function sanitizer(): HtmlSanitizer
    {
        $config = new HtmlSanitizerConfig;

        foreach (self::ALLOWED_ELEMENTS as $element) {
            $config = $config->allowElement($element);
        }

        $config = $config
            ->allowElement('a', ['href'])
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->forceAttribute('a', 'target', '_blank')
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->withMaxInputLength(200_000);

        return new HtmlSanitizer($config);
    }

    /**
     * Determine whether the cleaned HTML carries no text at all.
     */
    private function isEmpty(string $html): bool
    {
        return trim(strip_tags($html)) === '';
    }
}
