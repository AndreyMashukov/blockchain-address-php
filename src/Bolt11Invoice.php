<?php

declare(strict_types=1);

namespace Amashukov\BlockchainAddress;

use InvalidArgumentException;
use Psr\Clock\ClockInterface;
use Throwable;

final readonly class Bolt11Invoice implements ChainAddressInterface
{
    public const int DEFAULT_EXPIRY_SECONDS = 3600;

    private const int SIGNATURE_WORDS = 104;

    private const int TIMESTAMP_WORDS = 7;

    private const int TAG_PAYMENT_HASH = 1;

    private const int TAG_DESCRIPTION = 13;

    private const int TAG_EXPIRY = 6;

    private const int MSAT_PER_BTC = 100_000_000_000;

    private function __construct(
        private string $invoice,
        public Bolt11Network $network,
        public string $paymentHash,
        public ?string $amountMsat,
        public int $timestamp,
        public int $expirySeconds,
        public string $description,
    ) {}

    public static function fromString(string $raw): self
    {
        $trimmed = trim($raw);
        $decoded = Bech32::decode($trimmed);
        $hrp     = $decoded['hrp'];

        if (!str_starts_with($hrp, 'ln')) {
            throw new InvalidArgumentException(sprintf('Bolt11Invoice: "%s" is not a Lightning invoice.', $raw));
        }

        [$network, $amountPart] = self::splitHrp(substr($hrp, 2), $raw);

        $words = $decoded['words'];

        if (\count($words) < self::TIMESTAMP_WORDS + self::SIGNATURE_WORDS) {
            throw new InvalidArgumentException(sprintf('Bolt11Invoice: "%s" is too short to carry a signature.', $raw));
        }

        $fields = self::readTaggedFields($words);

        if (!isset($fields[self::TAG_PAYMENT_HASH])) {
            throw new InvalidArgumentException(sprintf('Bolt11Invoice: "%s" carries no payment hash.', $raw));
        }

        $paymentHash = self::toHex($fields[self::TAG_PAYMENT_HASH]);

        if (64 !== \strlen($paymentHash)) {
            throw new InvalidArgumentException(sprintf('Bolt11Invoice: payment hash in "%s" is not 32 bytes.', $raw));
        }

        return new self(
            strtolower($trimmed),
            $network,
            $paymentHash,
            self::amountMsat($amountPart, $raw),
            Bech32::wordsToInt(\array_slice($words, 0, self::TIMESTAMP_WORDS)),
            isset($fields[self::TAG_EXPIRY]) ? Bech32::wordsToInt($fields[self::TAG_EXPIRY]) : self::DEFAULT_EXPIRY_SECONDS,
            isset($fields[self::TAG_DESCRIPTION]) ? self::toUtf8($fields[self::TAG_DESCRIPTION]) : '',
        );
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
        return $other instanceof self && hash_equals($this->paymentHash, $other->paymentHash);
    }

    public function expiresAt(): int
    {
        return $this->timestamp + $this->expirySeconds;
    }

    public function isExpiredAt(ClockInterface $clock): bool
    {
        return $clock->now()->getTimestamp() >= $this->expiresAt();
    }

    public function hasAmount(): bool
    {
        return null !== $this->amountMsat;
    }

    public function toString(): string
    {
        return $this->invoice;
    }

    public function __toString(): string
    {
        return $this->invoice;
    }

    /**
     * @return array{Bolt11Network, string}
     */
    private static function splitHrp(string $body, string $raw): array
    {
        foreach ([Bolt11Network::Regtest, Bolt11Network::Testnet, Bolt11Network::Mainnet, Bolt11Network::Signet] as $candidate) {
            if (str_starts_with($body, $candidate->value)) {
                return [$candidate, substr($body, \strlen($candidate->value))];
            }
        }

        throw new InvalidArgumentException(sprintf('Bolt11Invoice: unknown Lightning network prefix in "%s".', $raw));
    }

    /**
     * @param list<int> $words
     *
     * @return array<int, list<int>>
     */
    private static function readTaggedFields(array $words): array
    {
        $fields = [];
        $cursor = self::TIMESTAMP_WORDS;
        $end    = \count($words) - self::SIGNATURE_WORDS;

        while ($cursor + 3 <= $end) {
            $type   = $words[$cursor];
            $length = $words[$cursor + 1] * 32 + $words[$cursor + 2];
            $cursor += 3;

            if ($cursor + $length > $end) {
                break;
            }

            if (!isset($fields[$type])) {
                $fields[$type] = \array_slice($words, $cursor, $length);
            }

            $cursor += $length;
        }

        return $fields;
    }

    /**
     * @param list<int> $words
     */
    private static function toHex(array $words): string
    {
        $hex = '';

        foreach (Bech32::wordsToBytes($words) as $byte) {
            $hex .= sprintf('%02x', $byte);
        }

        return $hex;
    }

    /**
     * @param list<int> $words
     */
    private static function toUtf8(array $words): string
    {
        $out = '';

        foreach (Bech32::wordsToBytes($words) as $byte) {
            $out .= \chr($byte);
        }

        return $out;
    }

    private static function amountMsat(string $amount, string $raw): ?string
    {
        if ('' === $amount) {
            return null;
        }

        if (1 !== preg_match('/^(\d+)([munp]?)$/', $amount, $matches)) {
            throw new InvalidArgumentException(sprintf('Bolt11Invoice: malformed amount "%s" in "%s".', $amount, $raw));
        }

        $digits = $matches[1];
        $msat   = bcmul($digits, (string) self::MSAT_PER_BTC, 0);

        $divisor = match ($matches[2]) {
            'm'     => '1000',
            'u'     => '1000000',
            'n'     => '1000000000',
            'p'     => '1000000000000',
            default => '1',
        };

        if ('0' !== bcmod($msat, $divisor)) {
            throw new InvalidArgumentException(sprintf('Bolt11Invoice: amount "%s" in "%s" is finer than one millisatoshi.', $amount, $raw));
        }

        return bcdiv($msat, $divisor, 0);
    }
}
