<?php

namespace Base\Booking\Tests\Payout;

use Base\Booking\Payout\Payouts;
use Base\Booking\Repository\PayoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/** A host's share less the host fee, rounded to the cent. */
final class PayoutsTest extends TestCase
{
    public function testTheHostFeeIsTakenFromTheShare(): void
    {
        $payouts = new Payouts((new \ReflectionClass(PayoutRepository::class))->newInstanceWithoutConstructor(), $this->createStub(EntityManagerInterface::class), 3.0);

        self::assertSame(['amount' => 38315, 'fee' => 1185], $payouts->split(39500));
        self::assertSame(['amount' => 0, 'fee' => 0], $payouts->split(0));
    }
}
