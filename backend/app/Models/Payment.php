<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['payment_number','customer_id','method','amount','currency','external_reference','transaction_reference','status','paid_at','notes','metadata'];
    protected function casts(): array { return ['amount'=>'decimal:2','paid_at'=>'datetime','metadata'=>'array']; }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function allocations(): HasMany { return $this->hasMany(PaymentAllocation::class); }
}
