<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Non-null TIMESTAMP columns created without a default. MySQL/MariaDB without
     * explicit_defaults_for_timestamp give the first such column of a table
     * ON UPDATE CURRENT_TIMESTAMP, so any later update of the row overwrote it
     * (an enrollment's date moved each time the student opened a lesson).
     */
    private const array COLUMNS = [
        'transactions' => 'paid_at',
        'enrollments' => 'enrolled_at',
        'quiz_attempts' => 'started_at',
        'certificates' => 'issued_at',
    ];

    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach (self::COLUMNS as $table => $column) {
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
        }
    }

    public function down(): void
    {
        //
    }
};
