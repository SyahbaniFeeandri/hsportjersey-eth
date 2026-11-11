# 04 — CONTRACT GAP ANALYSIS & PARALLEL WORKLOAD DESIGN

**Milestone:** Minggu 1 — Analisis & Scope Freeze
**Issue terkait:** `W1-ARB-01`, `W1-FDR-01`, `W1-FDR-02`
**Pemilik:** Arby Putra, Farelly De Rossi
**Pendukung:** Agustina, Luis, Kenjiro
**Reviewer:** Feeandri, Yasmin
**Status:** Draft desain v1.0 — interface dibekukan akhir Minggu 3
**Tanggal:** 9 November 2026

---

# BAGIAN A — AUDIT KONTRAK

## 1. Kondisi Kontrak Saat Ini

File: `blockchain/contracts/HSportStore.sol`
Compiler: Solidity `^0.8.19`
Network: Ganache `127.0.0.1:7545`

| Elemen | Implementasi saat ini |
|---|---|
| Enum | `OrderStatus { Created, Paid, Shipped, Completed }` — 4 state |
| Struct `Order` | `id, buyer, totalAmount, status, createdAt, updatedAt, orderNumber` |
| Struct `PaymentEscrow` | `orderId, buyer, amount, released` |
| Mapping | `orders[id]`, `escrows[id]` |
| Access control | `onlyOwner`, `onlyBuyer`, `orderExists` |
| Fungsi | `createOrder`, `shipOrder`, `confirmReceived`, `getOrderStatus`, `getOrderInfo` |
| Event | `OrderCreated`, `OrderShipped`, `OrderCompleted`, `EscrowReleased` |
| Dokumentasi | Tidak ada NatSpec |
| Test | Tidak ada folder `test/` |

---

## 2. Gap Analysis Kontrak

| # | Area | Existing | Target | Gap | Owner | Minggu |
|---|---|---|---|---|---|---|
| C01 | Enum state | 4 state | 8 state: `Created, AwaitingPayment, Paid, InProduction, ReadyToShip, Shipped, Completed, Cancelled` | 4 state baru + reorder enum | Arby | M3 |
| C02 | Struct `Order` | Tanpa hash/payment flag | + `externalOrderHash`, `paymentRecorded` | Field baru + migrasi ABI | Arby | M3 |
| C03 | Duplicate protection | Tidak ada | `mapping(bytes32 => uint256) orderIdByHash` | Mapping + guard | Arby | M3 |
| C04 | Operator | Tidak ada | `mapping(address => bool) operators`, `isOperator` | Mapping + modifier | Arby/Agustina | M3–M4 |
| C05 | `createOrder` | `(string, uint256)`, langsung `Paid`, tanpa hash | `(bytes32, string, uint256)`, state `Created`, hash unik | Ubah signature + status awal | Arby | M3 |
| C06 | `recordPayment` | Tidak ada | Operator mencatat pembayaran, set `Paid` | Fungsi baru | Agustina | M4 |
| C07 | `updateOrderStatus` | Tidak ada | Operator ubah status dengan validasi transisi | Fungsi baru + transition table | Agustina | M4 |
| C08 | `cancelOrder` | Tidak ada | Cancel + refund sesuai state | Fungsi baru + refund | Agustina | M4 |
| C09 | `shipOrder` | `onlyOwner`, dari `Paid` | `onlyOperator`, dari `ReadyToShip` | Ubah modifier + guard | Agustina | M4 |
| C10 | `confirmReceived` | Ada, release escrow | Pertahankan + event + proteksi | Tambah guard/event | Agustina | M4 |
| C11 | Operator management | Tidak ada | `addOperator`, `removeOperator`, `OperatorUpdated` | Fungsi + event | Agustina | M4 |
| C12 | Event | 4 event | + `PaymentRecorded`, `OrderStatusUpdated`, `OrderCancelled`, `OperatorUpdated`, `EscrowRefunded` | 5 event baru | Agustina | M4 |
| C13 | Read function | `getOrderInfo` | `getOrder` + alias `getOrderInfo` | Tambah fungsi, pertahankan alias | Arby | M3 |
| C14 | NatSpec | Tidak ada | Dokumentasi semua fungsi eksternal | Komentar | Arby/Agustina | M3–M4 |
| C15 | Test | Tidak ada | `blockchain/test/*.js` SC-01 s.d. SC-12 | 12 test | Arby/Agustina | M3–M4 |
| C16 | ABI/address | Ada 1 versi | Riwayat versi V1/V2 + deployment log | Arsip | Farrel | M2–M4 |
| C17 | Frontend compatibility | `createOrder(string,uint256)`, `shipOrder` dari `Paid` | Signature baru | Update `crypto-payment.php`, `admin/orders.php` | Luis/Farrel | M4 |

