<?php

declare(strict_types=1);

namespace Amashukov\BlockchainAddress\Tests;

use Amashukov\BlockchainAddress\Bitcoin\Base58Check;
use Amashukov\BlockchainAddress\Bitcoin\Bech32;
use Amashukov\BlockchainAddress\BitcoinAddress;
use Amashukov\BlockchainAddress\BitcoinAddressType;
use Amashukov\BlockchainAddress\BitcoinNetwork;
use Amashukov\BlockchainAddress\EvmAddress;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(BitcoinAddress::class)]
#[CoversClass(BitcoinNetwork::class)]
#[CoversClass(Bech32::class)]
#[CoversClass(Base58Check::class)]
#[CoversClass(BitcoinAddressType::class)]
final class BitcoinAddressTest extends TestCase
{
    /**
     * @return iterable<string, array{string, BitcoinNetwork, BitcoinAddressType, ?int, string, string}>
     */
    public static function validAddresses(): iterable
    {
        yield 'BIP-173 mainnet P2WPKH, upper case' => ['BC1QW508D6QEJXTDG4Y5R3ZARVARY0C5XW7KV8F3T4', BitcoinNetwork::Mainnet, BitcoinAddressType::P2wpkh, 0, '751e76e8199196d454941c45d1b3a323f1433bd6', 'bc1qw508d6qejxtdg4y5r3zarvary0c5xw7kv8f3t4'];
        yield 'BIP-173 testnet P2WSH' => ['tb1qrp33g0q5c5txsp9arysrx4k6zdkfs4nce4xj0gdcccefvpysxf3q0sl5k7', BitcoinNetwork::Testnet, BitcoinAddressType::P2wsh, 0, '1863143c14c5166804bd19203356da136c985678cd4d27a1b8c6329604903262', 'tb1qrp33g0q5c5txsp9arysrx4k6zdkfs4nce4xj0gdcccefvpysxf3q0sl5k7'];
        yield 'BIP-350 mainnet taproot' => ['bc1p0xlxvlhemja6c4dqv22uapctqupfhlxm9h8z3k2e72q4k9hcz7vqzk5jj0', BitcoinNetwork::Mainnet, BitcoinAddressType::P2tr, 1, '79be667ef9dcbbac55a06295ce870b07029bfcdb2dce28d959f2815b16f81798', 'bc1p0xlxvlhemja6c4dqv22uapctqupfhlxm9h8z3k2e72q4k9hcz7vqzk5jj0'];
        yield 'signet shares the testnet prefix' => ['tb1qrp33g0q5c5txsp9arysrx4k6zdkfs4nce4xj0gdcccefvpysxf3q0sl5k7', BitcoinNetwork::Signet, BitcoinAddressType::P2wsh, 0, '1863143c14c5166804bd19203356da136c985678cd4d27a1b8c6329604903262', 'tb1qrp33g0q5c5txsp9arysrx4k6zdkfs4nce4xj0gdcccefvpysxf3q0sl5k7'];
        yield 'regtest P2WPKH' => ['bcrt1qqqqsyqcyq5rqwzqfpg9scrgwpugpzysnard0ew', BitcoinNetwork::Regtest, BitcoinAddressType::P2wpkh, 0, '000102030405060708090a0b0c0d0e0f10111213', 'bcrt1qqqqsyqcyq5rqwzqfpg9scrgwpugpzysnard0ew'];
        yield 'regtest taproot' => ['bcrt1pqqqsyqcyq5rqwzqfpg9scrgwpugpzysnzs23v9ccrydpk8qarc0sj9hjuh', BitcoinNetwork::Regtest, BitcoinAddressType::P2tr, 1, '000102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f', 'bcrt1pqqqsyqcyq5rqwzqfpg9scrgwpugpzysnzs23v9ccrydpk8qarc0sj9hjuh'];
        yield 'mainnet P2PKH' => ['1BvBMSEYstWetqTFn5Au4m4GFg7xJaNVN2', BitcoinNetwork::Mainnet, BitcoinAddressType::P2pkh, null, '77bff20c60e522dfaa3350c39b030a5d004e839a', '1BvBMSEYstWetqTFn5Au4m4GFg7xJaNVN2'];
        yield 'mainnet P2PKH with leading zero bytes' => ['1111111111111111111114oLvT2', BitcoinNetwork::Mainnet, BitcoinAddressType::P2pkh, null, '0000000000000000000000000000000000000000', '1111111111111111111114oLvT2'];
        yield 'mainnet P2SH' => ['3J98t1WpEZ73CNmQviecrnyiWrnqRhWNLy', BitcoinNetwork::Mainnet, BitcoinAddressType::P2sh, null, 'b472a266d0bd89c13706a4132ccfb16f7c3b9fcb', '3J98t1WpEZ73CNmQviecrnyiWrnqRhWNLy'];
        yield 'testnet P2PKH' => ['mipcBbFg9gMiCh81Kj8tqqdgoZub1ZJRfn', BitcoinNetwork::Testnet, BitcoinAddressType::P2pkh, null, '243f1394f44554f4ce3fd68649c19adc483ce924', 'mipcBbFg9gMiCh81Kj8tqqdgoZub1ZJRfn'];
        yield 'testnet P2SH' => ['2MzQwSSnBHWHqSAqtTVQ6v47XtaisrJa1Vc', BitcoinNetwork::Testnet, BitcoinAddressType::P2sh, null, '4e9f39ca4688ff102128ea4ccda34105324305b0', '2MzQwSSnBHWHqSAqtTVQ6v47XtaisrJa1Vc'];
        yield 'regtest P2PKH' => ['mfWyW5fc9NUj75YAnFgoRLrjxgLDn2MMth', BitcoinNetwork::Regtest, BitcoinAddressType::P2pkh, null, '000102030405060708090a0b0c0d0e0f10111213', 'mfWyW5fc9NUj75YAnFgoRLrjxgLDn2MMth'];
        yield 'regtest P2SH' => ['2MsFFCK16VhsCcvPXruztdzzcTZEQCbNKjJ', BitcoinNetwork::Regtest, BitcoinAddressType::P2sh, null, '000102030405060708090a0b0c0d0e0f10111213', '2MsFFCK16VhsCcvPXruztdzzcTZEQCbNKjJ'];
    }

