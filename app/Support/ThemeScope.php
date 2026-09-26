<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Where dark mode applies. The public site (home, catalogue, learning space, auth,
 * a student's settings) is always light; only the staff dashboard follows the
 * Settings → Tampilan choice. Mirrors the layout picked in resources/js/app.ts.
 */
final class ThemeScope
{
    public const string SITE = 'site';

    public const string APP = 'app';

    /**
     * Page components rendered in the public layout.
     *
     * @var list<string>
     */
    private const array SITE_PAGES = ['Welcome', 'catalog/*', 'my-courses/*', 'learn/*', 'learning/*', 'users/Show', 'auth/*'];

    public static function for(string $component, ?User $user): string
    {
        if (Str::is(self::SITE_PAGES, $component)) {
            return self::SITE;
        }

        // Settings sit in the dashboard for staff and on the public site for students.
        if (Str::is('settings/*', $component) && ($user === null || $user->isStudent())) {
            return self::SITE;
        }

        return self::APP;
    }
}
