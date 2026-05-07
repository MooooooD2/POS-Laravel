<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * #36 تأمين الـ Session — منع Session Fixation + اكتشاف Hijacking
 */
class SessionSecurity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $session = $request->session();

            // #36 تخزين بصمة المتصفح عند أول تسجيل دخول
            if (!$session->has('_fingerprint')) {
                $session->put('_fingerprint', $this->fingerprint($request));
                $session->put('_login_ip',    $request->ip());
                $session->put('_login_at',    now()->timestamp);
            }

            // #36 اكتشاف Hijacking — بصمة مختلفة = جلسة مسروقة
            if ($session->get('_fingerprint') !== $this->fingerprint($request)) {
                Log::channel('audit')->warning('session_hijack_attempt', [
                    'user_id'    => Auth::id(),
                    'username'   => Auth::user()->username,
                    'ip'         => $request->ip(),
                    'stored_fp'  => $session->get('_fingerprint'),
                    'current_fp' => $this->fingerprint($request),
                    'timestamp'  => now()->toIso8601String(),
                ]);
                Auth::logout();
                $session->invalidate();
                $session->regenerateToken();
                return redirect()->route('login')
                    ->with('error', 'انتهت صلاحية الجلسة لأسباب أمنية. يرجى تسجيل الدخول مجدداً.');
            }

            // #36 انتهاء الجلسة بعد 8 ساعات من عدم النشاط
            $lastActivity = $session->get('_last_activity', now()->timestamp);
            if (now()->timestamp - $lastActivity > 8 * 3600) {
                Auth::logout();
                $session->invalidate();
                $session->regenerateToken();
                return redirect()->route('login')->with('error', 'انتهت جلستك. يرجى تسجيل الدخول مجدداً.');
            }

            $session->put('_last_activity', now()->timestamp);
        }

        return $next($request);
    }

    private function fingerprint(Request $request): string
    {
        // HMAC with the app key makes fingerprints application-specific and
        // prevents an attacker who controls user-agent/IP from forging a match.
        return hash_hmac(
            'sha256',
            $request->userAgent() . '|' .
            // First 3 octets only — tolerates small DHCP changes within same network
            \implode('.', \array_slice(\explode('.', $request->ip()), 0, 3)),
            config('app.key')
        );
    }
}
