<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * SequenceService — Race-condition-safe document numbering.
 * Uses MySQL LAST_INSERT_ID() trick for atomic increments.
 */
class SequenceService
{
    /**
     * Get the next formatted sequence number.
     *
     * @param  string      $name    e.g. 'invoice', 'purchase', 'return'
     * @param  string|null $prefix  Override the DB-stored prefix (optional)
     * @return string               e.g. 'INV-20260425-000001'
     */
    public function next(string $name, ?string $prefix = null): string
    {
        return DB::transaction(function () use ($name, $prefix) {
            // Ensure the sequence row exists (idempotent)
            DB::table('sequences')->insertOrIgnore([
                'name'   => $name,
                'value'  => 0,
                'prefix' => $prefix ?? strtoupper($name),
            ]);

            // Atomic increment — safe under concurrent requests
            DB::statement(
                'UPDATE sequences SET value = LAST_INSERT_ID(value + 1) WHERE name = ?',
                [$name]
            );

            $id = DB::select('SELECT LAST_INSERT_ID() as id')[0]->id;

            if (!$prefix) {
                $row    = DB::table('sequences')->where('name', $name)->first();
                $prefix = $row?->prefix ?? strtoupper($name);
            }

            return "{$prefix}-" . now()->format('Ymd') . '-' . str_pad($id, 6, '0', STR_PAD_LEFT);
        });
    }
}
