<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Every seeder only adds what is missing, so this can be run more than once.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            CourseSeeder::class,
            CurriculumSeeder::class,
            EnrollmentSeeder::class,
            LearningActivitySeeder::class,
            BlogSeeder::class,
        ]);
    }
}
