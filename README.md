# نظام نقطة البيع (POS) - Laravel
# Point of Sale System - Laravel

> تحويل كامل من Flask إلى Laravel مع دعم اللغتين العربية والإنجليزية

---

## 📋 متطلبات النظام / System Requirements

- PHP >= 8.1
- Composer >= 2.0
- MySQL >= 8.0 or MariaDB >= 10.4
- Node.js >= 18.x
- NPM >= 9.x

---

## 🚀 أوامر التثبيت الكاملة / Full Installation Commands

```bash
# 1. إنشاء مشروع Laravel جديد
composer create-project laravel/laravel pos-system

cd pos-system

# 2. تثبيت الحزم المطلوبة
composer require laravel/sanctum
composer require spatie/laravel-permission
composer require barryvdh/laravel-dompdf
composer require maatwebsite/excel
composer require mcamara/laravel-localization

# 3. نشر ملفات الإعداد
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider"

# 4. إعداد قاعدة البيانات في .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=pos_system
# DB_USERNAME=root
# DB_PASSWORD=your_password

# 5. إنشاء قاعدة البيانات
mysql -u root -p -e "CREATE DATABASE pos_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 6. تشغيل المايجريشنز والسيدرز
php artisan migrate --seed

# 7. تثبيت حزم npm
npm install
npm run build

# 8. رابط التخزين
php artisan storage:link

# 9. توليد مفتاح التطبيق
php artisan key:generate

# 10. تشغيل الخادم
php artisan serve
```

---

## 📁 هيكل الملفات الكاملة / Complete File Structure

```
pos-system/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── AuthController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── ProductController.php
│   │   │   ├── InvoiceController.php
│   │   │   ├── SupplierController.php
│   │   │   ├── PurchaseOrderController.php
│   │   │   ├── SupplierPaymentController.php
│   │   │   ├── SupplierAccountController.php
│   │   │   ├── AccountingController.php
│   │   │   ├── ReportController.php
│   │   │   └── ReturnController.php
│   │   ├── Middleware/
│   │   │   └── SetLocale.php
│   │   └── Requests/
│   │       ├── ProductRequest.php
│   │       ├── SupplierRequest.php
│   │       └── InvoiceRequest.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Product.php
│   │   ├── Invoice.php
│   │   ├── InvoiceItem.php
│   │   ├── StockMovement.php
│   │   ├── Supplier.php
│   │   ├── PurchaseOrder.php
│   │   ├── PurchaseOrderItem.php
│   │   ├── SupplierPayment.php
│   │   ├── SupplierAccount.php
│   │   ├── Account.php
│   │   ├── JournalEntry.php
│   │   ├── JournalEntryLine.php
│   │   ├── Return.php (SalesReturn)
│   │   └── ReturnItem.php
│   └── Services/
│       ├── InvoiceService.php
│       ├── StockService.php
│       ├── AccountingService.php
│       ├── PurchaseOrderService.php
│       └── ReturnService.php
├── database/
│   ├── migrations/
│   │   ├── create_users_table.php
│   │   ├── create_products_table.php
│   │   ├── create_invoices_table.php
│   │   ├── create_invoice_items_table.php
│   │   ├── create_stock_movements_table.php
│   │   ├── create_suppliers_table.php
│   │   ├── create_purchase_orders_table.php
│   │   ├── create_purchase_order_items_table.php
│   │   ├── create_supplier_payments_table.php
│   │   ├── create_supplier_accounts_table.php
│   │   ├── create_accounts_table.php
│   │   ├── create_journal_entries_table.php
│   │   ├── create_journal_entry_lines_table.php
│   │   ├── create_sales_returns_table.php
│   │   └── create_return_items_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── UserSeeder.php
│       ├── ProductSeeder.php
│       ├── SupplierSeeder.php
│       └── AccountSeeder.php
├── resources/
│   ├── lang/
│   │   ├── ar/
│   │   │   ├── auth.php
│   │   │   ├── pos.php
│   │   │   └── validation.php
│   │   └── en/
│   │       ├── auth.php
│   │       ├── pos.php
│   │       └── validation.php
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php
│       ├── auth/
│       │   └── login.blade.php
│       ├── dashboard/
│       │   └── index.blade.php
│       ├── warehouse/
│       │   └── index.blade.php
│       ├── pos/
│       │   └── index.blade.php
│       ├── suppliers/
│       │   └── index.blade.php
│       ├── purchase-orders/
│       │   └── index.blade.php
│       ├── supplier-payments/
│       │   └── index.blade.php
│       ├── supplier-accounts/
│       │   └── index.blade.php
│       ├── accounting/
│       │   └── index.blade.php
│       ├── reports/
│       │   └── index.blade.php
│       ├── financial-reports/
│       │   └── index.blade.php
│       └── returns/
│           └── index.blade.php
└── routes/
    ├── web.php
    └── api.php
```
