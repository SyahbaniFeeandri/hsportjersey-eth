<?php
/**
 * ContractService - Abstraksi Interaksi Smart Contract HSportStore
 * Issue: W5-LUI-06, INT-02, INT-05
 * Pemilik: Luis (Integration Engineer)
 */

declare(strict_types=1);

namespace HSport\Services;

use Exception;

class ContractService
{
    private BlockchainClient $client;
    private string $contractAddress;
    private array $abi;

    public const SELECTOR_OWNER = '0x8da5cb5b';               // owner()
    public const SELECTOR_ORDER_COUNT = '0x2453ffa8';         // orderCount()
    public const SELECTOR_GET_ORDER = '0xd09ef241';           // getOrder(uint256)
    public const SELECTOR_GET_ORDER_INFO = '0xd311636b';      // getOrderInfo(uint256)
    public const SELECTOR_GET_ORDER_STATUS = '0x45fa8aae';    // getOrderStatus(uint256)
    public const SELECTOR_GET_ORDER_ID_BY_HASH = '0x185e82b1';// getOrderIdByHash(bytes32)
    public const SELECTOR_IS_OPERATOR = '0x6d70f7ae';         // isOperator(address)
    public const SELECTOR_CREATE_ORDER = '0xc6c132fc';        // createOrder(bytes32,string,uint256)

    public const STATUS_LABELS = [
        0 => 'Created',
        1 => 'AwaitingPayment',
        2 => 'Paid',
        3 => 'InProduction',
        4 => 'ReadyToShip',
        5 => 'Shipped',
        6 => 'Completed',
        7 => 'Cancelled',
    ];

    public function __construct(?BlockchainClient $client = null, ?string $contractAddress = null)
    {
        $this->client = $client ?: new BlockchainClient();

        if ($contractAddress !== null && $contractAddress !== '') {
            $this->contractAddress = $contractAddress;
        } else {
            $addrFile = dirname(__DIR__, 2) . '/blockchain/config/contract_address.txt';
            if (is_file($addrFile)) {
                $this->contractAddress = trim((string)file_get_contents($addrFile));
            } else {
                $this->contractAddress = '0x2f3efA6bbDC5fAf4dC1a600765c7B7829e47bE10';
            }
        }

        $abiFile = dirname(__DIR__, 2) . '/blockchain/config/contract_abi.json';
        if (is_file($abiFile)) {
            $decoded = json_decode((string)file_get_contents($abiFile), true);
            $this->abi = is_array($decoded) ? $decoded : [];
        } else {
            $this->abi = [];
        }
    }

    public function getContractAddress(): string
    {
        return $this->contractAddress;
    }

    public function getAbi(): array
    {
        return $this->abi;
    }

    public function getClient(): BlockchainClient
    {
        return $this->client;
    }

    public function owner(): string
    {
        $raw = $this->client->call($this->contractAddress, self::SELECTOR_OWNER);
        return $this->decodeAddress($raw);
    }

    public function orderCount(): int
    {
        $raw = $this->client->call($this->contractAddress, self::SELECTOR_ORDER_COUNT);
        return (int)$this->decodeUint($raw);
    }

    public function isOperator(string $account): bool
    {
        $cleanAddr = strtolower(preg_replace('/^0x/', '', $account));
        $data = self::SELECTOR_IS_OPERATOR . str_pad($cleanAddr, 64, '0', STR_PAD_LEFT);
        $raw = $this->client->call($this->contractAddress, $data);
        return (int)$this->decodeUint($raw) === 1;
    }

    public function getOrderIdByHash(string $externalOrderHash): int
    {
        $cleanHash = preg_replace('/^0x/', '', $externalOrderHash);
        $data = self::SELECTOR_GET_ORDER_ID_BY_HASH . str_pad($cleanHash, 64, '0', STR_PAD_LEFT);
        $raw = $this->client->call($this->contractAddress, $data);
        return (int)$this->decodeUint($raw);
    }

