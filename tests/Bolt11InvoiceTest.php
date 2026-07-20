<?php

declare(strict_types=1);

namespace Amashukov\BlockchainAddress\Tests;

use Amashukov\BlockchainAddress\Bech32;
use Amashukov\BlockchainAddress\Bolt11Invoice;
use Amashukov\BlockchainAddress\Bolt11Network;
use Amashukov\BlockchainAddress\EvmAddress;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

#[CoversClass(Bolt11Invoice::class)]
#[CoversClass(Bech32::class)]
#[CoversClass(Bolt11Network::class)]
final class Bolt11InvoiceTest extends TestCase
{
    private const string BOLT11_SPEC_NO_AMOUNT = 'lnbc1pvjluezpp5qqqsyqcyq5rqwzqfqqqsyqcyq5rqwzqfqqqsyqcyq5rqwzqfqypqdpl2pkx2ctnv5sxxmmwwd5kgetjypeh2ursdae8g6twvus8g6rfwvs8qun0dfjkxaq8rkx3yf5tcsyz3d73gafnh3cax9rn449d9p5uxz9ezhhypd0elx87sjle52x86fux2ypatgddc6k63n7erqz25le42c4u4ecky03ylcqca784w';

    private const string BOLT11_SPEC_2500U = 'lnbc2500u1pvjluezpp5qqqsyqcyq5rqwzqfqqqsyqcyq5rqwzqfqqqsyqcyq5rqwzqfqypqdq5xysxxatsyp3k7enxv4jsxqzpuaztrnwngzn3kdzw5hydlzf03qdgm2hdq27cqv3agm2awhz5se903vruatfhq77w3ls4evs3ch9zw97j25emudupq63nyw24cg27h2rspfj9srp';

    private const string SPEC_PAYMENT_HASH = '0001020304050607080900010203040506070809000102030405060708090102';

    private const int SPEC_TIMESTAMP = 1496314658;

    public function testReadsThePaymentHashFromTheSpecInvoice(): void
    {
        $invoice = Bolt11Invoice::fromString(self::BOLT11_SPEC_2500U);

        self::assertSame(self::SPEC_PAYMENT_HASH, $invoice->paymentHash);
        self::assertSame(self::SPEC_TIMESTAMP, $invoice->timestamp);
        self::assertSame('1 cup coffee', $invoice->description);
    }

    public function testConvertsTheHumanReadableAmountToMillisatoshi(): void
    {
        $invoice = Bolt11Invoice::fromString(self::BOLT11_SPEC_2500U);

        self::assertTrue($invoice->hasAmount());
        self::assertSame('250000000', $invoice->amountMsat);
    }

    public function testAnInvoiceWithoutAnAmountReportsNullSoTheCallerCanRejectIt(): void
    {
        $invoice = Bolt11Invoice::fromString(self::BOLT11_SPEC_NO_AMOUNT);

        self::assertFalse($invoice->hasAmount());
        self::assertNull($invoice->amountMsat);
    }

    public function testReadsTheExplicitExpiryFieldWhenPresent(): void
    {
        self::assertSame(60, Bolt11Invoice::fromString(self::BOLT11_SPEC_2500U)->expirySeconds);
    }

    public function testFallsBackToTheSpecDefaultExpiryWhenTheFieldIsAbsent(): void
    {
        self::assertSame(3600, Bolt11Invoice::fromString(self::BOLT11_SPEC_NO_AMOUNT)->expirySeconds);
    }

    public function testExpiryIsMeasuredFromTheInvoiceTimestamp(): void
    {
        $invoice = Bolt11Invoice::fromString(self::BOLT11_SPEC_2500U);

        self::assertSame(self::SPEC_TIMESTAMP + 60, $invoice->expiresAt());
        self::assertFalse($invoice->isExpiredAt($this->clockAt(self::SPEC_TIMESTAMP + 59)));
        self::assertTrue($invoice->isExpiredAt($this->clockAt(self::SPEC_TIMESTAMP + 60)));
    }

