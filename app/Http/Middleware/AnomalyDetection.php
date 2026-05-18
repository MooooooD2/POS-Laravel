<?php
namespace App\Http\Middleware;

use App\Models\AuditLog;
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
                $this->detectOffHoursTransaction($request, $response);
            }

            $this->detectDiscountCapViolation($request, $response);

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

        Cache::add($key, 0, 60);
        $count = Cache::increment($key);

        if ($count > $threshold) {
            $this->writeAnomalyLog('anomaly.rapid_requests', $request, [
                'count'     => $count,
                'threshold' => $threshold,
                'url'       => $request->path(),
            ]);
        }
    }

    private function detectLargeInvoice(Request $request, Response $response): void
    {
        $threshold = (int) config('security.anomaly.invoice_amount_threshold', 50000);
        $body      = json_decode($response->getContent(), true);
        $total     = $body['invoice']['final_total'] ?? 0;

        if ($total >= $threshold) {
            $this->writeAnomalyLog('anomaly.large_invoice', $request, [
                'invoice_total'  => $total,
                'threshold'      => $threshold,
                'payment_method' => $request->input('payment_method'),
            ]);
        }
    }

    private function detectOffHoursTransaction(Request $request, Response $response): void
    {
        if (!auth()->check()) return;

        $start = (int) config('security.anomaly.off_hours_start', 22); // 10 PM
        $end   = (int) config('security.anomaly.off_hours_end', 6);    // 6 AM
        $hour  = (int) now()->format('G');

        $isOffHours = $hour >= $start || $hour < $end;
        if (!$isOffHours) return;

        $body  = json_decode($response->getContent(), true);
        $total = $body['invoice']['final_total'] ?? 0;

        $this->writeAnomalyLog('anomaly.off_hours_transaction', $request, [
            'hour'           => $hour,
            'invoice_total'  => $total,
            'payment_method' => $request->input('payment_method'),
            'off_hours_window' => "{$start}:00–{$end}:00",
        ]);
    }

    private function detectDiscountCapViolation(Request $request, Response $response): void
    {
        if (!auth()->check()) return;
        if (!$request->is('api/invoices') || !$request->isMethod('POST')) return;

        if ($response->getStatusCode() === 422) {
            $body    = json_decode($response->getContent(), true);
            $message = $body['message'] ?? '';

            if (str_contains($message, 'discount') || str_contains($message, 'خصم')) {
                $discountKey  = 'discount_attempt_' . auth()->id();
                $attemptCount = Cache::increment($discountKey, 1);
                Cache::expire($discountKey, 3600);

                if ($attemptCount >= 3) {
                    $this->writeAnomalyLog('anomaly.repeated_discount_attempts', $request, [
                        'attempts' => $attemptCount,
                    ]);
                }
            }
        }
    }

    /**
     * Dual-write: structured file log + audit_logs DB row.
     * The DB row makes anomalies queryable from the fraud detection API.
     * Failures are silently swallowed so anomaly tracking never breaks business ops.
     */
    private function writeAnomalyLog(string $action, Request $request, array $context = []): void
    {
        $userId   = auth()->id();
        $username = auth()->user()?->username ?? 'unknown';

        $payload = array_merge($context, [
            'ip'        => $request->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);

        try {
            Log::channel('audit')->warning($action, array_merge(
                ['user_id' => $userId, 'username' => $username],
                $payload,
            ));
        } catch (\Throwable) {}

        try {
            AuditLog::create([
                'action'     => $action,
                'model'      => null,
                'record_id'  => null,
                'user_id'    => $userId,
                'username'   => $username,
                'ip_address' => filter_var($request->ip(), FILTER_VALIDATE_IP) ? $request->ip() : 'invalid',
                'user_agent' => substr(preg_replace('/[\x00-\x1F\x7F]/', '', $request->userAgent() ?? ''), 0, 250),
                'changes'    => $payload,
                'created_at' => now(),
            ]);
        } catch (\Throwable) {}
    }
}
