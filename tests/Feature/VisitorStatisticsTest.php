<?php

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\VisitorStatistics;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

const BROWSER = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/130 Safari/537.36';

/**
 * The footer numbers as they stand now, without the minute-long cache.
 *
 * @return array<string, int>
 */
function visitorSummary(): array
{
    Cache::forget('visitor-statistics');

    return VisitorStatistics::summary();
}

test('opening public pages counts visits and visitors', function () {
    $this->withHeader('User-Agent', BROWSER)->get(route('home'))->assertOk();
    $this->withHeader('User-Agent', BROWSER)->get(route('catalog.index'))->assertOk();
    // Reloading the same page the same day is not another visit.
    $this->withHeader('User-Agent', BROWSER)->get(route('home'))->assertOk();

    expect(visitorSummary())->toBe([
        'today_visits' => 2,
        'today_visitors' => 1,
        'total_visits' => 2,
        'total_visitors' => 1,
        'online' => 1,
    ]);
});

test('each IP address is a separate visitor', function () {
    $this->withHeader('User-Agent', BROWSER)->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])->get(route('home'));
    $this->withHeader('User-Agent', BROWSER)->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])->get(route('home'));

    expect(visitorSummary())
        ->today_visits->toBe(2)
        ->today_visitors->toBe(2);
});

test('bots, the dashboard and missing pages are not counted', function () {
    $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1)')->get(route('home'));
    $this->withHeader('User-Agent', BROWSER)->get('/halaman-yang-tidak-ada/sama-sekali');
    $this->withHeader('User-Agent', BROWSER)->actingAs(User::factory()->admin()->create())->get(route('dashboard'));

    expect(visitorSummary()['total_visits'])->toBe(0);
    expect(DB::table('visitor_presences')->count())->toBe(0);
});

test('totals keep earlier days and online only covers the last five minutes', function () {
    DB::table('visitor_days')->insert(['date' => '2026-01-01', 'visits' => 40, 'visitors' => 12]);
    DB::table('visitor_presences')->insert([
        'date' => Carbon::now(VisitorStatistics::TIMEZONE)->toDateString(),
        'visitor' => str_repeat('a', 40),
        'last_seen_at' => now()->subMinutes(10),
    ]);

    $this->withHeader('User-Agent', BROWSER)->get(route('home'));

    expect(visitorSummary())->toBe([
        'today_visits' => 1,
        'today_visitors' => 1,
        'total_visits' => 41,
        'total_visitors' => 13,
        'online' => 1,
    ]);
});

test('the footer receives the numbers unless the admin hides the column', function () {
    $this->get(route('home'))->assertInertia(fn ($page) => $page
        ->where('site.footer.statistics.show', true)
        ->has('visitorStats.today_visits')
        ->has('visitorStats.online')
    );

    SiteSetting::put(SiteSetting::FOOTER, json_encode(['statistics' => ['show' => false]]));

    $this->get(route('home'))->assertInertia(fn ($page) => $page->where('visitorStats', null));
});

test('old presence rows are pruned while daily totals stay', function () {
    DB::table('visitor_days')->insert(['date' => '2026-01-01', 'visits' => 5, 'visitors' => 3]);
    DB::table('visitor_presences')->insert([
        ['date' => '2026-01-01', 'visitor' => str_repeat('a', 40), 'last_seen_at' => '2026-01-01 08:00:00'],
        ['date' => Carbon::now(VisitorStatistics::TIMEZONE)->toDateString(), 'visitor' => str_repeat('b', 40), 'last_seen_at' => now()],
    ]);

    $this->artisan('visitors:prune')->assertSuccessful();

    expect(DB::table('visitor_presences')->count())->toBe(1);
    expect(DB::table('visitor_days')->sum('visits'))->toEqual(5);
});
