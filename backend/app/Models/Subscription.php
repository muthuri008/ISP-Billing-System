<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'subscription_number', 'customer_id', 'package_id', 'status',
        'starts_at', 'ends_at', 'next_billing_at', 'auto_renew',
        'recurring_price', 'currency', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'next_billing_at' => 'date',
            'auto_renew' => 'boolean',
            'recurring_price' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function package(): BelongsTo { return $this->belongsTo(Package::class); }
}
