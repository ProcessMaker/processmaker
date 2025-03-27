<?php

namespace ProcessMaker\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;
use Closure;

class TrustHosts extends Middleware
{

    /**
     * Get the host patterns that should be trusted.
     *
     * @return array<int, string|null>
     */
    public function hosts(): array
    {
        $trustedHost = $this->allSubdomainsOfApplicationUrl();
        return [$trustedHost];
    }

    public function handle(\Illuminate\Http\Request $request, $next)
    {
        if ($request->hasHeader('X-Forwarded-Host')) {
            $forwardedHost = $request->header('X-Forwarded-Host');
            $trustedPattern = $this->allSubdomainsOfApplicationUrl();
            
            if (!$this->hostIsValid($forwardedHost, $trustedPattern)) {
                \Log::warning('Rejected request with untrusted X-Forwarded-Host', [
                    'forwarded_host' => $forwardedHost,
                    'trusted_pattern' => $trustedPattern
                ]);
                abort(400, 'Invalid Host Header');
            }
        }

        return parent::handle($request, $next);
    }

    protected function hostIsValid(string $host, string $pattern): bool
    {
        return preg_match('/' . str_replace('/', '\/', $pattern) . '/', $host) === 1;
    }
} 