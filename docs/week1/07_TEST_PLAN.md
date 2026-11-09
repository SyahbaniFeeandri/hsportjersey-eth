# 07 — TEST PLAN 21 CASE & TEST HARNESS

**Milestone:** Minggu 1 — Analisis & Scope Freeze
**Issue terkait:** `W1-KEN-01`
**Pemilik:** Kenjiro B.H.G.
**Pendukung:** Arby, Agustina, Luis, Farelly, Farrel
**Reviewer:** Feeandri, Yasmin
**Status:** Test plan v0.1 — dieksekusi Minggu 2–7
**Tanggal:** 9 November 2026

---

## 1. Tujuan

1. Menyediakan definisi tunggal 21 test case yang tidak ambigu.
2. Menetapkan bukti minimum untuk setiap PASS/FAIL.
3. Menyiapkan struktur test harness Truffle dan test integrasi/paralel.
4. Menjadi dasar regression test sebelum demo.

---

## 2. Ruang Lingkup

| Level | Cakupan | Jumlah |
|---|---|---:|
| Smart contract | Fungsi, state, access control, event, revert, escrow | 12 (SC-01 s.d. SC-12) |
| Integrasi | RPC, create order, receipt, proof, verifikasi | 5 (INT-01 s.d. INT-05) |
| Parallel & benchmark | Sequential, parallel, metrik, konkurensi | 4 (PAR-01 s.d. PAR-04) |
| **Total** | | **21** |

**Di luar scope test otomatis:** UAT manual (lihat `02_BUSINESS_PROCESS_AND_UAT.md`), UI visual, dan performa jaringan publik.

---

## 3. Rekonsiliasi Definisi 21 Test

Keputusan **D3** menetapkan basis escrow dari `DOKUMENTASI_PROJECT_DAN_PERAN.md`, lalu menambahkan fitur baru. Agar tetap 21:

| Perubahan | Penjelasan |
|---|---|
| Test escrow lama dipertahankan | `createOrder`, amount invalid, get order, ship, confirm, release ganda, event, unauthorized |
| Duplicate hash ditambahkan | Menempati SC-04 |
| Operator & recordPayment ditambahkan | SC-07 dan SC-08 |
| Cancel ditambahkan | SC-11 |
| Event tidak lagi menjadi test terpisah | Event diverifikasi di SC-02, SC-08, SC-09, SC-11, SC-12 |
| `shipOrder` digabung ke transisi valid | SC-09 |
| Release escrow digabung dengan confirm | SC-12 |

Dengan demikian tidak ada fitur baru yang hilang dan jumlah tetap 21.

---

## 4. Environment Test

| Komponen | Nilai |
|---|---|
| OS | Windows 11 Pro |
| PHP | 8.1.10 |
| Node | 24.20.0 |
| Truffle | 5.11.5 |
| Ganache | 7.9.1, `127.0.0.1:7545` |
| Solidity | 0.8.19 |
| MySQL | 8.0.30 |
| Akun | `accounts[0]` owner, `accounts[1]` buyer, `accounts[2]` operator, `accounts[3]` outsider |
| Amount uji | `1 ether` |
| Hash uji | `web3.utils.keccak256("ORDER-001")` |

---

## 5. Entry & Exit Criteria

### 5.1 Entry criteria

- [ ] Ganache berjalan dan `ganache:ping` sukses.
- [ ] Kontrak compile tanpa error.
- [ ] Migration deploy sukses dan address/ABI diperbarui.
- [ ] Database sudah dimigrasi (`migration_blockchain.sql`, `migration_parallel.sql`).
- [ ] Test data tersedia.
- [ ] Environment variables diisi (tanpa secret di repo).

### 5.2 Exit criteria

- [ ] 21/21 PASS, atau FAIL terdokumentasi dengan bug ID + root cause.
- [ ] Tidak ada bug **Critical** terbuka.
- [ ] Semua bukti tersimpan dan dapat diakses.
- [ ] Regression dijalankan ulang setelah fix.
- [ ] Hasil direview Kenjiro dan Feeandri.

