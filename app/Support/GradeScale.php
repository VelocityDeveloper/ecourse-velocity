<?php

namespace App\Support;

/**
 * Turns a final course percentage into a letter grade.
 */
class GradeScale
{
    /**
     * The lowest percentage for each letter, from best to worst.
     *
     * @var array<string, int>
     */
    public const array LETTERS = ['A' => 85, 'B' => 70, 'C' => 55, 'D' => 40, 'E' => 0];

    /**
     * Get the letter grade for a percentage.
     */
    public static function letter(int $percent): string
    {
        foreach (self::LETTERS as $letter => $minimum) {
            if ($percent >= $minimum) {
                return $letter;
            }
        }

        return 'E';
    }

    /**
     * Get the scale as rows the interface can list.
     *
     * @return list<array{letter: string, min: int}>
     */
    public static function rows(): array
    {
        return array_map(
            fn (string $letter, int $minimum): array => ['letter' => $letter, 'min' => $minimum],
            array_keys(self::LETTERS),
            array_values(self::LETTERS),
        );
    }
}
