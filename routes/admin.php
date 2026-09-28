<?php

use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentSettingController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
 * Admin-only pages of the dashboard, under /dasbor like the rest of it.
 */
Route::middleware(['auth', 'staff', 'admin'])->prefix('dasbor')->name('admin.')->group(function () {
    Route::get('pengguna', [UserController::class, 'index'])->name('users.index');
    Route::get('pengguna/tambah', [UserController::class, 'create'])->name('users.create');
    Route::post('pengguna', [UserController::class, 'store'])->name('users.store');
    Route::get('pengguna/{user:slug}/ubah', [UserController::class, 'edit'])->name('users.edit');
    Route::put('pengguna/{user:slug}', [UserController::class, 'update'])->name('users.update');
    Route::delete('pengguna/{user:slug}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('kategori', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('kategori/tambah', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('kategori', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('kategori/{category:slug}/ubah', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('kategori/{category:slug}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('kategori/{category:slug}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('blog', [PostController::class, 'index'])->name('posts.index');
    Route::get('blog/tulis', [PostController::class, 'create'])->name('posts.create');
    Route::post('blog', [PostController::class, 'store'])->name('posts.store');
    Route::get('blog/{post:slug}/ubah', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('blog/{post:slug}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('blog/{post:slug}', [PostController::class, 'destroy'])->name('posts.destroy');

    Route::redirect('pengaturan-situs', '/dasbor/pengaturan-situs/identitas');
    Route::get('pengaturan-situs/{section}', [SiteSettingController::class, 'edit'])
        ->whereIn('section', array_keys(SiteSettingController::SECTIONS))
        ->name('settings.edit');
    Route::post('pengaturan-situs', [SiteSettingController::class, 'update'])->name('settings.update');

    Route::get('pengaturan-situs/banner-promo', [BannerController::class, 'index'])->name('banners.index');
    Route::post('pengaturan-situs/banner-promo', [BannerController::class, 'store'])->name('banners.store');
    Route::post('pengaturan-situs/banner-promo/{banner}', [BannerController::class, 'update'])->name('banners.update');
    Route::post('pengaturan-situs/banner-promo/{banner}/pindah', [BannerController::class, 'move'])->name('banners.move');
    Route::delete('pengaturan-situs/banner-promo/{banner}', [BannerController::class, 'destroy'])->name('banners.destroy');

    Route::get('pengaturan-situs/testimoni', [TestimonialController::class, 'index'])->name('testimonials.index');
    Route::post('pengaturan-situs/testimoni', [TestimonialController::class, 'store'])->name('testimonials.store');
    Route::post('pengaturan-situs/testimoni/sumber', [TestimonialController::class, 'updateSource'])->name('testimonials.source');
    Route::post('pengaturan-situs/testimoni/jumlah', [TestimonialController::class, 'updateLimit'])->name('testimonials.limit');
    Route::post('pengaturan-situs/testimoni/{testimonial}', [TestimonialController::class, 'update'])->name('testimonials.update');
    Route::post('pengaturan-situs/testimoni/{testimonial}/pindah', [TestimonialController::class, 'move'])->name('testimonials.move');
    Route::delete('pengaturan-situs/testimoni/{testimonial}', [TestimonialController::class, 'destroy'])->name('testimonials.destroy');

    Route::post('pesanan/{order}/konfirmasi', [OrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('pesanan/{order}/tolak', [OrderController::class, 'reject'])->name('orders.reject');
    Route::post('pesanan/{order}/batalkan', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::get('pengaturan-pembayaran', [PaymentSettingController::class, 'edit'])->name('payment-settings.edit');
    Route::post('pengaturan-pembayaran', [PaymentSettingController::class, 'update'])->name('payment-settings.update');
});

/*
 * Sales pages instructors see too, limited to the orders and payments of their own courses.
 */
Route::middleware(['auth', 'staff'])->prefix('dasbor')->name('admin.')->group(function () {
    Route::get('pesanan', [OrderController::class, 'index'])->name('orders.index');
    Route::get('pesanan/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('transaksi', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('transaksi/ekspor', [TransactionController::class, 'export'])->name('transactions.export');
});
