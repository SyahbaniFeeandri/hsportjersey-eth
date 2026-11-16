# Minggu 5 — Integrasi PHP–Blockchain

**Milestone:** M5 — Integrasi PHP–Blockchain
**Periode:** 11–12 November 2026
**Lead:** Luis (Integration Engineer)
**Status milestone:** **Selesai** — 15/15 issue, 5/5 INT PASS
**Kontrak aktif (lokal):** `0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab` (Ganache, chain ID 1337)
**Kontrak historis M4:** `0x2f3efA6bbDC5fAf4dC1a600765c7B7829e47bE10`

> Catatan lingkungan: Ganache di-reset pada 12 November 2026 untuk eksekusi INT,
> lalu kontrak V2 di-deploy ulang. Karena Ganache deterministik dan deploy
> pertama dari akun #0, address yang dihasilkan sama dengan deployment
> lingkungan kedua (`0xe78A…a8Ab`). `blockchain/config/contract_address.txt`
> dan ABI sudah disinkronkan otomatis oleh migration.

---

## 1. Ringkasan

M5 menghubungkan backend PHP native dengan smart contract V2 secara dua arah:
menulis order (`createOrder`), membaca state (`getOrder`, `orderCount`),
mengambil receipt/event, menyimpan bukti kriptografis ke MySQL, dan
memverifikasi konsistensi DB vs chain (`VERIFIED`/`MISMATCH`).

| Komponen | Artefak |
|---|---|
| JSON-RPC client + write path | `api/services/BlockchainClient.php` |
| Abstraksi kontrak + ABI encoding | `api/services/ContractService.php` |
| Receipt & event parser | `api/services/ReceiptService.php` |
| Persistensi bukti | `api/services/ProofRepository.php` |
| Verifikasi DB vs chain | `api/services/VerificationService.php` |
| Orkestrasi | `api/services/OrderIntegrationService.php` |
| HTTP API | `api/blockchain-api.php` |
| Halaman bukti | `blockchain-proof.php` |
| Worker skeleton | `workers/verify_worker.php` + `config/parallel.php` |
| Test integrasi | `tests/integration/run_all.php` |

---

## 2. Status Issue

| ID | Issue | Status | Bukti |
|---|---|---|---|
| `W5-LUI-06` | Contract instance read/write dari backend | ✅ | `BlockchainClient`, `ContractService::createOrder` |
| `W5-LUI-07` | Receipt & event parser | ✅ | `ReceiptService` (9 event) |
| `W5-LUI-08` | Persist proof lengkap | ✅ | `ProofRepository::persistProof` |
| `W5-LUI-09` | Verification service | ✅ | `VerificationService` |
| `W5-LUI-10` | Error handling RPC/network/revert/not found | ✅ | [`03_ERROR_HANDLING_UX.md`](03_ERROR_HANDLING_UX.md) |
| `W5-LUI-11` | INT-01..05 evidence | ✅ | [`01_INTEGRATION_TEST_REPORT.md`](01_INTEGRATION_TEST_REPORT.md) |
| `W5-FRL-09` | Dukungan RPC/network + troubleshooting | ✅ | [`04_RPC_TROUBLESHOOTING.md`](04_RPC_TROUBLESHOOTING.md) |
| `W5-IHS-05` | Wallet UI + tx status | ✅ | `crypto-payment.php`, `order-detail.php` |
| `W5-IHS-06` | Blockchain proof page | ✅ | [`02_PROOF_PAGE.md`](02_PROOF_PAGE.md) |
| `W5-YAS-09` | Review error/UX + UAT integrasi | ✅ | [`03_ERROR_HANDLING_UX.md`](03_ERROR_HANDLING_UX.md) §4 |
| `W5-FEE-10` | Traceability REQ → implementasi → test | ✅ | `docs/week1/03_TRACEABILITY_MATRIX.md` |
| `W5-FDR-06` | Schema + queue config + worker skeleton | ✅ | [`05_WORKER_SKELETON.md`](05_WORKER_SKELETON.md) |
| `W5-KEN-05` | Integration test independen INT-01..05 | ✅ | [`01_INTEGRATION_TEST_REPORT.md`](01_INTEGRATION_TEST_REPORT.md) §5 |
| `W5-ARB-09` | Support bug fixing kontrak | ✅ | [`06_CONTRACT_SUPPORT.md`](06_CONTRACT_SUPPORT.md) |
| `W5-AGU-09` | Support bug fixing kontrak | ✅ | [`06_CONTRACT_SUPPORT.md`](06_CONTRACT_SUPPORT.md) |

**Rekap:** 15 selesai, 0 sebagian, 0 belum → **100%**.

---

## 3. Hasil INT

| Test | Hasil | Inti bukti |
|---|---|---|
| INT-01 | ✅ PASS | RPC 7545, chain ID 1337, bytecode kontrak terbaca |
| INT-02 | ✅ PASS | DB order → `createOrder` on-chain → relasi `blockchain_order_id` |
| INT-03 | ✅ PASS | Tx hash, receipt CONFIRMED, block, gas |
| INT-04 | ✅ PASS | Bukti tersimpan di `orders` dan `blockchain_transactions` |
| INT-05 | ✅ PASS | VERIFIED → manipulasi → MISMATCH → restore VERIFIED |

Bukti mentah:

- [`evidence/int01_int05_test.log`](evidence/int01_int05_test.log)
- [`evidence/int01_int05_test.json`](evidence/int01_int05_test.json)

---

## 4. Cara Menjalankan

Prasyarat: MySQL aktif, Ganache `127.0.0.1:7545` chain ID 1337, kontrak sudah
di-deploy, dan migrasi `migration_blockchain.sql` + `migration_parallel.sql`
sudah dijalankan.

```bash
# 1. Deploy ulang kontrak bila Ganache baru di-reset
cd blockchain
npx truffle migrate --network ganache --reset
node scripts/logDeployment.js
cd ..

# 2. Jalankan test integrasi
php tests/integration/run_all.php

# 3. Cek worker skeleton
php workers/verify_worker.php --dry-run
php workers/verify_worker.php --worker=w1

# 4. Health check API
curl -s http://localhost/project_jersey/api/blockchain-api.php?action=health
```

---

## 5. Carry-over ke M6

1. Worker paralel multi-instance (1/2/4/8) memakai skeleton yang sama.
2. Reducer agregasi dan perbandingan checksum sequential vs parallel.
3. Performance dashboard dan grafik speedup/efficiency.
4. Mode B benchmark: 100 order nyata di Ganache.
5. UAT browser penuh untuk pembayaran, cancel, dan halaman bukti.
