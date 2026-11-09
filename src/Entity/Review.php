<?php

namespace Base\Booking\Entity;

use Base\Booking\Enum\ReviewDirection;
use Base\Booking\Repository\ReviewRepository;
use Base\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * What one side of a stay says of the other, once it is over: the guest of
 * the host's place, the host of the guest. Neither reads the other's before
 * writing their own: a review is shown once both are written, or fourteen
 * days after the check-out (Reviews::isVisible()).
 */
#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ORM\Table(name: 'booking_review')]
#[ORM\UniqueConstraint(name: 'booking_review_once', fields: ['booking', 'direction'])]
class Review
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Booking::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Booking $booking;

    #[ORM\Column(length: 16, enumType: ReviewDirection::class)]
    private ReviewDirection $direction;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $author;

    /** Who it is about: the host's member or the guest. */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $subject;

    #[ORM\ManyToOne(targetEntity: Listing::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Listing $listing = null;

    #[ORM\Column(type: 'smallint')]
    private int $rating = 5;

    #[ORM\Column(type: 'text')]
    private string $comment = '';

    /** Hidden by the site's moderation. */
    #[ORM\Column]
    private bool $hidden = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(Booking $booking, ReviewDirection $direction, User $author, User $subject, int $rating, string $comment)
    {
        $this->booking = $booking;
        $this->direction = $direction;
        $this->author = $author;
        $this->subject = $subject;
        $this->listing = $booking->getListing();
        $this->rating = min(5, max(1, $rating));
        $this->comment = trim($comment);
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string { return $this->booking->getReference().' '.$this->direction->value; }
    public function getId(): ?int { return $this->id; }
    public function getBooking(): Booking { return $this->booking; }
    public function getDirection(): ReviewDirection { return $this->direction; }
    public function getAuthor(): User { return $this->author; }
    public function getSubject(): User { return $this->subject; }
    public function getListing(): ?Listing { return $this->listing; }
    public function getRating(): int { return $this->rating; }
    public function getComment(): string { return $this->comment; }
    public function isHidden(): bool { return $this->hidden; }
    public function setHidden(?bool $hidden): self { $this->hidden = (bool) $hidden; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $at): self { $this->createdAt = $at; return $this; }
}
