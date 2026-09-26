<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * The number of extra, randomly generated students to keep in the database.
     */
    private const int EXTRA_STUDENTS = 10;

    /**
     * Seed the demo accounts for every role. Each one logs in with "password".
     */
    public function run(): void
    {
        $accounts = [
            [
                'email' => 'admin@ecourse.com',
                'name' => 'Admin',
                'role' => User::ROLE_ADMIN,
                'headline' => 'Platform administrator',
                'bio' => null,
            ],
            [
                'email' => 'instructor@ecourse.com',
                'name' => 'Rizky Pratama',
                'role' => User::ROLE_INSTRUCTOR,
                'headline' => 'Senior Laravel Developer',
                'bio' => "Membangun aplikasi web dengan Laravel sejak 2015.\nSuka menjelaskan konsep rumit dengan contoh yang sederhana.",
            ],
            [
                'email' => 'sari@ecourse.com',
                'name' => 'Sari Wulandari',
                'role' => User::ROLE_INSTRUCTOR,
                'headline' => 'Mobile Engineer & Data Enthusiast',
                'bio' => 'Mengembangkan aplikasi Android dan Flutter, serta mengajar analisis data untuk pemula.',
            ],
            [
                'email' => 'dimas@ecourse.com',
                'name' => 'Dimas Aditya',
                'role' => User::ROLE_INSTRUCTOR,
                'headline' => 'Product Designer & DevOps Practitioner',
                'bio' => 'Menjembatani desain dan infrastruktur: dari Figma sampai pipeline CI/CD.',
            ],
            [
                'email' => 'student@ecourse.com',
                'name' => 'Nadia Putri',
                'role' => User::ROLE_STUDENT,
                'headline' => 'Mahasiswa Teknik Informatika',
                'bio' => 'Sedang belajar menjadi full-stack developer.',
            ],
            [
                'email' => 'test@example.com',
                'name' => 'Test User',
                'role' => User::ROLE_STUDENT,
                'headline' => null,
                'bio' => null,
            ],
        ];

        foreach ($accounts as $account) {
            User::query()->firstOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'headline' => $account['headline'],
                    'bio' => $account['bio'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );
        }

        $missingStudents = self::EXTRA_STUDENTS + 2 - User::query()->where('role', User::ROLE_STUDENT)->count();

        if ($missingStudents > 0) {
            User::factory()->student()->count($missingStudents)->create();
        }
    }
}
