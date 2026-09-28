<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    /**
     * Seed a few example blog articles, written by the demo admin.
     */
    public function run(): void
    {
        $author = User::query()->where('email', 'admin@ecourse.com')->first();

        $posts = [
            [
                'slug' => 'cara-belajar-pemrograman-agar-konsisten',
                'title' => 'Cara Belajar Pemrograman agar Konsisten',
                'excerpt' => 'Belajar sedikit tapi rutin lebih ampuh daripada maraton semalam. Ini lima kebiasaan kecil yang membantu Anda bertahan sampai mahir.',
                'content' => '<p>Banyak orang semangat di minggu pertama belajar pemrograman, lalu berhenti di minggu ketiga. Masalahnya jarang soal bakat; biasanya soal kebiasaan.</p>'
                    .'<h2>1. Tentukan jadwal yang realistis</h2><p>Tiga puluh menit setiap hari lebih baik daripada lima jam di akhir pekan. Pilih jam yang sama agar menjadi rutinitas.</p>'
                    .'<h2>2. Selesaikan satu kursus sebelum pindah</h2><p>Berpindah-pindah materi membuat Anda merasa sibuk tanpa benar-benar maju. Tuntaskan satu jalur belajar lebih dulu.</p>'
                    .'<h2>3. Langsung praktik</h2><p>Setiap selesai satu materi, tulis ulang kodenya tanpa melihat contoh. Kesalahan yang muncul justru bagian terpenting dari belajar.</p>'
                    .'<h2>4. Catat yang Anda pelajari</h2><p>Gunakan fitur catatan di setiap materi. Menulis ulang dengan kata-kata sendiri membantu ingatan.</p>'
                    .'<h2>5. Buat proyek kecil</h2><p>Aplikasi daftar tugas, kalkulator, atau halaman profil sederhana sudah cukup. Proyek membuat konsep yang terpisah-pisah menjadi utuh.</p>',
                'published_at' => now()->subDays(2),
            ],
            [
                'slug' => 'laravel-atau-node-js-untuk-pemula',
                'title' => 'Laravel atau Node.js untuk Pemula?',
                'excerpt' => 'Dua pilihan populer untuk membangun backend. Kami bandingkan dari sisi kurva belajar, ekosistem, dan peluang kerja.',
                'content' => '<p>Pertanyaan ini sering muncul dari siswa yang baru selesai belajar dasar HTML, CSS, dan JavaScript. Keduanya bagus; yang penting adalah memilih satu dan mendalaminya.</p>'
                    .'<h2>Kurva belajar</h2><p>Laravel memberi struktur yang jelas sejak awal: routing, controller, model, dan migrasi sudah punya tempatnya. Node.js lebih bebas, sehingga pemula perlu memutuskan banyak hal sendiri.</p>'
                    .'<h2>Ekosistem</h2><p>Laravel hadir dengan banyak fitur bawaan seperti autentikasi, antrean, dan penjadwalan. Node.js mengandalkan paket dari npm yang jumlahnya sangat banyak.</p>'
                    .'<h2>Kesimpulan</h2><p>Jika Anda ingin cepat membangun aplikasi web lengkap, mulailah dengan Laravel. Jika Anda sudah nyaman dengan JavaScript dan ingin satu bahasa untuk frontend dan backend, Node.js pilihan yang masuk akal.</p>',
                'published_at' => now()->subDays(6),
            ],
            [
                'slug' => 'membangun-portofolio-developer-dari-nol',
                'title' => 'Membangun Portofolio Developer dari Nol',
                'excerpt' => 'Belum punya pengalaman kerja? Portofolio yang rapi bisa menjadi bukti kemampuan Anda di mata perekrut.',
                'content' => '<p>Perekrut ingin melihat apa yang bisa Anda kerjakan, bukan hanya daftar kursus yang pernah diikuti. Portofolio adalah jawabannya.</p>'
                    .'<h2>Pilih tiga proyek terbaik</h2><p>Lebih baik tiga proyek yang selesai dan rapi daripada sepuluh proyek setengah jadi. Jelaskan masalah yang diselesaikan dan teknologi yang dipakai.</p>'
                    .'<h2>Tampilkan kodenya</h2><p>Unggah kode ke GitHub dengan README yang jelas: cara menjalankan, tangkapan layar, dan hal yang Anda pelajari.</p>'
                    .'<h2>Sertakan sertifikat</h2><p>Sertifikat kursus yang sudah Anda selesaikan bisa ditautkan langsung karena setiap sertifikat punya halaman verifikasi sendiri.</p>',
                'published_at' => now()->subDays(12),
            ],
        ];

        foreach ($posts as $post) {
            Post::query()->firstOrCreate(
                ['slug' => $post['slug']],
                [...$post, 'user_id' => $author?->id, 'status' => Post::STATUS_PUBLISHED],
            );
        }
    }
}
