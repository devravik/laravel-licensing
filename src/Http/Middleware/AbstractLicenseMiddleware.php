<?php

namespace DevRavik\LaravelLicensing\Http\Middleware;

use DevRavik\LaravelLicensing\Contracts\LicenseManagerContract;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared base for license-checking middleware.
 *
 * Provides key resolution (X-License-Key header → license_key parameter) and
 * a consistent deny-response helper used by CheckLicense and CheckValidLicense.
 */
abstract class AbstractLicenseMiddleware
{
    public function __construct(
        protected LicenseManagerContract $licenseManager
    ) {}

    // -------------------------------------------------------------------------
    // Shared helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve the license key from the request using the priority order:
     *   1. X-License-Key header (preferred for API clients)
     *   2. license_key query parameter or body field
     */
    protected function resolveLicenseKey(Request $request): ?string
    {
        if ($request->hasHeader('X-License-Key')) {
            return $request->header('X-License-Key');
        }

        return $request->input('license_key') ?: null;
    }

    /**
     * Return the appropriate denial response based on the request type.
     *
     * API requests (Accept: application/json) receive a JSON response.
     * Web requests receive an HTTP abort (renders the error page).
     */
    protected function denyResponse(Request $request, string $message, int $status): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'license_check_failed',
                'message' => $message,
            ], $status);
        }

        abort($status, $message);
    }
}
