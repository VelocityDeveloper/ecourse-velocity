<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The visitor counter shown in the footer, after velocity-addons' [velocity-statistics].
 *
 * A visit is one page seen by one visitor on one day (reloads don't add up),
 * a visitor is one IP address per day, and online means seen in the last five minutes.
 */
class VisitorStatistics
{
    /**
     * "Today" follows the clock of the people visiting, not the server's UTC.
     */
    public const string TIMEZONE = 'Asia/Jakarta';

    /**
     * How long after their last page a visitor still counts as online.
     */
    public const int ONLINE_MINUTES = 5;

    /**
     * Requests to these first path segments are the app itself, not the public site.
     *
     * @var list<string>
     */
    private const array IGNORED_PATHS = [
        '_inertia', 'admin', 'dasbor', 'dashboard', 'notifikasi', 'pengaturan', 'settings', 'storage', 'up', 'user', 'sitemap.xml', 'robots.txt', 'captcha',
    ];

    private const string BOTS = '~(bot|spider|crawl|slurp|bingpreview|yandex|ahrefs|facebookexternalhit|bytespider|semrush|lighthouse|headlesschrome|curl|wget|python|go-http)~i';

    private const string SUMMARY_CACHE_KEY = 'visitor-statistics';

    /**
     * Whether the request is a person opening a public page.
     */
    public static function counts(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        // Inertia prefetches pages on hover and reloads parts of them; neither is a visit.
        if ($request->header('Purpose') === 'prefetch' || $request->hasHeader('X-Inertia-Partial-Data')) {
            return false;
        }

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return false;
        }

        if (in_array($request->segment(1), self::IGNORED_PATHS, true)) {
            return false;
        }

        $agent = (string) $request->userAgent();

        return $agent !== '' && preg_match(self::BOTS, $agent) !== 1;
    }

    /**
     * Count the page the request opened.
     */
    public static function record(Request $request): void
    {
        $now = Carbon::now();
        $date = $now->copy()->setTimezone(self::TIMEZONE)->toDateString();
        $visitor = hash_hmac('sha1', (string) $request->ip(), (string) config('app.key'));

        $newVisitor = DB::table('visitor_presences')->insertOrIgnore([
            'date' => $date,
            'visitor' => $visitor,
            'last_seen_at' => $now,
        ]) > 0;

        if (! $newVisitor) {
            DB::table('visitor_presences')
                ->where('date', $date)
                ->where('visitor', $visitor)
                ->where('last_seen_at', '<', $now->copy()->subSeconds(10))
                ->update(['last_seen_at' => $now]);
        }

        // The same page seen again the same day is not another visit.
        $endOfDay = $now->copy()->setTimezone(self::TIMEZONE)->endOfDay();
        $newVisit = Cache::add('visit:'.$date.':'.sha1($visitor.'|'.$request->path()), true, $endOfDay);

        if (! $newVisit && ! $newVisitor) {
            return;
        }

        DB::table('visitor_days')->insertOrIgnore(['date' => $date, 'visits' => 0, 'visitors' => 0]);
        DB::table('visitor_days')->where('date', $date)->update([
            'visits' => DB::raw('visits + '.(int) $newVisit),
            'visitors' => DB::raw('visitors + '.(int) $newVisitor),
        ]);
    }

    /**
     * The numbers the footer shows, refreshed at most every minute.
     *
     * @return array{today_visits: int, today_visitors: int, total_visits: int, total_visitors: int, online: int}
     */
    public static function summary(): array
    {
        return Cache::remember(self::SUMMARY_CACHE_KEY, 60, function (): array {
            $today = DB::table('visitor_days')
                ->where('date', Carbon::now(self::TIMEZONE)->toDateString())
                ->first();
            $total = DB::table('visitor_days')
                ->selectRaw('COALESCE(SUM(visits), 0) AS visits, COALESCE(SUM(visitors), 0) AS visitors')
                ->first();

            return [
                'today_visits' => (int) ($today->visits ?? 0),
                'today_visitors' => (int) ($today->visitors ?? 0),
                'total_visits' => (int) ($total->visits ?? 0),
                'total_visitors' => (int) ($total->visitors ?? 0),
                'online' => DB::table('visitor_presences')
                    ->where('last_seen_at', '>=', Carbon::now()->subMinutes(self::ONLINE_MINUTES))
                    ->count(),
            ];
        });
    }

    /**
     * Drop the presence rows "online" and "today" no longer need; the daily totals stay.
     */
    public static function prune(): int
    {
        return DB::table('visitor_presences')
            ->where('date', '<', Carbon::now(self::TIMEZONE)->subDays(2)->toDateString())
            ->delete();
    }
}