---

## 6. Test Harness

### 6.1 Struktur yang direncanakan

```text
blockchain/
├── test/
│   ├── helpers/
│   │   └── expectRevert.js              # sudah ada
│   ├── 00_baseline_smoke.test.js        # sudah ada (4 PASS, bukan 21 test)
│   ├── 01_sc01_sc06.skeleton.test.js    # sudah ada (6 pending)
│   ├── sc01_init.test.js                # target Minggu 3
│   ├── sc02_create_order.test.js        # target Minggu 3
│   ├── sc03_create_invalid_amount.test.js
│   ├── sc04_duplicate_hash.test.js
│   ├── sc05_get_order.test.js
│   ├── sc06_get_missing.test.js
│   ├── sc07_access_control.test.js      # target Minggu 4
│   ├── sc08_record_payment.test.js
│   ├── sc09_valid_transition.test.js
│   ├── sc10_invalid_transition.test.js
│   ├── sc11_cancel.test.js
│   └── sc12_confirm_release.test.js
├── scripts/
│   ├── checkGanache.js
│   └── logDeployment.js                 # sudah ada
└── package.json                         # sudah ada script "test"
```

### 6.2 Perintah

```powershell
cd blockchain
npm run ganache:ping
npm test
npm test -- --grep "SMOKE-04"
```

> Gunakan `npm test` (bukan `npx truffle test`) karena script ini men-set `HSPORT_TEST=1` sehingga migration tidak menimpa `config/contract_address.txt` dengan alamat instance test.

### 6.3 Helper `expectRevert`

```javascript
async function expectRevert(promise, reason) {
    try {
        await promise;
        throw new Error("Expected revert but transaction succeeded");
    } catch (err) {
        if (!err.message.includes(reason)) {
            throw new Error(`Expected revert "${reason}", got: ${err.message}`);
        }
    }
}
```

### 6.4 Fixture dasar

```javascript
const HSportStore = artifacts.require("HSportStore");

contract("HSportStore", (accounts) => {
    const [owner, buyer, operator, outsider] = accounts;

    beforeEach(async () => {
        this.contract = await HSportStore.new({ from: owner });
        await this.contract.addOperator(operator, { from: owner });
        this.hash = web3.utils.keccak256("ORDER-001");
        this.amount = web3.utils.toWei("1", "ether");
    });
});
```

> Fixture ini mengasumsikan kontrak target Minggu 3–4. Untuk kontrak existing 4 state, test yang belum relevan ditandai `pending` sampai implementasi selesai.

### 6.5 Test integrasi & paralel

| Level | Lokasi | Cara jalan | Output |
|---|---|---|---|
| INT | `tests/integration/` | `php tests/integration/run_all.php` | JSON + log |
| PAR | `benchmark/` | `php benchmark/run.php --dataset=1000 --workers=4` | CSV/JSON + ringkasan |

---

## 7. 21 Test Case

### 7.1 Smart Contract (SC-01 s.d. SC-12)

#### SC-01 — Inisialisasi contract, owner, akses owner

| Field | Isi |
|---|---|
| Requirement | REQ-02, NFR-02 |
| Precondition | Kontrak dideploy oleh `owner` |
| Steps | 1) Deploy. 2) Baca `owner()`. 3) `addOperator` dari non-owner. 4) `addOperator` dari owner. |
| Expected | `owner == deployer`; non-owner revert `Not owner`; owner sukses dan `isOperator == true` |
| Evidence | Output test + address deploy |
| Owner / Validator | Arby + Farrel / Kenjiro |
| Status | Not Started |

#### SC-02 — `createOrder` valid

