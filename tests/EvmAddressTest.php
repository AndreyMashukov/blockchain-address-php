<?php

declare(strict_types=1);

namespace Amashukov\BlockchainAddress\Tests;

use Amashukov\BlockchainAddress\EvmAddress;
use Amashukov\BlockchainAddress\TonAddress;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EvmAddress::class)]
final class EvmAddressTest extends TestCase
{
    private const string CHECKSUM = '0xdAC17F958D2ee523a2206206994597C13D831ec7';

    private const string LOWER = '0xdac17f958d2ee523a2206206994597c13d831ec7';

    public function testEqIsCaseInsensitive(): void
    {
        self::assertTrue(EvmAddress::fromString(self::CHECKSUM)->eq(EvmAddress::fromString(self::LOWER)));
    }

    public function testEqIgnoresPrefix(): void
    {
        self::assertTrue(EvmAddress::fromString('dac17f958d2ee523a2206206994597c13d831ec7')->eq(EvmAddress::fromString(self::LOWER)));
    }

    public function testDifferentAddressesAreNotEqual(): void
    {
        self::assertFalse(EvmAddress::fromString(self::LOWER)->eq(EvmAddress::fromString('0x0000000000000000000000000000000000000001')));
    }

    public function testToHexIsLowercase(): void
    {
        self::assertSame(self::LOWER, EvmAddress::fromString(self::CHECKSUM)->toHex());
        self::assertSame(self::LOWER, EvmAddress::fromString(self::CHECKSUM)->toString());
        self::assertSame(self::LOWER, (string) EvmAddress::fromString(self::CHECKSUM));
    }

    public function testFromStringRejectsMalformed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EvmAddress::fromString('0x123');
    }

    public function testTryFromStringReturnsNullOnMalformed(): void
    {
        self::assertNull(EvmAddress::tryFromString('not-an-address'));
        self::assertNull(EvmAddress::tryFromString(''));
    }

    public function testCrossChainComparisonIsFalse(): void
    {
        self::assertFalse(EvmAddress::fromString(self::LOWER)->eq(TonAddress::fromString('0:b113a994b5024a16719f69139328eb759596c38a25f59028b146fecdc3621dfe')));
    }
}
