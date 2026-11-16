<?php
/**
 * ReceiptService - Pengambil dan Parser Receipt & Event Blockchain
 * Issue: W5-LUI-07, INT-03
 * Pemilik: Luis (Integration Engineer)
 */

declare(strict_types=1);

namespace HSport\Services;

class ReceiptService
{
    private BlockchainClient $client;

    public const EVENT_TOPICS = [
        '0x898e55f17466b0d2f5b6ede198848d0898f14f99ce394a384fc8cefbe5f912d8' => 'OrderCreated',
        '0x7de07bd2aa628dfa876fae9f600dec58e071fc4769629168d212d884c7f16907' => 'PaymentRecorded',
        '0x2b230c88a2dfbd05827496c85a5481f5b5fd85a1306a77da91f82d920e231694' => 'OrderStatusUpdated',
        '0x1a66e0c7d5d7422838f4f916462f11609026cbe8fb5d43ec23c45d04ddfcfca9' => 'OrderShipped',
        '0x19800bcb0ac4476f8c5dc58e76aa820026d28a52f87da5213e57d16b75ae0de9' => 'OrderCompleted',
        '0x6244ed823ca6be0f11bc890c3fafcf3c29cb23420c14243642e930b5e07e6d0a' => 'EscrowReleased',
        '0xf2820c9274d611a15d979823ac23bb965c1972a808eebd952297d9bd3a60f4b8' => 'OrderCancelled',
        '0xeac97bc1917fcedc984e3d0671d4e83b359890323d5d1c2de32b28d17c356ced' => 'EscrowRefunded',
        '0x6e2fc9d01852c697fb7c35402aae775c197f530f58b3830ab654467d717035d9' => 'OperatorUpdated',
    ];

    public function __construct(?BlockchainClient $client = null)
    {
        $this->client = $client ?: new BlockchainClient();
    }

    /**
     * Mengambil dan membedah transaction receipt
     */
    public function fetchAndParseReceipt(string $txHash): array
    {
        $receipt = $this->client->getReceipt($txHash);
        if ($receipt === null) {
            return [
                'found' => false,
                'status' => 'PENDING',
                'confirmed' => false,
                'message' => 'Receipt belum tersedia (transaksi masih pending atau tx_hash salah)',
            ];
        }

        $rawStatus = $receipt['status'] ?? '0x0';
        $isSuccess = hexdec((string)$rawStatus) === 1;
        $blockNumber = (int)hexdec((string)($receipt['blockNumber'] ?? '0x0'));
        $gasUsed = (int)hexdec((string)($receipt['gasUsed'] ?? '0x0'));
        $from = strtolower((string)($receipt['from'] ?? ''));
        $to = strtolower((string)($receipt['to'] ?? ''));

        $parsedEvents = [];
        $logs = $receipt['logs'] ?? [];
        if (is_array($logs)) {
            foreach ($logs as $log) {
                $topics = $log['topics'] ?? [];
                if (empty($topics)) continue;

                $topic0 = strtolower((string)$topics[0]);
                $eventName = self::EVENT_TOPICS[$topic0] ?? 'UnknownEvent';

                $parsedEvent = $this->parseEventLog($eventName, $topics, (string)($log['data'] ?? ''));
                $parsedEvent['raw_log'] = [
                    'address' => $log['address'] ?? '',
                    'blockNumber' => $blockNumber,
                    'logIndex' => hexdec((string)($log['logIndex'] ?? '0x0')),
                ];
                $parsedEvents[] = $parsedEvent;
            }
        }

        return [
            'found' => true,
            'confirmed' => true,
            'is_success' => $isSuccess,
            'status' => $isSuccess ? 'CONFIRMED' : 'FAILED',
            'block_number' => $blockNumber,
            'gas_used' => $gasUsed,
            'from' => $from,
            'to' => $to,
            'contract_address' => $to,
            'events' => $parsedEvents,
            'raw_receipt' => $receipt,
        ];
    }

