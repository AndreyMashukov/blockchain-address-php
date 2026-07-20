<?php

declare(strict_types=1);

namespace Amashukov\BlockchainAddress\Tests;

use Amashukov\BlockchainAddress\EvmAddress;
use Amashukov\BlockchainAddress\TonAddress;
use Amashukov\TonWallet\Address;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TonAddress::class)]
final class TonAddressTest extends TestCase
{
    private const string RAW = '0:b113a994b5024a16719f69139328eb759596c38a25f59028b146fecdc3621dfe';

    public function testRawAndUserFriendlyFormsAreEqual(): void
    {
        $userFriendly = Address::parse(self::RAW)->toString(userFriendly: true);

        self::assertTrue(TonAddress::fromString(self::RAW)->eq(TonAddress::fromString($userFriendly)));
    }

    public function testDifferentAddressesAreNotEqual(): void
    {
        $other = '0:0000000000000000000000000000000000000000000000000000000000000001';

        self::assertFalse(TonAddress::fromString(self::RAW)->eq(TonAddress::fromString($other)));
    }

    public function testToStringIsRawForm(): void
    {
        self::assertSame(self::RAW, TonAddress::fromString(self::RAW)->toString());
        self::assertSame(self::RAW, (string) TonAddress::fromString(self::RAW));
    }

    public function testFromStringRejectsMalformed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TonAddress::fromString('not-a-ton-address');
    }

    public function testTryFromStringReturnsNullOnMalformed(): void
    {
        self::assertNull(TonAddress::tryFromString('garbage'));
        self::assertNull(TonAddress::tryFromString(''));
    }

    public function testCrossChainComparisonIsFalse(): void
    {
        self::assertFalse(TonAddress::fromString(self::RAW)->eq(EvmAddress::fromString('0xdac17f958d2ee523a2206206994597c13d831ec7')));
    }
}
