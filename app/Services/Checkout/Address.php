<?php

namespace App\Services\Checkout;

final readonly class Address
{
    public function __construct(
        public string $name,
        public string $line1,
        public ?string $line2,
        public string $city,
        public string $state,
        public string $postalCode,
        public string $country,
    ) {}

    /**
     * @return array<string, string|null>
     */
    public function toAttributes(string $prefix): array
    {
        return [
            "{$prefix}_name" => $this->name,
            "{$prefix}_line1" => $this->line1,
            "{$prefix}_line2" => $this->line2,
            "{$prefix}_city" => $this->city,
            "{$prefix}_state" => $this->state,
            "{$prefix}_postal_code" => $this->postalCode,
            "{$prefix}_country" => $this->country,
        ];
    }
}
