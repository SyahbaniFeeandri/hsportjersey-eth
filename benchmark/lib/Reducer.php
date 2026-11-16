<?php
/**
 * Reducer - Agregasi hasil partisi menjadi ringkasan batch.
 * Issue: W6-FDR-09, W6-FDR-10, W6-FDR-11
 * Pemilik: Farelly De Rossi
 *
 * Reducer membaca `checkpoints` (payload JSON berisi hasil per order),
 * menggabungkan seluruh partisi, mengurutkan berdasarkan id, lalu menghitung
 * checksum dengan format yang sama seperti baseline sequential. Hasilnya
 * disimpan ke `job_batches` dan dibandingkan dengan baseline.
 */

declare(strict_types=1);

namespace HSport\Benchmark;

require_once __DIR__ . '/Checksum.php';

use PDO;
use RuntimeException;
use Throwable;

class Reducer
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array<string,mixed>
     */
    public function reduce(int $batchId, ?int $durationMsOverride = null): array
    {
        $batch = $this->fetchBatch($batchId);
        $jobs = $this->fetchJobs($batchId);
        $checkpoints = $this->fetchCheckpoints($batchId);

        if (empty($jobs)) {
            throw new RuntimeException("Batch #{$batchId} tidak memiliki verification_jobs.");
        }
        if (empty($checkpoints)) {
            throw new RuntimeException("Batch #{$batchId} belum memiliki checkpoint.");
        }

        $terminals = ['DONE', 'FAILED', 'DEAD', 'CANCELLED'];
        $incompleteJobs = array_values(array_filter(
            $jobs,
            static fn(array $job): bool => !in_array((string)$job['status'], $terminals, true)
        ));
        $complete = empty($incompleteJobs);

        $byId = [];
        $duplicateIds = [];
        $resultsAvailable = true;
        $checkpointSums = [
            'processed' => 0,
            'verified' => 0,
            'mismatch' => 0,
            'error' => 0,
            'revenue_all' => 0,
            'revenue_verified' => 0,
        ];

        foreach ($checkpoints as $checkpoint) {
            $checkpointSums['processed'] += (int)$checkpoint['processed_count'];
            $checkpointSums['verified'] += (int)$checkpoint['verified_count'];
            $checkpointSums['mismatch'] += (int)$checkpoint['mismatch_count'];
            $checkpointSums['error'] += (int)$checkpoint['error_count'];
            $checkpointSums['revenue_all'] += (float)$checkpoint['revenue_all'];
            $checkpointSums['revenue_verified'] += (float)$checkpoint['revenue_verified'];

            $payload = json_decode((string)($checkpoint['payload'] ?? ''), true);
            if (!is_array($payload) || !isset($payload['results']) || !is_array($payload['results'])) {
                $resultsAvailable = false;
                continue;
            }

            foreach ($payload['results'] as $result) {
                if (!is_array($result) || !isset($result['id'])) {
                    continue;
                }
                $id = (int)$result['id'];
                if (isset($byId[$id])) {
                    $duplicateIds[] = $id;
                    continue;
                }
                $byId[$id] = $result;
            }
        }

        ksort($byId, SORT_NUMERIC);
        $results = array_values($byId);

        $verified = 0;
        $mismatch = 0;
        $errors = 0;
        $skipped = 0;
        $paid = 0;
        $unpaid = 0;
        $revenueAll = 0;
        $revenueVerified = 0;

        foreach ($results as $result) {
            $verification = (string)($result['verification'] ?? 'ERROR');
            $amount = (int)($result['amount_idr'] ?? 0);
            $isPaid = ($result['paid'] ?? false) === true;

            if ($verification === 'VERIFIED') {
                if ($isPaid) {
                    $revenueVerified += $amount;
                }
                $verified++;
            } elseif ($verification === 'MISMATCH') {
                $mismatch++;
            } elseif ($verification === 'SKIPPED') {
                // Order tanpa relasi on-chain: bukan error verifikasi.
                $skipped++;
            } else {
                $errors++;
            }

            $revenueAll += $amount;
            $isPaid ? $paid++ : $unpaid++;
        }

        $processed = count($results);
        $expected = (int)$batch['total_orders'];
        $checksum = $resultsAvailable ? Checksum::compute($results) : null;

        $baseline = $this->findBaseline($batch);
        $baselineChecksum = $baseline['checksum'] ?? null;
        $checksumMatch = null;
        if ($checksum !== null && $baselineChecksum !== null && $baselineChecksum !== '') {
            $checksumMatch = hash_equals((string)$baselineChecksum, (string)$checksum);
        }

        $metricsConsistent = $resultsAvailable
            && $checkpointSums['processed'] === $processed
            && $checkpointSums['verified'] === $verified
            && $checkpointSums['mismatch'] === $mismatch
            && $checkpointSums['error'] === $errors
            && (int)round($checkpointSums['revenue_all']) === $revenueAll
            && (int)round($checkpointSums['revenue_verified']) === $revenueVerified;

        $complete = $complete && $processed === $expected && empty($duplicateIds);
        if ($checksumMatch === false) {
            $complete = false;
        }

        $durationMs = $durationMsOverride;
        if ($durationMs === null && !empty($batch['duration_ms'])) {
            // Reduce ulang (idempoten) tidak boleh menimpa wall-clock hasil runner.
            $durationMs = (int)$batch['duration_ms'];
        }
        if ($durationMs === null) {
            $startedTs = strtotime((string)($batch['started_at'] ?? $batch['created_at'] ?? 'now')) ?: time();
            $durationMs = max(0, (int)round((microtime(true) - $startedTs) * 1000));
        }

        $aov = $processed > 0 ? round($revenueAll / $processed, 4) : 0.0;
        $status = $complete ? 'DONE' : 'FAILED';

        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("UPDATE job_batches SET
                    status = ?, total_orders = ?, verified_count = ?, mismatch_count = ?, error_count = ?,
                    revenue_all = ?, revenue_verified = ?, aov = ?, checksum = ?, duration_ms = ?,
                    finished_at = NOW(), notes = ?
                WHERE id = ?")
                ->execute([
                    $status,
                    $processed,
                    $verified,
                    $mismatch,
                    $errors,
                    $revenueAll,
                    $revenueVerified,
                    $aov,
                    $checksum,
                    $durationMs,
                    $this->mergeNotes($batch, [
                        'reduced_at' => date('c'),
                        'checksum_match' => $checksumMatch,
                        'duplicate_ids' => $duplicateIds,
                        'metrics_consistent' => $metricsConsistent,
                        'expected_orders' => $expected,
                    ]),
                    $batchId,
                ]);

            $this->pdo->prepare("INSERT INTO worker_logs (batch_id, job_id, worker_id, level, message, context)
                    VALUES (?, NULL, 'reducer', ?, ?, ?)")
                ->execute([
                    $batchId,
                    $complete ? 'INFO' : 'ERROR',
                    $complete
                        ? "Reducer selesai: {$processed} order, checksum " . ($checksumMatch === true ? 'MATCH' : 'TANPA BASELINE')
                        : "Reducer menandai batch FAILED: processed={$processed}, expected={$expected}, duplicates=" . count($duplicateIds),
                    json_encode([
                        'verified' => $verified,
                        'mismatch' => $mismatch,
                        'error' => $errors,
                        'skipped' => $skipped,
                        'revenue_all' => $revenueAll,
                        'revenue_verified' => $revenueVerified,
                        'checksum' => $checksum,
                        'baseline_checksum' => $baselineChecksum,
                        'checksum_match' => $checksumMatch,
                        'duplicate_ids' => $duplicateIds,
                        'duration_ms' => $durationMs,
                    ], JSON_UNESCAPED_SLASHES),
                ]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return [
            'batch_id' => $batchId,
            'batch_code' => $batch['batch_code'],
            'mode' => $batch['mode'],
            'dataset_name' => $batch['dataset_name'],
            'dataset_size' => (int)$batch['dataset_size'],
            'worker_count' => (int)$batch['worker_count'],
            'status' => $status,
            'complete' => $complete,
            'expected_orders' => $expected,
            'processed_orders' => $processed,
            'verified_count' => $verified,
            'mismatch_count' => $mismatch,
            'error_count' => $errors,
            'skipped_count' => $skipped,
            'paid_count' => $paid,
            'unpaid_count' => $unpaid,
            'revenue_all' => $revenueAll,
            'revenue_verified' => $revenueVerified,
            'aov' => $aov,
            'checksum' => $checksum,
            'baseline_checksum' => $baselineChecksum,
            'checksum_match' => $checksumMatch,
            'metrics_consistent' => $metricsConsistent,
            'duplicate_ids' => $duplicateIds,
            'duration_ms' => $durationMs,
            'throughput_per_sec' => $durationMs > 0 ? round($processed / ($durationMs / 1000), 2) : null,
            'incomplete_jobs' => array_map(
                static fn(array $job): array => [
                    'id' => (int)$job['id'],
                    'partition_no' => (int)$job['partition_no'],
                    'status' => $job['status'],
                ],
                $incompleteJobs
            ),
            'partitions' => array_map(
                static fn(array $job): array => [
                    'job_id' => (int)$job['id'],
                    'partition_no' => (int)$job['partition_no'],
                    'range_start' => (int)$job['range_start'],
                    'range_end' => (int)$job['range_end'],
                    'status' => $job['status'],
                    'worker_id' => $job['worker_id'],
                    'attempts' => (int)$job['attempts'],
                ],
                $jobs
            ),
        ];
    }

    private function fetchBatch(int $batchId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM job_batches WHERE id = ?');
        $stmt->execute([$batchId]);
        $batch = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$batch) {
            throw new RuntimeException("Batch #{$batchId} tidak ditemukan.");
        }

        return $batch;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchJobs(int $batchId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM verification_jobs WHERE batch_id = ? ORDER BY partition_no');
        $stmt->execute([$batchId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchCheckpoints(int $batchId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM checkpoints WHERE batch_id = ? ORDER BY partition_no');
        $stmt->execute([$batchId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<string,mixed> $batch
     * @return array<string,mixed>|null
     */
    private function findBaseline(array $batch): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, checksum, duration_ms
            FROM job_batches
            WHERE mode = ? AND dataset_name = ? AND dataset_size = ?
              AND is_baseline = 1 AND status = 'DONE' AND id <> ?
            ORDER BY id DESC LIMIT 1");
        $stmt->execute([
            $batch['mode'],
            $batch['dataset_name'],
            $batch['dataset_size'],
            $batch['id'],
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @param array<string,mixed> $batch
     * @param array<string,mixed> $extra
     */
    private function mergeNotes(array $batch, array $extra): string
    {
        $notes = json_decode((string)($batch['notes'] ?? ''), true);
        if (!is_array($notes)) {
            $notes = ['notes' => (string)($batch['notes'] ?? '')];
        }

        return json_encode(array_merge($notes, $extra), JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}
