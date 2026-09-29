<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;
use App\Support\Slug;

test('a course lives at /{category}/{course}', function () {
    $category = Category::factory()->create(['slug' => 'web-development']);
    $course = Course::factory()->published()->create(['category_id' => $category->id, 'slug' => 'laravel-dasar']);

    expect($course->permalink())->toBe('/web-development/laravel-dasar');

    $this->get('/web-development/laravel-dasar')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('catalog/Show')->where('course.slug', 'laravel-dasar'));
});

test('a course without a category lives under /kursus', function () {
    $course = Course::factory()->published()->create(['category_id' => null, 'slug' => 'tanpa-kategori']);

    expect($course->permalink())->toBe('/kursus/tanpa-kategori');
    $this->get('/kursus/tanpa-kategori')->assertOk();
});

test('a wrong category segment redirects to the canonical course URL', function () {
    $category = Category::factory()->create(['slug' => 'devops']);
    $course = Course::factory()->published()->create(['category_id' => $category->id, 'slug' => 'docker']);

    $this->get('/kategori-lama/docker')->assertRedirect('/devops/docker')->assertStatus(301);
});

test('old id-based and English URLs redirect permanently', function () {
    $category = Category::factory()->create(['slug' => 'data-science']);
    $course = Course::factory()->published()->create(['category_id' => $category->id, 'slug' => 'python']);
    $instructor = User::factory()->instructor()->create(['name' => 'Sari Wulandari']);

    $this->get("/catalog/{$course->id}")->assertStatus(301)->assertRedirect('/data-science/python');
    $this->get("/users/{$instructor->id}")->assertStatus(301)->assertRedirect('/instruktur/sari-wulandari');
    $this->get('/catalog')->assertStatus(301)->assertRedirect('/kursus');
    $this->get('/login')->assertStatus(301)->assertRedirect('/masuk');
});

test('fixed pages win over the /{category}/{course} permalink', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)->get('/belajar-saya/kursus')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('my-courses/Index'));
});

test('lessons and quizzes are opened by slugs that are unique within a course', function () {
    $course = Course::factory()->published()->create(['slug' => 'flutter']);
    $section = Section::factory()->create(['course_id' => $course->id]);
    $first = Lesson::factory()->atPosition(1)->create(['section_id' => $section->id, 'title' => 'Pengenalan']);
    $second = Lesson::factory()->atPosition(2)->create(['section_id' => $section->id, 'title' => 'Pengenalan']);
    $quiz = Quiz::factory()->create(['section_id' => $section->id, 'title' => 'Kuis: Bab 1']);

    $otherSection = Section::factory()->create();
    $elsewhere = Lesson::factory()->create(['section_id' => $otherSection->id, 'title' => 'Pengenalan']);

    expect($first->slug)->toBe('pengenalan')
        ->and($second->slug)->toBe('pengenalan-2')
        ->and($elsewhere->slug)->toBe('pengenalan')
        ->and($quiz->slug)->toBe('kuis-bab-1');

    $student = User::factory()->student()->create();
    $course->enrollments()->create(['user_id' => $student->id, 'status' => 'active', 'enrolled_at' => now()]);

    $this->actingAs($student)
        ->get('/belajar/flutter/materi/pengenalan-2')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('lesson.id', $second->id));

    $this->actingAs($student)->get('/belajar/flutter/kuis/kuis-bab-1')->assertOk();
});

test('a lesson slug from another course is not found', function () {
    $course = Course::factory()->published()->create(['slug' => 'kotlin']);
    Section::factory()->create(['course_id' => $course->id]);
    $other = Lesson::factory()->create(['title' => 'Hanya di kursus lain']);
    $student = User::factory()->student()->create();
    $course->enrollments()->create(['user_id' => $student->id, 'status' => 'active', 'enrolled_at' => now()]);

    $this->actingAs($student)->get("/belajar/kotlin/materi/{$other->slug}")->assertNotFound();
});

test('the catalog filters by category slug', function () {
    $web = Category::factory()->create(['slug' => 'web-development']);
    $mobile = Category::factory()->create(['slug' => 'mobile-development']);
    Course::factory()->published()->create(['category_id' => $web->id]);
    Course::factory()->published()->create(['category_id' => $mobile->id]);

    $this->get('/kursus?kategori=web-development')
        ->assertInertia(fn ($page) => $page->has('courses.data', 1)->where('filters.kategori', 'web-development'));
});

test('titles become tidy slugs', function () {
    expect(Slug::from('UI/UX Design', 'x'))->toBe('ui-ux-design')
        ->and(Slug::from('Docker & CI/CD untuk Developer', 'x'))->toBe('docker-ci-cd-untuk-developer')
        ->and(Slug::from('!!!', 'kursus-baru'))->toBe('kursus-baru');
});

test('a category may not take a slug used by another page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.categories.store'), ['name' => 'Belajar Saya'])
        ->assertSessionHasErrors('slug');

    $this->actingAs($admin)
        ->post(route('admin.categories.store'), ['name' => 'UI/UX Design'])
        ->assertSessionHasNoErrors();

    expect(Category::query()->where('slug', 'ui-ux-design')->exists())->toBeTrue();
});

