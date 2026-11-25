<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'customer_code', 'given_name', 'family_name', 'date_of_birth', 'gender', 'residential_address', 'city',
        'country', 'phone', 'email', 'passport_photo_path', 'id_type', 'id_number', 'id_expiry', 'password',
        'app_access_enabled', 'created_by_office_id', 'registered_by_staff_id', 'registered_by_customer_id',
        'is_verified', 'verified_at', 'verified_by_staff_id', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token', 'id_number'];

    protected $appends = ['full_name', 'photo_url'];

    protected $casts = [
        'date_of_birth' => 'date:Y-m-d',
        'id_expiry' => 'date:Y-m-d',
        'verified_at' => 'datetime',
        'app_access_enabled' => 'boolean',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    public function createdByOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'created_by_office_id');
    }

    public function registeredByStaff(): BelongsTo
    {
        return $this->belongsTo(StaffUser::class, 'registered_by_staff_id');
    }

    public function registeredByCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'registered_by_customer_id');
    }

    /** People this customer sends to. */
    public function receivers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_receivers', 'sender_customer_id', 'receiver_customer_id')
            ->withPivot(['relationship', 'preferred_office_id'])
            ->withTimestamps();
    }

    /** People who send to this customer. */
    public function senders(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_receivers', 'receiver_customer_id', 'sender_customer_id')
            ->withPivot(['relationship'])
            ->withTimestamps();
    }

    public function sentTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'sender_customer_id');
    }

    public function receivedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'receiver_customer_id');
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->given_name} {$this->family_name}");
    }

    public function fullName(): string
    {
        return $this->full_name;
    }

    public function initials(): string
    {
        return strtoupper(mb_substr($this->given_name, 0, 1).mb_substr($this->family_name, 0, 1));
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->passport_photo_path ? Storage::disk('public')->url($this->passport_photo_path) : null;
    }

    public function age(): ?int
    {
        return $this->date_of_birth?->age;
    }

    /** Search by full name, customer code, phone or email (works on MySQL and SQLite). */
    public function scopeSearch(\Illuminate\Database\Eloquent\Builder $q, string $term): \Illuminate\Database\Eloquent\Builder
    {
        $term = trim($term);
        $full = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite'
            ? "(given_name || ' ' || family_name)"
            : "CONCAT(given_name, ' ', family_name)";

        return $q->where(function ($w) use ($term, $full) {
            $w->whereRaw("{$full} like ?", ['%'.$term.'%'])
                ->orWhere('customer_code', 'like', '%'.$term.'%')
                ->orWhere('phone', 'like', '%'.$term.'%')
                ->orWhere('email', 'like', '%'.$term.'%');
        });
    }

    /** Stricter search used by customers: exact code/phone, or name contains. */
    public function scopeReceiverLookup(\Illuminate\Database\Eloquent\Builder $q, string $term): \Illuminate\Database\Eloquent\Builder
    {
        $term = trim($term);
        $full = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite'
            ? "(given_name || ' ' || family_name)"
            : "CONCAT(given_name, ' ', family_name)";

        return $q->where(function ($w) use ($term, $full) {
            $w->where('customer_code', $term)
                ->orWhere('phone', $term)
                ->orWhereRaw("{$full} like ?", ['%'.$term.'%']);
        });
    }

    public static function generateCustomerCode(): string
    {
        $next = (int) (static::max('id') ?? 0) + 1;
        do {
            $code = 'MML-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (static::where('customer_code', $code)->exists());

        return $code;
    }
}
