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

            // FIX-8: رصد محاولات تجاوز حد الخصم
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

    /**
     * FIX-8: رصد محاولات تجاوز حد الخصم (سُجِّلت من InvoiceService)
     * هذا يضيف طبقة ثانية من الرصد على مستوى الـ middleware
     */
    private function detectDiscountCapViolation(Request $request, Response $response): void
    {
        if (!auth()->check()) return;
        if (!$request->is('api/invoices') || !$request->isMethod('POST')) return;

        // إذا رجع 422 وكان بسبب تجاوز حد الخصم
        if ($response->getStatusCode() === 422) {
            $body    = json_decode($response->getContent(), true);
            $message = $body['message'] ?? '';

            if (str_contains($message, 'discount') || str_contains($message, 'خصم')) {
                $discountKey   = 'discount_attempt_' . auth()->id();
                $attemptCount  = Cache::increment($discountKey, 1);
                Cache::expire($discountKey, 3600);

                // تنبيه عند تكرار محاولات تجاوز الخصم
                if ($attemptCount >= 3) {
                    Log::channel('audit')->warning('anomaly.repeated_discount_attempts', [
                        'user_id'  => auth()->id(),
                        'username' => auth()->user()->username,
                        'attempts' => $attemptCount,
                        'ip'       => $request->ip(),
                        'timestamp'=> now()->toIso8601String(),
                    ]);
                }
            }
        }
    }
}
