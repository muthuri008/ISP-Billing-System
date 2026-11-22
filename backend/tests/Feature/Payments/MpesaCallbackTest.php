<?php

namespace Tests\Feature\Payments;

use App\Models\Customer;
use App\Models\Payment;
use App\Services\Billing\InvoiceSettlementService;
use App\Services\Payments\MpesaGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MpesaCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_caller_supplied_payment_fields_are_rejected(): void
    {
        $this->postJson('/api/v1/payments/mpesa/callback', [
            'transaction_reference' => 'FAKE-RECEIPT',
            'amount' => 1500,
            'customer_id' => 1,
            'currency' => 'KES',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_callback_without_a_matching_stk_request_cannot_create_a_payment(): void
    {
        $this->postJson('/api/v1/payments/mpesa/callback', [
            'Body' => [
                'stkCallback' => [
                    'CheckoutRequestID' => 'unknown-checkout-request',
                    'ResultCode' => 0,
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'Amount', 'Value' => 1500],
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'QAB123XYZ'],
                            ['Name' => 'PhoneNumber', 'Value' => 254712345678],
                        ],
                    ],
                ],
            ],
        ])->assertNotFound();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_successful_callback_is_correlated_and_idempotent(): void
    {
        $customer = Customer::factory()->create();
        $payment = Payment::create([
            'payment_number' => 'PAY-CALLBACK-TEST',
            'customer_id' => $customer->id,
            'method' => 'mpesa',
            'amount' => 1500,
            'currency' => 'KES',
            'external_reference' => 'ws_CO_test_checkout',
            'status' => 'pending',
            'metadata' => ['phone' => '254712345678'],
        ]);

        $this->mock(MpesaGateway::class)
            ->shouldReceive('queryStkPush')
            ->once()
            ->with('ws_CO_test_checkout')
            ->andReturn(['ResultCode' => 0]);

        $this->mock(InvoiceSettlementService::class)
            ->shouldReceive('settlePayment')
            ->once()
            ->andReturnUsing(fn (Payment $settled) => $settled);

        $payload = [
            'Body' => [
                'stkCallback' => [
                    'CheckoutRequestID' => 'ws_CO_test_checkout',
                    'ResultCode' => 0,
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'Amount', 'Value' => 1500],
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'QAB123XYZ'],
                            ['Name' => 'PhoneNumber', 'Value' => 254712345678],
                        ],
                    ],
                ],
            ],
        ];

        $this->postJson('/api/v1/payments/mpesa/callback', $payload)->assertOk();
        $this->postJson('/api/v1/payments/mpesa/callback', $payload)->assertOk();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'completed',
            'transaction_reference' => 'QAB123XYZ',
            'amount' => '1500.00',
        ]);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_successful_callback_is_recorded_for_manual_review_if_invoice_is_no_longer_payable(): void
    {
        $customer = Customer::factory()->create();
        $invoice = \App\Models\Invoice::create([
            'invoice_number' => 'INV-MPESA-PAID',
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'status' => 'paid',
            'subtotal' => 1500,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 1500,
            'amount_paid' => 1500,
            'amount_due' => 0,
            'currency' => 'KES',
        ]);
        $payment = Payment::create([
            'payment_number' => 'PAY-MPESA-REVIEW',
            'customer_id' => $customer->id,
            'method' => 'mpesa',
            'amount' => 1500,
            'currency' => 'KES',
            'external_reference' => 'ws_CO_manual_review',
            'status' => 'pending',
            'metadata' => ['phone' => '254712345678', 'invoice_id' => $invoice->id],
        ]);

        $this->mock(MpesaGateway::class)
            ->shouldReceive('queryStkPush')
            ->once()
            ->with('ws_CO_manual_review')
            ->andReturn(['ResultCode' => 0]);

        $payload = [
            'Body' => [
                'stkCallback' => [
                    'CheckoutRequestID' => 'ws_CO_manual_review',
                    'ResultCode' => 0,
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'Amount', 'Value' => 1500],
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'QAB-REVIEW-01'],
                            ['Name' => 'PhoneNumber', 'Value' => 254712345678],
                        ],
                    ],
                ],
            ],
        ];

        $this->postJson('/api/v1/payments/mpesa/callback', $payload)->assertOk();

        $payment->refresh();
        $this->assertSame('completed', $payment->status);
        $this->assertSame('requires_manual_review', data_get($payment->metadata, 'allocation_status'));
        $this->assertDatabaseCount('payment_allocations', 0);
    }
}
