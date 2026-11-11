# 03 — TRACEABILITY MATRIX v0.1

**Milestone:** Minggu 1 — Analisis & Scope Freeze
**Issue terkait:** `W1-FEE-04`
**Pemilik:** Muhammad Feeandri Syahbani
**Reviewer:** Yasmin Akmaliyah, Kenjiro B.H.G.
**Status:** Diperbarui M4 — 12/21 test PASS, kontrak V2 terdeploy
**Tanggal:** 9 November 2026 (revisi 11 November 2026)

---

## 1. Cara Membaca Matriks

- **REQ** = requirement di [`00_DECISIONS_AND_SCOPE.md`](00_DECISIONS_AND_SCOPE.md).
- **Existing** = artefak yang benar-benar ada di repo saat audit.
- **Target** = artefak yang harus dibuat/diubah.
- **Test** = test otomatis di [`07_TEST_PLAN.md`](07_TEST_PLAN.md).
- **UAT** = skenario di [`02_BUSINESS_PROCESS_AND_UAT.md`](02_BUSINESS_PROCESS_AND_UAT.md).
- **Status implementasi:** `Selesai` (memenuhi requirement), `Sebagian` (ada pondasi), `Belum`.
- **Status test:** `PASS`, `FAIL`, `Not Started`.

**Aturan:** requirement dengan prioritas **Must** tidak boleh berstatus `Selesai` jika belum punya implementasi dan test evidence.

---

## 2. Requirement → Implementasi → Test → Owner

| REQ | Requirement (ringkas) | Existing implementation | Target implementation | Test | UAT | Owner | Milestone | Status impl. |
|---|---|---|---|---|---|---|---|---|
| **REQ-01** | Checkout membuat order off-chain + on-chain yang terhubung | `cart.php`, `checkout.php`, `crypto-payment.php` (client-side) | Service create order + simpan `blockchain_order_id` | INT-02 | UAT-01 | Luis, Arby | M3, M5 | Sebagian |
| **REQ-02** | `createOrder` validasi amount > 0 dan lock escrow | `HSportStore.createOrder` V1: `amount > 0`, `msg.value == amount`, escrow terkunci | Tambah `externalOrderHash`, state `Created`, escrow konsisten | SC-02, SC-03 | UAT-01, UAT-02 | Arby | M3 | Selesai |
| **REQ-03** | Tolak `externalOrderHash` duplikat | `orderIdByHash` + guard duplikat | `orderIdByHash`, guard duplikat | SC-04 | UAT-03 | Arby | M3 | Selesai |
| **REQ-04** | Hanya operator yang boleh catat pembayaran; double payment ditolak | `recordPayment`, `operators`, `paymentRecorded` | `recordPayment`, `operators`, `paymentRecorded` | SC-07, SC-08 | UAT-04, UAT-05, UAT-06 | Agustina | M4 | Selesai |
| **REQ-05** | 8 state + transisi tidak sah ditolak | Enum 8 state + `updateOrderStatus` + validasi transisi | Enum 8 state + `updateOrderStatus` + validasi transisi | SC-09, SC-10 | UAT-07, UAT-08 | Agustina | M4 | Selesai |
| **REQ-06** | Cancel oleh aktor berwenang; aktor lain ditolak | `cancelOrder` V2: buyer untuk `Created`/`AwaitingPayment`, operator/owner untuk `Paid`–`ReadyToShip`, `Shipped` ditolak | `cancelOrder` + aturan per state | SC-11 | UAT-09, UAT-10 | Agustina | M4 | Selesai |
| **REQ-07** | Konfirmasi terima merilis escrow tepat sekali | `confirmReceived` V1: guard state Shipped + `released` + transfer ke owner | Pertahankan + proteksi double release + event | SC-12 | UAT-11, UAT-12 | Agustina | M4 | Selesai |
| **REQ-08** | Event lengkap setiap perubahan state | 9 event dideklarasikan; seluruhnya dipancarkan di V2 | Tambah `PaymentRecorded`, `OrderStatusUpdated`, `OrderCancelled`, `OperatorUpdated`, `EscrowRefunded` | SC-02, SC-08, SC-09, SC-11, SC-12 | UAT-04, UAT-07, UAT-09, UAT-11 | Agustina | M3–M4 | Selesai |
| **REQ-09** | Simpan proof transaksi lengkap | `tx_hash`, `wallet_address` di DB/client; `migration_blockchain.sql` | Service simpan `block_number`, `contract_address`, `blockchain_order_id`, `external_order_hash` | INT-01, INT-03, INT-04 | UAT-01, UAT-04 | Luis | M5 | Sebagian |
| **REQ-10** | Verifikasi DB vs chain `VERIFIED`/`MISMATCH` | — | Verification service + tabel `blockchain_transactions` | INT-05 | UAT-13 | Luis | M5 | Belum |
| **REQ-11** | Admin lihat status verifikasi + retry | `admin/orders.php`, `admin/payments.php` (belum ada verifikasi) | Kolom verifikasi + aksi retry | INT-05 | UAT-13 | Ihsanul, Luis | M5 | Sebagian |
| **REQ-12** | Halaman proof blockchain pembeli | — | `order-proof.php` + komponen tx status | — (manual) | UAT-14 | Ihsanul | M5 | Belum |
| **REQ-13** | Sequential & parallel menghasilkan hasil identik | — | Worker, queue, partition/reduce | PAR-01, PAR-02 | UAT-15 | Farelly | M6 | Belum |
| **REQ-14** | Ukur execution time, speedup, efficiency, throughput | — | Benchmark script + grafik + dashboard | PAR-03, PAR-04 | UAT-16 | Kenjiro, Farelly | M7 | Belum |
| **REQ-15** | Reproducible + kontribusi tertelusur | `README.md`, panduan setup, Git repo | Deploy log, `.env.example`, weekly log, tag rilis | — (manual) | UAT-17 | Feeandri, semua | M2, M8 | Sebagian |