---

## 3. Target Interface (Draft — Freeze Akhir M3)

> **Penting:** Ini adalah spesifikasi desain, bukan kode final. Implementasi dilakukan Minggu 3–4. Nama file tetap `HSportStore.sol`.

```solidity
// SPDX-License-Identifier: MIT
pragma solidity ^0.8.19;

contract HSportStore {
    enum OrderStatus {
        Created,          // 0
        AwaitingPayment,  // 1
        Paid,             // 2
        InProduction,     // 3
        ReadyToShip,      // 4
        Shipped,          // 5
        Completed,        // 6
        Cancelled         // 7
    }

    struct Order {
        uint256 id;
        bytes32 externalOrderHash;
        address buyer;
        uint256 totalAmount;
        OrderStatus status;
        bool paymentRecorded;
        uint256 createdAt;
        uint256 updatedAt;
        string orderNumber;
    }

    struct PaymentEscrow {
        uint256 orderId;
        address buyer;
        uint256 amount;
        bool released;
        bool refunded;
    }

    address public owner;
    uint256 public orderCount;
    mapping(uint256 => Order) public orders;
    mapping(uint256 => PaymentEscrow) public escrows;
    mapping(bytes32 => uint256) public orderIdByHash;
    mapping(address => bool) public operators;

    // Events
    event OrderCreated(uint256 indexed orderId, address indexed buyer, bytes32 indexed externalOrderHash, uint256 amount, string orderNumber);
    event PaymentRecorded(uint256 indexed orderId, address indexed operator, uint256 amount);
    event OrderStatusUpdated(uint256 indexed orderId, OrderStatus oldStatus, OrderStatus newStatus, address indexed operator);
    event OrderShipped(uint256 indexed orderId, address indexed operator);
    event OrderCancelled(uint256 indexed orderId, address indexed actor, string reason);
    event EscrowRefunded(uint256 indexed orderId, address indexed buyer, uint256 amount);
    event OrderCompleted(uint256 indexed orderId, address indexed buyer);
    event EscrowReleased(uint256 indexed orderId, address indexed seller, uint256 amount);
    event OperatorUpdated(address indexed operator, bool active);

    // Modifiers
    modifier onlyOwner();
    modifier onlyOperator();
    modifier onlyBuyer(uint256 orderId);
    modifier orderExists(uint256 orderId);
    modifier notFinal(uint256 orderId);

    // Core
    function createOrder(bytes32 externalOrderHash, string memory orderNumber, uint256 amount) external payable returns (uint256);
    function getOrder(uint256 orderId) external view returns (Order memory);
    function getOrderInfo(uint256 orderId) external view returns (Order memory); // alias kompatibilitas
    function getOrderStatus(uint256 orderId) external view returns (OrderStatus);
    function getOrderIdByHash(bytes32 externalOrderHash) external view returns (uint256);
    function isOperator(address account) external view returns (bool);

    // Payment & state
    function recordPayment(uint256 orderId) external onlyOperator orderExists(orderId);
    function updateOrderStatus(uint256 orderId, OrderStatus newStatus) external onlyOperator orderExists(orderId);
    function shipOrder(uint256 orderId) external onlyOperator orderExists(orderId);
    function confirmReceived(uint256 orderId) external onlyBuyer(orderId) orderExists(orderId);
    function cancelOrder(uint256 orderId) external orderExists(orderId) notFinal(orderId);

    // Operator management
    function addOperator(address account) external onlyOwner;
    function removeOperator(address account) external onlyOwner;
}
```

### 3.1 Perilaku fungsi

| Fungsi | Actor | Guard utama | Efek | Event |
|---|---|---|---|---|
| `createOrder` | Pembeli | `amount > 0`; `msg.value == amount`; hash belum ada | Buat order `Created`, simpan escrow, set `orderIdByHash` | `OrderCreated` |
| `recordPayment` | Operator | state `Created`/`AwaitingPayment`; `!paymentRecorded`; escrow valid | `paymentRecorded = true`, status `Paid` | `PaymentRecorded` |
| `updateOrderStatus` | Operator | transisi valid sesuai tabel | Ubah status, `updatedAt` | `OrderStatusUpdated` |
| `shipOrder` | Operator | state `ReadyToShip` | Status `Shipped` | `OrderShipped`, `OrderStatusUpdated` |
| `confirmReceived` | Pembeli | state `Shipped`; `!released` | `Completed`, release escrow | `OrderCompleted`, `EscrowReleased` |
| `cancelOrder` | Pembeli/operator/owner | state sesuai aturan cancel; `!released` | `Cancelled`, refund bila perlu | `OrderCancelled`, `EscrowRefunded` |
| `addOperator` | Owner | alamat bukan 0; belum operator | `operators[account] = true` | `OperatorUpdated` |
| `removeOperator` | Owner | bukan owner; operator aktif | `operators[account] = false` | `OperatorUpdated` |

