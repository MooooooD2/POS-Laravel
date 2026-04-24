<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General - عام
            ['key' => 'store_name',       'value' => 'نظام نقطة البيع',   'type' => 'string',  'group' => 'general', 'label_ar' => 'اسم المتجر',           'label_en' => 'Store Name'],
            ['key' => 'store_address',    'value' => 'القاهرة، مصر',       'type' => 'string',  'group' => 'general', 'label_ar' => 'عنوان المتجر',         'label_en' => 'Store Address'],
            ['key' => 'store_phone',      'value' => '01000000000',        'type' => 'string',  'group' => 'general', 'label_ar' => 'هاتف المتجر',          'label_en' => 'Store Phone'],
            ['key' => 'store_email',      'value' => '',                   'type' => 'string',  'group' => 'general', 'label_ar' => 'البريد الإلكتروني',    'label_en' => 'Store Email'],
            ['key' => 'currency',         'value' => 'EGP',                'type' => 'string',  'group' => 'general', 'label_ar' => 'العملة',               'label_en' => 'Currency'],
            ['key' => 'currency_symbol',  'value' => 'ج.م',                'type' => 'string',  'group' => 'general', 'label_ar' => 'رمز العملة',           'label_en' => 'Currency Symbol'],
            ['key' => 'default_language', 'value' => 'ar',                 'type' => 'string',  'group' => 'general', 'label_ar' => 'اللغة الافتراضية',     'label_en' => 'Default Language'],

            // Tax - الضريبة
            ['key' => 'tax_enabled',      'value' => '0',                  'type' => 'boolean', 'group' => 'tax',     'label_ar' => 'تفعيل الضريبة',        'label_en' => 'Enable Tax'],
            ['key' => 'tax_rate',         'value' => '14',                 'type' => 'number',  'group' => 'tax',     'label_ar' => 'نسبة الضريبة (%)',     'label_en' => 'Tax Rate (%)'],
            ['key' => 'tax_name_ar',      'value' => 'ضريبة القيمة المضافة','type' => 'string', 'group' => 'tax',     'label_ar' => 'اسم الضريبة (عربي)',   'label_en' => 'Tax Name (Arabic)'],
            ['key' => 'tax_name_en',      'value' => 'VAT',                'type' => 'string',  'group' => 'tax',     'label_ar' => 'اسم الضريبة (إنجليزي)','label_en' => 'Tax Name (English)'],
            ['key' => 'tax_inclusive',    'value' => '0',                  'type' => 'boolean', 'group' => 'tax',     'label_ar' => 'السعر شامل الضريبة',   'label_en' => 'Price Includes Tax'],
            ['key' => 'tax_number',       'value' => '',                   'type' => 'string',  'group' => 'tax',     'label_ar' => 'الرقم الضريبي',        'label_en' => 'Tax Number'],

            // Invoice - الفاتورة
            ['key' => 'invoice_prefix',   'value' => 'INV',                'type' => 'string',  'group' => 'invoice', 'label_ar' => 'بادئة رقم الفاتورة',  'label_en' => 'Invoice Prefix'],
            ['key' => 'invoice_footer',   'value' => 'شكراً لتعاملكم معنا', 'type' => 'string', 'group' => 'invoice', 'label_ar' => 'تذييل الفاتورة',       'label_en' => 'Invoice Footer'],
            ['key' => 'show_tax_invoice', 'value' => '1',                  'type' => 'boolean', 'group' => 'invoice', 'label_ar' => 'إظهار الضريبة في الفاتورة','label_en' => 'Show Tax on Invoice'],
            ['key' => 'auto_print',       'value' => '0',                  'type' => 'boolean', 'group' => 'invoice', 'label_ar' => 'طباعة تلقائية',        'label_en' => 'Auto Print'],

            // POS - نقطة البيع
            ['key' => 'pos_sound',        'value' => '1',                  'type' => 'boolean', 'group' => 'pos',     'label_ar' => 'صوت عند المسح',        'label_en' => 'Beep on Scan'],
            ['key' => 'low_stock_alert',  'value' => '1',                  'type' => 'boolean', 'group' => 'pos',     'label_ar' => 'تنبيه المخزون المنخفض','label_en' => 'Low Stock Alert'],
            ['key' => 'allow_negative_stock','value' => '0',               'type' => 'boolean', 'group' => 'pos',     'label_ar' => 'السماح بمخزون سالب',   'label_en' => 'Allow Negative Stock'],
            ['key' => 'default_payment',  'value' => 'cash',               'type' => 'string',  'group' => 'pos',     'label_ar' => 'طريقة الدفع الافتراضية','label_en' => 'Default Payment Method'],
        ];

        foreach ($settings as $s) {
            DB::table('settings')->updateOrInsert(['key' => $s['key']], $s);
        }
    }
}