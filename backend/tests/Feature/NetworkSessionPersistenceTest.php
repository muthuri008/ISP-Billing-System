<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\NetworkSession;
use App\Models\ServiceAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetworkSessionPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_network_session_status_and_duration_are_persisted(): void
    {
        $customer = Customer::factory()->create();
        $account = ServiceAccount::create([
            'customer_id' => $customer->id,
            'username' => 'session-test-user',
            'access_type' => 'pppoe',
            'status' => 'active',
        ]);

        $session = NetworkSession::create([
            'service_account_id' => $account->id,
            'session_id' => 'session-test-001',
            'started_at' => now()->subMinutes(2),
            'status' => 'online',
        ]);

        $session->update([
            'status' => 'closed',
            'duration_seconds' => 120,
        ]);

        $this->assertDatabaseHas('network_sessions', [
            'id' => $session->id,
            'status' => 'closed',
            'duration_seconds' => 120,
        ]);
    }
}
