<?php

namespace Tests\Feature\Payments;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Payments\MpesaGateway;
use App\Services\Payments\MpesaPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class MpesaInvoiceInitiationTest extends TestCase
{
    use RefreshDatabase;

    private function invoice(Customer $customer, string $status = 'issued', string $currency = 'KES'): Invoice
    {
        return Invoice::create([
            'invoice_number' => 'INV-STK-'.str()->upper(str()->random(8)),
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => $status,
            'subtotal' => 1500,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 1500,
            'amount_paid' => 0,
            'amount_due' => 1500,
            'currency' => $currency,
        ]);
    }

    public function test_stk_push_rejects_void_invoices(): void
    {
        $customer = Customer::factory()->create();
        $invoice = $this->invoice($customer, 'void');

        $this->mock(MpesaGateway::class)->shouldNotReceive('stkPush');

        $this->expectException(RuntimeException::class);
        app(MpesaPaymentService::class)->initiateInvoiceStkPush($invoice, '0712345678');
    }

    public function test_stk_push_rejects_non_kes_invoices(): void
    {
        $customer = Customer::factory()->create();
        $invoice = $this->invoice($customer, 'issued', 'USD');

        $this->mock(MpesaGateway::class)->shouldNotReceive('stkPush');

        $this->expectException(RuntimeException::class);
        app(MpesaPaymentService::class)->initiateInvoiceStkPush($invoice, '0712345678');
    }

    public function test_second_stk_push_is_blocked_while_a_request_is_pending(): void
    {
        $customer = Customer::factory()->create();
        $invoice = $this->invoice($customer);

        Payment::create([
            'payment_number' => 'PAY-STK-PENDING',
            'customer_id' => $customer->id,
            'method' => 'mpesa',
            'amount' => 1500,
            'currency' => 'KES',
            'status' => 'pending',
            'metadata' => ['invoice_id' => $invoice->id, 'phone' => '254712345678'],
        ]);

        $this->mock(MpesaGateway::class)->shouldNotReceive('stkPush');

        $this->expectException(RuntimeException::class);
        app(MpesaPaymentService::class)->initiateInvoiceStkPush($invoice, '0712345678');
    }
}