test('every user gets a profile slug', function () {
    $first = User::factory()->instructor()->create(['name' => 'Dimas Aditya']);
    $second = User::factory()->create(['name' => 'Dimas Aditya']);

    expect($first->slug)->toBe('dimas-aditya')->and($second->slug)->toBe('dimas-aditya-2');

    $this->get('/instruktur/dimas-aditya')->assertOk();
});

test('dashboard pages live under /dasbor with slugs', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create(['name' => 'Budi Santoso']);
    $category = Category::factory()->create(['slug' => 'desain']);
    $course = Course::factory()->ownedBy($instructor)->create(['slug' => 'figma-dasar']);
    $section = Section::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['section_id' => $section->id, 'title' => 'Mengenal Frame']);
    $quiz = Quiz::factory()->create(['section_id' => $section->id, 'title' => 'Kuis Frame']);

    expect(route('courses.edit', $course, false))->toBe('/dasbor/kursus/figma-dasar/ubah')
        ->and(route('lessons.edit', ['course' => $course, 'lesson' => $lesson], false))->toBe('/dasbor/kursus/figma-dasar/materi/mengenal-frame/ubah')
        ->and(route('admin.users.edit', $instructor, false))->toBe('/dasbor/pengguna/budi-santoso/ubah')
        ->and(route('admin.categories.edit', $category, false))->toBe('/dasbor/kategori/desain/ubah');

    foreach ([
        '/dasbor', '/dasbor/kursus', '/dasbor/kursus/buat', '/dasbor/kursus/figma-dasar',
        '/dasbor/kursus/figma-dasar/progres', '/dasbor/kursus/figma-dasar/nilai', '/dasbor/kursus/figma-dasar/ulasan',
        '/dasbor/materi', '/dasbor/kursus/figma-dasar/materi/mengenal-frame/ubah',
        '/dasbor/kuis', '/dasbor/kursus/figma-dasar/kuis/kuis-frame/ubah', '/dasbor/pendaftaran',
        '/dasbor/pengguna', '/dasbor/pengguna/tambah', '/dasbor/pengguna/budi-santoso/ubah',
        '/dasbor/kategori', '/dasbor/kategori/tambah', '/dasbor/kategori/desain/ubah',
        '/dasbor/pengaturan-situs/identitas', '/dasbor/pengaturan-situs/banner-promo', '/dasbor/pengaturan-situs/testimoni',
        '/dasbor/keuangan', '/dasbor/keuangan/pesanan', '/dasbor/keuangan/penarikan-dana',
        '/dasbor/keuangan/instruktur', '/dasbor/instruktur', '/dasbor/pengaturan-situs/pembayaran',
        '/pengaturan/profil', '/pengaturan/keamanan', '/pengaturan/tampilan',
    ] as $path) {
        $this->actingAs($admin)->get($path)->assertOk();
    }
});

test('a lesson of another course cannot be edited through this course', function () {
    $admin = User::factory()->admin()->create();
    $course = Course::factory()->create(['slug' => 'figma-dasar']);
    $other = Lesson::factory()->create(['title' => 'Di kursus lain']);

    $this->actingAs($admin)->get("/dasbor/kursus/figma-dasar/materi/{$other->slug}/ubah")->assertNotFound();
});

test('old dashboard URLs redirect permanently', function () {
    $admin = User::factory()->admin()->create();
    $course = Course::factory()->create(['slug' => 'figma-dasar']);
    $lesson = Lesson::factory()->create(['section_id' => Section::factory()->create(['course_id' => $course->id])->id, 'title' => 'Frame']);
    $user = User::factory()->student()->create(['name' => 'Rina Ayu']);

    $this->actingAs($admin);
    $this->get('/dashboard')->assertRedirect('/dasbor')->assertStatus(301);
    $this->get("/courses/{$course->id}")->assertRedirect('/dasbor/kursus/figma-dasar')->assertStatus(301);
    $this->get("/lessons/{$lesson->id}/edit")->assertRedirect('/dasbor/kursus/figma-dasar/materi/frame/ubah');
    $this->get("/admin/users/{$user->id}/edit")->assertRedirect('/dasbor/pengguna/rina-ayu/ubah');
    $this->get('/admin/settings/warna')->assertRedirect('/dasbor/pengaturan-situs/warna');
    $this->get('/settings/profile')->assertRedirect('/pengaturan/profil');

    // Money pages moved under Keuangan, payment settings under Pengaturan Situs.
    $this->get('/dasbor/pesanan')->assertRedirect('/dasbor/keuangan/pesanan')->assertStatus(301);
    $this->get('/dasbor/pesanan/INV-20260101-ABCDE')->assertRedirect('/dasbor/keuangan/pesanan/INV-20260101-ABCDE');
    $this->get('/dasbor/transaksi')->assertRedirect('/dasbor/keuangan/pesanan?status=paid');
    $this->get('/dasbor/keuangan/transaksi')->assertRedirect('/dasbor/keuangan/pesanan?status=paid');
    $this->get('/dasbor/penarikan-dana')->assertRedirect('/dasbor/keuangan/penarikan-dana');
    $this->get('/dasbor/instruktur/budi-santoso')->assertRedirect('/dasbor/keuangan/instruktur/budi-santoso');
    $this->get('/dasbor/pengaturan-pembayaran')->assertRedirect('/dasbor/pengaturan-situs/pembayaran');
    $this->get('/admin/payment-settings')->assertRedirect('/dasbor/pengaturan-situs/pembayaran');
});
