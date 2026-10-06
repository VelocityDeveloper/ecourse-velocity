<?php

namespace App\Support\Dashboard;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

/**
 * The time range a dashboard covers, the same-length range before it for
 * comparison, and the buckets its charts are drawn in: days, or months for a year.
 */
final readonly class DashboardPeriod
{
    /**
     * The ranges a dashboard can show, keyed by their value in the URL.
     *
     * @var array<string, string>
     */
    public const array OPTIONS = [
        '7h' => '7 hari',
        '30h' => '30 hari',
        '90h' => '90 hari',
        '12b' => '12 bulan',
    ];

    public const string DEFAULT = '30h';

    private function __construct(
        public string $key,
        public CarbonImmutable $start,
        public CarbonImmutable $end,
        public CarbonImmutable $previousStart,
        public bool $monthly,
    ) {}

    public static function fromKey(?string $key): self
    {
        $key = array_key_exists((string) $key, self::OPTIONS) ? (string) $key : self::DEFAULT;
        $now = CarbonImmutable::instance(Date::now());

        if ($key === '12b') {
            $start = $now->startOfMonth()->subMonths(11);

            return new self($key, $start, $now, $start->subMonths(12), true);
        }

        $days = (int) $key;
        $start = $now->startOfDay()->subDays($days - 1);

        return new self($key, $start, $now, $start->subDays($days), false);
    }

    /**
     * The chart bucket a moment falls in.
     */
    public function bucket(CarbonInterface $at): string
    {
        return $at->format($this->monthly ? 'Y-m' : 'Y-m-d');
    }

    /**
     * Every bucket of the period, oldest first.
     *
     * @return list<string>
     */
    public function buckets(): array
    {
        $buckets = [];

        for ($at = $this->start; $at <= $this->end; $at = $this->monthly ? $at->addMonth() : $at->addDay()) {
            $buckets[] = $this->bucket($at);
        }

        return $buckets;
    }

    /**
     * The period as the page needs it: the chosen key, the choices, and the dates.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => self::OPTIONS[$this->key],
            'monthly' => $this->monthly,
            'start' => $this->start->toDateString(),
            'previous_start' => $this->previousStart->toDateString(),
            'options' => collect(self::OPTIONS)->map(fn (string $label, string $key): array => ['key' => $key, 'label' => $label])->values()->all(),
        ];
    }
}