### 2.1 Ringkasan status implementasi requirement

| Status | Jumlah | REQ |
|---|---:|---|
| Selesai | 7 | REQ-02, REQ-03, REQ-04, REQ-05, REQ-06, REQ-07, REQ-08 |
| Sebagian | 4 | REQ-01, REQ-09, REQ-11, REQ-15 |
| Belum | 4 | REQ-10, REQ-12, REQ-13, REQ-14 |
| **Total** | **15** | |

---

## 3. Test → Requirement → Owner

### 3.1 Smart Contract (SC-01 s.d. SC-12)

| Test | Nama | Requirement | Dev owner | Validator | Milestone | Status |
|---|---|---|---|---|---|---|
| **SC-01** | Inisialisasi contract, owner, akses owner | REQ-02, NFR-02 | Arby + Farrel | Kenjiro | M3 | **PASS** |
| **SC-02** | `createOrder` valid: escrow terkunci, state `Created`, event | REQ-01, REQ-02, REQ-08 | Arby | Kenjiro | M3 | **PASS** |
| **SC-03** | `createOrder` amount ≤ 0 / mismatch revert | REQ-02 | Arby | Kenjiro | M3 | **PASS** |
| **SC-04** | Duplicate `externalOrderHash` revert | REQ-03 | Arby | Kenjiro | M3 | **PASS** |
| **SC-05** | `getOrder` ID valid mengembalikan data benar | REQ-01, REQ-10 | Arby | Kenjiro | M3 | **PASS** |
| **SC-06** | `getOrder` ID tidak ada revert | REQ-10 | Arby | Kenjiro | M3 | **PASS** |
| **SC-07** | Access control: non-operator/non-buyer/non-owner revert | REQ-04, REQ-06, NFR-02 | Arby + Agustina | Kenjiro | M3–M4 | **PASS** |
| **SC-08** | `recordPayment` valid + double payment revert + event | REQ-04, REQ-08 | Agustina | Kenjiro | M4 | **PASS** |
| **SC-09** | Transisi valid `Paid → InProduction → ReadyToShip → Shipped` + event | REQ-05, REQ-08 | Agustina | Kenjiro | M4 | **PASS** |
| **SC-10** | Transisi tidak sah revert | REQ-05 | Agustina | Kenjiro | M4 | **PASS** |
| **SC-11** | Cancel valid, unauthorized, dan terminal state | REQ-06, REQ-08 | Agustina | Kenjiro | M4 | **PASS** |
| **SC-12** | `confirmReceived`, release escrow, double release revert, event | REQ-07, REQ-08 | Agustina | Kenjiro | M4 | **PASS** |

