<?php
namespace App\Services\Offline;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Log;

class SyncService
{
    public function __construct(private InvoiceService $invoiceService) {}

    /**
     * Sync a batch of offline-created invoices.
     *
     * Each invoice must have an `offline_uuid`. If the UUID already exists in
     * the database the row is returned without creating a duplicate (idempotent).
     *
     * Returns a summary array with per-invoice results.
     */
    public function syncInvoices(array $invoices): array
    {
        $synced  = 0;
        $skipped = 0;
        $failed  = 0;
        $results = [];

        foreach ($invoices as $inv) {
            $uuid = $inv['offline_uuid'] ?? null;

            // Guard: offline_uuid is required for batch sync
            if (!$uuid) {
                $results[] = ['offline_uuid' => null, 'status' => 'failed', 'message' => 'Missing offline_uuid'];
                $failed++;
                continue;
            }

            // Idempotency: already synced?
            $existing = Invoice::where('offline_uuid', $uuid)->first();
            if ($existing) {
                $results[] = ['offline_uuid' => $uuid, 'server_id' => $existing->id, 'status' => 'already_synced'];
                $skipped++;
                continue;
            }

            try {
                $invoice   = $this->invoiceService->createInvoice($inv);
                $results[] = ['offline_uuid' => $uuid, 'server_id' => $invoice->id, 'status' => 'synced'];
                $synced++;
            } catch (\Throwable $e) {
                Log::warning('offline.sync_failed', ['uuid' => $uuid, 'error' => $e->getMessage()]);
                $results[] = ['offline_uuid' => $uuid, 'status' => 'failed', 'message' => $e->getMessage()];
                $failed++;
            }
        }

        return [
            'synced'  => $synced,
            'skipped' => $skipped,
            'failed'  => $failed,
            'results' => $results,
        ];
    }
}
