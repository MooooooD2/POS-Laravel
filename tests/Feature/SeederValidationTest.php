<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FIX-2: اختبار أن الـ Seeder يرفض كلمات المرور الفارغة
 */
class SeederValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function seeder_throws_exception_when_admin_password_is_empty()
    {
        // حفظ القيمة الأصلية
        $original = env('ADMIN_PASSWORD');

        // محاكاة بيئة بدون ADMIN_PASSWORD
        putenv('ADMIN_PASSWORD=');
        $_ENV['ADMIN_PASSWORD'] = '';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/missing required passwords/i');

        $seeder = new \Database\Seeders\UserSeeder();
        // نستخدم reflection لاستدعاء الـ private method
        $method = new \ReflectionMethod($seeder, 'validatePasswords');
        $method->setAccessible(true);
        $method->invoke($seeder);

        // استعادة القيمة الأصلية
        if ($original) {
            putenv("ADMIN_PASSWORD={$original}");
        }
    }
}