    #[DataProvider('validAddresses')]
    public function testParsesValidAddress(string $raw, BitcoinNetwork $network, BitcoinAddressType $type, ?int $version, string $payloadHex, string $canonical): void
    {
        $address = BitcoinAddress::fromString($raw, $network);

        self::assertSame($network, $address->network());
        self::assertSame($type, $address->type());
        self::assertSame($version, $address->witnessVersion());
        self::assertSame(null !== $version, $address->type()->isSegwit());
        self::assertSame($payloadHex, $address->payloadHex());
        self::assertSame($canonical, $address->toString());
        self::assertSame($canonical, (string) $address);
    }

    /**
     * @return iterable<string, array{string, BitcoinNetwork}>
     */
    public static function invalidAddresses(): iterable
    {
        yield 'bad checksum' => ['BC1QW508D6QEJXTDG4Y5R3ZARVARY0C5XW7KV8F3T5', BitcoinNetwork::Mainnet];
        yield 'mixed case' => ['tb1qrp33g0q5c5txsp9arysrx4k6zdkfs4nce4xj0gdcccefvpysxf3q0sL5k7', BitcoinNetwork::Testnet];
        yield 'taproot with a bech32 checksum' => ['bc1p0xlxvlhemja6c4dqv22uapctqupfhlxm9h8z3k2e72q4k9hcz7vqh2y7hd', BitcoinNetwork::Mainnet];
        yield 'witness v1 with a bech32 checksum on testnet' => ['tb1z0xlxvlhemja6c4dqv22uapctqupfhlxm9h8z3k2e72q4k9hcz7vqglt7rf', BitcoinNetwork::Testnet];
        yield 'witness v16 with a bech32 checksum' => ['BC1S0XLXVLHEMJA6C4DQV22UAPCTQUPFHLXM9H8Z3K2E72Q4K9HCZ7VQ54WELL', BitcoinNetwork::Mainnet];
        yield 'witness v0 with a bech32m checksum' => ['bc1qw508d6qejxtdg4y5r3zarvary0c5xw7kemeawh', BitcoinNetwork::Mainnet];
        yield 'witness v0 with a bech32m checksum on testnet' => ['tb1q0xlxvlhemja6c4dqv22uapctqupfhlxm9h8z3k2e72q4k9hcz7vq24jc47', BitcoinNetwork::Testnet];
        yield 'invalid character' => ['bc1p38j9r5y49hruaue7wxjce0updqjuyyx0kh56v8s25huc6995vvpql3jow4', BitcoinNetwork::Mainnet];
        yield 'witness version 17' => ['BC130XLXVLHEMJA6C4DQV22UAPCTQUPFHLXM9H8Z3K2E72Q4K9HCZ7VQ7ZWS8R', BitcoinNetwork::Mainnet];
        yield 'one-byte program' => ['bc1pw5dgrnzv', BitcoinNetwork::Mainnet];
        yield 'sixteen-byte v0 program' => ['BC1QR508D6QEJXTDG4Y5R3ZARVARYV98GJ9P', BitcoinNetwork::Mainnet];
        yield 'non-zero padding' => ['bc1zw508d6qejxtdg4y5r3zarvaryvqyzf3du', BitcoinNetwork::Mainnet];
        yield 'empty data' => ['bc1gmk9yu', BitcoinNetwork::Mainnet];
        yield 'mainnet address on testnet' => ['bc1p0xlxvlhemja6c4dqv22uapctqupfhlxm9h8z3k2e72q4k9hcz7vqzk5jj0', BitcoinNetwork::Testnet];
        yield 'regtest address on mainnet' => ['bcrt1qqqqsyqcyq5rqwzqfpg9scrgwpugpzysnard0ew', BitcoinNetwork::Mainnet];
        yield 'P2PKH with a bad checksum' => ['1BvBMSEYstWetqTFn5Au4m4GFg7xJaNVN3', BitcoinNetwork::Mainnet];
        yield 'P2SH with a bad checksum' => ['3J98t1WpEZ73CNmQviecrnyiWrnqRhWNLz', BitcoinNetwork::Mainnet];
        yield 'mainnet P2PKH on testnet' => ['1BvBMSEYstWetqTFn5Au4m4GFg7xJaNVN2', BitcoinNetwork::Testnet];
        yield 'testnet P2SH on mainnet' => ['2MzQwSSnBHWHqSAqtTVQ6v47XtaisrJa1Vc', BitcoinNetwork::Mainnet];
        yield 'base58 character outside the alphabet' => ['1BvBMSEYstWetqTFn5Au4m4GFg7xJaNVN0', BitcoinNetwork::Mainnet];
        yield 'base58 payload of the wrong length' => ['1111111111111111111114oLvT', BitcoinNetwork::Mainnet];
        yield 'an EVM address' => ['0x751e76e8199196d454941c45d1b3a323f1433bd6', BitcoinNetwork::Mainnet];
        yield 'whitespace only' => ['   ', BitcoinNetwork::Mainnet];
        yield 'empty' => ['', BitcoinNetwork::Mainnet];
    }

