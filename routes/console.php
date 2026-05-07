<?php
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// #48 تنبيه المخزون المنخفض يومياً
Schedule::command('stock:alert')->daily()->at('08:00');

// تنظيف الجلسات المنتهية أسبوعياً
Schedule::command('session:gc')->weekly();

// #43 نسخ احتياطي يومي للـ Audit Log
Schedule::command('audit:backup')->daily()->at('23:00');
