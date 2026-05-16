<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;

    protected $connection = 'mysql';  // Should be 'mysql' not 'tenant'

    public static function getCustomColumns(): array
    {
        return ['id', 'name', 'code', 'plan', 'is_active'];
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];
}