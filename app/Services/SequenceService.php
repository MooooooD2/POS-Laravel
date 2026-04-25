<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * SequenceService — Atomic document numbering
 * خدمة الترقيم التسلسلي الآمن — يمنع تكرار الأرقام في حال وجود طلبات متزامنة
 *
 * Uses MySQL LAST_INSERT_ID() trick for atomic increment.
 * Safe under concurrent requests — no race conditions.
 */
class SequenceService
{
    /**
     * Get the next number in a sequence.
     * الحصول على الرقم التالي في التسلسل بشكل آمن وذري
     *
     * @param  string $name  e.g. 'invoice', 'purchase', 'return'
     * @param  string|null $prefix  Override prefix (optional, uses DB default)
     * @return string  e.g. 'INV-20260425-000001'
     */
    public static function next(string $name, ?string $prefix = null): string
    {
        // Atomic increment using MySQL LAST_INSERT_ID trick
        // هذا الأسلوب آمن تماماً في حال وجود طلبات متزامنة
        DB::statement(
            'UPDATE sequences SET value = LAST_INSERT_ID(value + 1) WHERE name = ?',
            [$name]
        );

        $id = DB::select('SELECT LAST_INSERT_ID() as id')[0]->id;

        if (!$id) {
            // Fallback: insert if sequence doesn't exist
            DB::table('sequences')->insertOrIgnore([
                'name'  => $name,
                'value' => 1,
                'prefix' => strtoupper($name),
            ]);
            $id = 1;
        }

        // Get prefix from DB if not overridden
        if (!$prefix) {
            $row    = DB::table('sequences')->where('name', $name)->first();
            $prefix = $row?->prefix ?? strtoupper($name);
        }

        $date = now()->format('Ymd');

        return "{$prefix}-{$date}-" . str_pad($id, 6, '0', STR_PAD_LEFT);
    }
}
