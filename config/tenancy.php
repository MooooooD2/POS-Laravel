<?php

declare(strict_types=1);

return [

    'tenant_model' => \App\Models\Tenant::class,

    'id_generator' => \Stancl\Tenancy\UUIDGenerator::class,

    // Not using domain-based tenancy – identification is via session after login
    'central_domains' => [],

    'bootstrappers' => [
        \Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        // CacheTenancyBootstrapper requires a taggable driver (Redis/Memcached).
        // For file/array cache, Spatie permission cache is flushed in InitializeTenancyBySession.
        // \Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
    ],

    'database' => [
        'central_connection' => env('DB_CONNECTION', 'mysql'),
        'template_tenant_connection' => null,

        // Tenant DB name: tenant_{id}  (e.g. tenant_550e8400-e29b-41d4-a716-446655440000)
        'prefix' => 'tenant_',
        'suffix' => '',

        'managers' => [
            'sqlite'  => \Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
            'mysql'   => env('DB_ADMIN_USERNAME')
                ? \App\Services\PrivilegedTenantDatabaseManager::class
                : \Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'mariadb' => env('DB_ADMIN_USERNAME')
                ? \App\Services\PrivilegedTenantDatabaseManager::class
                : \Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'pgsql'   => \Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
        ],
    ],

    // cPanel database name prefix.
    // On cPanel hosts all database names must start with the account username.
    // Set CPANEL_USERNAME in .env; leave empty on non-cPanel servers.
    'cpanel_username' => env('CPANEL_USERNAME', ''),

    'cache' => [
        'tag_base' => 'tenant',
    ],

    'filesystem' => [
        'suffix_base' => 'tenant_',
        'disks' => [],
        'root_override' => [],
    ],

    'redis' => [
        'prefix_base' => 'tenant',
        'prefixed_connections' => [],
    ],

    'queue' => [],

    'seeder_parameters' => [],

    // Tenant migrations live alongside central migrations.
    // Run: php artisan tenants:migrate
    'migration_parameters' => [
        '--path'      => [database_path('migrations')],
        '--realpath'  => true,
        '--force'     => true,
    ],

    'routes' => false,

    'unique_identifier_generators' => [],

    // The tenant whose admin can manage other tenants via /admin/tenants.
    // Set MASTER_TENANT_ID in .env after creating your first tenant.
    'master_tenant' => env('MASTER_TENANT_ID'),
];
