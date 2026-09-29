<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;

/**
 * The admin's manual payment details: bank accounts, the static QRIS code,
 * extra instructions and how long an unpaid order stays open.
 */
class PaymentSettings
{
    public const string BANK_ACCOUNTS = 'payment_bank_accounts';

    public const string QRIS_IMAGE = 'payment_qris_path';

    public const string QRIS_NAME = 'payment_qris_name';

    public const string INSTRUCTIONS = 'payment_instructions';

    public const string EXPIRY_HOURS = 'payment_expiry_hours';

    /**
     * The percentage of each sale the platform keeps; the instructor gets the rest.
     */
    public const string COMMISSION_RATE = 'platform_commission_rate';

    /**
     * The smallest payout an instructor may ask for, in rupiah.
     */
    public const string WITHDRAWAL_MINIMUM = 'withdrawal_minimum';

    public const int DEFAULT_WITHDRAWAL_MINIMUM = 50000;

    /**
     * Where the QRIS image is stored.
     */
    public const string QRIS_DISK = 'public';

    public const string QRIS_DIRECTORY = 'payments';

    /**
     * The most bank accounts an admin may list.
     */
    public const int MAX_BANK_ACCOUNTS = 5;

    public const int DEFAULT_EXPIRY_HOURS = 24;

    /**
     * Get the bank accounts students can transfer to.
     *
     * @return list<array{bank: string, account_number: string, account_name: string}>
     */
    public static function bankAccounts(): array
    {
        $decoded = json_decode(SiteSetting::get(self::BANK_ACCOUNTS) ?? '[]', true);

        if (! is_array($decoded)) {
            return [];
        }

        $accounts = [];

        foreach ($decoded as $account) {
            if (is_array($account) && isset($account['bank'], $account['account_number'], $account['account_name'])) {
                $accounts[] = [
                    'bank' => (string) $account['bank'],
                    'account_number' => (string) $account['account_number'],
                    'account_name' => (string) $account['account_name'],
                ];
            }
        }

        return $accounts;
    }

    /**
     * Get the public URL of the QRIS image, or null when none is uploaded.
     */
    public static function qrisUrl(): ?string
    {
        $path = SiteSetting::get(self::QRIS_IMAGE);

        return $path === null ? null : Storage::disk(self::QRIS_DISK)->url($path);
    }

    /**
     * Get how many hours a new order stays open for payment.
     */
    public static function expiryHours(): int
    {
        $hours = (int) (SiteSetting::get(self::EXPIRY_HOURS) ?? self::DEFAULT_EXPIRY_HOURS);

        return $hours > 0 ? $hours : self::DEFAULT_EXPIRY_HOURS;
    }

    /**
     * Get the platform commission as a percentage from 0 to 100 (0 when not set).
     */
    public static function commissionRate(): float
    {
        $rate = (float) (SiteSetting::get(self::COMMISSION_RATE) ?? 0);

        return max(0.0, min(100.0, $rate));
    }

    /**
     * Get the smallest payout an instructor may ask for.
     */
    public static function withdrawalMinimum(): int
    {
        $minimum = SiteSetting::get(self::WITHDRAWAL_MINIMUM);

        return $minimum === null ? self::DEFAULT_WITHDRAWAL_MINIMUM : max(0, (int) $minimum);
    }

    /**
     * Get the payment methods the admin has set up.
     *
     * @return list<string>
     */
    public static function availableMethods(): array
    {
        return array_values(array_filter([
            self::bankAccounts() !== [] ? 'bank_transfer' : null,
            self::qrisUrl() !== null ? 'qris' : null,
        ]));
    }

    /**
     * Everything the checkout page shows about paying.
     *
     * @return array{bank_accounts: list<array{bank: string, account_number: string, account_name: string}>, qris_url: string|null, qris_name: string|null, instructions: string|null, methods: list<string>}
     */
    public static function forCheckout(): array
    {
        return [
            'bank_accounts' => self::bankAccounts(),
            'qris_url' => self::qrisUrl(),
            'qris_name' => SiteSetting::get(self::QRIS_NAME),
            'instructions' => SiteSetting::get(self::INSTRUCTIONS),
            'methods' => self::availableMethods(),
        ];
    }
}
