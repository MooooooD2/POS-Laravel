<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Contracts\TenantDatabaseManager;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

/**
 * Tenant database manager for cPanel shared hosting.
 *
 * Instead of auto-creating databases (which requires privileges not available
 * on shared hosting), this manager expects the database to already exist.
 * The admin creates it manually in cPanel → MySQL Databases before registration.
 *
 * Database naming:
 *   Tenant code "mood_shop" → internal name "tenant_mood_shop"
 *   cPanel-resolved name   → "ffhzczwexx_tenant_mood_shop"
 *
 * Steps for each new tenant:
 *   1. Note the store code (e.g. mood_shop) from the registration form.
 *   2. In cPanel → MySQL Databases, create: ffhzczwexx_tenant_mood_shop
 *   3. Under "Add User to Database", add ffhzczwexx_nuqtah_pos with ALL PRIVILEGES.
 *   4. Submit the registration form — migrations run automatically.
 */
class CpanelTenantDatabaseManager implements TenantDatabaseManager
{
    protected ?string $connection = null;

    public function setConnection(string $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * Convert the internal tenancy database name to the cPanel-prefixed MySQL name.
     *
     * Internal:  tenant_mood_shop
     * cPanel:    ffhzczwexx_tenant_mood_shop
     */
    public function resolveDbName(string $name): string
    {
        $cpanelUser = config('tenancy.cpanel.username');
        return $cpanelUser ? "{$cpanelUser}_{$name}" : $name;
    }

    public function createDatabase(TenantWithDatabase $tenant): bool
    {
        $dbName  = $this->resolveDbName($tenant->database()->getName());
        $appUser = config('database.connections.' . ($this->connection ?? 'mysql') . '.username');

        if (!$this->databaseExists($tenant->database()->getName())) {
            throw new \RuntimeException(
                "Database [{$dbName}] does not exist. " .
                "Please create it in cPanel → MySQL Databases, " .
                "then grant ALL PRIVILEGES to [{$appUser}]."
            );
        }

        return true;
    }

    public function deleteDatabase(TenantWithDatabase $tenant): bool
    {
        // Databases are deleted manually in cPanel.
        return true;
    }

    public function databaseExists(string $name): bool
    {
        $dbName = $this->resolveDbName($name);
        $rows   = DB::connection($this->connection ?? 'mysql')
            ->select(
                'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?',
                [$dbName]
            );
        return \count($rows) > 0;
    }

    public function makeConnectionConfig(array $baseConfig, string $databaseName): array
    {
        $baseConfig['database'] = $this->resolveDbName($databaseName);
        return $baseConfig;
    }
}
