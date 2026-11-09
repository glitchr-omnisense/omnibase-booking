<?php

namespace Base\Booking\Tests\Controller;

use Base\Admin\Test\CrudFieldsTestCase;

/**
 * The fields marked required on this bundle's back office screens, against
 * what its records need (see CrudFieldsTestCase).
 */
final class CrudRequiredFieldsTest extends CrudFieldsTestCase
{
    protected static function sourceDirectory(): string
    {
        return __DIR__.'/../../src';
    }
}
