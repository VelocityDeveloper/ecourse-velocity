<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the default course categories.
     */
    public function run(): void
    {
        $categories = [
            'Web Development' => 'Membangun aplikasi web modern, dari front-end sampai back-end.',
            'Mobile Development' => 'Membuat aplikasi Android dan iOS.',
            'Data Science' => 'Analisis data, statistik, dan machine learning.',
            'UI/UX Design' => 'Merancang antarmuka dan pengalaman pengguna.',
            'DevOps' => 'Otomasi, deployment, dan infrastruktur.',
        ];

        foreach ($categories as $name => $description) {
            Category::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => $description],
            );
        }
    }
}
