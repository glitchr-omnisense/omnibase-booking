<?php

namespace Base\Booking\Stay;

use Base\Booking\Entity\CalendarNight;
use Base\Booking\Entity\Listing;
use Base\Booking\Entity\ListingPhoto;
use Base\Booking\Entity\Season;
use Omnistay\Local\Listing as LocalListing;
use Omnistay\Local\Pricing;
use Omnistay\Local\Rules;
use Omnistay\Local\Season as LocalSeason;
use Omnistay\Model\Location;
use Omnistay\Model\Money;
use Omnistay\Model\Photo;
use Omnistay\Model\Property;
use Omnistay\Model\PropertyRef;
use Omnistay\Model\RoomType;

/**
 * A listing as omnistay/local reads it: its Property (the point rounded to
 * about a hundred metres and marked approximate, the address left out - a
 * home's door is given once the booking is confirmed), its Pricing (the
 * nights the host priced on their own among them) and its Rules.
 */
final class ListingMapper
{
    public function __construct(private readonly string $provider = 'homes')
    {
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function ref(Listing $listing): PropertyRef
    {
        return new PropertyRef($this->provider, (string) $listing->getId());
    }

    /** @param list<CalendarNight> $nights the nights set apart: their own prices are read */
    public function toLocal(Listing $listing, array $nights = []): LocalListing
    {
        $currency = $listing->getCurrency();
        $money = static fn (?int $amount): ?Money => null === $amount ? null : Money::of($amount, $currency);
        $own = [];
        foreach ($nights as $night) {
            if (null !== $night->getPrice()) {
                $own[$night->getNight()->format('Y-m-d')] = Money::of($night->getPrice(), $currency);
            }
        }

        return new LocalListing(
            $this->property($listing),
            new Pricing(
                Money::of($listing->getNightlyPrice(), $currency),
                $money($listing->getWeekendPrice()),
                array_values(array_map(static fn (Season $s) => new LocalSeason($s->getStartsOn(), $s->getEndsOn(), Money::of($s->getNightlyPrice(), $currency), $s->getMinStay(), $s->getName()), $listing->getSeasons()->toArray())),
                $own,
                $money($listing->getCleaningFee()),
                $listing->getIncludedGuests(),
                $money($listing->getExtraGuestFee()),
                $money($listing->getTouristTax()),
                $listing->getWeeklyDiscount(),
                $listing->getMonthlyDiscount(),
            ),
            new Rules(
                $listing->getMaxGuests(),
                $listing->getMinStay(),
                $listing->getMaxStay(),
                $listing->getAdvanceNotice(),
                $listing->getBookingWindow(),
                $listing->getCheckInDays(),
                $listing->isInstantBook(),
                $listing->isPetsAllowed(),
                $listing->getCancellation(),
            ),
        );
    }

    public function property(Listing $listing): Property
    {
        $round = static fn (?float $degrees): ?float => null === $degrees ? null : round($degrees, 3);

        return new Property(
            $this->ref($listing),
            $listing->getTitle(),
            $listing->getKind(),
            new Location($round($listing->getLatitude()), $round($listing->getLongitude()), city: $listing->getCity(), countryCode: $listing->getCountryCode(), area: $listing->getArea(), exact: false),
            $listing->getDescription(),
            $listing->getAmenityList(),
            photos: array_values(array_filter(array_map(static fn (ListingPhoto $p) => null === $p->getSource() ? null : new Photo((string) $p->getSource(), $p->getCaption(), credit: $p->getCredit()), $listing->getPhotos()->toArray()))),
            roomTypes: [new RoomType('entire', $listing->getTitle(), null, $listing->getMaxGuests(), $listing->getBeds(), null, $listing->getBedrooms(), $listing->getBathrooms())],
            rating: $listing->getRating(),
            reviews: $listing->getReviewCount(),
            host: $listing->getHost()->getDisplayName(),
            checkInFrom: $listing->getCheckInFrom(),
            checkOutUntil: $listing->getCheckOutUntil(),
            maxGuests: $listing->getMaxGuests(),
        );
    }
}