    #[DataProvider('invalidAddresses')]
    public function testRejectsInvalidAddress(string $raw, BitcoinNetwork $network): void
    {
        self::assertNull(BitcoinAddress::tryFromString($raw, $network));

        $this->expectException(InvalidArgumentException::class);
        BitcoinAddress::fromString($raw, $network);
    }

    public function testSurroundingWhitespaceIsIgnored(): void
    {
        self::assertSame(
            'bc1p0xlxvlhemja6c4dqv22uapctqupfhlxm9h8z3k2e72q4k9hcz7vqzk5jj0',
            BitcoinAddress::fromString("  bc1p0xlxvlhemja6c4dqv22uapctqupfhlxm9h8z3k2e72q4k9hcz7vqzk5jj0\n", BitcoinNetwork::Mainnet)->toString(),
        );
    }

    public function testEqualityIgnoresCase(): void
    {
        $upper = BitcoinAddress::fromString('BC1QW508D6QEJXTDG4Y5R3ZARVARY0C5XW7KV8F3T4', BitcoinNetwork::Mainnet);
        $lower = BitcoinAddress::fromString('bc1qw508d6qejxtdg4y5r3zarvary0c5xw7kv8f3t4', BitcoinNetwork::Mainnet);

        self::assertTrue($upper->eq($lower));
    }