### 3.2 Helper internal

```solidity
function _isValidTransition(OrderStatus from, OrderStatus to) internal pure returns (bool);
function _refundIfNeeded(uint256 orderId) internal;
function _releaseEscrow(uint256 orderId) internal;
```

### 3.3 Kompatibilitas

1. `getOrderInfo` dipertahankan sebagai alias sampai frontend selesai migrasi.
2. `shipOrder(uint256)` signature dipertahankan, tetapi guard berubah dari `Paid` menjadi `ReadyToShip`.
3. `createOrder` berubah signature; frontend `crypto-payment.php` wajib diperbarui di Minggu 4 (`W4-FRL-08`).
4. ABI lama tetap disimpan di `blockchain/config/contract_abi.v1.json` sebelum diganti.

---

## 4. Rencana Implementasi Kontrak

| Minggu | Pekerjaan | Output | Gate |
|---|---|---|---|
| M2 | Finalkan struct/enum/mapping/modifier; siapkan test harness | Spesifikasi + skeleton test | Bisa `truffle test` |
| M3 | Implement C01–C05, C13–C14; test SC-01 s.d. SC-06 | Kontrak V1 + 6 test PASS | Interface freeze |
| M4 | Implement C06–C12; test SC-07 s.d. SC-12 | Kontrak V2 + 6 test PASS | Semua 12 SC PASS |
| M4 | Deploy V2, update ABI/address, update frontend | Address V2 + integrasi | Frontend kompatibel |

### 4.1 Checklist freeze interface (akhir M3)

- [ ] Enum 8 state final dan urutannya tidak berubah.
- [ ] Struct `Order` dan `PaymentEscrow` final.
- [ ] Signature semua fungsi eksternal final.
- [ ] Daftar event final.
- [ ] Revert message final dan unik.
- [ ] ABI V1 diarsipkan.
- [ ] Test SC-01 s.d. SC-06 PASS.
- [ ] Persetujuan Arby + Agustina + Luis + Kenjiro.

---

# BAGIAN B — DESAIN WORKLOAD PARALEL

## 5. Tujuan

Membuktikan bahwa verifikasi batch order (DB vs blockchain) dan agregasi finansial dapat diproses lebih cepat dengan multi-worker, **tanpa mengubah hasil** dibanding sequential.

**Bukan tujuan:** membuat smart contract multithreaded. Paralelisme terjadi di application layer (worker PHP), bukan di EVM.

---

## 6. Definisi Unit Kerja

**Satu job = verifikasi satu partisi order.**

Untuk setiap order dalam partisi:

1. Baca data order dari MySQL: `id`, `order_number`, `total_amount`, `payment_status`, `status`, `tx_hash`, `blockchain_order_id`, `external_order_hash`, `wallet_address`, `contract_address`, `verification_status`.
2. Baca state on-chain: `getOrder(blockchain_order_id)` (atau adapter mock untuk dataset besar).
3. Bandingkan field minimum:
   - `buyer` vs `wallet_address`,
   - `totalAmount` vs `total_amount` (konversi konsisten),
   - `status` on-chain vs mapping status DB,
   - `externalOrderHash` vs hash yang dihitung dari order,
   - `paymentRecorded` vs `payment_status`.
4. Hasil per order: `VERIFIED`, `MISMATCH`, atau `ERROR` (dengan alasan).
5. Tulis hasil ke `verification_jobs` + `worker_logs`.
6. Update checkpoint partisi.

**Output reduksi per batch:**

| Metrik | Rumus |
|---|---|
| `total_orders` | Jumlah order yang diverifikasi |
| `verified_count` | Jumlah `VERIFIED` |
| `mismatch_count` | Jumlah `MISMATCH` |
| `error_count` | Jumlah `ERROR` |
| `revenue_verified` | Sum `total_amount` untuk `VERIFIED` dan `payment_status = paid` |
| `revenue_all` | Sum `total_amount` seluruh order |
| `aov` | `revenue_all / total_orders` |
| `paid_count` / `unpaid_count` | Count berdasarkan `payment_status` |
| `checksum` | Hash dari urutan hasil terurut `id` (untuk correctness gate) |

---

## 7. Dataset Benchmark

