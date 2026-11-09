<?php

namespace Base\Booking\Tests\Stay;

use Base\Booking\Entity\CalendarNight;
use Base\Booking\Entity\HostProfile;
use Base\Booking\Entity\Listing;
use Base\Booking\Entity\ListingPhoto;
use Base\Booking\Entity\Season;
use Base\Booking\Stay\ListingMapper;
use Base\Entity\User;
use Omnistay\Local\CancellationTerms;
use Omnistay\Local\Check;
use Omnistay\Local\Quote;
use Omnistay\Model\Amenity;
use Omnistay\Model\Guests;
use Omnistay\Model\PropertyKind;
use Omnistay\Model\Stay;
use PHPUnit\Framework\TestCase;

/**
 * A listing as omnistay/local reads it: its point rounded and marked
 * approximate, its address left out, its prices (seasons, a night of its own),
 * its rules - and what a stay then costs, line by line.
 */
final class ListingMapperTest extends TestCase
{
    public function testAListingBecomesALocalListing(): void
    {
        $listing = self::listing();
        $id = new \ReflectionProperty(Listing::class, 'id');
        $id->setValue($listing, 7);
        $nights = [(new CalendarNight($listing, new \DateTimeImmutable('2026-12-24')))->setPrice(30000)];
        $local = (new ListingMapper('homes'))->toLocal($listing, $nights);

        self::assertSame('homes:7', (string) $local->property->ref);
        self::assertSame([45.842, 6.214, false, 'Rive est'], [$local->property->location->latitude, $local->property->location->longitude, $local->property->location->exact, $local->property->location->area]);
        self::assertSame([], $local->property->location->lines, 'no address before a booking');
        self::assertSame([Amenity::Wifi, Amenity::Fireplace], $local->property->amenities);
        self::assertSame(['/a.svg'], array_map(static fn ($p) => $p->url, $local->property->photos));
        self::assertSame('Inès', $local->property->host);
        self::assertSame([4, 2, 1, true, CancellationTerms::Strict], [$local->rules->maxGuests, $local->rules->minStay, $local->rules->advanceNotice, $local->rules->pets, $local->rules->cancellation]);

        self::assertSame(30000, $local->pricing->night(new \DateTimeImmutable('2026-12-24'))->amount, 'the night priced on its own');
        self::assertSame(19000, $local->pricing->night(new \DateTimeImmutable('2026-12-23'))->amount, 'the season');
        self::assertSame(15500, $local->pricing->night(new \DateTimeImmutable('2026-11-20'))->amount, 'a Friday');
        self::assertSame(4, Check::minStay($local, new \DateTimeImmutable('2026-12-22')));

        $rate = Quote::rate($local, Stay::of('2026-11-17', '2026-11-19'), new Guests(2), 12);
        self::assertSame([27000, 4500, 3780, 660], array_map(static fn ($c) => $c->amount->amount, $rate->charges));
        self::assertSame(35940, $rate->total->amount, "165 per adult and night, two of each");
    }

    private static function listing(): Listing
    {
        $listing = (new Listing(new HostProfile(self::user(), 'Inès')))
            ->setTitle('Cabane')->setSlug('cabane')->setKind(PropertyKind::Cabin)->setCity('Talloires')->setArea('Rive est')
            ->setLatitude(45.84153)->setLongitude(6.21391)->setAddress('1 chemin Example')
            ->setMaxGuests(4)->setNightlyPrice(13500)->setWeekendPrice(15500)->setCleaningFee(4500)->setTouristTax(165)
            ->setMinStay(2)->setAdvanceNotice(1)->setPetsAllowed(true)->setCancellation(CancellationTerms::Strict)
            ->setAmenities(['wifi', 'fireplace', 'not-an-amenity']);
        $listing->addSeason((new Season())->setName('Fêtes')->setStartsOn(new \DateTimeImmutable('2026-12-19'))->setEndsOn(new \DateTimeImmutable('2027-01-03'))->setNightlyPrice(19000)->setMinStay(4));
        $listing->addPhoto((new ListingPhoto())->setUrl('/a.svg'));

        return $listing;
    }

    /** A member without the core's construction (its state enums are the application's). */
    private static function user(): User
    {
        return (new \ReflectionClass(User::class))->newInstanceWithoutConstructor();
    }
}