    public function testDifferentProgramsAreNotEqual(): void
    {
        $first  = BitcoinAddress::fromString('bcrt1qqqqsyqcyq5rqwzqfpg9scrgwpugpzysnard0ew', BitcoinNetwork::Regtest);
        $second = BitcoinAddress::fromString('bcrt1pqqqsyqcyq5rqwzqfpg9scrgwpugpzysnzs23v9ccrydpk8qarc0sj9hjuh', BitcoinNetwork::Regtest);

        self::assertFalse($first->eq($second));
    }

    public function testNeverEqualToAnotherChain(): void
    {
        $bitcoin = BitcoinAddress::fromString('bc1qw508d6qejxtdg4y5r3zarvary0c5xw7kv8f3t4', BitcoinNetwork::Mainnet);

        self::assertFalse($bitcoin->eq(EvmAddress::fromString('0x751e76e8199196d454941c45d1b3a323f1433bd6')));
    }

    /**
     * @return iterable<string, array{string, BitcoinNetwork}>
     */
    public static function networkNames(): iterable
    {
        yield 'mainnet' => ['mainnet', BitcoinNetwork::Mainnet];
        yield 'bitcoin' => ['bitcoin', BitcoinNetwork::Mainnet];
        yield 'testnet4' => ['testnet4', BitcoinNetwork::Testnet];
        yield 'signet' => ['Signet', BitcoinNetwork::Signet];
        yield 'regtest' => [' regtest ', BitcoinNetwork::Regtest];
    }

    #[DataProvider('networkNames')]
    public function testNetworkResolvesFromItsName(string $name, BitcoinNetwork $expected): void
    {
        self::assertSame($expected, BitcoinNetwork::fromName($name));
    }

    public function testUnknownNetworkNameIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        BitcoinNetwork::fromName('litecoin');
    }

    public function testLegacyAndSegwitForTheSameKeyAreDifferentAddresses(): void
    {
        $legacy = BitcoinAddress::fromString('mfWyW5fc9NUj75YAnFgoRLrjxgLDn2MMth', BitcoinNetwork::Regtest);
        $segwit = BitcoinAddress::fromString('bcrt1qqqqsyqcyq5rqwzqfpg9scrgwpugpzysnard0ew', BitcoinNetwork::Regtest);

        self::assertSame($legacy->payloadHex(), $segwit->payloadHex());
        self::assertFalse($legacy->eq($segwit));
    }

    public function testLegacyVersionBytesPerNetwork(): void
    {
        self::assertSame([0x00, 0x05], [BitcoinNetwork::Mainnet->p2pkhVersion(), BitcoinNetwork::Mainnet->p2shVersion()]);
        self::assertSame([0x6F, 0xC4], [BitcoinNetwork::Testnet->p2pkhVersion(), BitcoinNetwork::Testnet->p2shVersion()]);
        self::assertSame([0x6F, 0xC4], [BitcoinNetwork::Regtest->p2pkhVersion(), BitcoinNetwork::Regtest->p2shVersion()]);
    }

    public function testNetworkPrefixes(): void
    {
        self::assertSame('bc', BitcoinNetwork::Mainnet->hrp());
        self::assertSame('tb', BitcoinNetwork::Testnet->hrp());
        self::assertSame('tb', BitcoinNetwork::Signet->hrp());
        self::assertSame('bcrt', BitcoinNetwork::Regtest->hrp());
    }
}