    private function parseEventLog(string $eventName, array $topics, string $data): array
    {
        $dataClean = preg_replace('/^0x/', '', $data);
        $eventInfo = ['event' => $eventName];

        switch ($eventName) {
            case 'OrderCreated':
                // topics: [topic0, indexed orderId, indexed buyer, indexed externalOrderHash]
                // data: [amount, orderNumber (offset, length, string)]
                $eventInfo['order_id'] = isset($topics[1]) ? (int)hexdec($topics[1]) : 0;
                $eventInfo['buyer'] = isset($topics[2]) ? '0x' . strtolower(substr($topics[2], -40)) : '';
                $eventInfo['external_order_hash'] = isset($topics[3]) ? strtolower($topics[3]) : '';
                if (strlen($dataClean) >= 64) {
                    $eventInfo['amount_wei'] = $this->hexToDecStr(substr($dataClean, 0, 64));
                }
                break;

            case 'PaymentRecorded':
                // topics: [topic0, indexed orderId, indexed operator]
                // data: [amount]
                $eventInfo['order_id'] = isset($topics[1]) ? (int)hexdec($topics[1]) : 0;
                $eventInfo['operator'] = isset($topics[2]) ? '0x' . strtolower(substr($topics[2], -40)) : '';
                if (strlen($dataClean) >= 64) {
                    $eventInfo['amount_wei'] = $this->hexToDecStr(substr($dataClean, 0, 64));
                }
                break;

            case 'OrderStatusUpdated':
                // topics: [topic0, indexed orderId, indexed updatedBy]
                // data: [previousStatus, newStatus]
                $eventInfo['order_id'] = isset($topics[1]) ? (int)hexdec($topics[1]) : 0;
                $eventInfo['updated_by'] = isset($topics[2]) ? '0x' . strtolower(substr($topics[2], -40)) : '';
                if (strlen($dataClean) >= 128) {
                    $eventInfo['previous_status'] = (int)hexdec(substr($dataClean, 0, 64));
                    $eventInfo['new_status'] = (int)hexdec(substr($dataClean, 64, 64));
                }
                break;

            case 'OrderShipped':
                // topics: [topic0, indexed orderId, indexed operator]
                $eventInfo['order_id'] = isset($topics[1]) ? (int)hexdec($topics[1]) : 0;
                $eventInfo['operator'] = isset($topics[2]) ? '0x' . strtolower(substr($topics[2], -40)) : '';
                break;

            case 'OrderCompleted':
                // topics: [topic0, indexed orderId, indexed buyer]
                $eventInfo['order_id'] = isset($topics[1]) ? (int)hexdec($topics[1]) : 0;
                $eventInfo['buyer'] = isset($topics[2]) ? '0x' . strtolower(substr($topics[2], -40)) : '';
                break;

            case 'EscrowReleased':
                // topics: [topic0, indexed orderId, indexed seller]
                // data: [amount]
                $eventInfo['order_id'] = isset($topics[1]) ? (int)hexdec($topics[1]) : 0;
                $eventInfo['seller'] = isset($topics[2]) ? '0x' . strtolower(substr($topics[2], -40)) : '';
                if (strlen($dataClean) >= 64) {
                    $eventInfo['amount_wei'] = $this->hexToDecStr(substr($dataClean, 0, 64));
                }
                break;

            case 'OrderCancelled':
                // topics: [topic0, indexed orderId, indexed cancelledBy]
                // data: [offset reason, length, reason (string)]
                $eventInfo['order_id'] = isset($topics[1]) ? (int)hexdec($topics[1]) : 0;
                $eventInfo['cancelled_by'] = isset($topics[2]) ? '0x' . strtolower(substr($topics[2], -40)) : '';
                $eventInfo['reason'] = $this->decodeStringFromData($dataClean);
                break;

            case 'EscrowRefunded':
                // topics: [topic0, indexed orderId, indexed buyer]
                // data: [amount]
                $eventInfo['order_id'] = isset($topics[1]) ? (int)hexdec($topics[1]) : 0;
                $eventInfo['buyer'] = isset($topics[2]) ? '0x' . strtolower(substr($topics[2], -40)) : '';
                if (strlen($dataClean) >= 64) {
                    $eventInfo['amount_wei'] = $this->hexToDecStr(substr($dataClean, 0, 64));
                }
                break;

            default:
                $eventInfo['topics'] = $topics;
                $eventInfo['data'] = $data;
                break;
        }

        return $eventInfo;
    }

    /**
     * Decode string dinamis ABI dari field data event (offset → length → bytes).
     */
    private function decodeStringFromData(string $dataClean): string
    {
        if (strlen($dataClean) < 128) {
            return '';
        }

        $offsetBytes = (int)hexdec(substr($dataClean, 0, 64));
        $lengthPos = $offsetBytes * 2;
        if ($lengthPos <= 0 || strlen($dataClean) < $lengthPos + 64) {
            return '';
        }

        $length = (int)hexdec(substr($dataClean, $lengthPos, 64));
        if ($length <= 0) {
            return '';
        }

        $hex = substr($dataClean, $lengthPos + 64, $length * 2);
        $decoded = $hex !== '' ? hex2bin($hex) : false;

        return $decoded === false ? '' : $decoded;
    }

    private function hexToDecStr(string $hex): string    {
        $hex = ltrim($hex, '0');
        if ($hex === '') return '0';
        $dec = '0';
        $len = strlen($hex);
        for ($i = 0; $i < $len; $i++) {
            $dec = bcmul($dec, '16');
            $dec = bcadd($dec, (string)hexdec($hex[$i]));
        }
        return $dec;
    }
}

