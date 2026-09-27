<?php

namespace App\Http\Requests;

use App\Models\Order;
use App\Support\PaymentSettings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitPaymentProofRequest extends FormRequest
{
    /**
     * The largest proof file, in kilobytes.
     */
    public const int MAX_PROOF_KB = 5120;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'bank_index' => [
                Rule::requiredIf($this->route('order') instanceof Order
                    && $this->route('order')->payment_method === Order::METHOD_BANK_TRANSFER),
                'nullable', 'integer', 'min:0', 'max:'.max(0, count(PaymentSettings::bankAccounts()) - 1),
            ],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:'.self::MAX_PROOF_KB],
            'payer_name' => ['required', 'string', 'max:100'],
            'payer_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
