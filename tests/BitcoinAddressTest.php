<?php

declare(strict_types=1);

namespace Amashukov\BlockchainAddress\Tests;

use Amashukov\BlockchainAddress\Bitcoin\Bech32;
use Amashukov\BlockchainAddress\BitcoinAddress;
use Amashukov\BlockchainAddress\BitcoinNetwork;
use Amashukov\BlockchainAddress\EvmAddress;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(BitcoinAddress::class)]
#[CoversClass(BitcoinNetwork::class)]
#[CoversClass(Bech32::class)]
final class BitcoinAddressTest extends TestCase
{
    /**
     * @return iterable<string, array{string, BitcoinNetwork, int, string}>
     */
    public static function validAddresses(): iterable
    {
        yield 'BIP-173 mainnet P2WPKH, upper case' => ['BC1QW508D6QEJXTDG4Y5R3ZARVARY0C5XW7KV8F3T4', BitcoinNetwork::Mainnet, 0, '751e76e8199196d454941c45d1b3a323f1433bd6'];
        yield 'BIP-173 testnet P2WSH' => ['tb1qrp33g0q5c5txsp9arysrx4k6zdkfs4nce4xj0gdcccefvpysxf3q0sl5k7', BitcoinNetwork::Testnet, 0, '1863143c14c5166804bd19203356da136c985678cd4d27a1b8c6329604903262'];
        yield 'BIP-350 mainnet taproot' => ['bc1p0xlxvlhemja6c4dqv22uapctqupfhlxm9h8z3k2e72q4k9hcz7vqzk5jj0', BitcoinNetwork::Mainnet, 1, '79be667ef9dcbbac55a06295ce870b07029bfcdb2dce28d959f2815b16f81798'];
        yield 'signet shares the testnet prefix' => ['tb1qrp33g0q5c5txsp9arysrx4k6zdkfs4nce4xj0gdcccefvpysxf3q0sl5k7', BitcoinNetwork::Signet, 0, '1863143c14c5166804bd19203356da136c985678cd4d27a1b8c6329604903262'];
        yield 'regtest P2WPKH' => ['bcrt1qqqqsyqcyq5rqwzqfpg9scrgwpugpzysnard0ew', BitcoinNetwork::Regtest, 0, '000102030405060708090a0b0c0d0e0f10111213'];
        yield 'regtest taproot' => ['bcrt1pqqqsyqcyq5rqwzqfpg9scrgwpugpzysnzs23v9ccrydpk8qarc0sj9hjuh', BitcoinNetwork::Regtest, 1, '000102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f'];
    }

    #[DataProvider('validAddresses')]
    public function testParsesValidAddress(string $raw, BitcoinNetwork $network, int $version, string $programHex): void
    {
        $address = BitcoinAddress::fromString($raw, $network);

        self::assertSame($network, $address->network());
        self::assertSame($version, $address->witnessVersion());
        self::assertSame($programHex, $address->witnessProgramHex());
        self::assertSame(strtolower($raw), $address->toString());
        self::assertSame(strtolower($raw), (string) $address);
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
        yield 'legacy base58 is not a segwit address' => ['1BvBMSEYstWetqTFn5Au4m4GFg7xJaNVN2', BitcoinNetwork::Mainnet];
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

    public function testNetworkPrefixes(): void
    {
        self::assertSame('bc', BitcoinNetwork::Mainnet->hrp());
        self::assertSame('tb', BitcoinNetwork::Testnet->hrp());
        self::assertSame('tb', BitcoinNetwork::Signet->hrp());
        self::assertSame('bcrt', BitcoinNetwork::Regtest->hrp());
    }
}
