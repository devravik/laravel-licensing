<?php

namespace DevRavik\LaravelLicensing\Http\Middleware;

use Closure;
use DevRavik\LaravelLicensing\Exceptions\LicenseManagerException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware that checks for any valid license, regardless of product.
 *
 * Usage in routes:
 *   Route::middleware('license.valid')->group(function () { ... });
 *
 * This is the simpler counterpart to CheckLicense. Use it when you
 * only need to verify that the caller has a valid license of any kind.
 */
class CheckValidLicense extends AbstractLicenseMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveLicenseKey($request);

        if ($key === null) {
            return $this->denyResponse($request, 'A valid license key is required.', 401);
        }

        try {
            $license = $this->licenseManager->validate($key);
        } catch (LicenseManagerException $e) {
            return $this->denyResponse($request, $e->getMessage(), $e->getStatusCode());
        }

        // Attach the resolved license to the request for downstream use.
        $request->attributes->set('license', $license);

        return $next($request);
    }
}
