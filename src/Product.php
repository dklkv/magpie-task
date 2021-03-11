<?php

declare(strict_types=1);

namespace App;

use DateTime;
use JsonSerializable;

class Product implements JsonSerializable
{
    private string $title;
    private float $price;
    private string $imageUrl;
    private int $capacityMb;
    private string $availabilityText;
    private ?string $shippingText = null;
    private ?DateTime $shippingDate = null;
    private string $color;


    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setCapacityMb(int $capacityMb): void
    {
        $this->capacityMb = $capacityMb;
    }

    public function setImageUrl(string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function setAvailabilityText(string $availabilityText): void
    {
        $this->availabilityText = $availabilityText;
    }

    public function setShippingText(?string $shippingText): void
    {
        $this->shippingText = $shippingText;

        if ($this->shippingText) {
            $this->setShippingDate($this->shippingText);
        }
    }

    public function setColor(string $color): void
    {
        $this->color = $color;
    }

    private function setShippingDate(string $shippingText): void
    {
        $datePatternsMapping = [
            '/\d{1,2}\s+[A-Za-z]{3}\s+\d{4}/' => 'j M Y',
            '/\d{1,2}(st|nd|rd|th)\s+[A-Za-z]{3}\s+\d{4}/' => 'jS M Y',
            '/\d{4}-\d{2}-\d{2}/' => 'Y-m-d',
        ];

        foreach ($datePatternsMapping as $pattern => $dateFormat)  {
            $matches = [];
            preg_match($pattern, $shippingText, $matches);

            if (count($matches) > 0) {
                $this->shippingDate = DateTime::createFromFormat($dateFormat, $matches[0]) ?: null;

                break;
            }
        }
    }

    private function isAvailable(): bool
    {
        return stripos($this->availabilityText, 'in stock') !== false;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'title' => $this->title,
            'price' => $this->price,
            'imageUrl' => $this->imageUrl,
            'capacityMB' => $this->capacityMb,
            'colour' => $this->color,
            'availabilityText' => $this->availabilityText,
            'isAvailable' => $this->isAvailable(),
            'shippingText' => $this->shippingText,
            'shippingDate' => $this->shippingDate ? $this->shippingDate->format('Y-m-d') : null
        ];
    }

    public function __toString()
    {
        return $this->title . $this->price .$this->capacityMb . $this->color .
            $this->availabilityText . $this->shippingText;
    }
}
