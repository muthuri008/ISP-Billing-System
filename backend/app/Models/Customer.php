<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_number', 'first_name', 'last_name', 'email', 'phone',
        'national_id', 'status', 'billing_type', 'registered_at', 'notes',
    ];

    protected function casts(): array
    {
        return ['registered_at' => 'date'];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
