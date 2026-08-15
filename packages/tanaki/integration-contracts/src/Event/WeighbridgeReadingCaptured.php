<?php

namespace Tanaki\IntegrationContracts\Event;

use Tanaki\IntegrationContracts\DTO\WeighbridgeReadingDTO;

/**
 * A plain PHP value object, not a Laravel event class — Dispatchable,
 * InteractsWithSockets, and SerializesModels were removed (see ADR-0006):
 * nothing calls this event's static ::dispatch(), nothing broadcasts it,
 * and it carries a DTO rather than an Eloquent model, so none of those
 * traits' behavior was ever exercised. Dispatch happens via the plain
 * event() helper, which accepts any object.
 */
final readonly class WeighbridgeReadingCaptured
{
    public function __construct(public WeighbridgeReadingDTO $reading)
    {
    }
}
