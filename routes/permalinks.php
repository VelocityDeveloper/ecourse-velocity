<?php

use App\Http\Controllers\CatalogController;
use App\Support\Slug;
use Illuminate\Support\Facades\Route;

/*
 * The course page, /{category}/{course}, like WordPress' /%category%/%postname%/.
 * It is loaded after every other route file (see bootstrap/app.php) so fixed
 * paths such as /belajar-saya/kursus or /admin/banners always win.
 */
Route::get('{category}/{course:slug}', [CatalogController::class, 'show'])
    ->where('category', Slug::PATTERN)
    ->name('catalog.show');
