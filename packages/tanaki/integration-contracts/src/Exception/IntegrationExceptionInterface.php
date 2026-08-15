<?php

namespace Tanaki\IntegrationContracts\Exception;

use Throwable;

/**
 * Marker interface implemented by every exception this engine throws.
 * PHP has no multiple class inheritance, and each of the 4 exceptions
 * already extends a distinct SPL base (RuntimeException) for its own
 * reasons — a shared base class isn't an option. This interface is the
 * standard alternative (the same pattern Doctrine, Guzzle, and Symfony use):
 * a third-party driver author can `catch (IntegrationExceptionInterface)` to
 * handle any failure from this engine specifically, without also catching
 * unrelated RuntimeExceptions from their own code in the same try block.
 */
interface IntegrationExceptionInterface extends Throwable
{
}
