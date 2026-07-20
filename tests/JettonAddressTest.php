<?php

declare(strict_types=1);

namespace Amashukov\BlockchainAddress\Tests;

use Amashukov\BlockchainAddress\EvmAddress;
use Amashukov\BlockchainAddress\JettonAddress;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JettonAddress::class)]
final class JettonAddressTest extends TestCase
{
    private const string CONTRACT = 'EQDk2VTvn04SUKJrW7rXahzdF8_Qi6utb0wj43InCu9vdjrR';

    private const string MASTER = 'EQCxE6mUtQJKFnGfaROTKOt1lZbDiiX1kCixRv7Nw2Id_sDs';

    private const string WALLET = 'UQCd8mtzdqikXcgkM-pamItenickeBCTGXJRanHPn1FZQSCz';

    public function testFromStringsRoundTripsContractMasterAndWallet(): void
    {
        $jetton = JettonAddress::fromStrings(self::CONTRACT, self::MASTER, self::WALLET);

        self::assertSame(self::CONTRACT, $jetton->contract()->toString());
        self::assertSame(self::MASTER, $jetton->master()->toString());
        self::assertSame(self::WALLET, $jetton->wallet()->toString());
    }

    public function testToStringIsTheContract(): void
    {
        $jetton = JettonAddress::fromStrings(self::CONTRACT, self::MASTER, self::WALLET);

        self::assertSame(self::CONTRACT, $jetton->toString());
        self::assertSame(self::CONTRACT, (string) $jetton);
    }

    public function testEqIsTrueForSameTriple(): void
    {
        $a = JettonAddress::fromStrings(self::CONTRACT, self::MASTER, self::WALLET);
        $b = JettonAddress::fromStrings(self::CONTRACT, self::MASTER, self::WALLET);

        self::assertTrue($a->eq($b));
    }

    public function testEqIsFalseForDifferentTriple(): void
    {
        $a = JettonAddress::fromStrings(self::CONTRACT, self::MASTER, self::WALLET);
        $b = JettonAddress::fromStrings(self::CONTRACT, self::WALLET, self::MASTER);

        self::assertFalse($a->eq($b));
    }

    public function testEqIsFalseAcrossChainTypes(): void
    {
        $jetton = JettonAddress::fromStrings(self::CONTRACT, self::MASTER, self::WALLET);
        $evm    = EvmAddress::fromString('0x1111111111111111111111111111111111111111');

        self::assertFalse($jetton->eq($evm));
    }

    public function testFromStringsThrowsOnEmptyContract(): void
    {
        $this->expectException(InvalidArgumentException::class);
        JettonAddress::fromStrings('', self::MASTER, self::WALLET);
    }
}
