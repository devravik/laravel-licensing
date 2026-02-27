<?php

namespace DevRavik\LaravelLicensing\Http\Middleware;

use Closure;
use DevRavik\LaravelLicensing\Exceptions\LicenseManagerException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware that checks for a valid license for a specific product tier.
 *
 * Usage in routes:
 *   Route::middleware('license:pro')->group(function () { ... });
 *   Route::middleware('license:enterprise')->group(function () { ... });
 *
 * The middleware resolves the license key from (in order):
 *   1. X-License-Key header
 *   2. license_key query parameter
 *   3. license_key body field (JSON or form)
 */
class CheckLicense extends AbstractLicenseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  string  $product  The required product tier (passed by Laravel's middleware pipeline).
     */
    public function handle(Request $request, Closure $next, string $product): Response
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

        // Verify the license is for the required product.
        if ($license->getProduct() !== $product) {
            return $this->denyResponse(
                $request,
                "This route requires a '{$product}' license. "
                . "The provided license is for '{$license->getProduct()}'.",
                403
            );
        }

        // Attach the resolved license to the request for downstream use.
        $request->attributes->set('license', $license);

        return $next($request);
    }
}
