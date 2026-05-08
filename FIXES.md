# سجل الإصلاحات الأمنية — POS Laravel

## ملخص التغييرات

| # | الثغرة | الملفات المعدّلة | الخطورة |
|---|--------|-----------------|---------|
| FIX-1 | SESSION_ENCRYPT=false | `.env.example` | متوسطة |
| FIX-2 | خصم بلا حد أقصى | `StoreInvoiceRequest`, `InvoiceService`, `UserSeeder`, migration | عالية |
| FIX-3 | ReturnController بلا authorize() | `ReturnController`, `AppServiceProvider` | متوسطة |
| FIX-4 | auditTrail بلا تحقق من وجود المنتج | `StockReconciliationController` | منخفضة |
| FIX-5 | SequenceService يفشل صامتاً | `SequenceService` | منخفضة |
| FIX-6 | Session timeout غير متزامن | `SessionSecurity` | منخفضة |
| FIX-7 | CSP يسمح بـ unsafe-inline للـ scripts | `SecurityHeaders` | متوسطة |
| FIX-8 | AnomalyDetection لا تراقب الخصم | `AnomalyDetection` | منخفضة |

---

## تفاصيل كل إصلاح

### FIX-1: تشفير الجلسات
**المشكلة:** `SESSION_ENCRYPT=false` — الجلسات مخزنة كـ plaintext في قاعدة البيانات.

**الإصلاح:** تغيير القيمة الافتراضية إلى `SESSION_ENCRYPT=true` في `.env.example`.

**ملاحظة:** يجب تحديث ملف `.env` الفعلي في الخادم بنفس القيمة.

```env
SESSION_ENCRYPT=true
```

---

### FIX-2: حد الخصم الأقصى
**المشكلة:** أي كاشير يمكنه تطبيق خصم 100% على أي فاتورة بدون قيود — خسارة مالية مباشرة.

**الإصلاح:**
1. إضافة `MAX_DISCOUNT_PERCENT=20` في `.env.example`
2. قراءة الحد من `Setting::get('max_discount_percent')` في `InvoiceService`
3. إضافة migration يُدخل الإعداد في قاعدة البيانات
4. `UserSeeder` يرفض الـ seed إذا كانت كلمات المرور فارغة

**كيفية تغيير الحد:**
- من `.env`: `MAX_DISCOUNT_PERCENT=30`
- أو من واجهة الإعدادات في النظام (settings table)

---

### FIX-3: Authorization في ReturnController
**المشكلة:** `ReturnController::store()` لم يكن يستدعي `$this->authorize()` داخلياً — الاعتماد فقط على route middleware.

**الإصلاح:**
1. إضافة `$this->authorize('create', SalesReturn::class)` في الـ controller
2. تسجيل جميع الـ Policies صراحةً في `AppServiceProvider::$policies`

---

### FIX-4: auditTrail بلا تحقق
**المشكلة:** `/api/stock/audit-trail/{productId}` يقبل أي رقم كـ productId حتى لو المنتج غير موجود — يُرجع خطأ DB غير معالج.

**الإصلاح:** استخدام `Product::findOrFail($productId)` قبل استدعاء الـ service — يُرجع 404 نظيفاً.

---

### FIX-5: SequenceService الصامت
**المشكلة:** عند عدم وجود الـ sequence، الكود يُعيد `id=1` دائماً بدون تسجيل أي خطأ — فواتير بنفس الرقم ممكنة.

**الإصلاح:** إضافة `Log::warning()` عند الـ fallback + إعادة قراءة القيمة الفعلية من DB بعد الإنشاء.

---

### FIX-6: Session Timeout غير متزامن
**المشكلة:** `SessionSecurity` كانت تستخدم `8 * 3600` ثابتاً بينما `.env` يحدد `SESSION_LIFETIME=120` دقيقة — تعارض يُربك المستخدمين.

**الإصلاح:** استخدام `config('session.lifetime') * 60` بدلاً من القيمة الثابتة.

```env
# الآن SESSION_LIFETIME=480 (8 ساعات) في .env.example
SESSION_LIFETIME=480
```

---

### FIX-7: CSP و unsafe-inline
**المشكلة:** `script-src 'unsafe-inline'` يُبطل فعالية CSP في منع XSS.

**الإصلاح:** إزالة `unsafe-inline` من `script-src` فقط. `style-src` لا تزال تحتوي عليه لأن إزالتها تتطلب نقل كل الـ inline styles لملفات منفصلة (تحسين مستقبلي).

---

### FIX-8: مراقبة محاولات تجاوز الخصم
**المشكلة:** محاولات تجاوز حد الخصم لم تكن مرصودة.

**الإصلاح:** إضافة `detectDiscountCapViolation()` في `AnomalyDetection` — تُسجّل تحذيراً بعد 3 محاولات متكررة.

---

## خطوات التطبيق

```bash
# 1. تحديث .env
SESSION_ENCRYPT=true
SESSION_LIFETIME=480
MAX_DISCOUNT_PERCENT=20
ADMIN_PASSWORD=YourStrongPassword@123
CASHIER_PASSWORD=YourStrongPassword@456
WAREHOUSE_PASSWORD=YourStrongPassword@789

# 2. تشغيل الـ migration الجديد
php artisan migrate

# 3. تحديث كاش الصلاحيات
php artisan permission:cache-reset

# 4. تشغيل الاختبارات
php artisan test

# 5. مسح الكاش
php artisan cache:clear
php artisan config:clear
```

---

## ما تبقى (تحسينات مستقبلية)

| التحسين | الأولوية | الوصف |
|---------|----------|-------|
| 2FA للـ Admin | عالية | إضافة TOTP للمدير فقط في المرحلة الأولى |
| إزالة unsafe-inline من CSS | متوسطة | نقل الـ inline styles لملفات منفصلة |
| نسخ احتياطية تلقائية | عالية | إعداد Laravel Backup |
| Redis للـ Cache | منخفضة | عند زيادة المستخدمين |

