<?php

declare(strict_types=1);

namespace Amashukov\BlockchainAddress;

use InvalidArgumentException;
use Throwable;

final readonly class EvmAddress implements ChainAddressInterface
{
    private function __construct(
        private string $bytes,
    ) {}

    public static function fromString(string $raw): self
    {
        $hex = strtolower(trim($raw));
        if (str_starts_with($hex, '0x')) {
            $hex = substr($hex, 2);
        }

        if (1 !== preg_match('/^[0-9a-f]{40}$/', $hex)) {
            throw new InvalidArgumentException(sprintf('EvmAddress: invalid EVM address "%s".', $raw));
        }

        return new self((string) hex2bin($hex));
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
        return $other instanceof self && hash_equals($this->bytes, $other->bytes);
    }

    public function toHex(): string
    {
        return '0x' . bin2hex($this->bytes);
    }

    public function toString(): string
    {
        return $this->toHex();
    }

    public function __toString(): string
    {
        return $this->toHex();
    }
}
