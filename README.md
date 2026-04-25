# نظام نقطة البيع — POS Laravel

نظام متكامل لنقطة البيع مبني بـ **Laravel 11** و **PHP 8.2** مع دعم كامل للغتين العربية والإنجليزية.

---

## المتطلبات

| المتطلب | الإصدار |
|---------|---------|
| PHP | ^8.2 |
| MySQL / MariaDB | ^8.0 |
| Composer | ^2.x |
| Node.js | ^18.x |

---

## التثبيت السريع

```bash
# 1. استنساخ المشروع
git clone <repo-url>
cd POS-Laravel

# 2. تثبيت الاعتماديات
composer install
npm install

# 3. إعداد البيئة
cp .env.example .env
php artisan key:generate

# 4. ضبط قاعدة البيانات في .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pos_system
DB_USERNAME=root
DB_PASSWORD=your_password

# ⚠️ تأكد من تغيير كلمات المرور الافتراضية
ADMIN_PASSWORD=كلمة_مرور_قوية
CASHIER_PASSWORD=كلمة_مرور_قوية
WAREHOUSE_PASSWORD=كلمة_مرور_قوية

# 5. تشغيل الـ Migrations والـ Seeders
php artisan migrate --seed

# 6. نشر إعدادات Spatie Permissions
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate

# 7. تشغيل التطبيق
php artisan serve
```

---

## بيانات الدخول الافتراضية

| المستخدم | اسم الدخول | الصلاحيات |
|----------|------------|-----------|
| المدير العام | `admin` | كامل |
| أمين الصندوق | `cashier` | POS والمرتجعات فقط |
| مسؤول المخزن | `warehouse` | المخزن والموردين والتقارير |

> **تحذير:** غيّر كلمات المرور في `.env` قبل النشر للإنتاج.

---

## نظام الصلاحيات (RBAC)

| الصفحة | admin | cashier | warehouse |
|--------|-------|---------|-----------|
| Dashboard | ✅ | ✅ | ✅ |
| نقطة البيع (POS) | ✅ | ✅ | ✅ |
| المرتجعات | ✅ | ✅ | ✅ |
| المخزن / المنتجات | ✅ | ❌ | ✅ |
| الموردين | ✅ | ❌ | ✅ |
| أوامر الشراء | ✅ | ❌ | ✅ |
| المحاسبة | ✅ | ❌ | ❌ |
| التقارير المالية | ✅ | ❌ | ❌ |
| الإعدادات | ✅ | ❌ | ❌ |
| تقارير المبيعات والمخزون | ✅ | ❌ | ✅ |

---

## البنية المعمارية

```
app/
├── Http/
│   ├── Controllers/     # 14 Controller
│   ├── Middleware/
│   │   └── SetLocale.php
│   └── Requests/        # Form Requests للـ Validation
│       ├── StoreInvoiceRequest.php
│       ├── StoreProductRequest.php
│       ├── UpdateProductRequest.php
│       ├── StoreReturnRequest.php
│       └── StoreSupplierRequest.php
├── Models/              # 14 Model
└── Services/            # Business Logic
    ├── InvoiceService.php
    ├── StockService.php
    ├── AccountingService.php
    ├── PurchaseOrderService.php
    ├── ReturnService.php
    └── SequenceService.php   ← جديد: ترقيم آمن
```

---

## تشغيل الاختبارات

```bash
php artisan test
# أو
./vendor/bin/phpunit
```

---

## الإصلاحات المطبّقة (v1.1)

- ✅ نظام صلاحيات كامل (RBAC) باستخدام `spatie/laravel-permission`
- ✅ `SequenceService` — ترقيم ذري آمن يمنع تكرار الأرقام
- ✅ إضافة عمود `language` لجدول المستخدمين
- ✅ حساب الضريبة وحفظها بشكل صحيح في الفواتير
- ✅ التحقق من كميات المرتجعات قبل المعالجة
- ✅ `Cache::forget()` بدلاً من `Cache::flush()` في الإعدادات
- ✅ Form Request classes لجميع نقاط الـ API
- ✅ حذف ملفات `_all_*.php` المربكة
- ✅ Unit Tests للـ Services الأساسية
- ✅ Factories لجميع Models المستخدمة في الاختبارات
- ✅ `.env.example` محدّث بـ MySQL وكلمات مرور قوية