| Field | Isi |
|---|---|
| Requirement | REQ-01, REQ-02, REQ-08 |
| Precondition | Buyer punya ETH; hash belum dipakai |
| Steps | 1) `createOrder(hash, "ORDER-001", 1 ether)` dengan `value = 1 ether`. 2) Baca `getOrder(1)`. 3) Cek event. |
| Expected | `orderCount == 1`; `buyer`, `totalAmount`, `externalOrderHash` benar; status `Created`; escrow `amount == 1 ether`, `released == false`; `orderIdByHash[hash] == 1`; event `OrderCreated` |
| Evidence | Tx hash + decoded event + assert output |
| Owner / Validator | Arby / Kenjiro |
| Status | Not Started |

#### SC-03 — `createOrder` amount tidak valid

| Field | Isi |
|---|---|
| Requirement | REQ-02 |
| Precondition | Kontrak aktif |
| Steps | 1) `createOrder(hash, "ORDER-002", 0)` dengan `value = 0`. 2) `createOrder(hash2, "ORDER-003", 1 ether)` dengan `value = 0.5 ether`. |
| Expected | Revert `Amount must be > 0`; revert `Incorrect amount`; `orderCount` tidak bertambah |
| Evidence | Output revert |
| Owner / Validator | Arby / Kenjiro |
| Status | Not Started |

#### SC-04 — Duplicate `externalOrderHash`

| Field | Isi |
|---|---|
| Requirement | REQ-03 |
| Precondition | Order dengan hash H sudah ada |
| Steps | 1) Buat order hash H. 2) Buat order baru dengan hash H. |
| Expected | Revert `Duplicate order hash`; `orderCount` tetap 1 |
| Evidence | Output revert + `orderIdByHash[H]` tetap 1 |
| Owner / Validator | Arby / Kenjiro |
| Status | Not Started |

#### SC-05 — `getOrder` ID valid

| Field | Isi |
|---|---|
| Requirement | REQ-01, REQ-10 |
| Precondition | Order ID 1 sudah dibuat |
| Steps | Panggil `getOrder(1)` |
| Expected | Semua field sesuai input; status sesuai state saat ini |
| Evidence | Output assert |
| Owner / Validator | Arby / Kenjiro |
| Status | Not Started |

#### SC-06 — `getOrder` ID tidak ada

| Field | Isi |
|---|---|
| Requirement | REQ-10 |
| Precondition | Tidak ada order ID 999 |
| Steps | Panggil `getOrder(999)` |
| Expected | Revert `Order not found` |
| Evidence | Output revert |
| Owner / Validator | Arby / Kenjiro |
| Status | Not Started |

#### SC-07 — Access control

| Field | Isi |
|---|---|
| Requirement | REQ-04, REQ-06, NFR-02 |
| Precondition | Order ID 1 ada; operator terdaftar |
| Steps | 1) `recordPayment(1)` dari `outsider`. 2) `confirmReceived(1)` dari `outsider`. 3) `addOperator(outsider)` dari `outsider`. 4) `removeOperator(owner)` dari owner. |
| Expected | Revert `Not operator`; revert `Not buyer`; revert `Not owner`; revert `Cannot remove owner` |
| Evidence | Output revert |
| Owner / Validator | Arby + Agustina / Kenjiro |
| Status | Not Started |

#### SC-08 — `recordPayment` valid + double payment

| Field | Isi |
|---|---|
| Requirement | REQ-04, REQ-08 |
| Precondition | Order ID 1 state `Created`; escrow terisi; operator terdaftar |
| Steps | 1) `recordPayment(1)` dari operator. 2) Baca order. 3) `recordPayment(1)` lagi. |
| Expected | Status `Paid`; `paymentRecorded == true`; event `PaymentRecorded`; panggilan kedua revert `Payment already recorded` (atau `Invalid state transition`) |
| Evidence | Tx hash + event + output revert |
| Owner / Validator | Agustina / Kenjiro |
| Status | Not Started |

#### SC-09 — Transisi valid & `shipOrder`

| Field | Isi |
|---|---|
| Requirement | REQ-05, REQ-08 |
| Precondition | Order ID 1 state `Paid` |
| Steps | 1) `updateOrderStatus(1, InProduction)`. 2) `updateOrderStatus(1, ReadyToShip)`. 3) `shipOrder(1)`. |
| Expected | Status berurutan `InProduction → ReadyToShip → Shipped`; `updatedAt` berubah; event `OrderStatusUpdated` (dengan old/new) dan `OrderShipped` |
| Evidence | Tx hash tiap langkah + event |
| Owner / Validator | Agustina / Kenjiro |
| Status | Not Started |