### 7.1 Ukuran

| Dataset | Jumlah order | Tujuan |
|---|---:|---|
| D100 | 100 | Smoke test, validasi correctness |
| D500 | 500 | Skala kecil, uji overhead |
| D1000 | 1.000 | Skala utama benchmark |
| D5000 | 5.000 | Skala besar, uji kontensi |

### 7.2 Komposisi

| Kategori | Proporsi | Deskripsi |
|---|---:|---|
| `VERIFIED` | 70% | Data DB cocok dengan chain |
| `MISMATCH` | 20% | Amount/status/wallet/hash berbeda |
| `ERROR` | 10% | Order tidak ditemukan / RPC error tersimulasi |

Distribusi status order dibuat merata secara proporsional: `Created`, `Paid`, `InProduction`, `ReadyToShip`, `Shipped`, `Completed`, `Cancelled`.

### 7.3 Aturan generasi

1. Deterministik: seed tetap (mis. `HSPORT-2026`), sehingga dataset bisa diregenerasi.
2. Dataset disimpan sebagai SQL/CSV di `benchmark/datasets/` (atau dibangkitkan oleh script).
3. `dataset_hash` dicatat di `job_batches`.
4. Tidak ada data personal nyata; nama/email/alamat dummy.
5. Dataset 100 dan 500 dipakai untuk validasi awal; 1.000 dan 5.000 untuk benchmark final.
6. Untuk 5.000 order, verifikasi on-chain memakai **mock chain adapter** dengan latency deterministik agar benchmark tidak didominasi Ganache. Validasi kebenaran adapter dilakukan pada 100 order nyata di Ganache.

### 7.4 Mode benchmark

| Mode | Sumber state on-chain | Pemakaian |
|---|---|---|
| **A — Algorithm benchmark** | Mock adapter deterministik | D100–D5000, perbandingan worker |
| **B — Integration benchmark** | Ganache nyata | D100, validasi correctness end-to-end |

Hasil Mode A dan Mode B dilaporkan terpisah; tidak dicampur.

---

## 8. Partisi & Reduksi

### 8.1 Partisi

- Jumlah partisi `P` = jumlah worker yang diuji (1, 2, 4, 8).
- Ukuran partisi = `ceil(N / P)`.
- Partisi dibuat **contiguous** berdasarkan `id` yang sudah diurutkan, sehingga beban mendekati seimbang.
- Setiap partisi menjadi satu baris `verification_jobs` dengan `partition_no`.

Contoh N=1.000, P=4:

| Partisi | Range ID | Jumlah |
|---|---|---:|
| 1 | 1–250 | 250 |
| 2 | 251–500 | 250 |
| 3 | 501–750 | 250 |
| 4 | 751–1.000 | 250 |

### 8.2 Klaim job oleh worker

Worker mengambil job secara atomik:

```sql
START TRANSACTION;
SELECT id FROM verification_jobs
WHERE batch_id = ? AND status = 'PENDING'
ORDER BY partition_no
LIMIT 1
FOR UPDATE SKIP LOCKED;
UPDATE verification_jobs
SET status = 'RUNNING', worker_id = ?, started_at = NOW()
WHERE id = ?;
COMMIT;
```

`SKIP LOCKED` mencegah dua worker mengambil job yang sama.

### 8.3 Reduksi

Reducer hanya berjalan setelah semua partisi `DONE` atau `FAILED`.

1. Baca hasil per partisi dari `checkpoints` (JSON).
2. Jumlahkan metrik (sum/count).
3. Hitung AOV.
4. Urutkan hasil per order `id`, hitung `checksum`.
5. Bandingkan `checksum` dengan hasil sequential.
6. Simpan ringkasan ke `job_batches`.

**Sifat reduksi:** associative dan commutative untuk sum/count; checksum membutuhkan urutan stabil, sehingga reducer mengurutkan berdasarkan `id`.

---

## 9. Worker

| Aspek | Keputusan |
|---|---|
| Bahasa | PHP CLI (sesuai D1) |
| Jumlah worker | 1, 2, 4, 8 proses |
| Komunikasi | Tabel MySQL (`verification_jobs`) |
| Isolasi | Satu worker satu proses; tidak berbagi memori |
| Error handling | Job `FAILED`, retry maksimal 2x, lalu `DEAD` |
| Idempotensi | `UNIQUE(batch_id, partition_no)`; worker tidak memproses partisi selesai |
| Log | `worker_logs` per job |
| Timeout | 300 detik per job (dapat dikonfigurasi) |

---

## 10. Skema Database Paralel

Draft lengkap ada di [`../../migration_parallel.sql`](../../migration_parallel.sql).

