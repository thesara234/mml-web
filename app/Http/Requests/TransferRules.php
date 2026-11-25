<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use Illuminate\Validation\Rule;

/** Validation shared by the app, the customer portal and the staff console. */
class TransferRules
{
    public static function rules(bool $staff = false): array
    {
        $rules = [
            'transfer_type' => ['required', Rule::in(['money', 'goods'])],
            'amount' => ['required', 'numeric', 'min:1'],
            'amount_mode' => ['nullable', Rule::in(['send', 'receive'])],
            'local_currency' => ['required', 'string', 'size:3'],
            'foreign_currency' => ['required', 'string', 'size:3'],
            'sender_office_id' => ['nullable', 'integer', 'exists:offices,id'],
            'receiver_customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'receiver_office_id' => ['nullable', 'integer', 'exists:offices,id'],
            'purpose' => ['nullable', 'string', 'max:120'],
            'sender_income_source' => ['nullable', 'string', 'max:120'],
            'payment_method' => ['nullable', Rule::in(array_keys(Transaction::PAYMENT_METHODS))],
            'items' => ['nullable', 'array', 'max:50'],
            'items.*.stock_item_id' => ['required_with:items', 'integer', 'exists:stock_items,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1', 'max:999'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];

        if ($staff) {
            $rules['discount'] = ['nullable', 'numeric', 'min:0'];
            $rules['payment_reference'] = ['nullable', 'string', 'max:60'];
        }

        return $rules;
    }
}
