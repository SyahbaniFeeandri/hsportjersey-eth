<?php
/**
 * ProofRepository - Persistensi Data Bukti Blockchain ke Database
 * Issue: W5-LUI-08, INT-04
 * Pemilik: Luis (Integration Engineer)
 */

declare(strict_types=1);

namespace HSport\Services;

use PDO;
use Exception;

class ProofRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getOrderById(int $orderId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getOrderByTxHash(string $txHash): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE tx_hash = ?");
        $stmt->execute([$txHash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getOrderByBlockchainOrderId(int $blockchainOrderId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE blockchain_order_id = ?");
        $stmt->execute([$blockchainOrderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Menyimpan bukti lengkap ke tabel orders dan mencatat ke blockchain_transactions
     */
    public function persistProof(int $orderId, array $data): bool
    {
        $txHash = $data['tx_hash'] ?? null;
        $wallet = $data['wallet_address'] ?? null;
        $blockNum = isset($data['block_number']) ? (int)$data['block_number'] : null;
        $contract = $data['contract_address'] ?? null;
        $chainOrderId = isset($data['blockchain_order_id']) ? (int)$data['blockchain_order_id'] : null;
        $extHash = $data['external_order_hash'] ?? null;
        $verifStatus = $data['verification_status'] ?? 'PENDING';
        $paymentStatus = $data['payment_status'] ?? 'paid';
        $eventName = $data['event_name'] ?? 'OrderCreated';
        $txStatus = $data['tx_status'] ?? 'CONFIRMED';
        $payload = isset($data['payload']) ? (is_string($data['payload']) ? $data['payload'] : json_encode($data['payload'])) : null;

        // 1. Update tabel orders
        $sql = "UPDATE orders SET 
                    payment_type = 'crypto',
                    payment_status = :payment_status,
                    status = CASE WHEN status = 'pending' THEN 'paid' ELSE status END,
                    tx_hash = :tx_hash,
                    wallet_address = :wallet_address,
                    block_number = :block_number,
                    contract_address = :contract_address,
                    blockchain_order_id = :blockchain_order_id,
                    external_order_hash = :external_order_hash,
                    verification_status = :verification_status,
                    paid_at = COALESCE(paid_at, NOW()),
                    updated_at = NOW()
                WHERE id = :order_id";

        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute([
            ':payment_status' => $paymentStatus,
            ':tx_hash' => $txHash,
            ':wallet_address' => $wallet,
            ':block_number' => $blockNum,
            ':contract_address' => $contract,
            ':blockchain_order_id' => $chainOrderId,
            ':external_order_hash' => $extHash,
            ':verification_status' => $verifStatus,
            ':order_id' => $orderId,
        ]);

        if (!$ok) {
            return false;
        }

        // 2. Insert atau Update ke blockchain_transactions
        try {
            if ($txHash) {
                // Cek apakah tx_hash ini sudah dicatat
                $chk = $this->pdo->prepare("SELECT id FROM blockchain_transactions WHERE tx_hash = ? LIMIT 1");
                $chk->execute([$txHash]);
                $existing = $chk->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    $upd = $this->pdo->prepare("UPDATE blockchain_transactions SET 
                                                    order_id = ?,
                                                    blockchain_order_id = ?,
                                                    block_number = ?,
                                                    contract_address = ?,
                                                    wallet_address = ?,
                                                    event_name = ?,
                                                    status = ?,
                                                    verification_status = ?,
                                                    payload = ?,
                                                    updated_at = NOW()
                                                WHERE id = ?");
                    $upd->execute([
                        $orderId,
                        $chainOrderId,
                        $blockNum,
                        $contract,
                        $wallet,
                        $eventName,
                        $txStatus,
                        $verifStatus,
                        $payload,
                        $existing['id'],
                    ]);
                } else {
                    $ins = $this->pdo->prepare("INSERT INTO blockchain_transactions 
                                                    (order_id, blockchain_order_id, tx_hash, block_number, contract_address, wallet_address, event_name, status, verification_status, payload, created_at, updated_at)
                                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                    $ins->execute([
                        $orderId,
                        $chainOrderId,
                        $txHash,
                        $blockNum,
                        $contract,
                        $wallet,
                        $eventName,
                        $txStatus,
                        $verifStatus,
                        $payload,
                    ]);
                }
            }
        } catch (Exception $e) {
            // Log warning tetapi jangan batalkan update orders
            error_log("Gagal mencatat blockchain_transactions: " . $e->getMessage());
        }

        return true;
    }

    /**
     * Menyimpan bukti pembatalan order tanpa menimpa tx_hash pembayaran.
     * Order hanya ditandai cancelled bila belum final.
     * Issue: W4-IHS-04 (UI cancel), W4-YAS-08 (UAT cancel)
     */
    public function recordCancellation(int $orderId, array $data): bool
    {
        $txHash = (string)($data['tx_hash'] ?? '');
        $wallet = $data['wallet_address'] ?? null;
        $blockNum = isset($data['block_number']) ? (int)$data['block_number'] : null;
        $contract = $data['contract_address'] ?? null;
        $chainOrderId = isset($data['blockchain_order_id']) ? (int)$data['blockchain_order_id'] : null;
        $reason = (string)($data['reason'] ?? '');
        $txStatus = $data['tx_status'] ?? 'CONFIRMED';
        $payload = isset($data['payload'])
            ? (is_string($data['payload']) ? $data['payload'] : json_encode($data['payload']))
            : json_encode(['reason' => $reason]);

        $stmt = $this->pdo->prepare("UPDATE orders SET
                                        status = 'cancelled',
                                        verification_status = 'PENDING',
                                        updated_at = NOW()
                                    WHERE id = :order_id
                                      AND status NOT IN ('completed', 'cancelled')");
        $ok = $stmt->execute([':order_id' => $orderId]);

        if ($txHash === '') {
            return $ok;
        }

        try {
            $chk = $this->pdo->prepare("SELECT id FROM blockchain_transactions WHERE tx_hash = ? LIMIT 1");
            $chk->execute([$txHash]);
            $existing = $chk->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $upd = $this->pdo->prepare("UPDATE blockchain_transactions SET
                                                order_id = ?,
                                                blockchain_order_id = ?,
                                                block_number = ?,
                                                contract_address = ?,
                                                wallet_address = ?,
                                                event_name = 'OrderCancelled',
                                                status = ?,
                                                verification_status = 'PENDING',
                                                payload = ?,
                                                updated_at = NOW()
                                            WHERE id = ?");
                $upd->execute([
                    $orderId,
                    $chainOrderId,
                    $blockNum,
                    $contract,
                    $wallet,
                    $txStatus,
                    $payload,
                    $existing['id'],
                ]);
            } else {
                $ins = $this->pdo->prepare("INSERT INTO blockchain_transactions
                                                (order_id, blockchain_order_id, tx_hash, block_number, contract_address, wallet_address, event_name, status, verification_status, payload, created_at, updated_at)
                                            VALUES (?, ?, ?, ?, ?, ?, 'OrderCancelled', ?, 'PENDING', ?, NOW(), NOW())");
                $ins->execute([
                    $orderId,
                    $chainOrderId,
                    $txHash,
                    $blockNum,
                    $contract,
                    $wallet,
                    $txStatus,
                    $payload,
                ]);
            }
        } catch (Exception $e) {
            error_log("Gagal mencatat pembatalan ke blockchain_transactions: " . $e->getMessage());
        }

        return $ok;
    }

    /**
     * Event terakhir untuk sebuah order, mis. OrderCancelled.
     */
    public function getLatestEvent(int $orderId, string $eventName): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM blockchain_transactions
                                         WHERE order_id = ? AND event_name = ?
                                         ORDER BY id DESC LIMIT 1");
            $stmt->execute([$orderId, $eventName]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function updateVerificationStatus(int $orderId, string $status, ?string $verifiedAt = null): bool
    {
        $sql = "UPDATE orders SET verification_status = ?, verified_at = COALESCE(?, NOW()) WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute([$status, $verifiedAt, $orderId]);

        // Sinkronkan ke blockchain_transactions
        try {
            $stmtTx = $this->pdo->prepare("UPDATE blockchain_transactions SET verification_status = ?, updated_at = NOW() WHERE order_id = ?");
            $stmtTx->execute([$status, $orderId]);
        } catch (Exception $e) {}

        return $ok;
    }

    public function getTransactionsByOrderId(int $orderId): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM blockchain_transactions WHERE order_id = ? ORDER BY id ASC");
            $stmt->execute([$orderId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}

