<?php

namespace App\Services\Network;

use App\Models\NetworkSession;
use App\Models\ServiceAccount;
use Illuminate\Support\Carbon;

class SessionAccountingService
{
    public function start(ServiceAccount $account, array $data): NetworkSession
    {
        return NetworkSession::updateOrCreate(
            ['session_id' => $data['session_id'], 'router_id' => $data['router_id']],
            [
                'service_account_id' => $account->id,
                'started_at' => $data['started_at'] ?? now(),
                'ended_at' => null,
                'input_octets' => (int) ($data['input_octets'] ?? 0),
                'output_octets' => (int) ($data['output_octets'] ?? 0),
                'nas_address' => $data['nas_address'] ?? null,
                'ip_address' => $data['ip_address'] ?? null,
            ]
        );
    }

    public function interim(string $sessionId, int $routerId, array $data): ?NetworkSession
    {
        $session = NetworkSession::where('session_id', $sessionId)->where('router_id', $routerId)->first();
        if (! $session) return null;

        $session->update([
            'input_octets' => (int) ($data['input_octets'] ?? $session->input_octets),
            'output_octets' => (int) ($data['output_octets'] ?? $session->output_octets),
            'ip_address' => $data['ip_address'] ?? $session->ip_address,
        ]);
        return $session->fresh();
    }

    public function stop(string $sessionId, int $routerId, array $data = []): ?NetworkSession
    {
        $session = NetworkSession::where('session_id', $sessionId)->where('router_id', $routerId)->first();
        if (! $session) return null;

        $session->update([
            'ended_at' => $data['ended_at'] ?? now(),
            'input_octets' => (int) ($data['input_octets'] ?? $session->input_octets),
            'output_octets' => (int) ($data['output_octets'] ?? $session->output_octets),
        ]);
        return $session->fresh();
    }
}
