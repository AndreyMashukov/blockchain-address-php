<?php

declare(strict_types=1);

namespace Amashukov\BlockchainAddress\Tests;

use Amashukov\BlockchainAddress\Erc20Address;
use Amashukov\BlockchainAddress\TonAddress;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Erc20Address::class)]
final class Erc20AddressTest extends TestCase
{
    private const string CONTRACT = '0x1111111111111111111111111111111111111111';

    private const string TOKEN = '0xDAC17F958D2ee523a2206206994597C13D831ec7';

    public function testFromStringsLowercasesContractAndToken(): void
    {
        $erc20 = Erc20Address::fromStrings(self::CONTRACT, self::TOKEN);

        self::assertSame(strtolower(self::CONTRACT), $erc20->contract()->toString());
        self::assertSame(strtolower(self::TOKEN), $erc20->token()->toString());
    }

    public function testToStringIsTheContract(): void
    {
        $erc20 = Erc20Address::fromStrings(self::CONTRACT, self::TOKEN);

        self::assertSame(strtolower(self::CONTRACT), $erc20->toString());
        self::assertSame(strtolower(self::CONTRACT), (string) $erc20);
    }

    public function testEqIsTrueForSamePair(): void
    {
        $a = Erc20Address::fromStrings(self::CONTRACT, self::TOKEN);
        $b = Erc20Address::fromStrings(self::CONTRACT, self::TOKEN);

        self::assertTrue($a->eq($b));
    }

    public function testEqIsFalseForDifferentPair(): void
    {
        $a = Erc20Address::fromStrings(self::CONTRACT, self::TOKEN);
        $b = Erc20Address::fromStrings(self::TOKEN, self::CONTRACT);

        self::assertFalse($a->eq($b));
    }

    public function testEqIsFalseAcrossChainTypes(): void
    {
        $erc20 = Erc20Address::fromStrings(self::CONTRACT, self::TOKEN);
        $ton   = TonAddress::fromString('EQDk2VTvn04SUKJrW7rXahzdF8_Qi6utb0wj43InCu9vdjrR');

        self::assertFalse($erc20->eq($ton));
    }

    public function testFromStringsThrowsOnEmptyContract(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Erc20Address::fromStrings('', self::TOKEN);
    }
}
