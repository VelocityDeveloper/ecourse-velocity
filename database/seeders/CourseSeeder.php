<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\BrandPalette;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseSeeder extends Seeder
{
    /**
     * The hue of the dark navy surface behind the header, hero and footer.
     */
    private const float SURFACE_HUE = 223.0;

    private const int THUMBNAIL_WIDTH = 1280;

    private const int THUMBNAIL_HEIGHT = 720;

    /**
     * The published courses shown on the public site, shared out between instructors.
     *
     * @var list<array{title: string, category: string, level: string, price: string, description: string}>
     */
    private const array CATALOGUE = [
        ['title' => 'Laravel 12 dari Nol', 'category' => 'Web Development', 'level' => 'beginner', 'price' => '0.00', 'description' => 'Pahami routing, controller, Eloquent, dan validasi sambil membangun aplikasi Laravel pertamamu dari awal sampai deploy.'],
        ['title' => 'Vue 3 & Inertia untuk Aplikasi Modern', 'category' => 'Web Development', 'level' => 'intermediate', 'price' => '149000.00', 'description' => 'Bangun single-page app tanpa API terpisah: komponen Vue, form, validasi, dan navigasi dengan Inertia.'],
        ['title' => 'REST API dengan Laravel Sanctum', 'category' => 'Web Development', 'level' => 'advanced', 'price' => '199000.00', 'description' => 'Rancang API yang rapi: resource, autentikasi token, versioning, rate limiting, dan pengujian.'],
        ['title' => 'Flutter untuk Pemula', 'category' => 'Mobile Development', 'level' => 'beginner', 'price' => '99000.00', 'description' => 'Membuat aplikasi Android dan iOS dari satu basis kode dengan widget, state, dan navigasi Flutter.'],
        ['title' => 'Kotlin Android Lanjutan', 'category' => 'Mobile Development', 'level' => 'advanced', 'price' => '249000.00', 'description' => 'Arsitektur MVVM, coroutines, Room, dan Jetpack Compose untuk aplikasi Android skala produksi.'],
        ['title' => 'Python untuk Analisis Data', 'category' => 'Data Science', 'level' => 'beginner', 'price' => '0.00', 'description' => 'Mengolah data dengan pandas, membuat visualisasi, dan menarik insight dari dataset nyata.'],
        ['title' => 'Machine Learning Praktis', 'category' => 'Data Science', 'level' => 'intermediate', 'price' => '299000.00', 'description' => 'Regresi, klasifikasi, dan evaluasi model dengan scikit-learn, lengkap dengan studi kasus.'],
        ['title' => 'Dasar UI Design dengan Figma', 'category' => 'UI/UX Design', 'level' => 'beginner', 'price' => '79000.00', 'description' => 'Tipografi, warna, layout, dan komponen; dari wireframe sampai prototipe interaktif di Figma.'],
        ['title' => 'Docker & CI/CD untuk Developer', 'category' => 'DevOps', 'level' => 'intermediate', 'price' => '179000.00', 'description' => 'Kemas aplikasi dengan Docker lalu otomatiskan test dan deploy menggunakan pipeline CI/CD.'],
    ];

    /**
     * Seed the published catalogue plus one unpublished course per status for
     * each instructor, each with a thumbnail.
     */
    public function run(): void
    {
        $instructors = User::query()->where('role', User::ROLE_INSTRUCTOR)->orderBy('id')->get();

        if ($instructors->isEmpty() || Category::query()->doesntExist()) {
            return;
        }

        foreach (self::CATALOGUE as $index => $entry) {
            $this->publishedCourse($entry, $instructors[$index % $instructors->count()]);
        }

        foreach ($instructors as $instructor) {
            foreach ([Course::STATUS_DRAFT, Course::STATUS_PENDING, Course::STATUS_ARCHIVED] as $status) {
                $this->courseFor($instructor, $status);
            }
        }

        Course::query()
            ->whereNull('thumbnail_path')
            ->with('category:id,name')
            ->each(function (Course $course): void {
                $course->update(['thumbnail_path' => $this->makeThumbnail($course)]);
            });
    }

    /**
     * Create a published catalogue course, or leave it alone when it already exists.
     *
     * @param  array{title: string, category: string, level: string, price: string, description: string}  $entry
     */
    private function publishedCourse(array $entry, User $instructor): Course
    {
        $category = Category::query()->where('name', $entry['category'])->value('id')
            ?? Category::query()->inRandomOrder()->value('id');

        return Course::query()->firstOrCreate(
            ['slug' => Str::slug($entry['title'])],
            [
                'title' => $entry['title'],
                'description' => $entry['description'],
                'category_id' => $category,
                'instructor_id' => $instructor->id,
                'price' => $entry['price'],
                'level' => $entry['level'],
                'status' => Course::STATUS_PUBLISHED,
            ],
        );
    }

    /**
     * Get the instructor's course for the given status, creating it when missing.
     */
    private function courseFor(User $instructor, string $status): Course
    {
        $existing = Course::query()
            ->where('instructor_id', $instructor->id)
            ->where('status', $status)
            ->first();

        if ($existing instanceof Course) {
            return $existing;
        }

        return Course::factory()
            ->ownedBy($instructor)
            ->state([
                'status' => $status,
                'category_id' => Category::query()->inRandomOrder()->value('id'),
            ])
            ->create();
    }

    /**
     * Draw a placeholder thumbnail for the course and store it on disk.
     *
     * Every thumbnail shares the brand look (the dark surface colour, a glow in
     * the main colour, category eyebrow and title), so catalogue cards read as one set without
     * any network access.
     */
    private function makeThumbnail(Course $course): ?string
    {
        $image = imagecreatetruecolor(self::THUMBNAIL_WIDTH, self::THUMBNAIL_HEIGHT);

        if ($image === false) {
            return null;
        }

        imagealphablending($image, true);

        $this->paintGradient($image);
        $this->paintAccents($image);
        $this->paintText($image, $course);

        ob_start();
        imagejpeg($image, null, 88);
        $contents = ob_get_clean();
        imagedestroy($image);

        if ($contents === false) {
            return null;
        }

        $path = Course::THUMBNAIL_DIRECTORY.'/seed-'.$course->slug.'.jpg';
        Storage::disk(Course::THUMBNAIL_DISK)->put($path, $contents);

        return $path;
    }

    /**
     * Fill the canvas with a diagonal gradient from warm charcoal to near black.
     */
    private function paintGradient(\GdImage $image): void
    {
        $surface = self::SURFACE_HUE;
        [$fromR, $fromG, $fromB] = $this->hslToRgb($surface, 0.45, 0.16);
        [$toR, $toG, $toB] = $this->hslToRgb($surface, 0.50, 0.07);
        $span = self::THUMBNAIL_WIDTH + self::THUMBNAIL_HEIGHT;

        for ($x = 0; $x < $span; $x++) {
            $ratio = $x / ($span - 1);

            $color = $this->color(
                $image,
                $this->blendChannel($fromR, $toR, $ratio),
                $this->blendChannel($fromG, $toG, $ratio),
                $this->blendChannel($fromB, $toB, $ratio),
            );

            imageline($image, $x, 0, $x - self::THUMBNAIL_HEIGHT, self::THUMBNAIL_HEIGHT, $color);
        }
    }

    /**
     * Add the brand glow, a fading dot grid and the orange accent bar.
     */
    private function paintAccents(\GdImage $image): void
    {
        [$r, $g, $b] = $this->hslToRgb($this->brandHue(), 0.95, 0.53);

        // Soft glow in the top right corner, built from stacked translucent discs.
        for ($step = 0; $step < 40; $step++) {
            $diameter = 1000 - $step * 22;
            $color = imagecolorallocatealpha($image, $r, $g, $b, 124);

            if ($color !== false) {
                imagefilledellipse($image, self::THUMBNAIL_WIDTH - 120, 60, $diameter, $diameter, $color);
            }
        }

        // Dot grid that fades out towards the bottom left.
        for ($y = 32; $y < self::THUMBNAIL_HEIGHT; $y += 40) {
            for ($x = 32; $x < self::THUMBNAIL_WIDTH; $x += 40) {
                $fade = 1 - min(1.0, hypot(self::THUMBNAIL_WIDTH - $x, $y) / 1100);

                if ($fade <= 0) {
                    continue;
                }

                $dot = imagecolorallocatealpha($image, 255, 255, 255, max(0, min(127, (int) round(127 - 40 * $fade))));

                if ($dot !== false) {
                    imagefilledellipse($image, $x, $y, 4, 4, $dot);
                }
            }
        }

        imagefilledrectangle($image, 64, 150, 64 + 72, 157, $this->color($image, $r, $g, $b));
    }

    /**
     * Write the category, title and level onto the canvas.
     */
    private function paintText(\GdImage $image, Course $course): void
    {
        $font = $this->findFont();
        $white = $this->color($image, 255, 255, 255);
        [$r, $g, $b] = $this->hslToRgb($this->brandHue(), 0.95, 0.65);
        $orange = $this->color($image, $r, $g, $b);
        $muted = imagecolorallocatealpha($image, 255, 255, 255, 50) ?: $white;

        if ($font === null) {
            imagestring($image, 5, 64, 64, $course->title, $white);

            return;
        }

        $categoryName = $course->category()->value('name');
        $category = is_string($categoryName) ? $categoryName : 'Uncategorised';
        imagettftext($image, 28, 0, 64, 118, $orange, $font, strtoupper($category));

        $lines = $this->wrapTitle($course->title, $font, 62, self::THUMBNAIL_WIDTH - 200);
        $y = 262;

        foreach ($lines as $line) {
            imagettftext($image, 62, 0, 64, $y, $white, $font, $line);
            $y += 84;
        }

        $level = match ($course->level) {
            'beginner' => 'Pemula',
            'intermediate' => 'Menengah',
            'advanced' => 'Lanjutan',
            default => ucfirst($course->level),
        };
        imagettftext($image, 26, 0, 64, self::THUMBNAIL_HEIGHT - 64, $muted, $font, $level);
    }

    /**
     * The hue of the site's main colour (Admin → Pengaturan Situs → Warna), orange by default.
     */
    private function brandHue(): float
    {
        $color = SiteSetting::get(SiteSetting::PRIMARY_COLOR);

        return $color === null ? 24.0 : BrandPalette::hueOf($color);
    }

    /**
     * Break the title into lines that fit inside the given pixel width.
     *
     * @return list<string>
     */
    private function wrapTitle(string $title, string $font, int $size, int $maxWidth): array
    {
        $lines = [];
        $current = '';

        foreach (explode(' ', $title) as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;
            $box = imagettfbbox($size, 0, $font, $candidate);

            if ($box !== false && ($box[2] - $box[0]) > $maxWidth && $current !== '') {
                $lines[] = $current;
                $current = $word;

                continue;
            }

            $current = $candidate;
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return array_slice($lines, 0, 3);
    }

    /**
     * Find a TrueType font to draw with, if the machine has one.
     */
    private function findFont(): ?string
    {
        $candidates = [
            'C:/Windows/Fonts/segoeuib.ttf',
            'C:/Windows/Fonts/arialbd.ttf',
            'C:/Windows/Fonts/arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/dejavu-sans-fonts/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Allocate a colour, falling back to black when the palette is full.
     *
     * @param  int<0, 255>  $red
     * @param  int<0, 255>  $green
     * @param  int<0, 255>  $blue
     */
    private function color(\GdImage $image, int $red, int $green, int $blue): int
    {
        $color = imagecolorallocate($image, $red, $green, $blue);

        return $color === false ? 0 : $color;
    }

    /**
     * Convert an HSL colour to its RGB components.
     *
     * @return array{int<0, 255>, int<0, 255>, int<0, 255>}
     */
    private function hslToRgb(float $hue, float $saturation, float $lightness): array
    {
        $chroma = (1 - abs(2 * $lightness - 1)) * $saturation;
        $second = $chroma * (1 - abs(fmod($hue / 60, 2) - 1));
        $match = $lightness - $chroma / 2;

        [$red, $green, $blue] = match (true) {
            $hue < 60 => [$chroma, $second, 0.0],
            $hue < 120 => [$second, $chroma, 0.0],
            $hue < 180 => [0.0, $chroma, $second],
            $hue < 240 => [0.0, $second, $chroma],
            $hue < 300 => [$second, 0.0, $chroma],
            default => [$chroma, 0.0, $second],
        };

        return [
            $this->toChannel($red + $match),
            $this->toChannel($green + $match),
            $this->toChannel($blue + $match),
        ];
    }

    /**
     * Blend two colour channels, clamped to a single byte.
     *
     * @param  int<0, 255>  $from
     * @param  int<0, 255>  $to
     * @return int<0, 255>
     */
    private function blendChannel(int $from, int $to, float $ratio): int
    {
        return max(0, min(255, (int) round($from + ($to - $from) * $ratio)));
    }

    /**
     * Turn a 0..1 colour component into a byte, guarding against rounding drift.
     *
     * @return int<0, 255>
     */
    private function toChannel(float $component): int
    {
        return max(0, min(255, (int) round($component * 255)));
    }
}
