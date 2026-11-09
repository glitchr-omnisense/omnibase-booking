<?php

namespace Base\Booking\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A stretch of the year a listing prices apart, both days included: the
 * holidays, the summer. A longer minimum stay may come with it. The season
 * starting later wins where two overlap.
 */
#[ORM\Entity]
#[ORM\Table(name: 'booking_season')]
class Season
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Listing::class, inversedBy: 'seasons')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Listing $listing = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $startsOn;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $endsOn;

    #[ORM\Column]
    private int $nightlyPrice = 0;

    #[ORM\Column(nullable: true)]
    private ?int $minStay = null;

    public function __construct()
    {
        $this->startsOn = new \DateTimeImmutable('today');
        $this->endsOn = new \DateTimeImmutable('today');
    }

    public function __toString(): string { return $this->name ?? $this->startsOn->format('Y-m-d').' – '.$this->endsOn->format('Y-m-d'); }
    public function getId(): ?int { return $this->id; }
    public function getListing(): ?Listing { return $this->listing; }
    public function setListing(?Listing $listing): self { $this->listing = $listing; return $this; }
    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): self { $this->name = $name; return $this; }
    public function getStartsOn(): \DateTimeImmutable { return $this->startsOn; }
    public function setStartsOn(\DateTimeInterface $date): self { $this->startsOn = \DateTimeImmutable::createFromInterface($date); return $this; }
    public function getEndsOn(): \DateTimeImmutable { return $this->endsOn; }
    public function setEndsOn(\DateTimeInterface $date): self { $this->endsOn = \DateTimeImmutable::createFromInterface($date); return $this; }
    public function getNightlyPrice(): int { return $this->nightlyPrice; }
    public function setNightlyPrice(?int $price): self { $this->nightlyPrice = max(0, (int) $price); return $this; }
    public function getMinStay(): ?int { return $this->minStay; }
    public function setMinStay(?int $nights): self { $this->minStay = $nights; return $this; }
}
