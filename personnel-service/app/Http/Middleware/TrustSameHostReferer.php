<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustSameHostReferer
{
    public function handle(Request $request, Closure $next): Response
    {
        $referer = $request->headers->get('referer');
        $refererParts = is_string($referer) ? parse_url($referer) : false;
        $applicationParts = parse_url((string) config('app.url'));
        $trustedOrigin = is_array($refererParts)
            && is_array($applicationParts)
            && strtolower((string) ($refererParts['scheme'] ?? '')) === strtolower((string) ($applicationParts['scheme'] ?? ''))
            && strcasecmp((string) ($refererParts['host'] ?? ''), (string) ($applicationParts['host'] ?? '')) === 0
            && $this->port($refererParts) === $this->port($applicationParts);

        if (! $trustedOrigin) {
            $path = match (true) {
                $request->isMethod('POST') && $request->path() === 'employees' => 'employees/create',
                preg_match('#^employees/(\d+)$#', $request->path(), $matches) === 1
                    && in_array($request->method(), ['PUT', 'PATCH'], true) => "employees/{$matches[1]}/edit",
                default => 'employees',
            };

            $request->headers->set('referer', rtrim((string) config('app.url'), '/').'/'.$path);
        }

        return $next($request);
    }

    /**
     * @param  array<string, mixed>  $urlParts
     */
    private function port(array $urlParts): int
    {
        return (int) ($urlParts['port'] ?? (strtolower((string) ($urlParts['scheme'] ?? '')) === 'https' ? 443 : 80));
    }
}
