<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseSeeder extends Seeder
{
    use WithoutModelEvents;

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
     * The colours are derived from the slug, so a course always gets the same
     * image without any network access.
     */
    private function makeThumbnail(Course $course): ?string
    {
        $image = imagecreatetruecolor(self::THUMBNAIL_WIDTH, self::THUMBNAIL_HEIGHT);

        if ($image === false) {
            return null;
        }

        $hue = (float) (crc32($course->slug) % 360);

        $this->paintGradient($image, $hue);
        $this->paintAccents($image, $hue);
        $this->paintText($image, $course);

        ob_start();
        imagejpeg($image, null, 85);
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
     * Fill the canvas with a vertical two tone gradient.
     */
    private function paintGradient(\GdImage $image, float $hue): void
    {
        [$fromR, $fromG, $fromB] = $this->hslToRgb($hue, 0.58, 0.46);
        [$toR, $toG, $toB] = $this->hslToRgb(fmod($hue + 45.0, 360.0), 0.62, 0.20);

        for ($y = 0; $y < self::THUMBNAIL_HEIGHT; $y++) {
            $ratio = $y / (self::THUMBNAIL_HEIGHT - 1);

            $color = $this->color(
                $image,
                $this->blendChannel($fromR, $toR, $ratio),
                $this->blendChannel($fromG, $toG, $ratio),
                $this->blendChannel($fromB, $toB, $ratio),
            );

            imageline($image, 0, $y, self::THUMBNAIL_WIDTH, $y, $color);
        }
    }

    /**
     * Scatter a few translucent circles so the image is not a flat wash.
     */
    private function paintAccents(\GdImage $image, float $hue): void
    {
        [$r, $g, $b] = $this->hslToRgb(fmod($hue + 180.0, 360.0), 0.70, 0.72);

        $circles = [
            [1060, 170, 460],
            [1210, 560, 300],
            [180, 640, 240],
        ];

        foreach ($circles as [$x, $y, $diameter]) {
            $color = imagecolorallocatealpha($image, $r, $g, $b, 105);

            if ($color !== false) {
                imagefilledellipse($image, $x, $y, $diameter, $diameter, $color);
            }
        }
    }

    /**
     * Write the category, title and status onto the canvas.
     */
    private function paintText(\GdImage $image, Course $course): void
    {
        $font = $this->findFont();
        $white = $this->color($image, 255, 255, 255);

        if ($font === null) {
            imagestring($image, 5, 60, 60, strtoupper($course->title), $white);

            return;
        }

        $categoryName = $course->category()->value('name');
        $category = is_string($categoryName) ? $categoryName : 'Uncategorised';
        imagettftext($image, 26, 0, 64, 96, $white, $font, strtoupper($category));

        $lines = $this->wrapTitle($course->title, $font, 58, self::THUMBNAIL_WIDTH - 128);
        $y = self::THUMBNAIL_HEIGHT - 150 - (count($lines) - 1) * 74;

        foreach ($lines as $line) {
            imagettftext($image, 58, 0, 64, $y, $white, $font, $line);
            $y += 74;
        }

        imagettftext($image, 22, 0, 64, self::THUMBNAIL_HEIGHT - 64, $white, $font, strtoupper($course->status));
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