#### SC-10 — Transisi tidak sah

| Field | Isi |
|---|---|
| Requirement | REQ-05 |
| Precondition | Order ID 1 `Created`; order ID 2 `Completed` |
| Steps | 1) `shipOrder(1)`. 2) `updateOrderStatus(2, InProduction)`. 3) `updateOrderStatus(1, Completed)`. |
| Expected | Semua revert `Invalid state transition` / `Not ready to ship`; state tidak berubah |
| Evidence | Output revert |
| Owner / Validator | Agustina / Kenjiro |
| Status | Not Started |

#### SC-11 — `cancelOrder` valid, unauthorized, terminal

| Field | Isi |
|---|---|
| Requirement | REQ-06, REQ-08 |
| Precondition | Order ID 1 `Created`; order ID 2 `Paid`; order ID 3 `Shipped` |
| Steps | 1) Buyer cancel order 1. 2) Buyer cancel order 2. 3) Operator cancel order 2. 4) Operator cancel order 3. |
| Expected | Order 1 `Cancelled` + refund; buyer cancel order 2 revert `Not authorized to cancel`; operator cancel order 2 `Cancelled` + refund; order 3 revert `Invalid state transition` / `Order is final` |
| Evidence | Tx hash + event `OrderCancelled`/`EscrowRefunded` + revert |
| Owner / Validator | Agustina / Kenjiro |
| Status | Not Started |

#### SC-12 — `confirmReceived`, release escrow, double release

| Field | Isi |
|---|---|
| Requirement | REQ-07, REQ-08 |
| Precondition | Order ID 1 `Shipped`; saldo seller dicatat |
| Steps | 1) Buyer `confirmReceived(1)`. 2) Cek saldo seller. 3) `confirmReceived(1)` lagi. 4) `confirmReceived` pada order yang belum `Shipped`. |
| Expected | Status `Completed`; `escrow.released == true`; saldo seller naik `amount`; event `OrderCompleted` + `EscrowReleased`; panggilan kedua revert `Already released`/`Order is final`; order belum shipped revert `Not shipped yet` |
| Evidence | Tx hash + saldo before/after + event + revert |
| Owner / Validator | Agustina / Kenjiro |
| Status | Not Started |

### 7.2 Integrasi (INT-01 s.d. INT-05)

#### INT-01 — Koneksi RPC backend

| Field | Isi |
|---|---|
| Requirement | REQ-09 |
| Precondition | Ganache berjalan; service dikonfigurasi |
| Steps | 1) Panggil `health`. 2) Cek `eth_chainId`, `eth_blockNumber`, `eth_getCode(contract)`. |
| Expected | RPC merespons; chain ID sesuai config; contract code tidak kosong |
| Evidence | JSON response + log |
| Owner / Validator | Luis + Farrel / Kenjiro |
| Status | Not Started |

#### INT-02 — Checkout DB + `createOrder` on-chain

| Field | Isi |
|---|---|
| Requirement | REQ-01, REQ-09 |
| Precondition | DB + kontrak aktif; buyer punya ETH |
| Steps | 1) Checkout dari web. 2) Approve `createOrder`. 3) Simpan proof. 4) Baca `getOrder`. |
| Expected | Order DB dan on-chain terhubung; `blockchain_order_id` benar; `external_order_hash` sama |
| Evidence | Order ID DB + tx hash + output `getOrder` |
| Owner / Validator | Luis / Kenjiro |
| Status | Not Started |

#### INT-03 — Ambil tx hash, receipt, block number

