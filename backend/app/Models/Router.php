<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class Router extends Model
{
    protected $hidden = ['api_password', 'radius_secret'];

    protected $fillable = [
        'name',
        'hostname',
        'api_port',
        'rest_scheme',
        'rest_port',
        'radius_secret',
        'api_username',
        'api_password',
        'status',
        'last_seen_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'api_password' => 'encrypted',
            'radius_secret' => 'encrypted',
            'last_seen_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function serviceAccounts(): HasMany
    {
        return $this->hasMany(ServiceAccount::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(NetworkSession::class);
    }
}
