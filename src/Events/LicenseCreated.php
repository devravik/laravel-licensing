<?php

namespace DevRavik\LaravelLicensing\Events;

use DevRavik\LaravelLicensing\Contracts\LicenseContract;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired immediately after a new license is persisted to the database.
 *
 * Note: $event->license->key contains the RAW (unhashed) key at this point,
 * because LicenseManager sets the raw key on the model after saving.
 * Listeners that need to send the key to the user (e.g. email notification)
 * should use this event.
 *
 * After this event is handled, the raw key is no longer accessible.
 *
 * IMPORTANT — Queued listeners:
 * Because SerializesModels re-hydrates the model from the database, the raw
 * key set in-memory is NOT available in queued listeners. Consumers who need
 * the raw key in a queued context must either:
 *   (a) use a synchronous listener, or
 *   (b) pass the raw key as an additional event property before queuing.
 *
 * Example (synchronous — raw key IS available):
 *
 *   Event::listen(LicenseCreated::class, function ($event) {
 *       $rawKey = $event->license->key; // plaintext — send to user NOW
 *   });
 *
 * Example (queued — raw key is NOT available):
 *
 *   class SendKeyEmail implements ShouldQueue
 *   {
 *       public function handle(LicenseCreated $event): void
 *       {
 *           $event->license->key; // This is now the HASH, not the raw key!
 *       }
 *   }
 */
class LicenseCreated
{
    use Dispatchable, SerializesModels;

    /**
     * The newly created license instance.
     *
     * Note: The key attribute on this model is the RAW key (not hashed).
     * It will NOT match the database value on any subsequent retrieval.
     */
    public LicenseContract $license;

    /**
     * @param  LicenseContract  $license  The created license with raw key attached.
     */
    public function __construct(LicenseContract $license)
    {
        $this->license = $license;
    }
}
