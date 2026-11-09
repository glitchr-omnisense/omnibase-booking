<?php

namespace Base\Booking\Entity;

use Base\Database\Attribute\Uploader;
use Doctrine\ORM\Mapping as ORM;

/**
 * A photograph of a listing, in its gallery's order: an upload, or an
 * address (a picture the site serves itself, a demo's placeholder); its
 * credit as its licence asks.
 */
#[ORM\Entity]
#[ORM\Table(name: 'booking_listing_photo')]
class ListingPhoto
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Listing::class, inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Listing $listing = null;

    /** Untyped: the form hands the setter an UploadedFile; the column keeps its uuid. */
    #[ORM\Column(length: 255, nullable: true)]
    #[Uploader(max_size: '12MB', mime_types: ['image/*'], missable: true)]
    private $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $caption = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $credit = null;

    #[ORM\Column]
    private int $position = 0;

    public function __toString(): string { return $this->caption ?? ('#'.$this->id); }
    public function getId(): ?int { return $this->id; }
    public function getListing(): ?Listing { return $this->listing; }
    public function setListing(?Listing $listing): self { $this->listing = $listing; return $this; }
    public function getImage(): ?string { return null !== $this->image ? Uploader::getPublic($this, 'image') : null; }
    public function getImageFile(): mixed { return Uploader::get($this, 'image'); }
    public function setImage(mixed $image): self { $this->image = $image ?: null; return $this; }
    public function getUrl(): ?string { return $this->url; }
    public function setUrl(?string $url): self { $this->url = $url; return $this; }
    /** Where the picture is: the upload's public address, else the address given. */
    public function getSource(): ?string { return $this->getImage() ?? $this->url; }
    public function getCaption(): ?string { return $this->caption; }
    public function setCaption(?string $caption): self { $this->caption = $caption; return $this; }
    public function getCredit(): ?string { return $this->credit; }
    public function setCredit(?string $credit): self { $this->credit = $credit; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(?int $position): self { $this->position = (int) $position; return $this; }
}
