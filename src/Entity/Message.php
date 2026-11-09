<?php

namespace Base\Booking\Entity;

use Base\Booking\Repository\MessageRepository;
use Base\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/** A message between a booking's guest and its host: plain text, in the booking's thread. */
#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: 'booking_message')]
class Message
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Booking::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Booking $booking;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $sender;

    #[ORM\Column(type: 'text')]
    private string $body;

    #[ORM\Column]
    private \DateTimeImmutable $sentAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    public function __construct(Booking $booking, User $sender, string $body)
    {
        $this->booking = $booking;
        $this->sender = $sender;
        $this->body = trim($body);
        $this->sentAt = new \DateTimeImmutable();
    }

    public function __toString(): string { return mb_strimwidth($this->body, 0, 60, '…'); }
    public function getId(): ?int { return $this->id; }
    public function getBooking(): Booking { return $this->booking; }
    public function getSender(): User { return $this->sender; }
    public function getBody(): string { return $this->body; }
    public function getSentAt(): \DateTimeImmutable { return $this->sentAt; }
    public function setSentAt(\DateTimeImmutable $at): self { $this->sentAt = $at; return $this; }
    public function getReadAt(): ?\DateTimeImmutable { return $this->readAt; }
    public function markRead(\DateTimeImmutable $at): self { $this->readAt ??= $at; return $this; }
}
