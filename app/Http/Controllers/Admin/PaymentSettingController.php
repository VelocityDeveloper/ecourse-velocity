<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\Transaction;
use App\Support\PaymentSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PaymentSettingController extends Controller
{
    /**
     * Show the manual payment settings.
     */
    public function edit(): Response
    {
        return Inertia::render('admin/Payments/Settings', [
            'bankAccounts' => PaymentSettings::bankAccounts(),
            'qrisUrl' => PaymentSettings::qrisUrl(),
            'qrisName' => SiteSetting::get(PaymentSettings::QRIS_NAME),
            'instructions' => SiteSetting::get(PaymentSettings::INSTRUCTIONS),
            'expiryHours' => PaymentSettings::expiryHours(),
            'maxBankAccounts' => PaymentSettings::MAX_BANK_ACCOUNTS,
            'commissionRate' => PaymentSettings::commissionRate(),
            'withdrawalMinimum' => PaymentSettings::withdrawalMinimum(),
            'uncommissionedCount' => Transaction::query()->where('commission_rate', 0)->where('amount', '>', 0)->count(),
        ]);
    }

    /**
     * Save the bank accounts, the QRIS code, the order deadline and the platform commission.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'bank_accounts' => ['nullable', 'array', 'max:'.PaymentSettings::MAX_BANK_ACCOUNTS],
            'bank_accounts.*.bank' => ['required', 'string', 'max:50'],
            'bank_accounts.*.account_number' => ['required', 'string', 'max:50', 'regex:/^[0-9 .\-]+$/'],
            'bank_accounts.*.account_name' => ['required', 'string', 'max:100'],
            'qris' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'remove_qris' => ['boolean'],
            'qris_name' => ['nullable', 'string', 'max:100'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'expiry_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'commission_rate' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'apply_to_past' => ['boolean'],
            'withdrawal_minimum' => ['sometimes', 'required', 'integer', 'min:0', 'max:100000000'],
        ]);

        $accounts = array_map(fn (array $account): array => [
            'bank' => trim((string) $account['bank']),
            'account_number' => trim((string) $account['account_number']),
            'account_name' => trim((string) $account['account_name']),
        ], array_values($request->array('bank_accounts')));

        SiteSetting::put(PaymentSettings::BANK_ACCOUNTS, $accounts === [] ? null : (string) json_encode($accounts));
        SiteSetting::put(PaymentSettings::QRIS_NAME, $request->filled('qris_name') ? $request->string('qris_name')->toString() : null);
        SiteSetting::put(PaymentSettings::INSTRUCTIONS, $request->filled('instructions') ? $request->string('instructions')->toString() : null);
        SiteSetting::put(PaymentSettings::EXPIRY_HOURS, (string) $request->integer('expiry_hours'));

        if ($request->has('commission_rate')) {
            SiteSetting::put(PaymentSettings::COMMISSION_RATE, number_format($request->float('commission_rate'), 2, '.', ''));
        }

        if ($request->has('withdrawal_minimum')) {
            SiteSetting::put(PaymentSettings::WITHDRAWAL_MINIMUM, (string) $request->integer('withdrawal_minimum'));
        }

        $applied = $request->boolean('apply_to_past') ? $this->applyToPastSales(PaymentSettings::commissionRate()) : 0;

        $disk = Storage::disk(PaymentSettings::QRIS_DISK);
        $current = SiteSetting::get(PaymentSettings::QRIS_IMAGE);

        if ($request->hasFile('qris') || $request->boolean('remove_qris')) {
            if ($current !== null) {
                $disk->delete($current);
            }

            $file = $request->file('qris');
            $stored = $file !== null && ! is_array($file)
                ? $file->store(PaymentSettings::QRIS_DIRECTORY, PaymentSettings::QRIS_DISK)
                : false;

            SiteSetting::put(PaymentSettings::QRIS_IMAGE, $stored === false ? null : $stored);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $applied > 0
            ? __('Payment settings saved. Commission applied to :count past sales.', ['count' => $applied])
            : __('Payment settings saved.')]);

        return back();
    }

    /**
     * Charge the commission on sales recorded without one (made before commissions
     * existed, or while the rate was 0%). Sales that already carry a commission keep it.
     */
    private function applyToPastSales(float $rate): int
    {
        if ($rate <= 0) {
            return 0;
        }

        $transactions = Transaction::query()->where('commission_rate', 0)->where('amount', '>', 0)->get();

        foreach ($transactions as $transaction) {
            $transaction->update(Transaction::split($transaction->amount, $rate));
        }

        return $transactions->count();
    }
}