| Field | Isi |
|---|---|
| Requirement | REQ-09 |
| Precondition | Ada tx createOrder |
| Steps | 1) Ambil receipt via RPC. 2) Parse status, blockNumber, logs. |
| Expected | `status == 1`; `block_number > 0`; event `OrderCreated` ditemukan |
| Evidence | Receipt JSON + log parser |
| Owner / Validator | Luis / Kenjiro |
| Status | Not Started |

#### INT-04 — Simpan proof ke DB

| Field | Isi |
|---|---|
| Requirement | REQ-09 |
| Precondition | Receipt tersedia |
| Steps | 1) Jalankan `save_crypto_order` lengkap. 2) Query `orders` dan `blockchain_transactions`. |
| Expected | `tx_hash`, `block_number`, `contract_address`, `wallet_address`, `blockchain_order_id`, `external_order_hash` tersimpan; event tersimpan di `blockchain_transactions` |
| Evidence | Query SELECT + screenshot |
| Owner / Validator | Luis / Kenjiro |
| Status | Not Started |

#### INT-05 — Verifikasi DB vs chain

| Field | Isi |
|---|---|
| Requirement | REQ-10, REQ-11 |
| Precondition | Order punya proof lengkap |
| Steps | 1) `verify_order` pada data cocok. 2) Ubah `total_amount` di DB. 3) `verify_order` lagi. |
| Expected | Pertama `VERIFIED`; kedua `MISMATCH` dengan detail field `total_amount`; tidak ada false positive |
| Evidence | JSON response + query DB |
| Owner / Validator | Luis / Kenjiro |
| Status | Not Started |

### 7.3 Parallel & Benchmark (PAR-01 s.d. PAR-04)

#### PAR-01 — Sequential baseline

| Field | Isi |
|---|---|
| Requirement | REQ-13 |
| Precondition | Dataset D1000 tersedia; worker 1 |
| Steps | 1) Buat batch `is_baseline=1`, `worker_count=1`. 2) Jalankan verifikasi. 3) Catat waktu dan agregasi. |
| Expected | Semua order diproses; `total_orders == 1000`; `verified + mismatch + error == 1000`; `duration_ms` tercatat; checksum tersimpan |
| Evidence | Baris `job_batches` + `checkpoints` + log |
| Owner / Validator | Farelly / Kenjiro |
| Status | Not Started |

#### PAR-02 — Parallel multi-worker

| Field | Isi |
|---|---|
| Requirement | REQ-13 |
| Precondition | Baseline PAR-01 selesai |
| Steps | 1) Buat batch D1000 dengan 4 worker. 2) Jalankan 4 worker. 3) Bandingkan hasil. |
| Expected | `checksum` sama dengan baseline; `total_orders` sama; tidak ada partisi diproses dua kali; `duration_ms` tercatat |
| Evidence | 4 worker log + checkpoint + query view |
| Owner / Validator | Farelly / Kenjiro |
| Status | Not Started |

#### PAR-03 — Hitung throughput, speedup, efficiency

| Field | Isi |
|---|---|
| Requirement | REQ-14 |
| Precondition | Baseline + parallel 2/4/8 selesai |
| Steps | 1) Hitung `S = T1/Tp`. 2) Hitung `E = S/p`. 3) Hitung throughput `N/Tp`. 4) Bandingkan dengan rumus manual. |
| Expected | Nilai sesuai rumus; tabel dan grafik dihasilkan; tidak ada pembagian nol |
| Evidence | Tabel/CSV + grafik + perhitungan manual |
| Owner / Validator | Kenjiro + Farelly / Feeandri |
| Status | Not Started |

#### PAR-04 — Ketahanan konkurensi

| Field | Isi |
|---|---|
| Requirement | REQ-14, NFR-01 |
| Precondition | 8 worker; dataset D5000 |
| Steps | 1) Jalankan 8 worker bersamaan. 2) Ulangi 3x. 3) Simulasikan satu worker crash di tengah job. 4) Cek duplikasi dan agregasi. |
| Expected | Tidak ada job diproses dua kali; retry bekerja; agregasi utuh; checksum konsisten; tidak ada race condition |
| Evidence | `worker_logs`, status job, checksum 3 run |
| Owner / Validator | Kenjiro + Farelly / Yasmin |
| Status | Not Started |

