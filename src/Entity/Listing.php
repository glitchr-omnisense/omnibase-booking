<?php

namespace Base\Booking\Entity;

use Base\Booking\Repository\ListingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\Mapping as ORM;
use Omnistay\Local\CancellationTerms;
use Omnistay\Model\Amenity;
use Omnistay\Model\PropertyKind;

/**
 * A place a host rents out: what it is and where, what it offers, how it is
 * priced (in minor units of its currency) and what it accepts. Published,
 * guests find it through omnistay/local; its calendar is its CalendarNights
 * (blocked nights, a night's own price) and its Bookings. Its address stays
 * hidden until a booking is confirmed: the public sees the area and an
 * approximate point.
 */
#[ORM\Entity(repositoryClass: ListingRepository::class)]
#[ORM\Table(name: 'booking_listing')]
#[ORM\Index(fields: ['latitude', 'longitude'], name: 'booking_listing_point')]
class Listing
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: HostProfile::class, inversedBy: 'listings')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private HostProfile $host;

    #[ORM\Column(length: 120)]
    private string $title = '';

    #[ORM\Column(length: 140, unique: true)]
    private string $slug = '';

    #[ORM\Column(length: 16, enumType: PropertyKind::class)]
    private PropertyKind $kind = PropertyKind::Apartment;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $postcode = null;

    #[ORM\Column(length: 120)]
    private string $city = '';

    /** The neighbourhood or the valley: what the public sees instead of the address. */
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $area = null;

    #[ORM\Column(length: 2)]
    private string $countryCode = 'FR';

    #[ORM\Column(nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column]
    private int $maxGuests = 2;

    #[ORM\Column]
    private int $bedrooms = 1;

    #[ORM\Column]
    private int $beds = 1;

    #[ORM\Column(type: 'decimal', precision: 3, scale: 1)]
    private string $bathrooms = '1.0';

    /** @var list<string> Amenity values */
    #[ORM\Column(type: 'json')]
    private array $amenities = [];

    #[ORM\Column(length: 3)]
    private string $currency = 'EUR';

    #[ORM\Column]
    private int $nightlyPrice = 10000;

    #[ORM\Column(nullable: true)]
    private ?int $weekendPrice = null;

    #[ORM\Column(nullable: true)]
    private ?int $cleaningFee = null;

    #[ORM\Column]
    private int $includedGuests = 2;

    #[ORM\Column(nullable: true)]
    private ?int $extraGuestFee = null;

    /** Per adult and night, as the town levies it. */
    #[ORM\Column(nullable: true)]
    private ?int $touristTax = null;

    #[ORM\Column]
    private int $weeklyDiscount = 0;

    #[ORM\Column]
    private int $monthlyDiscount = 0;

    #[ORM\Column]
    private int $minStay = 1;

    #[ORM\Column(nullable: true)]
    private ?int $maxStay = null;

    #[ORM\Column]
    private int $advanceNotice = 1;

    #[ORM\Column]
    private int $bookingWindow = 365;

    /** @var list<int> ISO weekdays a stay may start on (1 Monday ... 7 Sunday); none: any */
    #[ORM\Column(type: 'json')]
    private array $checkInDays = [];

    #[ORM\Column]
    private bool $instantBook = true;

    #[ORM\Column]
    private bool $petsAllowed = false;

    #[ORM\Column(length: 16, enumType: CancellationTerms::class)]
    private CancellationTerms $cancellation = CancellationTerms::Moderate;

    #[ORM\Column(length: 5)]
    private string $checkInFrom = '16:00';

    #[ORM\Column(length: 5)]
    private string $checkOutUntil = '11:00';

    #[ORM\Column]
    private bool $published = false;

    /** The guests' average, kept in step with the reviews (Reviews::write()). */
    #[ORM\Column(nullable: true)]
    private ?float $rating = null;

    #[ORM\Column]
    private int $reviewCount = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, ListingPhoto> */
    #[ORM\OneToMany(targetEntity: ListingPhoto::class, mappedBy: 'listing', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => Order::Ascending->value])]
    private Collection $photos;

    /** @var Collection<int, Season> */
    #[ORM\OneToMany(targetEntity: Season::class, mappedBy: 'listing', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['startsOn' => Order::Ascending->value])]
    private Collection $seasons;

    public function __construct(?HostProfile $host = null)
    {
        if (null !== $host) {
            $this->host = $host;
        }
        $this->createdAt = new \DateTimeImmutable();
        $this->photos = new ArrayCollection();
        $this->seasons = new ArrayCollection();
    }

    public function __toString(): string { return $this->title; }
    public function getId(): ?int { return $this->id; }
    public function getHost(): HostProfile { return $this->host; }
    public function setHost(HostProfile $host): self { $this->host = $host; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(?string $title): self { $this->title = trim((string) $title); return $this; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(?string $slug): self { $this->slug = (string) $slug; return $this; }
    public function getKind(): PropertyKind { return $this->kind; }
    public function setKind(PropertyKind|string $kind): self { $this->kind = \is_string($kind) ? PropertyKind::from($kind) : $kind; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): self { $this->address = $address; return $this; }
    public function getPostcode(): ?string { return $this->postcode; }
    public function setPostcode(?string $postcode): self { $this->postcode = $postcode; return $this; }
    public function getCity(): string { return $this->city; }
    public function setCity(?string $city): self { $this->city = trim((string) $city); return $this; }
    public function getArea(): ?string { return $this->area; }
    public function setArea(?string $area): self { $this->area = $area; return $this; }
    public function getCountryCode(): string { return $this->countryCode; }
    public function setCountryCode(?string $code): self { $this->countryCode = strtoupper((string) $code) ?: 'FR'; return $this; }
    public function getLatitude(): ?float { return $this->latitude; }
    public function setLatitude(?float $latitude): self { $this->latitude = $latitude; return $this; }
    public function getLongitude(): ?float { return $this->longitude; }
    public function setLongitude(?float $longitude): self { $this->longitude = $longitude; return $this; }
    public function getMaxGuests(): int { return $this->maxGuests; }
    public function setMaxGuests(?int $guests): self { $this->maxGuests = max(1, (int) $guests); return $this; }
    public function getBedrooms(): int { return $this->bedrooms; }
    public function setBedrooms(?int $bedrooms): self { $this->bedrooms = max(0, (int) $bedrooms); return $this; }
    public function getBeds(): int { return $this->beds; }
    public function setBeds(?int $beds): self { $this->beds = max(1, (int) $beds); return $this; }
    public function getBathrooms(): float { return (float) $this->bathrooms; }
    public function setBathrooms(float|string|null $bathrooms): self { $this->bathrooms = number_format(max(0, (float) $bathrooms), 1, '.', ''); return $this; }
    /** @return list<string> */
    public function getAmenities(): array { return $this->amenities; }
    /** @param list<string|Amenity>|null $amenities */
    public function setAmenities(?array $amenities): self
    {
        $this->amenities = array_values(array_unique(array_map(static fn (Amenity|string $a) => $a instanceof Amenity ? $a->value : $a, array_filter($amenities ?? [], static fn ($a) => $a instanceof Amenity || null !== Amenity::tryFrom((string) $a)))));

        return $this;
    }
    /** @return list<Amenity> */
    public function getAmenityList(): array { return Amenity::list($this->amenities); }
    public function getCurrency(): string { return $this->currency; }
    public function setCurrency(?string $currency): self { $this->currency = strtoupper((string) $currency) ?: 'EUR'; return $this; }
    public function getNightlyPrice(): int { return $this->nightlyPrice; }
    public function setNightlyPrice(?int $price): self { $this->nightlyPrice = max(0, (int) $price); return $this; }
    public function getWeekendPrice(): ?int { return $this->weekendPrice; }
    public function setWeekendPrice(?int $price): self { $this->weekendPrice = $price; return $this; }
    public function getCleaningFee(): ?int { return $this->cleaningFee; }
    public function setCleaningFee(?int $fee): self { $this->cleaningFee = $fee; return $this; }
    public function getIncludedGuests(): int { return $this->includedGuests; }
    public function setIncludedGuests(?int $guests): self { $this->includedGuests = max(1, (int) $guests); return $this; }
    public function getExtraGuestFee(): ?int { return $this->extraGuestFee; }
    public function setExtraGuestFee(?int $fee): self { $this->extraGuestFee = $fee; return $this; }
    public function getTouristTax(): ?int { return $this->touristTax; }
    public function setTouristTax(?int $tax): self { $this->touristTax = $tax; return $this; }
    public function getWeeklyDiscount(): int { return $this->weeklyDiscount; }
    public function setWeeklyDiscount(?int $percent): self { $this->weeklyDiscount = min(90, max(0, (int) $percent)); return $this; }
    public function getMonthlyDiscount(): int { return $this->monthlyDiscount; }
    public function setMonthlyDiscount(?int $percent): self { $this->monthlyDiscount = min(90, max(0, (int) $percent)); return $this; }
    public function getMinStay(): int { return $this->minStay; }
    public function setMinStay(?int $nights): self { $this->minStay = max(1, (int) $nights); return $this; }
    public function getMaxStay(): ?int { return $this->maxStay; }
    public function setMaxStay(?int $nights): self { $this->maxStay = $nights; return $this; }
    public function getAdvanceNotice(): int { return $this->advanceNotice; }
    public function setAdvanceNotice(?int $days): self { $this->advanceNotice = max(0, (int) $days); return $this; }
    public function getBookingWindow(): int { return $this->bookingWindow; }
    public function setBookingWindow(?int $days): self { $this->bookingWindow = max(1, (int) $days); return $this; }
    /** @return list<int> */
    public function getCheckInDays(): array { return $this->checkInDays; }
    /** @param list<int|string>|null $days */
    public function setCheckInDays(?array $days): self { $this->checkInDays = array_values(array_unique(array_filter(array_map('intval', $days ?? []), static fn (int $d) => $d >= 1 && $d <= 7))); return $this; }
    public function isInstantBook(): bool { return $this->instantBook; }
    public function setInstantBook(?bool $instant): self { $this->instantBook = (bool) $instant; return $this; }
    public function isPetsAllowed(): bool { return $this->petsAllowed; }
    public function setPetsAllowed(?bool $pets): self { $this->petsAllowed = (bool) $pets; return $this; }
    public function getCancellation(): CancellationTerms { return $this->cancellation; }
    public function setCancellation(CancellationTerms|string $terms): self { $this->cancellation = \is_string($terms) ? CancellationTerms::from($terms) : $terms; return $this; }
    public function getCheckInFrom(): string { return $this->checkInFrom; }
    public function setCheckInFrom(?string $time): self { $this->checkInFrom = $time ?: '16:00'; return $this; }
    public function getCheckOutUntil(): string { return $this->checkOutUntil; }
    public function setCheckOutUntil(?string $time): self { $this->checkOutUntil = $time ?: '11:00'; return $this; }
    public function isPublished(): bool { return $this->published; }
    public function setPublished(?bool $published): self { $this->published = (bool) $published; return $this; }
    public function getRating(): ?float { return $this->rating; }
    public function getReviewCount(): int { return $this->reviewCount; }
    /** The guests' average and count, as Reviews keeps them. */
    public function setRating(?float $rating, int $count): self { $this->rating = null === $rating ? null : round($rating, 2); $this->reviewCount = $count; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    /** @return Collection<int, ListingPhoto> */
    public function getPhotos(): Collection { return $this->photos; }
    public function addPhoto(ListingPhoto $photo): self { if (!$this->photos->contains($photo)) { $this->photos->add($photo); $photo->setListing($this); } return $this; }
    public function removePhoto(ListingPhoto $photo): self { $this->photos->removeElement($photo); return $this; }
    /** @return Collection<int, Season> */
    public function getSeasons(): Collection { return $this->seasons; }
    public function addSeason(Season $season): self { if (!$this->seasons->contains($season)) { $this->seasons->add($season); $season->setListing($this); } return $this; }
    public function removeSeason(Season $season): self { $this->seasons->removeElement($season); return $this; }
}
