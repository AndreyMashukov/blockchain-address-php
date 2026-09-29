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

    /**
     * @return iterable<string, array{string, BitcoinNetwork, BitcoinAddressType, ?int, string, string}>
     */
    public static function wellKnownMainnetAddresses(): iterable
    {
        yield 'early P2PKH, recipient of the first peer-to-peer transfer' => ['12cbQLTFMXRnSzktFkuoG3eHoMeFtpTu3S', BitcoinNetwork::Mainnet, BitcoinAddressType::P2pkh, null, '11b366edfc0a8b66feebae5c2e25a7b6a5d1cf31', '12cbQLTFMXRnSzktFkuoG3eHoMeFtpTu3S'];
        yield 'vanity burn P2PKH' => ['1BitcoinEaterAddressDontSendf59kuE', BitcoinNetwork::Mainnet, BitcoinAddressType::P2pkh, null, '759d6677091e973b9e9d99f19c68fbf43e3f05f9', '1BitcoinEaterAddressDontSendf59kuE'];
        yield 'proof-of-burn P2PKH' => ['1CounterpartyXXXXXXXXXXXXXXXUWLpVr', BitcoinNetwork::Mainnet, BitcoinAddressType::P2pkh, null, '818895f3dc2c178629d3d2d8fa3ec4a3f8179821', '1CounterpartyXXXXXXXXXXXXXXXUWLpVr'];
        yield 'exchange cold wallet P2SH' => ['34xp4vRoCGJym3xR7yCVPFHoCNxv4Twseo', BitcoinNetwork::Mainnet, BitcoinAddressType::P2sh, null, '23e522dfc6656a8fda3d47b4fa53f7585ac758cd', '34xp4vRoCGJym3xR7yCVPFHoCNxv4Twseo'];
        yield 'multisig P2SH' => ['3FZbgi29cpjq2GjdwV8eyHuJJnkLtktZc5', BitcoinNetwork::Mainnet, BitcoinAddressType::P2sh, null, '982a9dacf9e0365a252185cb664fca73a559bc89', '3FZbgi29cpjq2GjdwV8eyHuJJnkLtktZc5'];
        yield 'exchange cold wallet P2WSH' => ['bc1qgdjqv0av3q56jvd82tkdjpy7gdp9ut8tlqmgrpmv24sq90ecnvqqjwvw97', BitcoinNetwork::Mainnet, BitcoinAddressType::P2wsh, 0, '4364063fac8829a931a752ecd9049e43425e2cebf83681876c556002bf389b00', 'bc1qgdjqv0av3q56jvd82tkdjpy7gdp9ut8tlqmgrpmv24sq90ecnvqqjwvw97'];
        yield 'custody P2WSH' => ['bc1q9d4ywgfnd8h43da5tpcxcn6ajv590cg6d3tg6axemvljvt2k76zs50tv4q', BitcoinNetwork::Mainnet, BitcoinAddressType::P2wsh, 0, '2b6a47213369ef58b7b458706c4f5d932857e11a6c568d74d9db3f262d56f685', 'bc1q9d4ywgfnd8h43da5tpcxcn6ajv590cg6d3tg6axemvljvt2k76zs50tv4q'];
        yield 'documentation P2WPKH' => ['bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh', BitcoinNetwork::Mainnet, BitcoinAddressType::P2wpkh, 0, '311564348890e005880a9bc834aaa5884f1b5932', 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh'];
        yield 'exchange hot wallet P2WPKH' => ['bc1qar0srrr7xfkvy5l643lydnw9re59gtzzwf5mdq', BitcoinNetwork::Mainnet, BitcoinAddressType::P2wpkh, 0, 'e8df018c7e326cc253faac7e46cdc51e68542c42', 'bc1qar0srrr7xfkvy5l643lydnw9re59gtzzwf5mdq'];
        yield 'seized-funds P2WPKH' => ['bc1qazcm763858nkj2dj986etajv6wquslv8uxwczt', BitcoinNetwork::Mainnet, BitcoinAddressType::P2wpkh, 0, 'e8b1bf6a27a1e76929b229f595f64cd381c87d87', 'bc1qazcm763858nkj2dj986etajv6wquslv8uxwczt'];
        yield 'custody P2WPKH' => ['bc1qm34lsc65zpw79lxes69zkqmk6ee3ewf0j77s3h', BitcoinNetwork::Mainnet, BitcoinAddressType::P2wpkh, 0, 'dc6bf86354105de2fcd9868a2b0376d6731cb92f', 'bc1qm34lsc65zpw79lxes69zkqmk6ee3ewf0j77s3h'];
        yield 'taproot P2TR' => ['bc1p5d7rjq7g6rdk2yhzks9smlaqtedr4dekq08ge8ztwac72sfr9rusxg3297', BitcoinNetwork::Mainnet, BitcoinAddressType::P2tr, 1, 'a37c3903c8d0db6512e2b40b0dffa05e5a3ab73603ce8c9c4b7771e5412328f9', 'bc1p5d7rjq7g6rdk2yhzks9smlaqtedr4dekq08ge8ztwac72sfr9rusxg3297'];
    }

    /**
     * @return iterable<string, array{string, BitcoinNetwork, BitcoinAddressType, ?int, string, string}>
     */
    public static function bipFutureWitnessVectors(): iterable
    {
        yield 'BIP-350 witness v1, 40-byte program' => ['bc1pw508d6qejxtdg4y5r3zarvary0c5xw7kw508d6qejxtdg4y5r3zarvary0c5xw7kt5nd6y', BitcoinNetwork::Mainnet, BitcoinAddressType::WitnessFuture, 1, '751e76e8199196d454941c45d1b3a323f1433bd6751e76e8199196d454941c45d1b3a323f1433bd6', 'bc1pw508d6qejxtdg4y5r3zarvary0c5xw7kw508d6qejxtdg4y5r3zarvary0c5xw7kt5nd6y'];
        yield 'BIP-350 witness v16, 2-byte program' => ['BC1SW50QGDZ25J', BitcoinNetwork::Mainnet, BitcoinAddressType::WitnessFuture, 16, '751e', 'bc1sw50qgdz25j'];
        yield 'BIP-350 witness v2, 16-byte program' => ['bc1zw508d6qejxtdg4y5r3zarvaryvaxxpcs', BitcoinNetwork::Mainnet, BitcoinAddressType::WitnessFuture, 2, '751e76e8199196d454941c45d1b3a323', 'bc1zw508d6qejxtdg4y5r3zarvaryvaxxpcs'];
        yield 'BIP-350 testnet P2WSH' => ['tb1qqqqqp399et2xygdj5xreqhjjvcmzhxw4aywxecjdzew6hylgvsesrxh6hy', BitcoinNetwork::Testnet, BitcoinAddressType::P2wsh, 0, '000000c4a5cad46221b2a187905e5266362b99d5e91c6ce24d165dab93e86433', 'tb1qqqqqp399et2xygdj5xreqhjjvcmzhxw4aywxecjdzew6hylgvsesrxh6hy'];
        yield 'BIP-350 testnet P2TR' => ['tb1pqqqqp399et2xygdj5xreqhjjvcmzhxw4aywxecjdzew6hylgvsesf3hn0c', BitcoinNetwork::Testnet, BitcoinAddressType::P2tr, 1, '000000c4a5cad46221b2a187905e5266362b99d5e91c6ce24d165dab93e86433', 'tb1pqqqqp399et2xygdj5xreqhjjvcmzhxw4aywxecjdzew6hylgvsesf3hn0c'];
    }

    #[DataProvider('validAddresses')]
    #[DataProvider('wellKnownMainnetAddresses')]
    #[DataProvider('bipFutureWitnessVectors')]
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
        yield 'BIP-350 invalid human-readable part' => ['tc1p0xlxvlhemja6c4dqv22uapctqupfhlxm9h8z3k2e72q4k9hcz7vq5zuyut', BitcoinNetwork::Mainnet];
        yield 'BIP-350 one-byte program on witness v3' => ['bc1rw5uspcuh', BitcoinNetwork::Mainnet];
        yield 'BIP-350 41-byte program' => ['bc10w508d6qejxtdg4y5r3zarvary0c5xw7kw508d6qejxtdg4y5r3zarvary0c5xw7kw5rljs90', BitcoinNetwork::Mainnet];
        yield 'BIP-350 more than four padding bits' => ['bc1p0xlxvlhemja6c4dqv22uapctqupfhlxm9h8z3k2e72q4k9hcz7v07qwwzcrf', BitcoinNetwork::Mainnet];
        yield 'BIP-350 non-zero padding on testnet' => ['tb1p0xlxvlhemja6c4dqv22uapctqupfhlxm9h8z3k2e72q4k9hcz7vpggkg4j', BitcoinNetwork::Testnet];
        yield 'mainnet P2WSH on testnet' => ['bc1q9d4ywgfnd8h43da5tpcxcn6ajv590cg6d3tg6axemvljvt2k76zs50tv4q', BitcoinNetwork::Testnet];
        yield 'mainnet P2SH on regtest' => ['34xp4vRoCGJym3xR7yCVPFHoCNxv4Twseo', BitcoinNetwork::Regtest];
        yield 'testnet P2PKH on mainnet' => ['mipcBbFg9gMiCh81Kj8tqqdgoZub1ZJRfn', BitcoinNetwork::Mainnet];
        yield 'P2WPKH with one upper-case character' => ['bc1qar0srrr7xfkvy5l643lydnw9re59gtzzwf5mdQ', BitcoinNetwork::Mainnet];
        yield 'P2WPKH with a mistyped last character' => ['bc1qar0srrr7xfkvy5l643lydnw9re59gtzzwf5mdr', BitcoinNetwork::Mainnet];
        yield 'P2WPKH with an extra character' => ['bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlhb', BitcoinNetwork::Mainnet];
        yield 'P2PKH with a mistyped last character' => ['12cbQLTFMXRnSzktFkuoG3eHoMeFtpTu3T', BitcoinNetwork::Mainnet];
        yield 'P2SH with a mistyped last character' => ['3FZbgi29cpjq2GjdwV8eyHuJJnkLtktZc6', BitcoinNetwork::Mainnet];
        yield 'payment URI instead of an address' => ['bitcoin:bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh', BitcoinNetwork::Mainnet];
        yield 'address followed by an amount' => ['bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh?amount=0.1', BitcoinNetwork::Mainnet];
        yield 'TON address' => ['UQAht13a44YMjGClyRbYCFi9sEPaQbfP6RZJhy_2RGv4Wi1D', BitcoinNetwork::Mainnet];
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
