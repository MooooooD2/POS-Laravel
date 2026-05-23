<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'web';

        $permissions = [
            // Dashboard
            'view_dashboard',

            // POS
            'view_pos',
            'create_invoice',
            'search_products',

            // Returns
            'view_returns',
            'create_return',

            // Warehouse — products
            'view_warehouse',
            'add_product',
            'edit_product',
            'delete_product',
            'add_stock',

            // Warehouse — suppliers
            'view_suppliers',
            'add_supplier',
            'edit_supplier',
            'delete_supplier',

            // Warehouse — purchase orders
            'view_purchase_orders',
            'create_purchase_order',
            'approve_purchase_order',   // item 20
            'receive_purchase_order',

            // Warehouse — supplier payments & accounts
            'view_supplier_payments',
            'create_supplier_payment',

            // Accounting
            'view_accounting',
            'manage_accounts',
            'create_journal_entry',

            // Reports
            'view_reports',
            'view_financial_reports',

            // Settings
            'view_settings',
            'update_settings',

            // User & role management
            'manage_roles',
            'manage_permissions',

            // Tenant management (master-tenant only)
            'manage_tenants',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }

        // ── Roles ─────────────────────────────────────────────────────────────
        $admin = Role::firstOrCreate(['name' => 'admin',     'guard_name' => $guard]);
        $warehouse = Role::firstOrCreate(['name' => 'warehouse', 'guard_name' => $guard]);
        $cashier = Role::firstOrCreate(['name' => 'cashier',   'guard_name' => $guard]);

        // Admin gets everything except manage_tenants — that belongs to the master tenant only.
        // If this IS the master tenant, also grant manage_tenants.
        $isMaster = config('tenancy.master_tenant') &&
                    tenancy()->tenant?->id === config('tenancy.master_tenant');

        $adminPerms = Permission::where('name', '!=', 'manage_tenants')->get();
        if ($isMaster) {
            $adminPerms = Permission::all();
        }
        $admin->syncPermissions($adminPerms);

        // Warehouse: full warehouse + POS + returns (no accounting/settings/roles)
        $warehouse->syncPermissions([
            'view_dashboard',
            'view_pos', 'create_invoice', 'search_products',
            'view_returns', 'create_return',
            'view_warehouse',
            'add_product', 'edit_product', 'delete_product', 'add_stock',
            'view_suppliers', 'add_supplier', 'edit_supplier', 'delete_supplier',
            'view_purchase_orders', 'create_purchase_order', 'receive_purchase_order',
            'view_supplier_payments', 'create_supplier_payment',
            'view_reports',
        ]);

        // Cashier: POS and returns only
        $cashier->syncPermissions([
            'view_dashboard',
            'view_pos', 'create_invoice', 'search_products',
            'view_returns', 'create_return',
        ]);
    }
}
