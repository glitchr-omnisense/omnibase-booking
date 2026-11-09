<?php

namespace Base\Booking\Entity;

use Base\Booking\Repository\HostProfileRepository;
use Base\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A member who rents out places: the name guests see, a few words, the
 * languages spoken, where payouts go. One per member.
 */
#[ORM\Entity(repositoryClass: HostProfileRepository::class)]
#[ORM\Table(name: 'booking_host_profile')]
class HostProfile
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 80)]
    private string $displayName = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $about = null;

    /** @var list<string> BCP 47 codes */
    #[ORM\Column(type: 'json')]
    private array $languages = [];

    /** The account payouts are sent to, as the host typed it; only its end is ever shown. */
    #[ORM\Column(length: 34, nullable: true)]
    private ?string $payoutAccount = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, Listing> */
    #[ORM\OneToMany(targetEntity: Listing::class, mappedBy: 'host')]
    private Collection $listings;

    public function __construct(User $user, string $displayName = '')
    {
        $this->user = $user;
        $this->displayName = $displayName;
        $this->createdAt = new \DateTimeImmutable();
        $this->listings = new ArrayCollection();
    }

    public function __toString(): string { return $this->displayName; }
    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    public function getDisplayName(): string { return $this->displayName; }
    public function setDisplayName(?string $name): self { $this->displayName = trim((string) $name); return $this; }
    public function getAbout(): ?string { return $this->about; }
    public function setAbout(?string $about): self { $this->about = $about; return $this; }
    /** @return list<string> */
    public function getLanguages(): array { return $this->languages; }
    /** @param list<string>|null $languages */
    public function setLanguages(?array $languages): self { $this->languages = array_values($languages ?? []); return $this; }
    public function getPayoutAccount(): ?string { return $this->payoutAccount; }
    public function setPayoutAccount(?string $account): self { $this->payoutAccount = $account ? strtoupper(preg_replace('~\s+~', '', $account) ?? '') : null; return $this; }
    /** The account's last four characters, what a page shows. */
    public function getPayoutAccountEnd(): ?string { return $this->payoutAccount ? '•••• '.substr($this->payoutAccount, -4) : null; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    /** @return Collection<int, Listing> */
    public function getListings(): Collection { return $this->listings; }
}
