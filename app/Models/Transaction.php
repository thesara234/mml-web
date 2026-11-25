<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    public const DRAFT = 'draft';
    public const AWAITING_PAYMENT = 'awaiting_payment';
    public const READY = 'ready_for_payout';
    public const ON_HOLD = 'on_hold';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';
    public const REFUNDED = 'refunded';

    public const STATUSES = [
        self::DRAFT => 'Draft',
        self::AWAITING_PAYMENT => 'Awaiting payment',
        self::READY => 'Ready to collect',
        self::ON_HOLD => 'On hold',
        self::COMPLETED => 'Completed',
        self::CANCELLED => 'Cancelled',
        self::REFUNDED => 'Refunded',
    ];

    public const PURPOSES = [
        'Family support', 'Education / school fees', 'Medical expenses', 'Church / community donation',
        'Funeral / faikava / special occasion', 'Building / housing', 'Business', 'Savings', 'Other',
    ];

    public const INCOME_SOURCES = [
        'Salary / wages', 'Self-employed / business', 'Seasonal work (RSE)', 'Pension / benefit',
        'Savings', 'Gift from family', 'Other',
    ];

    public const PAYMENT_METHODS = [
        'internet_banking' => 'Internet banking',
        'cash' => 'Cash at office',
    ];

    protected $fillable = [
        'reference_no', 'transaction_date', 'transfer_type',
        'sender_customer_id', 'sender_office_id', 'receiver_customer_id', 'receiver_office_id',
        'local_currency', 'foreign_currency', 'exchange_rate_id', 'exchange_rate',
        'transfer_amount', 'converted_amount', 'service_fee', 'discount', 'total_payable', 'goods_total', 'cash_payout',
        'purpose', 'sender_income_source',
        'payment_method', 'payment_reference', 'payment_status', 'paid_at', 'payment_confirmed_by_staff_id',
        'payout_code', 'status', 'status_before_hold', 'hold_reason',
        'initiated_via', 'created_by_staff_id', 'completed_at', 'completed_by_staff_id', 'notes',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
        'exchange_rate' => 'float',
        'transfer_amount' => 'float',
        'converted_amount' => 'float',
        'service_fee' => 'float',
        'discount' => 'float',
        'total_payable' => 'float',
        'goods_total' => 'float',
        'cash_payout' => 'float',
    ];

    protected $hidden = ['payout_code'];

    // ── Relationships ─────────────────────────────────────────────

    public function senderCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'sender_customer_id');
    }

    public function receiverCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'receiver_customer_id');
    }

    public function senderOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'sender_office_id');
    }

    public function receiverOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'receiver_office_id');
    }

    public function rate(): BelongsTo
    {
        return $this->belongsTo(ExchangeRate::class, 'exchange_rate_id');
    }

    public function createdByStaff(): BelongsTo
    {
        return $this->belongsTo(StaffUser::class, 'created_by_staff_id');
    }

    public function completedByStaff(): BelongsTo
    {
        return $this->belongsTo(StaffUser::class, 'completed_by_staff_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TransactionEvent::class)->orderBy('id');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeForOffice(Builder $q, int $officeId): Builder
    {
        return $q->where(fn ($w) => $w->where('sender_office_id', $officeId)->orWhere('receiver_office_id', $officeId));
    }

    public function scopeVisibleToStaff(Builder $q): Builder
    {
        return $q->where('status', '!=', self::DRAFT);
    }

    // ── State machine ─────────────────────────────────────────────

    /** Allowed status moves. Hold/release and refund rules live here so web + API agree. */
    public const TRANSITIONS = [
        self::DRAFT => [self::AWAITING_PAYMENT, self::READY, self::CANCELLED],
        self::AWAITING_PAYMENT => [self::READY, self::ON_HOLD, self::CANCELLED],
        self::READY => [self::COMPLETED, self::ON_HOLD, self::REFUNDED],
        self::ON_HOLD => [self::AWAITING_PAYMENT, self::READY, self::CANCELLED, self::REFUNDED],
        self::COMPLETED => [],
        self::CANCELLED => [],
        self::REFUNDED => [],
    ];

    public function canMoveTo(string $status): bool
    {
        if (! in_array($status, self::TRANSITIONS[$this->status] ?? [], true)) {
            return false;
        }

        return match ($status) {
            self::CANCELLED => $this->payment_status !== 'paid',   // paid money must be refunded instead
            self::REFUNDED => $this->payment_status === 'paid',
            self::COMPLETED => $this->payment_status === 'paid',
            self::READY => $this->payment_status === 'paid' || $this->status === self::DRAFT,
            default => true,
        };
    }

    public function canHold(): bool
    {
        return $this->canMoveTo(self::ON_HOLD);
    }

    public function canRelease(): bool
    {
        return $this->status === self::ON_HOLD;
    }

    public function canCancel(): bool
    {
        return $this->canMoveTo(self::CANCELLED);
    }

    public function canRefund(): bool
    {
        return $this->canMoveTo(self::REFUNDED);
    }

    public function canConfirmPayment(): bool
    {
        return $this->status === self::AWAITING_PAYMENT && $this->payment_status === 'unpaid';
    }

    public function canPayout(): bool
    {
        return $this->status === self::READY && $this->payment_status === 'paid';
    }

    public function isEditableBySender(): bool
    {
        return $this->status === self::DRAFT;
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, [self::COMPLETED, self::CANCELLED, self::REFUNDED], true);
    }

    // ── Presentation helpers ──────────────────────────────────────

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    /** CSS modifier used by the badge component. */
    public function statusTone(): string
    {
        return match ($this->status) {
            self::DRAFT => 'neutral',
            self::AWAITING_PAYMENT => 'amber',
            self::READY => 'blue',
            self::ON_HOLD => 'orange',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red',
            self::REFUNDED => 'violet',
            default => 'neutral',
        };
    }

    public function typeLabel(): string
    {
        return $this->transfer_type === 'goods' ? 'Shopping' : 'Money';
    }

    public function paymentMethodLabel(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? '—';
    }

    public static function generateReferenceNo(): string
    {
        $prefix = 'MML'.now()->format('ymd');
        do {
            $ref = $prefix.'-'.strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 5));
        } while (static::where('reference_no', $ref)->exists());

        return $ref;
    }

    public static function generatePayoutCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /** JSON shape returned to the mobile app / customer portal. */
    public function toApiArray(?Customer $viewer = null): array
    {
        $isReceiver = $viewer && $viewer->id === $this->receiver_customer_id;
        $isSender = $viewer && $viewer->id === $this->sender_customer_id;

        return [
            'id' => $this->id,
            'reference_no' => $this->reference_no,
            'transaction_date' => $this->transaction_date?->toIso8601String(),
            'transfer_type' => $this->transfer_type,
            'direction' => $isReceiver && ! $isSender ? 'incoming' : 'outgoing',
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'local_currency' => $this->local_currency,
            'foreign_currency' => $this->foreign_currency,
            'exchange_rate' => $this->exchange_rate,
            'transfer_amount' => $this->transfer_amount,
            'converted_amount' => $this->converted_amount,
            'service_fee' => $this->service_fee,
            'discount' => $this->discount,
            'total_payable' => $this->total_payable,
            'goods_total' => $this->goods_total,
            'cash_payout' => $this->cash_payout,
            'purpose' => $this->purpose,
            'sender_income_source' => $this->sender_income_source,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'hold_reason' => $this->status === self::ON_HOLD ? $this->hold_reason : null,
            // The receiver sees the collection code once it can be collected. The sender also sees it
            // while the transfer is open, so they can pass it on to a receiver who has no app.
            'payout_code' => ($isReceiver && $this->status === self::READY)
                || ($isSender && $this->isOpen() && $this->status !== self::DRAFT) ? $this->payout_code : null,
            'sender' => $this->senderCustomer ? self::personArray($this->senderCustomer) : null,
            'receiver' => $this->receiverCustomer ? self::personArray($this->receiverCustomer) : null,
            'sender_office' => $this->senderOffice ? self::officeArray($this->senderOffice) : null,
            'receiver_office' => $this->receiverOffice ? self::officeArray($this->receiverOffice) : null,
            'items' => $this->relationLoaded('items') ? $this->items->map(fn ($i) => [
                'stock_item_id' => $i->stock_item_id, 'name' => $i->name, 'unit' => $i->unit,
                'quantity' => $i->quantity, 'unit_price' => $i->unit_price, 'line_total' => $i->line_total,
            ])->values() : [],
            'events' => $this->relationLoaded('events') ? $this->events->map(fn ($e) => [
                'status' => $e->to_status,
                'label' => self::STATUSES[$e->to_status] ?? $e->to_status,
                'remarks' => $e->remarks,
                'at' => $e->created_at?->toIso8601String(),
            ])->values() : [],
            'payment_instructions' => $isSender && $this->status === self::AWAITING_PAYMENT
                ? $this->paymentInstructions() : null,
        ];
    }

    public function paymentInstructions(): array
    {
        $office = $this->senderOffice;

        return [
            'method' => $this->payment_method,
            'amount' => $this->total_payable,
            'currency' => $this->local_currency,
            'reference' => $this->reference_no,
            'bank_name' => $office?->bank_name,
            'account_name' => $office?->bank_account_name,
            'account_number' => $office?->bank_account_number,
            'office_name' => $office?->name,
            'office_address' => $office?->address,
            'opening_hours' => $office?->opening_hours,
        ];
    }

    public static function personArray(Customer $c): array
    {
        return [
            'id' => $c->id, 'customer_code' => $c->customer_code, 'full_name' => $c->full_name,
            'given_name' => $c->given_name, 'family_name' => $c->family_name,
            'country' => $c->country, 'city' => $c->city, 'phone' => $c->phone, 'photo_url' => $c->photo_url,
            'is_verified' => $c->is_verified,
        ];
    }

    public static function officeArray(Office $o): array
    {
        return [
            'id' => $o->id, 'code' => $o->code, 'name' => $o->name, 'city' => $o->city, 'country' => $o->country,
            'country_code' => $o->country_code, 'address' => $o->address, 'phone' => $o->phone,
            'opening_hours' => $o->opening_hours, 'currency' => $o->currency, 'has_store' => $o->has_store,
            'office_type' => $o->office_type,
        ];
    }
}