---

## 8. Test Data

| Dataset | Jumlah | Komposisi | Lokasi |
|---|---:|---|---|
| D100 | 100 | 70 verified, 20 mismatch, 10 error | `benchmark/datasets/d100.sql` |
| D500 | 500 | idem | `benchmark/datasets/d500.sql` |
| D1000 | 1.000 | idem | `benchmark/datasets/d1000.sql` |
| D5000 | 5.000 | idem | `benchmark/datasets/d5000.sql` |

Aturan: seed `HSPORT-2026`; hash dataset dicatat; tidak ada data personal nyata.

---

## 9. Defect Management

### 9.1 Severity

| Severity | Definisi | Contoh | SLA |
|---|---|---|---|
| **S1 Critical** | Kehilangan dana, bypass akses, data loss | Escrow release ganda; non-owner tambah operator | Blokir milestone; fix < 24 jam |
| **S2 High** | Alur utama gagal | createOrder gagal; proof tidak tersimpan | Fix sebelum milestone |
| **S3 Medium** | Sebagian fitur/UI salah | Status badge salah; retry tidak muncul | Fix minggu yang sama |
| **S4 Low** | Kosmetik/typo | Label, alignment | Backlog |

### 9.2 Bug report minimum

| Field | Isi |
|---|---|
| Bug ID | `BUG-<minggu>-<nomor>` |
| Ditemukan oleh | Nama |
| Test case | SC/INT/PAR |
| Precondition | — |
| Langkah reproduksi | 1, 2, 3 |
| Expected | — |
| Actual | — |
| Severity | S1–S4 |
| Owner fix | — |
| Root cause | — |
| Fix/commit | — |
| Retest | PASS/FAIL |

---

## 10. Jadwal Eksekusi

| Minggu | Test yang dijalankan | Target |
|---|---|---|
| M2 | Harness + skeleton SC-01 s.d. SC-06 | Harness berjalan |
| M3 | SC-01 s.d. SC-06 | 6/6 PASS |
| M4 | SC-07 s.d. SC-12 + regresi SC-01–06 | 12/12 PASS |
| M5 | INT-01 s.d. INT-05 | 5/5 PASS |
| M6 | PAR-01, PAR-02 | 2/2 PASS |
| M7 | PAR-03, PAR-04 + full regression 21 | 21/21 PASS atau FAIL terdokumentasi |
| M8 | Regression final untuk demo | 21/21 PASS |

---

## 11. Ringkasan Status

| Kelompok | Total | PASS | FAIL | Not Started |
|---|---:|---:|---:|---:|
| Smart Contract | 12 | 0 | 0 | 12 |
| Integrasi | 5 | 0 | 0 | 5 |
| Parallel | 4 | 0 | 0 | 4 |
| **Total** | **21** | **0** | **0** | **21** |

> Status per 9 November 2026: `blockchain/test/` sudah dibuat. Baseline smoke **4 PASS** (bukan bagian 21 test), skeleton SC-01 s.d. SC-06 berstatus **6 pending**. Belum ada test SC yang diaktifkan; 0/21 test wajib PASS. Test SC sebenarnya menunggu kontrak target (Minggu 3–4).

---

## 12. Tautan

- Requirement: [`00_DECISIONS_AND_SCOPE.md`](00_DECISIONS_AND_SCOPE.md)
- State machine: [`01_ON_OFF_CHAIN_AND_STATE.md`](01_ON_OFF_CHAIN_AND_STATE.md)
- UAT: [`02_BUSINESS_PROCESS_AND_UAT.md`](02_BUSINESS_PROCESS_AND_UAT.md)
- Traceability: [`03_TRACEABILITY_MATRIX.md`](03_TRACEABILITY_MATRIX.md)
- Desain kontrak: [`04_CONTRACT_AND_PARALLEL_DESIGN.md`](04_CONTRACT_AND_PARALLEL_DESIGN.md)
