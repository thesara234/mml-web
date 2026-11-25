<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class StaffUser extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'office_id', 'given_name', 'family_name', 'email', 'phone', 'password', 'role', 'is_active', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_active' => 'boolean',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
    ];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function fullName(): string
    {
        return trim("{$this->given_name} {$this->family_name}");
    }

    public function initials(): string
    {
        return strtoupper(mb_substr($this->given_name, 0, 1).mb_substr($this->family_name, 0, 1));
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Managers and admins maintain rates, fees, stock, offices and staff. */
    public function canManage(): bool
    {
        return in_array($this->role, ['admin', 'manager'], true);
    }
}
