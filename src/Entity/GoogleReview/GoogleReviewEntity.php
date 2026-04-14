<?php declare(strict_types=1);

namespace Sven\DasGoogle\Entity\GoogleReview;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class GoogleReviewEntity extends Entity
{
    use EntityIdTrait;

    protected string $authorName;
    protected int $rating;
    protected ?string $text;
    protected int $reviewTime;
    protected ?string $relativeTimeDescription;
    protected ?string $profilePhotoUrl;
    protected string $placeId;

    public function getAuthorName(): string { return $this->authorName; }
    public function setAuthorName(string $authorName): void { $this->authorName = $authorName; }

    public function getRating(): int { return $this->rating; }
    public function setRating(int $rating): void { $this->rating = $rating; }

    public function getText(): ?string { return $this->text; }
    public function setText(?string $text): void { $this->text = $text; }

    public function getReviewTime(): int { return $this->reviewTime; }
    public function setReviewTime(int $reviewTime): void { $this->reviewTime = $reviewTime; }

    public function getRelativeTimeDescription(): ?string { return $this->relativeTimeDescription; }
    public function setRelativeTimeDescription(?string $relativeTimeDescription): void { $this->relativeTimeDescription = $relativeTimeDescription; }

    public function getProfilePhotoUrl(): ?string { return $this->profilePhotoUrl; }
    public function setProfilePhotoUrl(?string $profilePhotoUrl): void { $this->profilePhotoUrl = $profilePhotoUrl; }

    public function getPlaceId(): string { return $this->placeId; }
    public function setPlaceId(string $placeId): void { $this->placeId = $placeId; }
}
