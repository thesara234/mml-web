<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\StaffUser;

/** Who performed an action — recorded on every transaction event. */
class Actor
{
    public function __construct(
        public readonly string $type,
        public readonly ?int $id,
        public readonly string $name,
    ) {}

    public static function staff(StaffUser $s): self
    {
        return new self('staff', $s->id, $s->fullName().' ('.($s->office?->code ?? 'MML').')');
    }

    public static function customer(Customer $c): self
    {
        return new self('customer', $c->id, $c->full_name);
    }

    public static function system(): self
    {
        return new self('system', null, 'System');
    }

    public function isStaff(): bool
    {
        return $this->type === 'staff';
    }
}
