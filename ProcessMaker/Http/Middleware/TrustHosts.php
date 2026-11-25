<?php

namespace ProcessMaker\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;
use Illuminate\Http\Request;
use ProcessMaker\Multitenancy\SwitchTenant;
use ProcessMaker\Multitenancy\TenantDomainCache;

class TrustHosts extends Middleware
{
    public function hosts(): array
    {
        $hosts = array_filter([
            $this->applicationUrlPattern(),
        ]);

        if (config('app.multitenancy')) {
            foreach (TenantDomainCache::domains() as $domain) {
                $hosts[] = $this->hostPattern($domain);
            }
        }

        $trustedHostsEnv = config('app.trusted_hosts_links', '');
        if ($trustedHostsEnv !== '') {
            foreach (array_filter(array_map('trim', explode(',', $trustedHostsEnv))) as $host) {
                if (str_contains($host, '://')) {
                    $host = parse_url($host, PHP_URL_HOST) ?: $host;
                }
                $hosts[] = $this->hostPattern($host);
            }
        }

        return array_values(array_unique($hosts));
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

            if (!$trusted) {
                \Log::warning('Rejected request with untrusted X-Forwarded-Host', [
                    'forwarded_host' => $forwardedHost,
                ]);
                abort(400, 'Invalid Host Header');
            }
        }

        return parent::handle($request, $next);
    }

    /**
     * Prefer the landlord APP_URL under multitenancy so Octane does not narrow
     * trusted hosts to whichever tenant last called SwitchTenant.
     */
    protected function applicationUrlPattern(): ?string
    {
        $url = config('app.url');

        if (config('app.multitenancy') && SwitchTenant::$landlordValues) {
            $url = SwitchTenant::$landlordValues['app']['url'] ?? $url;
        }

        $host = parse_url((string) $url, PHP_URL_HOST);

        return $host ? $this->hostPattern($host) : null;
    }

    protected function hostPattern(string $host): string
    {
        return '^(.+\.)?' . preg_quote($host) . '$';
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
