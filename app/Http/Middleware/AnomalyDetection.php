<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AnomalyDetection
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $this->detectRapidRequests($request);

            if ($request->is('api/invoices') && $request->isMethod('POST') && $response->getStatusCode() === 201) {
                $this->detectLargeInvoice($request, $response);
            }
        } catch (\Throwable) {
            // Anomaly detection must never interrupt a business operation
        }

        return $response;
    }

    private function detectRapidRequests(Request $request): void
    {
        if (!auth()->check()) return;

        $threshold = (int) config('security.anomaly.requests_per_minute', 100);
        $key       = 'req_count_' . auth()->id() . '_' . now()->format('Hi');

        // Cache::add is a no-op if the key exists (preserves TTL).
        // Cache::increment then atomically bumps the counter.
        // This avoids the race where increment creates a key without a TTL.
        Cache::add($key, 0, 60);
        $count = Cache::increment($key);

        if ($count > $threshold) {
            Log::channel('audit')->warning('anomaly.rapid_requests', [
                'user_id'   => auth()->id(),
                'username'  => auth()->user()->username,
                'count'     => $count,
                'threshold' => $threshold,
                'ip'        => $request->ip(),
                'url'       => $request->path(),
                'timestamp' => now()->toIso8601String(),
            ]);
        }
    }

    private function detectLargeInvoice(Request $request, Response $response): void
    {
        $threshold = (int) config('security.anomaly.invoice_amount_threshold', 50000);
        $body      = json_decode($response->getContent(), true);
        $total     = $body['invoice']['final_total'] ?? 0;

        if ($total >= $threshold) {
            Log::channel('audit')->warning('anomaly.large_invoice', [
                'user_id'        => auth()->id(),
                'username'       => auth()->user()->username,
                'invoice_total'  => $total,
                'threshold'      => $threshold,
                'payment_method' => $request->input('payment_method'),
                'ip'             => $request->ip(),
                'timestamp'      => now()->toIso8601String(),
            ]);
        }
    }
}