| Tabel | Fungsi | Kolom kunci |
|---|---|---|
| `job_batches` | Satu baris per benchmark/batch | `id`, `batch_code`, `dataset_size`, `worker_count`, `mode`, `dataset_hash`, `status`, `started_at`, `finished_at`, `total_orders`, `verified_count`, `mismatch_count`, `error_count`, `revenue_all`, `revenue_verified`, `aov`, `checksum`, `duration_ms` |
| `verification_jobs` | Satu baris per partisi | `id`, `batch_id`, `partition_no`, `range_start`, `range_end`, `status`, `worker_id`, `attempts`, `started_at`, `finished_at`, `error_message` |
| `checkpoints` | Hasil per partisi | `id`, `batch_id`, `job_id`, `partition_no`, `processed_count`, `verified_count`, `mismatch_count`, `error_count`, `revenue_all`, `revenue_verified`, `checksum`, `payload`, `created_at` |
| `worker_logs` | Log eksekusi | `id`, `batch_id`, `job_id`, `worker_id`, `level`, `message`, `context`, `created_at` |

---

## 11. Metrik Benchmark

| Metrik | Rumus | Satuan |
|---|---|---|
| Execution time | `T = finished_at - started_at` | detik/ms |
| Speedup | `S(p) = T(1) / T(p)` | kali |
| Efficiency | `E(p) = S(p) / p` | persen |
| Throughput | `N / T(p)` | order/detik |
| Correctness | `checksum_parallel == checksum_sequential` | boolean |
| Overhead | `T(p) - T(1)/p` | detik/ms |

Target yang **diukur**, bukan dijanjikan:

- `S(4) > 1` untuk D1000 dan D5000.
- `E(4) ≥ 0.5` (target internal, dapat direvisi setelah data nyata).
- Tidak ada `MISMATCH` palsu akibat race condition.

---

## 12. Protokol Benchmark

1. Pastikan tidak ada proses berat lain di mesin.
2. Jalankan warm-up 1x untuk setiap konfigurasi.
3. Jalankan 3 repetisi; ambil **median**.
4. Urutan pengujian: sequential → 2 → 4 → 8 worker; ulangi urutan terbalik untuk mengurangi bias.
5. Catat spesifikasi mesin, versi PHP/MySQL/Node, dataset hash, dan waktu.
6. Hentikan jika correctness gate gagal.
7. Simpan hasil mentah (CSV/JSON) dan ringkasan (tabel/grafik).
8. Benchmark Mode A dan Mode B dipisah.

### 12.1 Faktor yang dapat merusak validitas

| Faktor | Mitigasi |
|---|---|
| Ganache single-threaded | Gunakan mock adapter untuk dataset besar; validasi 100 order nyata |
| DB contention | Index, koneksi per worker, batch commit |
| Worker tidak seimbang | Partisi contiguous dengan jumlah merata |
| Cache OS | Warm-up + repetisi |
| Overhead proses | Catat startup time terpisah |
| Dataset tidak deterministik | Seed + dataset hash |

---

## 13. Risiko & Mitigasi

| # | Risiko | Dampak | Mitigasi | Owner |
|---|---|---|---|---|
| P1 | MySQL `SKIP LOCKED` tidak didukung versi lama | Worker saling menimpa | Pastikan MySQL 8.0.30 (sudah) | Farelly |
| P2 | 5.000 order on-chain terlalu lambat | Benchmark tidak selesai | Mock adapter + validasi 100 order nyata | Farelly |
| P3 | Signature kontrak berubah setelah integrasi | Frontend rusak | Freeze interface akhir M3 + update frontend M4 | Arby/Luis |
| P4 | Worker crash di tengah job | Hasil tidak lengkap | Checkpoint + retry + status `DEAD` | Farelly |
| P5 | Reduksi tidak deterministik | Correctness gate gagal | Urutkan `id` sebelum checksum | Farelly |
| P6 | Data DB tidak konsisten | Hasil mismatch palsu | Seed dataset + transaksi | Kenjiro |
| P7 | Ganache reset | Proof lama hilang | Deployment log + dataset snapshot | Farrel |

---

## 14. Tautan

- State machine: [`01_ON_OFF_CHAIN_AND_STATE.md`](01_ON_OFF_CHAIN_AND_STATE.md)
- Skema paralel: [`../../migration_parallel.sql`](../../migration_parallel.sql)
- Test plan: [`07_TEST_PLAN.md`](07_TEST_PLAN.md)
- Integrasi: [`05_INTEGRATION_AND_UI_DESIGN.md`](05_INTEGRATION_AND_UI_DESIGN.md)
