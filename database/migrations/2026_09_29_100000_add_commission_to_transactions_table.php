<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Split every payment into the platform's commission and the instructor's share,
     * frozen at the rate in force when the payment was confirmed.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->default(0)->after('amount');
            // Amounts are whole rupiah.
            $table->unsignedBigInteger('commission_amount')->default(0)->after('commission_rate');
            $table->unsignedBigInteger('instructor_amount')->default(0)->after('commission_amount');
        });

        // Payments made before commissions existed went wholly to the instructor.
        // paid_at is set to itself so a lingering ON UPDATE CURRENT_TIMESTAMP cannot move it.
        DB::table('transactions')->update(['instructor_amount' => DB::raw('amount'), 'paid_at' => DB::raw('paid_at')]);
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['commission_rate', 'commission_amount', 'instructor_amount']);
        });
    }
};
