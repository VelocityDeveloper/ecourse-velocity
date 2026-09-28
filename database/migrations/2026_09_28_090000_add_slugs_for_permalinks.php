<?php

use App\Support\Slug;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Slugs for the readable URLs: /instruktur/{user}, /belajar/{course}/materi/{lesson}
 * and /belajar/{course}/kuis/{quiz}. Existing rows get a slug from their title or name.
 *
 * Course and category slugs that were generated automatically ("uiux-design" from
 * "UI/UX Design") are regenerated with the tidier rules; hand-picked ones are kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
        });
        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
        });

        $this->fillUserSlugs();
        $this->fillCourseScopedSlugs('lessons', 'materi');
        $this->fillCourseScopedSlugs('quizzes', 'kuis');
        $this->tidyGeneratedSlugs('categories', 'name', 'kategori');
        $this->tidyGeneratedSlugs('courses', 'title', 'kursus-baru');

        Schema::table('users', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
            $table->unique('slug');
        });
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
            $table->index('slug');
        });
        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }

    private function fillUserSlugs(): void
    {
        $taken = [];

        foreach (DB::table('users')->orderBy('id')->get(['id', 'name']) as $user) {
            $slug = Slug::unique($user->name, 'pengguna', fn (string $slug): bool => isset($taken[$slug]));
            $taken[$slug] = true;
            DB::table('users')->where('id', $user->id)->update(['slug' => $slug]);
        }
    }

    /**
     * Give each lesson or quiz a slug that is unique within its course, in curriculum order.
     */
    private function fillCourseScopedSlugs(string $table, string $fallback): void
    {
        $rows = DB::table($table)
            ->join('sections', 'sections.id', '=', "{$table}.section_id")
            ->orderBy('sections.course_id')
            ->orderBy('sections.position')
            ->orderBy("{$table}.position")
            ->get(["{$table}.id", "{$table}.title", 'sections.course_id']);

        $taken = [];

        foreach ($rows as $row) {
            $slug = Slug::unique($row->title, $fallback, fn (string $slug): bool => isset($taken[$row->course_id][$slug]));
            $taken[$row->course_id][$slug] = true;
            DB::table($table)->where('id', $row->id)->update(['slug' => $slug]);
        }
    }

    /**
     * Regenerate slugs that still match what the old Str::slug() made from the title.
     */
    private function tidyGeneratedSlugs(string $table, string $titleColumn, string $fallback): void
    {
        $rows = DB::table($table)->orderBy('id')->get(['id', 'slug', $titleColumn]);
        $taken = $rows->pluck('slug')->flip()->all();

        foreach ($rows as $row) {
            $title = (string) $row->{$titleColumn};
            $tidy = Slug::from($title, $fallback);

            if ($row->slug === $tidy || $row->slug !== Str::slug($title)) {
                continue;
            }

            unset($taken[$row->slug]);
            $slug = Slug::unique($title, $fallback, fn (string $slug): bool => isset($taken[$slug]));
            $taken[$slug] = true;
            DB::table($table)->where('id', $row->id)->update(['slug' => $slug]);
        }
    }
};
