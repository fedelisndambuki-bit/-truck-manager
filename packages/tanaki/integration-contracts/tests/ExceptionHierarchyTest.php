<?php

namespace Tanaki\IntegrationContracts\Tests;

use PHPUnit\Framework\TestCase;
use Tanaki\IntegrationContracts\Exception\DriverNotFoundException;
use Tanaki\IntegrationContracts\Exception\IntegrationExceptionInterface;

class ExceptionHierarchyTest extends TestCase
{
    public function test_exceptions_implement_the_marker_interface(): void
    {
        $exception = DriverNotFoundException::forVendor('vendor-1');

        $this->assertInstanceOf(IntegrationExceptionInterface::class, $exception);
    }
}
