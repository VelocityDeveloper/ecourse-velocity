<?php

use App\Support\BrandPalette;

test('the built-in orange reproduces the default tokens', function () {
    $palette = BrandPalette::fromHex('#cb450b');

    expect($palette->light['primary'])->toBe('hsl(18 89.7% 42%)')
        ->and($palette->dark['primary'])->toBe('hsl(24 94.7% 53%)');
});

test('text on the main colour stays readable in both modes', function (string $hex) {
    $palette = BrandPalette::fromHex($hex);

    $parse = function (string $css): array {
        preg_match('/hsl\(([\d.]+) ([\d.]+)% ([\d.]+)%\)/', $css, $m);

        return [(float) $m[1], (float) $m[2], (float) $m[3]];
    };

    expect(BrandPalette::contrast($parse($palette->light['primary']), $parse($palette->light['primary-foreground'])))
        ->toBeGreaterThanOrEqual(BrandPalette::MIN_CONTRAST - 0.05)
        ->and(BrandPalette::contrast($parse($palette->dark['primary']), $parse($palette->dark['primary-foreground'])))
        ->toBeGreaterThanOrEqual(BrandPalette::MIN_CONTRAST - 0.05);
})->with(['#cb450b', '#2563eb', '#facc15', '#22c55e', '#ffffff', '#000000', '#7c3aed', '#00ffff']);

test('the css only overrides the brand tokens', function () {
    $css = BrandPalette::fromHex('#2563eb')->toCss();

    expect($css)->toStartWith(':root{--primary:')
        ->toContain('.dark{--primary:')
        ->not->toContain('--background');
});

test('anything but #rrggbb is rejected', function () {
    BrandPalette::fromHex('blue');
})->throws(InvalidArgumentException::class);
