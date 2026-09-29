<?php

declare(strict_types=1);

namespace Amashukov\BlockchainAddress;

use Amashukov\BlockchainAddress\Bitcoin\Bech32;
use InvalidArgumentException;
use Throwable;

final readonly class BitcoinAddress implements ChainAddressInterface
{
    private function __construct(
        private BitcoinNetwork $network,
        private int $witnessVersion,
        private string $program,
        private string $canonical,
    ) {}

    public static function fromString(string $raw, BitcoinNetwork $network): self
    {
        $trimmed = trim($raw);

        try {
            [$hrp, $data, $constant] = Bech32::decode($trimmed);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException(sprintf('BitcoinAddress: invalid address "%s": %s', $raw, $exception->getMessage()), 0, $exception);
        }

        if ($hrp !== $network->hrp()) {
            throw new InvalidArgumentException(sprintf('BitcoinAddress: "%s" does not belong to %s.', $raw, $network->value));
        }
        if ([] === $data) {
            throw new InvalidArgumentException(sprintf('BitcoinAddress: "%s" has no witness version.', $raw));
        }

        $version = $data[0];
        if ($version > 16) {
            throw new InvalidArgumentException(sprintf('BitcoinAddress: "%s" has an invalid witness version.', $raw));
        }

        $expectedConstant = 0 === $version ? Bech32::BECH32 : Bech32::BECH32M;
        if ($constant !== $expectedConstant) {
            throw new InvalidArgumentException(sprintf('BitcoinAddress: "%s" uses the wrong checksum variant for witness version %d.', $raw, $version));
        }

        try {
            $bytes = Bech32::convertBits(array_slice($data, 1), 5, 8, false);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException(sprintf('BitcoinAddress: invalid witness program in "%s".', $raw), 0, $exception);
        }

        $length = count($bytes);
        if ($length < 2 || $length > 40 || (0 === $version && 20 !== $length && 32 !== $length)) {
            throw new InvalidArgumentException(sprintf('BitcoinAddress: invalid witness program length in "%s".', $raw));
        }

        return new self($network, $version, pack('C*', ...$bytes), strtolower($trimmed));
    }

    public static function tryFromString(string $raw, BitcoinNetwork $network): ?self
    {
        try {
            return self::fromString($raw, $network);
        } catch (Throwable) {
            return null;
        }
    }

    public function network(): BitcoinNetwork
    {
        return $this->network;
    }

    public function witnessVersion(): int
    {
        return $this->witnessVersion;
    }

    public function witnessProgramHex(): string
    {
        return bin2hex($this->program);
    }

    public function eq(ChainAddressInterface $other): bool
    {
        return $other instanceof self
            && $this->network->hrp() === $other->network->hrp()
            && $this->witnessVersion === $other->witnessVersion
            && hash_equals($this->program, $other->program);
    }

    public function toString(): string
    {
        return $this->canonical;
    }

    public function __toString(): string
    {
        return $this->canonical;
    }
}
