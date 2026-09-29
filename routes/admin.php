<?php

use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\InstructorApplicationController;
use App\Http\Controllers\Admin\InstructorController;
use App\Http\Controllers\Admin\InstructorFinanceController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentSettingController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\WithdrawalController;
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
    Route::post('pengguna/{user:slug}/tangguhkan', [UserController::class, 'suspend'])->name('users.suspend');
    Route::delete('pengguna/{user:slug}/tangguhkan', [UserController::class, 'unsuspend'])->name('users.unsuspend');
    Route::post('pengguna/{user:slug}/batasi-pembelian', [UserController::class, 'blockPurchases'])->name('users.block-purchases');
    Route::delete('pengguna/{user:slug}/batasi-pembelian', [UserController::class, 'unblockPurchases'])->name('users.unblock-purchases');

    Route::get('instruktur', [InstructorController::class, 'index'])->name('instructors.index');
    Route::get('instruktur/pengajuan', [InstructorApplicationController::class, 'index'])->name('instructor-applications.index');
    Route::post('instruktur/pengajuan/{application}/setujui', [InstructorApplicationController::class, 'approve'])->name('instructor-applications.approve');
    Route::post('instruktur/pengajuan/{application}/tolak', [InstructorApplicationController::class, 'reject'])->name('instructor-applications.reject');
    // An instructor's money moved to Keuangan; keep old links working.
    Route::get('instruktur/{slug}', fn (string $slug) => redirect('/dasbor/keuangan/instruktur/'.$slug, 301))->where('slug', '[a-z0-9-]+');

    Route::get('keuangan/instruktur', [InstructorFinanceController::class, 'index'])->name('finance.instructors.index');
    Route::get('keuangan/instruktur/ekspor', [InstructorFinanceController::class, 'export'])->name('finance.instructors.export');
    Route::get('keuangan/instruktur/{user:slug}', [InstructorFinanceController::class, 'show'])->name('finance.instructors.show');

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

    Route::post('keuangan/pesanan/{order}/konfirmasi', [OrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('keuangan/pesanan/{order}/tolak', [OrderController::class, 'reject'])->name('orders.reject');
    Route::post('keuangan/pesanan/{order}/batalkan', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::post('keuangan/penarikan-dana/{withdrawal}/bayar', [WithdrawalController::class, 'pay'])->name('withdrawals.pay');
    Route::post('keuangan/penarikan-dana/{withdrawal}/tolak', [WithdrawalController::class, 'reject'])->name('withdrawals.reject');

    Route::get('pengaturan-situs/pembayaran', [PaymentSettingController::class, 'edit'])->name('payment-settings.edit');
    Route::post('pengaturan-situs/pembayaran', [PaymentSettingController::class, 'update'])->name('payment-settings.update');
    Route::permanentRedirect('pengaturan-pembayaran', '/dasbor/pengaturan-situs/pembayaran');
});

/*
 * Keuangan: every page about money in one place. Instructors see it too, limited
 * to the orders, payments and withdrawals of their own courses.
 */
Route::middleware(['auth', 'staff'])->prefix('dasbor')->name('admin.')->group(function () {
    Route::get('keuangan', [FinanceController::class, 'index'])->name('finance.index');

    Route::get('keuangan/pesanan', [OrderController::class, 'index'])->name('orders.index');
    Route::get('keuangan/pesanan/ekspor', [OrderController::class, 'export'])->name('orders.export');
    Route::get('keuangan/pesanan/{order}', [OrderController::class, 'show'])->name('orders.show');

    Route::get('keuangan/penarikan-dana', [WithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::post('keuangan/penarikan-dana', [WithdrawalController::class, 'store'])->middleware('throttle:6,1')->name('withdrawals.store');
    Route::post('keuangan/penarikan-dana/{withdrawal}/batalkan', [WithdrawalController::class, 'cancel'])->name('withdrawals.cancel');
    Route::get('keuangan/penarikan-dana/{withdrawal}/bukti', [WithdrawalController::class, 'proof'])->name('withdrawals.proof');

    // Where these pages lived before Keuangan.
    Route::permanentRedirect('pesanan', '/dasbor/keuangan/pesanan');
    Route::get('pesanan/{order}', fn (string $order) => redirect('/dasbor/keuangan/pesanan/'.$order, 301))->where('order', '[A-Za-z0-9-]+');
    // Transactions are paid orders now, shown in the order list.
    Route::permanentRedirect('keuangan/transaksi', '/dasbor/keuangan/pesanan?status=paid');
    Route::permanentRedirect('transaksi', '/dasbor/keuangan/pesanan?status=paid');
    Route::permanentRedirect('penarikan-dana', '/dasbor/keuangan/penarikan-dana');
});
