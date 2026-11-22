<?php

namespace Tests\Feature\Billing;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Billing\InvoiceSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PaymentAllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_and_full_payment_allocations_update_valid_invoice_statuses(): void
    {
        $customer = Customer::factory()->create();
        $invoice = Invoice::create([
            'invoice_number' => 'INV-ALLOC-TEST',
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'issued',
            'subtotal' => 1000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 1000,
            'amount_paid' => 0,
            'amount_due' => 1000,
            'currency' => 'KES',
        ]);

        $firstPayment = Payment::create([
            'payment_number' => 'PAY-ALLOC-001',
            'customer_id' => $customer->id,
            'method' => 'manual',
            'amount' => 400,
            'currency' => 'KES',
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        $settlement = app(InvoiceSettlementService::class);
        $settlement->allocate($firstPayment, $invoice);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'partially_paid',
            'amount_paid' => 400,
            'amount_due' => 600,
        ]);

        $secondPayment = Payment::create([
            'payment_number' => 'PAY-ALLOC-002',
            'customer_id' => $customer->id,
            'method' => 'manual',
            'amount' => 600,
            'currency' => 'KES',
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        $settlement->allocate($secondPayment, $invoice->fresh());

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
            'amount_paid' => 1000,
            'amount_due' => 0,
        ]);
    }

    public function test_uncompleted_payments_cannot_be_allocated(): void
    {
        $customer = Customer::factory()->create();
        $invoice = Invoice::create([
            'invoice_number' => 'INV-ALLOC-PENDING',
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'issued',
            'subtotal' => 100,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 100,
            'amount_paid' => 0,
            'amount_due' => 100,
            'currency' => 'KES',
        ]);
        $payment = Payment::create([
            'payment_number' => 'PAY-ALLOC-PENDING',
            'customer_id' => $customer->id,
            'method' => 'mpesa',
            'amount' => 100,
            'currency' => 'KES',
            'status' => 'pending',
        ]);

        $this->expectException(RuntimeException::class);
        app(InvoiceSettlementService::class)->allocate($payment, $invoice);
    }

    public function test_void_invoice_cannot_receive_a_payment_allocation(): void
    {
        $customer = Customer::factory()->create();
        $invoice = Invoice::create([
            'invoice_number' => 'INV-VOID-GUARD',
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'void',
            'subtotal' => 100,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 100,
            'amount_paid' => 0,
            'amount_due' => 100,
            'currency' => 'KES',
        ]);
        $payment = Payment::create([
            'payment_number' => 'PAY-VOID-GUARD',
            'customer_id' => $customer->id,
            'method' => 'manual',
            'amount' => 100,
            'currency' => 'KES',
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        app(\App\Services\Billing\PaymentServiceV2::class)->allocate($payment, $invoice, 100);
    }

    public function test_payment_currency_must_match_invoice_currency(): void
    {
        $customer = Customer::factory()->create();
        $invoice = Invoice::create([
            'invoice_number' => 'INV-CURRENCY-GUARD',
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'issued',
            'subtotal' => 100,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 100,
            'amount_paid' => 0,
            'amount_due' => 100,
            'currency' => 'USD',
        ]);
        $payment = Payment::create([
            'payment_number' => 'PAY-CURRENCY-GUARD',
            'customer_id' => $customer->id,
            'method' => 'manual',
            'amount' => 100,
            'currency' => 'KES',
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        app(\App\Services\Billing\PaymentServiceV2::class)->allocate($payment, $invoice, 100);
    }
}
