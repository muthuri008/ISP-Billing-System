<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\Portal\CustomerDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDashboardCurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_does_not_add_balances_from_different_currencies(): void
    {
        $customer = Customer::factory()->create();

        foreach ([['KES', 1500, 'issued'], ['USD', 25, 'overdue']] as [$currency, $amount, $status]) {
            Invoice::create([
                'invoice_number' => 'INV-CURRENCY-'.str()->upper(str()->random(8)),
                'customer_id' => $customer->id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->subDay()->toDateString(),
                'status' => $status,
                'subtotal' => $amount,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => $amount,
                'amount_paid' => 0,
                'amount_due' => $amount,
                'currency' => $currency,
            ]);
        }

        $summary = app(CustomerDashboardService::class)->summary($customer);

        $outstanding = $summary['billing']['outstanding_by_currency']->toArray();
        $overdue = $summary['billing']['overdue_by_currency']->toArray();

        $this->assertEquals(1500.0, $outstanding['KES']);
        $this->assertEquals(25.0, $outstanding['USD']);
        $this->assertEquals(25.0, $overdue['USD']);
        $this->assertArrayNotHasKey('KES', $overdue);
    }
}
