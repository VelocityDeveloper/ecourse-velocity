<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Keeps a dense 1..n "position" sequence among sibling records.
 *
 * @property int $position
 *
 * @phpstan-require-extends Model
 */
trait HasPosition
{
    public const string MOVE_UP = 'up';

    public const string MOVE_DOWN = 'down';

    /**
     * The directions a record may be moved in.
     *
     * @var list<string>
     */
    public const array MOVE_DIRECTIONS = [self::MOVE_UP, self::MOVE_DOWN];

    /**
     * Get a query for the records this one is ordered against.
     *
     * @return Builder<static>
     */
    abstract public function siblings(): Builder;

    /**
     * Swap this record with the neighbour in the given direction.
     *
     * Returns false when the record is already at the end of the list.
     */
    public function move(string $direction): bool
    {
        $target = $direction === self::MOVE_UP
            ? $this->position - 1
            : $this->position + 1;

        $neighbour = $this->siblings()
            ->whereKeyNot($this->getKey())
            ->where('position', $target)
            ->first();

        if ($neighbour === null) {
            return false;
        }

        $current = $this->position;
        $swapped = $neighbour->position;

        DB::transaction(function () use ($neighbour, $current, $swapped): void {
            $neighbour->update(['position' => $current]);
            $this->update(['position' => $swapped]);
        });

        return true;
    }

    /**
     * Close any gaps left in the sibling sequence.
     */
    public function resequenceSiblings(): void
    {
        $this->siblings()
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->each(function (Model $sibling, int $index): void {
                $sibling->update(['position' => $index + 1]);
            });
    }
}
