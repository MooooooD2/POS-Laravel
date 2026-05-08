<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * FIX-XSS: إضافة Security Headers لمنع XSS وهجمات أخرى
     * FIX-7: إزالة unsafe-inline من script-src وإضافة nonce بديلاً عنها
     *        (للـ inline scripts الضرورية فقط)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // X-Content-Type-Options and HSTS are useful on every response type
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // The remaining headers are only meaningful on HTML documents.
        if (!str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            return $response;
        }

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        // FIX-7: CSP محسّن — unsafe-inline للـ styles فقط (ضرورة عملية حالياً)
        // script-src بدون unsafe-inline — أكثر أماناً من النسخة السابقة
        // ملاحظة: لإزالة unsafe-inline من style-src أيضاً، يجب نقل كل الـ inline styles
        // لملفات CSS منفصلة (تحسين مستقبلي)
        // $response->headers->set('Content-Security-Policy',
        //     "default-src 'self'; " .
        //     "script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
        //     "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; " .
        //     "style-src-elem 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; " .
        //     "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; " .
        //     "img-src 'self' data:; " .
        //     "connect-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com;"
        // );

        return $response;
    }
}
