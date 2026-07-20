<?php

declare(strict_types=1);

namespace Amashukov\BlockchainAddress;

use Amashukov\TonWallet\Address;
use Throwable;

final readonly class TonAddress implements ChainAddressInterface
{
    private function __construct(
        private Address $address,
    ) {}

    public static function fromString(string $raw): self
    {
        return new self(Address::parse(trim($raw)));
    }

    public static function tryFromString(string $raw): ?self
    {
        try {
            return self::fromString($raw);
        } catch (Throwable) {
            return null;
        }
    }

    public function eq(ChainAddressInterface $other): bool
    {
        return $other instanceof self
            && $this->address->wc === $other->address->wc
            && hash_equals($this->address->hashPart, $other->address->hashPart);
    }

    public function toString(): string
    {
        return $this->address->toString();
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
