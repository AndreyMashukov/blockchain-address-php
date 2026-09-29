<?php

declare(strict_types=1);

namespace Amashukov\BlockchainAddress;

use Amashukov\BlockchainAddress\Bitcoin\Base58Check;
use Amashukov\BlockchainAddress\Bitcoin\Bech32;
use InvalidArgumentException;
use Throwable;

final readonly class BitcoinAddress implements ChainAddressInterface
{
    private function __construct(
        private BitcoinNetwork $network,
        private BitcoinAddressType $type,
        private ?int $witnessVersion,
        private string $payload,
        private string $canonical,
    ) {}

    public static function fromString(string $raw, BitcoinNetwork $network): self
    {
        $trimmed = trim($raw);
        if ('' === $trimmed) {
            throw new InvalidArgumentException('BitcoinAddress: empty address.');
        }

        return str_starts_with(strtolower($trimmed), $network->hrp() . '1')
            ? self::fromSegwit($trimmed, $raw, $network)
            : self::fromBase58($trimmed, $raw, $network);
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

    public function type(): BitcoinAddressType
    {
        return $this->type;
    }

    public function witnessVersion(): ?int
    {
        return $this->witnessVersion;
    }

    public function payloadHex(): string
    {
        return bin2hex($this->payload);
    }

    public function eq(ChainAddressInterface $other): bool
    {
        return $other instanceof self
            && $this->network->hrp() === $other->network->hrp()
            && $this->type === $other->type
            && $this->witnessVersion === $other->witnessVersion
            && hash_equals($this->payload, $other->payload);
    }

    public function toString(): string
    {
        return $this->canonical;
    }

    public function __toString(): string
    {
        return $this->canonical;
    }

    private static function fromSegwit(string $trimmed, string $raw, BitcoinNetwork $network): self
    {
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

        $type = match (true) {
            0 === $version && 20 === $length => BitcoinAddressType::P2wpkh,
            0 === $version                   => BitcoinAddressType::P2wsh,
            1 === $version && 32 === $length => BitcoinAddressType::P2tr,
            default                          => BitcoinAddressType::WitnessFuture,
        };

        return new self($network, $type, $version, pack('C*', ...$bytes), strtolower($trimmed));
    }

    private static function fromBase58(string $trimmed, string $raw, BitcoinNetwork $network): self
    {
        try {
            $payload = Base58Check::decode($trimmed);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException(sprintf('BitcoinAddress: invalid address "%s": %s', $raw, $exception->getMessage()), 0, $exception);
        }

        if (21 !== strlen($payload)) {
            throw new InvalidArgumentException(sprintf('BitcoinAddress: "%s" has an invalid payload length.', $raw));
        }

        $type = match (ord($payload[0])) {
            $network->p2pkhVersion() => BitcoinAddressType::P2pkh,
            $network->p2shVersion()  => BitcoinAddressType::P2sh,
            default                  => throw new InvalidArgumentException(sprintf('BitcoinAddress: "%s" does not belong to %s.', $raw, $network->value)),
        };

        return new self($network, $type, null, substr($payload, 1), $trimmed);
    }
}
