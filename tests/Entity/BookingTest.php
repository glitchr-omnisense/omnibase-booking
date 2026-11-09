<?php

namespace Base\Booking\Tests\Entity;

use Base\Booking\Entity\Booking;
use Base\Booking\Entity\HostProfile;
use Base\Booking\Entity\Listing;
use Base\Entity\User;
use Omnistay\Model\BookingStatus;
use Omnistay\Model\Stay;
use PHPUnit\Framework\TestCase;

/** What a booking holds, whose it is, and its reference. */
final class BookingTest extends TestCase
{
    public function testAPendingBookingHoldsItsNightsUntilItsHoldEnds(): void
    {
        $now = new \DateTimeImmutable('2026-11-09 10:00');
        $booking = (new Booking(self::user()))->setStay(Stay::of('2026-12-02', '2026-12-05'))->setHoldExpiresAt($now->modify('+30 minutes'));

        self::assertTrue($booking->holds($now));
        self::assertFalse($booking->holds($now->modify('+31 minutes')));
        self::assertTrue($booking->setStatus(BookingStatus::Confirmed)->holds($now->modify('+1 day')));
        self::assertFalse($booking->cancel($now)->holds($now));
        self::assertSame(3, $booking->getNights());
    }

    public function testItsGuestAndItsHost(): void
    {
        $guest = self::user();
        $host = self::user();
        $booking = (new Booking($guest))->setListing(new Listing(new HostProfile($host, 'Inès')));

        self::assertTrue($booking->isGuest($guest));
        self::assertFalse($booking->isGuest($host));
        self::assertTrue($booking->isHost($host));
        self::assertFalse($booking->isHost(self::user()));
        self::assertSame($host, $booking->getHostUser());
    }

    public function testAReferenceNoOneMisreads(): void
    {
        for ($i = 0; $i < 50; ++$i) {
            self::assertMatchesRegularExpression('~^SL-[2-9A-HJ-NP-Z]{6}$~', Booking::newReference());
        }
    }

    /** A member without the core's construction (its state enums are the application's). */
    private static function user(): User
    {
        return (new \ReflectionClass(User::class))->newInstanceWithoutConstructor();
    }
}
