<?php

namespace Base\Booking\Entity;

use Base\Booking\Enum\PaymentState;
use Base\Booking\Repository\BookingRepository;
use Base\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Omnistay\Model\BookingStatus;
use Omnistay\Model\Stay;

/**
 * A stay booked by a member: at one of the site's listings - then this row
 * is what holds its nights (DoctrineListingStore) - or at a property of
 * another provider (a hotel), then $providerReference is that provider's.
 * It keeps what was quoted line by line, the cancellation policy as it was
 * offered, the payment's gateway and reference, and while it waits for its
 * payment, when its hold ends.
 */
#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Table(name: 'booking_booking')]
#[ORM\Index(fields: ['checkIn', 'checkOut'], name: 'booking_booking_nights')]
class Booking
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    /** The site's reference, shown to guest and host: "SL-7Q2K9M". */
    #[ORM\Column(length: 16, unique: true)]
    private string $reference;

    #[ORM\ManyToOne(targetEntity: Listing::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Listing $listing = null;

    /** The omnistay provider's name ("homes", "hotels") and its property's ref. */
    #[ORM\Column(length: 32)]
    private string $provider = 'homes';

    #[ORM\Column(length: 160)]
    private string $propertyRef = '';

    #[ORM\Column(length: 160)]
    private string $propertyName = '';

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $providerReference = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $guest;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $checkIn;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $checkOut;

    #[ORM\Column]
    private int $adults = 1;

    /** @var list<int> */
    #[ORM\Column(type: 'json')]
    private array $childAges = [];

    #[ORM\Column]
    private int $pets = 0;

    #[ORM\Column(length: 16, enumType: BookingStatus::class)]
    private BookingStatus $status = BookingStatus::Pending;

    #[ORM\Column(length: 3)]
    private string $currency = 'EUR';

    #[ORM\Column]
    private int $total = 0;

    /** What the host is owed before the host fee: nights, extra guests, cleaning, discounts. */
    #[ORM\Column]
    private int $hostShare = 0;

    /** @var array<mixed> the rate as quoted (Omnistay\Model\Rate::toArray()) */
    #[ORM\Column(type: 'json')]
    private array $breakdown = [];

    /** @var array<mixed> the cancellation policy as offered */
    #[ORM\Column(type: 'json')]
    private array $cancellation = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $offerId = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $paymentGateway = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $paymentReference = null;

    #[ORM\Column(length: 20, enumType: PaymentState::class)]
    private PaymentState $paymentState = PaymentState::None;

    #[ORM\Column]
    private int $refunded = 0;

    /** A pending booking holds its nights until then. */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $holdExpiresAt = null;

    /** The guest's first words to the host. */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $cancelReason = null;

    public function __construct(User $guest, ?string $reference = null)
    {
        $this->guest = $guest;
        $this->reference = $reference ?? self::newReference();
        $this->createdAt = new \DateTimeImmutable();
        $this->checkIn = new \DateTimeImmutable('today');
        $this->checkOut = new \DateTimeImmutable('tomorrow');
    }

    /** "SL-" and six characters no one misreads (no 0, O, 1, I). */
    public static function newReference(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $reference = 'SL-';
        for ($i = 0; $i < 6; ++$i) {
            $reference .= $alphabet[random_int(0, \strlen($alphabet) - 1)];
        }

        return $reference;
    }

    public function __toString(): string { return $this->reference; }
    public function getId(): ?int { return $this->id; }
    public function getReference(): string { return $this->reference; }
    public function getListing(): ?Listing { return $this->listing; }
    public function setListing(?Listing $listing): self { $this->listing = $listing; return $this; }
    public function getProvider(): string { return $this->provider; }
    public function setProvider(string $provider): self { $this->provider = $provider; return $this; }
    public function getPropertyRef(): string { return $this->propertyRef; }
    public function setPropertyRef(string $ref): self { $this->propertyRef = $ref; return $this; }
    public function getPropertyName(): string { return $this->propertyName; }
    public function setPropertyName(string $name): self { $this->propertyName = $name; return $this; }
    public function getProviderReference(): ?string { return $this->providerReference; }
    public function setProviderReference(?string $reference): self { $this->providerReference = $reference; return $this; }
    public function getGuest(): User { return $this->guest; }
    public function getCheckIn(): \DateTimeImmutable { return $this->checkIn; }
    public function getCheckOut(): \DateTimeImmutable { return $this->checkOut; }
    public function setStay(Stay $stay): self { $this->checkIn = $stay->checkIn; $this->checkOut = $stay->checkOut; return $this; }
    public function getStay(): Stay { return new Stay($this->checkIn, $this->checkOut); }
    public function getNights(): int { return $this->getStay()->count(); }
    public function getAdults(): int { return $this->adults; }
    /** @return list<int> */
    public function getChildAges(): array { return $this->childAges; }
    public function getPets(): int { return $this->pets; }
    /** @param list<int> $childAges */
    public function setGuests(int $adults, array $childAges = [], int $pets = 0): self { $this->adults = $adults; $this->childAges = $childAges; $this->pets = $pets; return $this; }
    public function getGuestCount(): int { return $this->adults + \count($this->childAges); }
    public function getStatus(): BookingStatus { return $this->status; }
    public function setStatus(BookingStatus $status): self { $this->status = $status; return $this; }
    public function getCurrency(): string { return $this->currency; }
    public function getTotal(): int { return $this->total; }
    public function getHostShare(): int { return $this->hostShare; }
    public function setAmounts(string $currency, int $total, int $hostShare): self { $this->currency = $currency; $this->total = $total; $this->hostShare = $hostShare; return $this; }
    /** @return array<mixed> */
    public function getBreakdown(): array { return $this->breakdown; }
    /** @param array<mixed> $breakdown */
    public function setBreakdown(array $breakdown): self { $this->breakdown = $breakdown; return $this; }
    /** @return array<mixed> */
    public function getCancellation(): array { return $this->cancellation; }
    /** @param array<mixed> $cancellation */
    public function setCancellation(array $cancellation): self { $this->cancellation = $cancellation; return $this; }
    public function getOfferId(): ?string { return $this->offerId; }
    public function setOfferId(?string $id): self { $this->offerId = $id; return $this; }
    public function getPaymentGateway(): ?string { return $this->paymentGateway; }
    public function getPaymentReference(): ?string { return $this->paymentReference; }
    public function setPayment(?string $gateway, ?string $reference, PaymentState $state): self { $this->paymentGateway = $gateway; $this->paymentReference = $reference; $this->paymentState = $state; return $this; }
    public function getPaymentState(): PaymentState { return $this->paymentState; }
    public function setPaymentState(PaymentState $state): self { $this->paymentState = $state; return $this; }
    public function getRefunded(): int { return $this->refunded; }
    public function setRefunded(int $refunded): self { $this->refunded = $refunded; return $this; }
    public function getHoldExpiresAt(): ?\DateTimeImmutable { return $this->holdExpiresAt; }
    public function setHoldExpiresAt(?\DateTimeImmutable $at): self { $this->holdExpiresAt = $at; return $this; }
    public function getMessage(): ?string { return $this->message; }
    public function setMessage(?string $message): self { $this->message = $message; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $at): self { $this->createdAt = $at; return $this; }
    public function getConfirmedAt(): ?\DateTimeImmutable { return $this->confirmedAt; }
    public function setConfirmedAt(?\DateTimeImmutable $at): self { $this->confirmedAt = $at; return $this; }
    public function getCancelledAt(): ?\DateTimeImmutable { return $this->cancelledAt; }
    public function getCancelReason(): ?string { return $this->cancelReason; }
    public function cancel(\DateTimeImmutable $at, ?string $reason = null): self { $this->status = BookingStatus::Cancelled; $this->cancelledAt = $at; $this->cancelReason = $reason; $this->holdExpiresAt = null; return $this; }

    /** Whether it holds its nights at this moment: confirmed, on request, or pending within its hold. */
    public function holds(\DateTimeImmutable $now): bool
    {
        return $this->status->holds() && !(BookingStatus::Pending === $this->status && null !== $this->holdExpiresAt && $this->holdExpiresAt <= $now);
    }

    /** The host's member, for a booking at one of the site's listings. */
    public function getHostUser(): ?User { return $this->listing?->getHost()->getUser(); }

    public function isHost(?object $user): bool { return null !== $user && null !== $this->listing && $this->listing->getHost()->getUser() === $user; }

    public function isGuest(?object $user): bool { return null !== $user && $this->guest === $user; }
}