### 3.2 Integrasi (INT-01 s.d. INT-05)

| Test | Nama | Requirement | Dev owner | Validator | Milestone | Status |
|---|---|---|---|---|---|---|
| **INT-01** | Koneksi RPC backend ke node | REQ-09 | Luis + Farrel | Kenjiro | M5 | Not Started |
| **INT-02** | Checkout DB + `createOrder` on-chain terhubung | REQ-01, REQ-09 | Luis | Kenjiro | M5 | Not Started |
| **INT-03** | Ambil tx hash, receipt, block number | REQ-09 | Luis | Kenjiro | M5 | Not Started |
| **INT-04** | Simpan proof lengkap ke DB | REQ-09 | Luis | Kenjiro | M5 | Not Started |
| **INT-05** | Verifikasi DB vs chain `VERIFIED`/`MISMATCH` | REQ-10, REQ-11 | Luis | Kenjiro | M5 | Not Started |

### 3.3 Parallel & Benchmark (PAR-01 s.d. PAR-04)

| Test | Nama | Requirement | Dev owner | Validator | Milestone | Status |
|---|---|---|---|---|---|---|
| **PAR-01** | Sequential baseline: waktu & agregasi | REQ-13 | Farelly | Kenjiro | M6 | Not Started |
| **PAR-02** | Parallel multi-worker partition/reduce = sequential | REQ-13 | Farelly | Kenjiro | M6 | Not Started |
| **PAR-03** | Hitung throughput, speedup, efficiency | REQ-14 | Kenjiro + Farelly | Feeandri | M7 | Not Started |
| **PAR-04** | Ketahanan konkurensi & integritas agregasi | REQ-14, NFR-01 | Kenjiro + Farelly | Yasmin | M7 | Not Started |

---

## 4. Ringkasan Kesehatan Traceability

| Metrik | Nilai | Target |
|---|---:|---:|
| Requirement total | 15 | — |
| Requirement Must | 14 | — |
| Requirement dengan minimal 1 test otomatis | 9 | 14 |
| Requirement dengan test otomatis **atau** UAT | 15 | 15 |
| Requirement tanpa verifikasi apa pun | 0 | 0 |
| Test total | 21 | 21 |
| Test dengan requirement | 21 | 21 |
| Test PASS | 12 | 21 |
| Orphan test (tanpa REQ) | 0 | 0 |
| Orphan requirement Must (tanpa test) | 0 (REQ-12/15 punya UAT manual) | 0 |

> **Catatan M4 (11 November 2026):** SC-01 s.d. SC-12 PASS (12/12) terhadap kontrak V2. `cancelOrder` + refund terbukti lewat SC-11; regresi V1 bersih. Requirement yang sudah punya test otomatis PASS: REQ-01, REQ-02, REQ-03, REQ-04, REQ-05, REQ-06, REQ-07, REQ-08, REQ-10. Sisa 9 test (INT-01..05, PAR-01..04) menunggu M5–M7.
>
> **Catatan:** REQ-12 dan REQ-15 diverifikasi lewat UAT manual, bukan test otomatis. Ini disengaja karena keduanya bersifat UI/reproducibility, bukan logika kontrak.

