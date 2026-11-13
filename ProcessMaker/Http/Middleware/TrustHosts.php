<?php

namespace ProcessMaker\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;
use Closure;
use Illuminate\Http\Request;

class TrustHosts extends Middleware
{
    public function hosts(): array
    {
        $hosts = array_filter([
            $this->allSubdomainsOfApplicationUrl(),
        ]);

        $trustedHostsEnv = config('app.trusted_hosts_links', '');
        if ($trustedHostsEnv !== '') {
            foreach (array_filter(array_map('trim', explode(',', $trustedHostsEnv))) as $host) {
                if (str_contains($host, '://')) {
                    $host = parse_url($host, PHP_URL_HOST) ?: $host;
                }
                $hosts[] = '^(.+\.)?' . preg_quote($host) . '$';
            }
        }

        return array_values($hosts);
    }

    public function handle(Request $request, $next)
    {
        if ($request->hasHeader('X-Forwarded-Host')) {
            $forwardedHost = $request->header('X-Forwarded-Host');
            if (is_array($forwardedHost)) {
                $forwardedHost = $forwardedHost[0] ?? '';
            }

            // Proxies may send a host chain; Symfony uses the first value.
            $forwardedHost = trim(explode(',', (string) $forwardedHost, 2)[0]);
            $trusted = false;

            foreach ($this->hosts() as $pattern) {
                if ($this->hostIsValid($forwardedHost, $pattern)) {
                    $trusted = true;
                    break;
                }
            }

            if (! $trusted) {
                \Log::warning('Rejected request with untrusted X-Forwarded-Host', [
                    'forwarded_host' => $forwardedHost,
                ]);
                abort(400, 'Invalid Host Header');
            }
        }

        return parent::handle($request, $next);
    }

    /**
     * Match a host against a trusted pattern using Symfony's getHost() rules:
     * lowercase and strip a trailing :port before comparing.
     */
    protected function hostIsValid(string $host, string $pattern): bool
    {
        $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));

        return preg_match('/' . str_replace('/', '\/', $pattern) . '/i', $host) === 1;
    }
} 