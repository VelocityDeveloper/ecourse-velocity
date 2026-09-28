<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Str;

/**
 * Readable URL slugs, and the rules that keep the WordPress-style
 * /{category}/{course} permalinks from colliding with the site's own pages.
 */
final class Slug
{
    /**
     * The first URL segment of a course that has no category: /kursus/{course}.
     */
    public const string UNCATEGORIZED = 'kursus';

    /**
     * A valid slug: lowercase words joined by single dashes.
     */
    public const string PATTERN = '[a-z0-9]+(?:-[a-z0-9]+)*';

    /**
     * First URL segments already used by other pages, so no category may take them.
     *
     * @var list<string>
     */
    public const array RESERVED = [
        'admin', 'api', 'attachments', 'atur-ulang-kata-sandi', 'belajar', 'belajar-saya',
        'beli', 'blog', 'build', 'catalog', 'checkout', 'courses', 'daftar', 'dasbor', 'dashboard',
        'email', 'enrollments', 'instruktur', 'keluar', 'kursus', 'learn', 'learning',
        'lessons', 'login', 'logout', 'lupa-kata-sandi', 'masuk', 'my-courses', 'orders',
        'pendaftaran', 'pengaturan', 'questions', 'quizzes', 'register', 'reset-password',
        'reviews', 'sections', 'sertifikat', 'settings', 'storage', 'two-factor-challenge',
        'ulasan', 'up', 'user', 'users',
    ];

    /**
     * Turn a title into a slug; "UI/UX Design" becomes "ui-ux-design", not "uiux-design".
     */
    public static function from(string $text, string $fallback): string
    {
        $slug = Str::slug((string) preg_replace('~[/\\\\&+.:]+~', ' ', $text));

        return $slug === '' ? $fallback : $slug;
    }

    /**
     * Turn a title into a slug that is not taken yet, appending -2, -3, ... when needed.
     *
     * @param  Closure(string): bool  $taken
     */
    public static function unique(string $text, string $fallback, Closure $taken): string
    {
        $base = self::from($text, $fallback);
        $candidate = $base;
        $suffix = 2;

        while ($taken($candidate)) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