---

## 5. Aturan Pemeliharaan

1. Setiap PR yang menambah/mengubah requirement **wajib** memperbarui tabel Bagian 2.
2. Setiap test baru wajib punya baris di Bagian 3 dan link bukti.
3. Status `Selesai` hanya boleh diisi jika:
   - implementasi ada di repo,
   - test/UAT PASS,
   - bukti (log/screenshot/tx hash) dapat diakses.
4. Perubahan definisi test harus disetujui Kenjiro dan dicatat di `07_TEST_PLAN.md`.
5. Traceability diperbarui minimal setiap Jumat bersamaan dengan weekly log.
6. Jika requirement dihapus, baris tidak dihapus; status diubah menjadi `Superseded` dengan alasan.

---

## 6. Tautan Bukti (diisi saat eksekusi)

| Test | Bukti | Link/Path | Tanggal |
|---|---|---|---|
| SC-01 | Log test | [`../week3/evidence/sc01_sc06_test.log`](../week3/evidence/sc01_sc06_test.log) | 11 Nov 2026 |
| SC-02 | Log test | [`../week3/evidence/sc01_sc06_test.log`](../week3/evidence/sc01_sc06_test.log) | 11 Nov 2026 |
| SC-03 | Log test | [`../week3/evidence/sc01_sc06_test.log`](../week3/evidence/sc01_sc06_test.log) | 11 Nov 2026 |
| SC-04 | Log test | [`../week3/evidence/sc01_sc06_test.log`](../week3/evidence/sc01_sc06_test.log) | 11 Nov 2026 |
| SC-05 | Log test | [`../week3/evidence/sc01_sc06_test.log`](../week3/evidence/sc01_sc06_test.log) | 11 Nov 2026 |
| SC-06 | Log test | [`../week3/evidence/sc01_sc06_test.log`](../week3/evidence/sc01_sc06_test.log) | 11 Nov 2026 |
| SC-07 | Log test V2 | [`../week4/evidence/sc07_sc12_test.log`](../week4/evidence/sc07_sc12_test.log) | 11 Nov 2026 |
| SC-08 | Log test V2 | [`../week4/evidence/sc07_sc12_test.log`](../week4/evidence/sc07_sc12_test.log) | 11 Nov 2026 |
| SC-09 | Log test V2 | [`../week4/evidence/sc07_sc12_test.log`](../week4/evidence/sc07_sc12_test.log) | 11 Nov 2026 |
| SC-10 | Log test V2 | [`../week4/evidence/sc07_sc12_test.log`](../week4/evidence/sc07_sc12_test.log) | 11 Nov 2026 |
| SC-11 | Log test V2 | [`../week4/evidence/sc07_sc12_test.log`](../week4/evidence/sc07_sc12_test.log) | 11 Nov 2026 |
| SC-12 | Log test V2 | [`../week4/evidence/sc07_sc12_test.log`](../week4/evidence/sc07_sc12_test.log) | 11 Nov 2026 |
| INT-01 | — | — | — |
| INT-02 | — | — | — |
| INT-03 | — | — | — |
| INT-04 | — | — | — |
| INT-05 | — | — | — |
| PAR-01 | — | — | — |
| PAR-02 | — | — | — |
| PAR-03 | — | — | — |
| PAR-04 | — | — | — |

---

## 7. Tautan

- Requirement: [`00_DECISIONS_AND_SCOPE.md`](00_DECISIONS_AND_SCOPE.md)
- State machine: [`01_ON_OFF_CHAIN_AND_STATE.md`](01_ON_OFF_CHAIN_AND_STATE.md)
- UAT: [`02_BUSINESS_PROCESS_AND_UAT.md`](02_BUSINESS_PROCESS_AND_UAT.md)
- Test plan: [`07_TEST_PLAN.md`](07_TEST_PLAN.md)
