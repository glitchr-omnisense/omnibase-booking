<?php

namespace Base\Booking\Entity;

use Base\Booking\Repository\CalendarNightRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One night of a listing's calendar the host set apart: blocked (not for
 * rent), or priced on its own. A night without a row follows the listing's
 * prices and is free unless booked.
 */
#[ORM\Entity(repositoryClass: CalendarNightRepository::class)]
#[ORM\Table(name: 'booking_calendar_night')]
#[ORM\UniqueConstraint(name: 'booking_calendar_night_unique', fields: ['listing', 'night'])]
class CalendarNight
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Listing::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Listing $listing;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $night;

    #[ORM\Column]
    private bool $blocked = false;

    #[ORM\Column(nullable: true)]
    private ?int $price = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $note = null;

    public function __construct(Listing $listing, \DateTimeImmutable $night)
    {
        $this->listing = $listing;
        $this->night = $night;
    }

    public function getId(): ?int { return $this->id; }
    public function getListing(): Listing { return $this->listing; }
    public function getNight(): \DateTimeImmutable { return $this->night; }
    public function isBlocked(): bool { return $this->blocked; }
    public function setBlocked(bool $blocked): self { $this->blocked = $blocked; return $this; }
    public function getPrice(): ?int { return $this->price; }
    public function setPrice(?int $price): self { $this->price = $price; return $this; }
    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $note): self { $this->note = $note; return $this; }
    /** Nothing set apart any more: the row may go. */
    public function isEmpty(): bool { return !$this->blocked && null === $this->price && null === $this->note; }
}
