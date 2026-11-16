<?php

namespace Tests\\Feature\\Payments;

use App\\Models\\Customer;
use App\\Models\\Payment;
use App\\Services\\Billing\\InvoiceSettlementService;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\TestCase;

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
            'metadata' => ['invoice_id' => 1],
        ]);

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
}
