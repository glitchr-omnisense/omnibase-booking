<?php

namespace Base\Booking\Entity;

use Base\Booking\Enum\PayoutStatus;
use Base\Booking\Repository\PayoutRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * What the site owes a host for a booking: the host's share less the host
 * fee, due the day after the check-in; marked paid when the transfer is
 * made (booking:payouts), cancelled with the booking.
 */
#[ORM\Entity(repositoryClass: PayoutRepository::class)]
#[ORM\Table(name: 'booking_payout')]
class Payout
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: HostProfile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private HostProfile $host;

    #[ORM\OneToOne(targetEntity: Booking::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private Booking $booking;

    #[ORM\Column]
    private int $amount;

    #[ORM\Column]
    private int $fee;

    #[ORM\Column(length: 3)]
    private string $currency;

    #[ORM\Column(length: 16, enumType: PayoutStatus::class)]
    private PayoutStatus $status = PayoutStatus::Scheduled;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $dueOn;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $transferReference = null;

    public function __construct(HostProfile $host, Booking $booking, int $amount, int $fee, string $currency, \DateTimeImmutable $dueOn)
    {
        $this->host = $host;
        $this->booking = $booking;
        $this->amount = $amount;
        $this->fee = $fee;
        $this->currency = $currency;
        $this->dueOn = $dueOn;
    }

    public function __toString(): string { return $this->booking->getReference(); }
    public function getId(): ?int { return $this->id; }
    public function getHost(): HostProfile { return $this->host; }
    public function getBooking(): Booking { return $this->booking; }
    public function getAmount(): int { return $this->amount; }
    public function getFee(): int { return $this->fee; }
    public function getCurrency(): string { return $this->currency; }
    public function getStatus(): PayoutStatus { return $this->status; }
    public function getDueOn(): \DateTimeImmutable { return $this->dueOn; }
    public function getPaidAt(): ?\DateTimeImmutable { return $this->paidAt; }
    public function getTransferReference(): ?string { return $this->transferReference; }
    public function markPaid(\DateTimeImmutable $at, ?string $reference = null): self { $this->status = PayoutStatus::Paid; $this->paidAt = $at; $this->transferReference = $reference; return $this; }
    public function cancel(): self { $this->status = PayoutStatus::Cancelled; return $this; }
}
