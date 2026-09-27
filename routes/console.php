<?php

use App\Models\Order;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('orders:expire', function () {
    $this->info(Order::expireOverdue().' order(s) expired.');
})->purpose('Expire unpaid orders whose payment deadline has passed');

Schedule::command('orders:expire')->everyFifteenMinutes();
