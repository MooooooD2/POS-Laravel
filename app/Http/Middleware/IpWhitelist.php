<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class IpWhitelist
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = $this->allowedIps();

        // Empty whitelist = feature disabled, allow all
        if (empty($allowed)) {
            return $next($request);
        }

        $clientIp = $request->ip();

        foreach ($allowed as $entry) {
            if ($this->matches($clientIp, trim($entry))) {
                return $next($request);
            }
        }

        Log::channel('audit')->warning('ip_whitelist.blocked', [
            'ip'        => $clientIp,
            'path'      => $request->path(),
            'user_id'   => auth()->id(),
            'timestamp' => now()->toIso8601String(),
        ]);

        abort(403, 'Access denied: your IP address is not whitelisted.');
    }

    /** Load the comma-separated whitelist from settings, cached for 5 minutes. */
    private function allowedIps(): array
    {
        $raw = Cache::remember('ip_whitelist', 300, function () {
            return \App\Models\Setting::where('key', 'ip_whitelist')->value('value') ?? '';
        });

        if (empty(trim($raw))) return [];

        return array_filter(array_map('trim', explode(',', $raw)));
    }

    /**
     * Match an IP against a single entry that may be:
     *  - an exact IP       (192.168.1.10)
     *  - a CIDR range      (192.168.1.0/24)
     *  - a wildcard prefix (192.168.1.*)
     */
    private function matches(string $ip, string $entry): bool
    {
        if ($ip === $entry) return true;

        // Wildcard: 192.168.1.*
        if (str_ends_with($entry, '.*')) {
            $prefix = rtrim($entry, '.*');
            return str_starts_with($ip, $prefix . '.');
        }

        // CIDR: 10.0.0.0/8
        if (str_contains($entry, '/')) {
            return $this->ipInCidr($ip, $entry);
        }

        return false;
    }

    private function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        $ipLong     = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) return false;

        $mask = $bits >= 32 ? -1 : ~((1 << (32 - (int) $bits)) - 1);
        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