    public function testIdentifiesMainnetFromTheHumanReadablePrefix(): void
    {
        $invoice = Bolt11Invoice::fromString(self::BOLT11_SPEC_2500U);

        self::assertSame(Bolt11Network::Mainnet, $invoice->network);
        self::assertTrue($invoice->network->isMainnet());
    }

    public function testUppercaseInvoicesDecodeSinceQrEncodersEmitThemForDensity(): void
    {
        $invoice = Bolt11Invoice::fromString(strtoupper(self::BOLT11_SPEC_2500U));

        self::assertSame(self::SPEC_PAYMENT_HASH, $invoice->paymentHash);
        self::assertSame(strtolower(self::BOLT11_SPEC_2500U), $invoice->toString());
    }

    public function testRejectsAMixedCaseInvoiceBecauseBech32ForbidsIt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Bolt11Invoice::fromString(ucfirst(self::BOLT11_SPEC_2500U));
    }

    public function testRejectsAnInvoiceWhoseChecksumDoesNotMatch(): void
    {
        $corrupted = substr(self::BOLT11_SPEC_2500U, 0, -1) . 'q';

        $this->expectException(InvalidArgumentException::class);
        Bolt11Invoice::fromString($corrupted);
    }

    public function testASignetInvoiceIsRecognisedRatherThanReadAsTestnet(): void
    {
        self::assertStringStartsWith(
            Bolt11Network::Testnet->value,
            Bolt11Network::Signet->value,
            'signet HRP extends the testnet one, which is why it has to be matched before it',
        );
        self::assertFalse(Bolt11Network::Signet->isMainnet());
    }

    public function testTheHumanReadablePartMustHoldOnlyPrintableCharacters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Bech32::decode("ln\x01bc1qqqqqq");
    }

    public function testAnIntegerFieldWiderThanAnIntIsRefusedRatherThanOverflowing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Bech32::wordsToInt(array_fill(0, 13, 31));
    }

    #[DataProvider('nonInvoices')]
    public function testRejectsAnythingThatIsNotALightningInvoice(string $raw): void
    {
        $this->expectException(InvalidArgumentException::class);
        Bolt11Invoice::fromString($raw);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonInvoices(): iterable
    {
        yield 'empty'          => [''];
        yield 'evm address'    => ['0xDAC17F958D2ee523a2206206994597C13D831ec7'];
        yield 'no separator'   => ['lnbc'];
        yield 'bare prefix'    => ['ln1qqqqqq'];
        yield 'bitcoin bech32' => ['bc1qw508d6qejxtdg4y5r3zarvary0c5xw7kv8f3t4'];
    }

    public function testTryFromStringReturnsNullRatherThanRaisingOnJunk(): void
    {
        self::assertNull(Bolt11Invoice::tryFromString('not an invoice'));
        self::assertInstanceOf(Bolt11Invoice::class, Bolt11Invoice::tryFromString(self::BOLT11_SPEC_2500U));
    }

    public function testTwoInvoicesForTheSamePaymentHashAreEqual(): void
    {
        $a = Bolt11Invoice::fromString(self::BOLT11_SPEC_2500U);
        $b = Bolt11Invoice::fromString(strtoupper(self::BOLT11_SPEC_2500U));

        self::assertTrue($a->eq($b));
    }

    public function testEqIsFalseAcrossChainTypes(): void
    {
        $invoice = Bolt11Invoice::fromString(self::BOLT11_SPEC_2500U);
        $evm     = EvmAddress::fromString('0xDAC17F958D2ee523a2206206994597C13D831ec7');

        self::assertFalse($invoice->eq($evm));
    }

    private function clockAt(int $timestamp): ClockInterface
    {
        return new class ($timestamp) implements ClockInterface {
            public function __construct(private readonly int $timestamp) {}

            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('@' . $this->timestamp);
            }
        };
    }
}
