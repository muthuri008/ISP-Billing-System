<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkSession extends Model
{
    protected $fillable = [
        'service_account_id', 'router_id', 'session_id', 'started_at', 'ended_at',
        'input_octets', 'output_octets', 'nas_address', 'ip_address', 'status',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'input_octets' => 'integer',
            'output_octets' => 'integer',
        ];
    }

    public function serviceAccount(): BelongsTo { return $this->belongsTo(ServiceAccount::class); }
    public function router(): BelongsTo { return $this->belongsTo(Router::class); }
}
