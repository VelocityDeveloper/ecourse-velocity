<?php

namespace App\Support;

use App\Models\SiteSetting;
use GdImage;
use Illuminate\Validation\ValidationException;

/**
 * The picture of digits the login and registration forms ask a person to type,
 * which keeps scripts from guessing passwords or signing up in bulk.
 *
 * The digits live in the session only; each code answers one submission, right
 * or wrong, so a solved code can't be replayed against many passwords.
 */
class Captcha
{
    /**
     * The forms that can ask for a code, each switchable under Pengaturan Situs → Keamanan.
     *
     * @var list<string>
     */
    public const array FORMS = ['login', 'register'];

    public const int LENGTH = 5;

    /**
     * How long a picture stays answerable after it was shown.
     */
    public const int TTL_MINUTES = 10;

    private const int WIDTH = 168;

    private const int HEIGHT = 56;

    /**
     * The setting that turns the code off for a form; unset means on.
     */
    public static function settingKey(string $form): string
    {
        return 'captcha_'.$form;
    }

    public static function enabled(string $form): bool
    {
        return SiteSetting::get(self::settingKey($form)) !== '0';
    }

    /**
     * Pick new digits for the form, remember them in the session, and draw them.
     */
    public static function issue(string $form): string
    {
        $code = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= (string) random_int(0, 9);
        }

        session()->put(self::sessionKey($form), [
            'code' => $code,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES)->getTimestamp(),
        ]);

        return self::image($code);
    }

    /**
     * Whether the answer matches the shown digits. The code is spent either way.
     */
    public static function check(string $form, mixed $answer): bool
    {
        /** @var array{code: string, expires_at: int}|null $issued */
        $issued = session()->pull(self::sessionKey($form));

        if (! is_array($issued) || $issued['expires_at'] < now()->getTimestamp()) {
            return false;
        }

        $answer = is_scalar($answer) ? preg_replace('/\D+/', '', (string) $answer) : '';

        return is_string($answer) && hash_equals($issued['code'], $answer);
    }

    /**
     * Stop the submission when the form asks for a code and the answer is wrong.
     *
     * @throws ValidationException
     */
    public static function validate(string $form, mixed $answer): void
    {
        if (! self::enabled($form)) {
            return;
        }

        if ($answer === null || $answer === '') {
            throw ValidationException::withMessages(['captcha' => __('Type the numbers shown in the picture.')]);
        }

        if (! self::check($form, $answer)) {
            throw ValidationException::withMessages(['captcha' => __('The security code is wrong. Type the numbers in the new picture.')]);
        }
    }

    /**
     * The digits as a PNG: each tilted and coloured on its own, over faint lines and dots.
     */
    public static function image(string $code): string
    {
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagefilledrectangle($image, 0, 0, self::WIDTH - 1, self::HEIGHT - 1, self::color($image, 246, 248, 251));

        for ($i = 0; $i < 7; $i++) {
            $shade = random_int(170, 215);
            imagesetthickness($image, random_int(1, 2));
            imageline(
                $image,
                random_int(0, self::WIDTH), random_int(0, self::HEIGHT),
                random_int(0, self::WIDTH), random_int(0, self::HEIGHT),
                self::color($image, $shade, $shade + random_int(-20, 20), $shade + random_int(-20, 30)),
            );
        }

        $slot = intdiv(self::WIDTH - 12, strlen($code));

        foreach (str_split($code) as $index => $digit) {
            $glyph = self::glyph($digit);
            $x = 6 + $index * $slot + random_int(0, max(0, $slot - imagesx($glyph)));
            $y = random_int(0, max(0, self::HEIGHT - imagesy($glyph)));

            self::stamp($image, $glyph, $x, $y, [random_int(20, 90), random_int(30, 80), random_int(60, 140)]);
        }

        // One thin line through the digits, in ink, so they can't be cut apart by colour alone.
        $ink = self::color($image, 70, 80, 110);
        $phase = random_int(0, 628) / 100;
        $middle = self::HEIGHT / 2 + random_int(-6, 6);

        for ($x = 0; $x < self::WIDTH - 1; $x++) {
            imageline($image, $x, (int) ($middle + 6 * sin($phase + $x / 18)), $x + 1, (int) ($middle + 6 * sin($phase + ($x + 1) / 18)), $ink);
        }

        for ($i = 0; $i < 140; $i++) {
            $shade = random_int(120, 200);
            imagesetpixel($image, random_int(0, self::WIDTH - 1), random_int(0, self::HEIGHT - 1), self::color($image, $shade, $shade, $shade));
        }

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    private static function sessionKey(string $form): string
    {
        return 'captcha.'.$form;
    }

    /**
     * One digit in GD's built-in font, enlarged and tilted, black on white.
     */
    private static function glyph(string $digit): GdImage
    {
        $small = imagecreatetruecolor(10, 16);
        imagefill($small, 0, 0, self::color($small, 255, 255, 255));
        imagestring($small, 5, 1, 0, $digit, self::color($small, 0, 0, 0));

        $width = random_int(22, 27);
        $height = random_int(34, 40);
        $large = imagecreatetruecolor($width, $height);
        imagecopyresampled($large, $small, 0, 0, 0, 0, $width, $height, 10, 16);

        $rotated = imagerotate($large, random_int(-25, 25), self::color($large, 255, 255, 255));

        return $rotated === false ? $large : $rotated;
    }

    /**
     * Paint the glyph's dark pixels onto the picture in the colour, keeping its soft edges.
     *
     * @param  array{int, int, int}  $rgb
     */
    private static function stamp(GdImage $image, GdImage $glyph, int $left, int $top, array $rgb): void
    {
        for ($x = 0; $x < imagesx($glyph); $x++) {
            for ($y = 0; $y < imagesy($glyph); $y++) {
                $tx = $left + $x;
                $ty = $top + $y;

                if ($tx >= self::WIDTH || $ty >= self::HEIGHT) {
                    continue;
                }

                $coverage = 1 - (imagecolorat($glyph, $x, $y) & 0xFF) / 255;

                if ($coverage < 0.1) {
                    continue;
                }

                $under = imagecolorat($image, $tx, $ty);
                $mix = fn (int $ink, int $shift): int => (int) round($ink * $coverage + (($under >> $shift) & 0xFF) * (1 - $coverage));

                imagesetpixel($image, $tx, $ty, self::color($image, $mix($rgb[0], 16), $mix($rgb[1], 8), $mix($rgb[2], 0)));
            }
        }
    }

    private static function color(GdImage $image, int $red, int $green, int $blue): int
    {
        $clamp = fn (int $value): int => max(0, min(255, $value));

        return (int) imagecolorallocate($image, $clamp($red), $clamp($green), $clamp($blue));
    }
}
