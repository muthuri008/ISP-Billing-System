<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['invoice_number','customer_id','subscription_id','invoice_date','due_date','period_start','period_end','status','subtotal','discount_amount','tax_amount','total_amount','amount_paid','amount_due','currency','notes'];

    protected function casts(): array
    {
        return ['invoice_date'=>'date','due_date'=>'date','period_start'=>'date','period_end'=>'date','subtotal'=>'decimal:2','discount_amount'=>'decimal:2','tax_amount'=>'decimal:2','total_amount'=>'decimal:2','amount_paid'=>'decimal:2','amount_due'=>'decimal:2'];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function subscription(): BelongsTo { return $this->belongsTo(Subscription::class); }
    public function items(): HasMany { return $this->hasMany(InvoiceItem::class); }
    public function allocations(): HasMany { return $this->hasMany(PaymentAllocation::class); }
}