    public function getOrderStatus(int $orderId): int
    {
        $data = self::SELECTOR_GET_ORDER_STATUS . str_pad(dechex($orderId), 64, '0', STR_PAD_LEFT);
        $raw = $this->client->call($this->contractAddress, $data);
        return (int)$this->decodeUint($raw);
    }

    /**
     * Membaca struct Order lengkap on-chain
     */
    public function getOrder(int $orderId): array
    {
        $data = self::SELECTOR_GET_ORDER . str_pad(dechex($orderId), 64, '0', STR_PAD_LEFT);
        $raw = $this->client->call($this->contractAddress, $data);
        $clean = preg_replace('/^0x/', '', $raw);

        // Minimal 10 words (640 hex chars)
        if (strlen($clean) < 64 * 10) {
            throw new BlockchainException("Respons getOrder tidak lengkap atau order ID {$orderId} tidak ditemukan.");
        }

        // Word 0 is tuple offset (0x20 = 32 bytes)
        // Struct start at word 1 (offset 64 hex chars)
        $id = hexdec(substr($clean, 64 * 1, 64));
        $externalHash = '0x' . substr($clean, 64 * 2, 64);
        $buyer = '0x' . substr(substr($clean, 64 * 3, 64), -40);
        
        // totalAmount in wei (word 4)
        $amountHex = substr($clean, 64 * 4, 64);
        $amountWei = $this->hexToDecStr($amountHex);

        // status (word 5)
        $status = (int)hexdec(substr($clean, 64 * 5, 64));

        // paymentRecorded (word 6)
        $paymentRecorded = (int)hexdec(substr($clean, 64 * 6, 64)) === 1;

        // createdAt (word 7) & updatedAt (word 8)
        $createdAt = hexdec(substr($clean, 64 * 7, 64));
        $updatedAt = hexdec(substr($clean, 64 * 8, 64));

        // orderNumber (word 9 is relative offset from start of struct)
        $orderNumber = '';
        if (strlen($clean) >= 64 * 11) {
            $offsetBytes = hexdec(substr($clean, 64 * 9, 64));
            // Offset from start of struct (word 1 = 32 bytes)
            $strLenWordIdx = 1 + ($offsetBytes / 32);
            $strLenHexOffset = (int)($strLenWordIdx * 64);

            if (strlen($clean) >= $strLenHexOffset + 64) {
                $strLen = (int)hexdec(substr($clean, $strLenHexOffset, 64));
                $strHex = substr($clean, $strLenHexOffset + 64, $strLen * 2);
                $orderNumber = hex2bin($strHex) ?: '';
            }
        }

        return [
            'id' => (int)$id,
            'externalOrderHash' => strtolower($externalHash),
            'buyer' => strtolower($buyer),
            'totalAmountWei' => $amountWei,
            'totalAmountEth' => bcdiv($amountWei, '1000000000000000000', 6),
            'status' => $status,
            'statusLabel' => self::STATUS_LABELS[$status] ?? 'Unknown',
            'paymentRecorded' => $paymentRecorded,
            'createdAt' => (int)$createdAt,
            'updatedAt' => (int)$updatedAt,
            'orderNumber' => $orderNumber,
        ];
    }

    private function decodeAddress(string $hex): string
    {
        $clean = preg_replace('/^0x/', '', $hex);
        return '0x' . strtolower(substr($clean, -40));
    }

    private function decodeUint(string $hex): string
    {
        $clean = preg_replace('/^0x/', '', $hex);
        if ($clean === '') return '0';
        return $this->hexToDecStr($clean);
    }

    private function hexToDecStr(string $hex): string
    {
        $hex = ltrim($hex, '0');
        if ($hex === '') return '0';
        
        $dec = '0';
        $len = strlen($hex);
        for ($i = 0; $i < $len; $i++) {
            $current = hexdec($hex[$i]);
            $dec = bcmul($dec, '16');
            $dec = bcadd($dec, (string)$current);
        }
        return $dec;
    }
}
