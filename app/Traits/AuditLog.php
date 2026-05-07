<?php
namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

trait AuditLog
{
    protected function audit(string $action, string $model, int|string $id, array $changes = []): void
    {
        try {
            Log::channel('audit')->info($action, [
                'model'      => $model,
                'record_id'  => $id,
                'user_id'    => Auth::id(),
                'username'   => Auth::user()?->username,
                'ip'         => $this->auditSanitizeIp(request()->ip()),
                'user_agent' => $this->auditSanitizeUserAgent(request()->userAgent()),
                'changes'    => $changes,
                'timestamp'  => now()->toIso8601String(),
            ]);
        } catch (\Throwable) {
            // Audit logging must never break a business operation
        }
    }

    private function auditSanitizeIp(?string $ip): string
    {
        if (!$ip) return 'unknown';
        // Reject anything that isn't a valid IPv4 or IPv6 address to prevent log injection
        return \filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'invalid';
    }

    private function auditSanitizeUserAgent(?string $ua): string
    {
        if (!$ua) return 'unknown';
        // Strip control characters that could corrupt structured log lines
        return \substr(\preg_replace('/[\x00-\x1F\x7F]/', '', $ua), 0, 250);
    }
}
