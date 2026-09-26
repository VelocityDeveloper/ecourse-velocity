<?php

use Illuminate\Support\Facades\App;

/**
 * Every string the app passes through __() so a new flash or validation
 * message cannot ship without its Indonesian line.
 *
 * @return list<string>
 */
function translatableAppStrings(): array
{
    $keys = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path()));

    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        preg_match_all("/__\(\s*'((?:\\\\'|[^'])*)'/", (string) file_get_contents($file->getPathname()), $matches);

        foreach ($matches[1] as $key) {
            $keys[] = str_replace("\\'", "'", $key);
        }
    }

    return array_values(array_unique($keys));
}

test('every translatable app string has an Indonesian line', function () {
    $lines = json_decode((string) file_get_contents(lang_path('id.json')), true);
    $missing = array_values(array_filter(
        translatableAppStrings(),
        // Group keys such as "validation.required" live in lang/id/*.php, not in id.json.
        fn (string $key) => preg_match('/^[a-z_]+\.[a-z_.]+$/', $key) !== 1 && ! array_key_exists($key, $lines),
    ));

    expect(translatableAppStrings())->not->toBeEmpty()
        ->and($missing)->toBe([]);
});

test('the Indonesian validation file covers every framework rule', function () {
    $english = require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php');
    $indonesian = require lang_path('id/validation.php');

    expect(array_diff(array_keys($english), array_keys($indonesian)))->toBe([]);
});

test('messages are shown in Indonesian when the locale is id', function () {
    App::setLocale('id');

    expect(__('Note saved.'))->toBe('Catatan tersimpan.')
        ->and(__('You are now enrolled in :course.', ['course' => 'Laravel 12 dari Nol']))->toBe('Anda sekarang terdaftar di Laravel 12 dari Nol.')
        ->and(__('validation.required', ['attribute' => __('validation.attributes.title')]))->toBe('Kolom judul wajib diisi.')
        ->and(__('auth.failed'))->toBe('Email atau kata sandi tidak cocok dengan data kami.');
});
