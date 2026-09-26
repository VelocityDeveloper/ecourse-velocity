<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Derives the theme's brand tokens (resources/css/app.css) from one colour an
 * admin picks, for both light and dark mode.
 *
 * The relationships mirror the built-in orange palette: a darker "deep" shade
 * for gradient ends, a brighter decorative "brand", a pale accent for hovers.
 * The main colour's lightness is nudged until its text colour reaches a 4.5:1
 * contrast ratio, so buttons stay readable whatever colour is chosen.
 */
final class BrandPalette
{
    /**
     * The minimum contrast between a primary surface and the text on it (WCAG AA).
     */
    public const float MIN_CONTRAST = 4.5;

    /**
     * @param  array<string, string>  $light  token => hsl() value for light mode
     * @param  array<string, string>  $dark  token => hsl() value for dark mode
     */
    private function __construct(
        public readonly array $light,
        public readonly array $dark,
    ) {}

    /**
     * Build the palette from a "#rrggbb" colour.
     */
    public static function fromHex(string $hex): self
    {
        [$h, $s, $l] = self::parseHex($hex);

        $white = [0.0, 0.0, 100.0];
        $lightPrimary = self::untilContrast([$h, $s, $l], $white, -1.0);

        $darkForeground = [$h, 40.0, 8.0];
        $darkPrimary = self::untilContrast([self::hue($h + 6), min($s + 5, 100.0), max($l, 53.0)], $darkForeground, 1.0);

        $light = [
            'primary' => $lightPrimary,
            'primary-foreground' => $white,
            'primary-deep' => [self::hue($h - 6), $s * 0.95, max($lightPrimary[2] - 8, 8.0)],
            'brand' => [self::hue($h + 6), min($s + 5, 100.0), min(max($l + 11, 45.0), 60.0)],
            'accent' => [self::hue($h + 10), min($s + 10, 100.0), 96.0],
            'accent-foreground' => [$h, $s * 0.9, 28.0],
            'ring' => $lightPrimary,
            'sidebar-primary' => $lightPrimary,
            'sidebar-primary-foreground' => $white,
        ];

        $dark = [
            'primary' => $darkPrimary,
            'primary-foreground' => $darkForeground,
            'primary-deep' => [self::hue($h - 3), $s * 0.9, 40.0],
            'brand' => $darkPrimary,
            'accent' => [self::hue($h - 4), 25.0, 14.0],
            'accent-foreground' => [self::hue($h + 4), 95.0, 72.0],
            'ring' => $darkPrimary,
            'sidebar-primary' => $darkPrimary,
            'sidebar-primary-foreground' => $darkForeground,
        ];

        return new self(array_map(self::css(...), $light), array_map(self::css(...), $dark));
    }

    /**
     * The hue (0-360) of a "#rrggbb" colour, for artwork drawn in the brand colours.
     */
    public static function hueOf(string $hex): float
    {
        return self::parseHex($hex)[0];
    }

    /**
     * The CSS for the dark surface tokens (hero and footer) from a "#rrggbb" colour.
     *
     * Text on the surface is white, so the colour is darkened until white reaches
     * the minimum contrast; the lighter "muted" shade is for borders and tiles.
     */
    public static function surfaceCss(string $hex): string
    {
        [$h, $s, $l] = self::parseHex($hex);
        $surface = self::untilContrast([$h, $s, $l], [0.0, 0.0, 100.0], -1.0);
        $tokens = self::declarations([
            'surface' => self::css($surface),
            'surface-foreground' => self::css([0.0, 0.0, 100.0]),
            'surface-muted' => self::css([$h, $s * 0.8, min($surface[2] + 8, 40.0)]),
        ]);

        return ':root{'.$tokens.'}.dark{'.$tokens.'}';
    }

    /**
     * The CSS that overrides the default tokens; load it after app.css.
     */
    public function toCss(): string
    {
        return ':root{'.self::declarations($this->light).'}.dark{'.self::declarations($this->dark).'}';
    }

    /**
     * The WCAG contrast ratio between two HSL colours.
     *
     * @param  array{float, float, float}  $a
     * @param  array{float, float, float}  $b
     */
    public static function contrast(array $a, array $b): float
    {
        $la = self::luminance(...self::hslToRgb(...$a));
        $lb = self::luminance(...self::hslToRgb(...$b));

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /**
     * @return array{float, float, float}
     */
    private static function parseHex(string $hex): array
    {
        if (preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', $hex, $m) !== 1) {
            throw new InvalidArgumentException("Not a #rrggbb colour: {$hex}");
        }

        return self::rgbToHsl((int) hexdec($m[1]), (int) hexdec($m[2]), (int) hexdec($m[3]));
    }

    /**
     * Move the colour's lightness one step at a time until it contrasts enough with $text.
     *
     * @param  array{float, float, float}  $color
     * @param  array{float, float, float}  $text
     * @return array{float, float, float}
     */
    private static function untilContrast(array $color, array $text, float $step): array
    {
        while (self::contrast($color, $text) < self::MIN_CONTRAST && $color[2] + $step >= 0 && $color[2] + $step <= 100) {
            $color[2] += $step;
        }

        return $color;
    }

    /**
     * @param  array<string, string>  $tokens
     */
    private static function declarations(array $tokens): string
    {
        $css = '';

        foreach ($tokens as $token => $value) {
            $css .= "--{$token}:{$value};";
        }

        return $css;
    }

    /**
     * @param  array{float, float, float}  $hsl
     */
    private static function css(array $hsl): string
    {
        return sprintf('hsl(%s %s%% %s%%)', round($hsl[0]), round($hsl[1], 1), round($hsl[2], 1));
    }

    private static function hue(float $hue): float
    {
        return fmod($hue + 360.0, 360.0);
    }

    /**
     * @return array{float, float, float} hue 0-360, saturation and lightness 0-100
     */
    private static function rgbToHsl(int $red, int $green, int $blue): array
    {
        $r = $red / 255;
        $g = $green / 255;
        $b = $blue / 255;
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        $d = $max - $min;

        if ($d == 0.0) {
            return [0.0, 0.0, $l * 100];
        }

        $s = $d / (1 - abs(2 * $l - 1));
        $h = match ($max) {
            $r => fmod(($g - $b) / $d + 6, 6),
            $g => ($b - $r) / $d + 2,
            default => ($r - $g) / $d + 4,
        };

        return [$h * 60, $s * 100, $l * 100];
    }

    /**
     * @return array{float, float, float} red, green and blue 0-1
     */
    private static function hslToRgb(float $hue, float $saturation, float $lightness): array
    {
        $s = $saturation / 100;
        $l = $lightness / 100;
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($hue / 60, 2) - 1));
        $m = $l - $c / 2;

        [$r, $g, $b] = match (true) {
            $hue < 60 => [$c, $x, 0.0],
            $hue < 120 => [$x, $c, 0.0],
            $hue < 180 => [0.0, $c, $x],
            $hue < 240 => [0.0, $x, $c],
            $hue < 300 => [$x, 0.0, $c],
            default => [$c, 0.0, $x],
        };

        return [$r + $m, $g + $m, $b + $m];
    }

    private static function luminance(float $r, float $g, float $b): float
    {
        $channel = fn (float $v): float => $v <= 0.04045 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;

        return 0.2126 * $channel($r) + 0.7152 * $channel($g) + 0.0722 * $channel($b);
    }
}
