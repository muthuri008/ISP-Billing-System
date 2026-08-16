<?php

namespace App\Services\Audit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditLogger
{
    public function log(string $action, string $entityType, string|int|null $entityId = null, array $metadata = []): void
    {
        if (! DB::getSchemaBuilder()->hasTable('audit_logs')) return;
        DB::table('audit_logs')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId ? (string) $entityId : null,
            'metadata' => json_encode($metadata),
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 1000),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
