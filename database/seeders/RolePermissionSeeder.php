<?php
// database/seeders/RolePermissionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Artisan;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Guard name for all permissions/roles
        $guardName = 'web';

        // Define permissions with guard name
        $permissions = [
            // POS permissions
            'view_pos',
            'create_invoice',
            'search_products',

            // Warehouse permissions
            'view_warehouse',
            'add_product',
            'edit_product',
            'delete_product',
            'add_stock',

            // Supplier permissions
            'view_suppliers',
            'add_supplier',
            'edit_supplier',
            'delete_supplier',
            'view_purchase_orders',
            'create_purchase_order',
            'receive_purchase_order',
            'view_supplier_payments',
            'create_supplier_payment',

            // Return permissions
            'view_returns',
            'create_return',

            // Accounting permissions
            'view_accounting',
            'manage_accounts',
            'view_journal_entries',
            'create_journal_entry',

            // Report permissions
            'view_reports',
            'view_sales_report',
            'view_stock_report',
            'view_financial_reports',

            // Settings permissions
            'view_settings',
            'update_settings',
            'manage_roles',
            'manage_permissions',

            // Dashboard permission
            'view_dashboard',
        ];

        // Create permissions with guard name
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guardName
            ]);
        }

        // Create roles with guard name
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => $guardName
        ]);

        $warehouseRole = Role::firstOrCreate([
            'name' => 'warehouse',
            'guard_name' => $guardName
        ]);

        $cashierRole = Role::firstOrCreate([
            'name' => 'cashier',
            'guard_name' => $guardName
        ]);

        // Assign all permissions to admin
        $adminRole->syncPermissions(Permission::all());

        // Assign warehouse permissions
        $warehouseRole->syncPermissions([
            'view_dashboard',
            'view_warehouse',
            'add_product',
            'edit_product',
            'delete_product',
            'add_stock',
            'view_suppliers',
            'add_supplier',
            'edit_supplier',
            'delete_supplier',
            'view_purchase_orders',
            'create_purchase_order',
            'receive_purchase_order',
            'view_supplier_payments',
            'create_supplier_payment',
            'view_returns',
            'create_return',
            'view_reports',
            'view_sales_report',
            'view_stock_report',
            'view_pos',
            'create_invoice',
            'search_products',
        ]);

        // Assign cashier permissions
        $cashierRole->syncPermissions([
            'view_dashboard',
            'view_pos',
            'create_invoice',
            'search_products',
            'view_returns',
            'create_return',
        ]);

        Artisan::call('cache:clear');
    }
}