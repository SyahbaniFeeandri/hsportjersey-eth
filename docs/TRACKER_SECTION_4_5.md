# LAMPIRAN PROGRESS TRACKER — BAGIAN 4 & 5

Dokumen ini adalah draft isi untuk melengkapi `docs/Progress Tracker.docx` yang saat ini melompat dari Bagian 3 ke Bagian 6.

- **Bagian 4** menggantikan/menambahkan bagian Test Case Tracker yang hilang.
- **Bagian 5** menggantikan/menambahkan bagian Weekly Progress Log yang hilang.
- Isi ini sudah disisipkan ke `Progress Tracker.docx` sebelum Bagian 6.
- **Status akhir (16 November 2026):** 21/21 test PASS, 72/72 entri log Final, rilis `v1.0.0`.

---

## 4. Test Case Tracker (21 Wajib)

**Status:** `Not Started` / `In Progress` / `PASS` / `FAIL`
**Warna status:** abu-abu = Not Started, kuning = In Progress, hijau = PASS, merah = FAIL.

### 4.1 Kelompok A — Smart Contract (SC-01 s.d. SC-12)

| ID | Test Case | Expected Result | Dev Owner | Validator | Status | Tanggal | Bukti / Catatan |
|---|---|---|---|---|---|---|---|
| SC-01 | Inisialisasi contract, owner, akses owner | `owner` = deployer; non-owner `addOperator` REVERT `Not owner` | Arby + Farrel | Kenjiro | PASS | 13 Nov 2026 | `docs/week7/evidence/sc_regression.log` |
| SC-02 | `createOrder` valid | Escrow terkunci, state `Created`, event `OrderCreated` | Arby | Kenjiro | PASS | 13 Nov 2026 | `docs/week7/evidence/sc_regression.log` |
| SC-03 | `createOrder` amount tidak valid | REVERT: `Amount must be > 0` / `Incorrect amount` | Arby | Kenjiro | PASS | 13 Nov 2026 | `docs/week7/evidence/sc_regression.log` |
| SC-04 | Duplicate `externalOrderHash` | REVERT: `Duplicate order hash` | Arby | Kenjiro | PASS | 13 Nov 2026 | `docs/week7/evidence/sc_regression.log` |
| SC-05 | `getOrder` ID valid | Data order sesuai input | Arby | Kenjiro | PASS | 13 Nov 2026 | `docs/week7/evidence/sc_regression.log` |
| SC-06 | `getOrder` ID tidak ada | REVERT: `Order not found` | Arby | Kenjiro | PASS | 13 Nov 2026 | `docs/week7/evidence/sc_regression.log` |
| SC-07 | Access control | Non-operator/non-buyer/non-owner REVERT sesuai modifier | Arby + Agustina | Kenjiro | PASS | 13 Nov 2026 | `docs/week7/evidence/sc_regression.log` |
| SC-08 | `recordPayment` valid + double payment | `Paid` + event `PaymentRecorded`; double REVERT `Payment already recorded` | Agustina | Kenjiro | PASS | 13 Nov 2026 | `docs/week7/evidence/sc_regression.log` |
| SC-09 | Transisi valid + `shipOrder` | `InProduction → ReadyToShip → Shipped` + event | Agustina | Kenjiro | PASS | 13 Nov 2026 | `docs/week7/evidence/sc_regression.log` |
| SC-10 | Transisi tidak sah | REVERT: `Invalid state transition` | Agustina | Kenjiro | PASS | 13 Nov 2026 | `docs/week7/evidence/sc_regression.log` |
| SC-11 | Cancel valid/unauthorized/terminal | `Cancelled` + refund; unauthorized/terminal REVERT | Agustina | Kenjiro | PASS | 13 Nov 2026 | `docs/week7/evidence/sc_regression.log` |
| SC-12 | `confirmReceived` + release escrow | `Completed`, escrow released, saldo seller naik; double release REVERT | Agustina | Kenjiro | PASS | 13 Nov 2026 | `docs/week7/evidence/sc_regression.log` |

### 4.2 Kelompok B — Integrasi (INT-01 s.d. INT-05)

| ID | Test Case | Expected Result | Dev Owner | Validator | Status | Tanggal | Bukti / Catatan |
|---|---|---|---|---|---|---|---|
| INT-01 | Koneksi RPC backend ke node | RPC merespons, `eth_chainId` sesuai | Luis + Farrel | Kenjiro | PASS | 16 Nov 2026 | `docs/week8/evidence/final_int_suite.log` |
| INT-02 | Checkout DB + `createOrder` on-chain | Order DB dan order on-chain terhubung | Luis | Kenjiro | PASS | 16 Nov 2026 | `docs/week8/evidence/final_int_suite.log` |
| INT-03 | Ambil tx hash & receipt asinkron | `tx_hash`, `block_number`, status receipt terbaca | Luis | Kenjiro | PASS | 16 Nov 2026 | `docs/week8/evidence/final_int_suite.log` |
| INT-04 | Simpan proof ke DB | Tx hash, block number, wallet, contract address persisten | Luis | Kenjiro | PASS | 16 Nov 2026 | `docs/week8/evidence/final_int_suite.log` |
| INT-05 | Verifikasi DB vs chain | Hasil `VERIFIED` / `MISMATCH` benar | Luis | Kenjiro | PASS | 16 Nov 2026 | `docs/week8/evidence/final_int_suite.log`; `req11_admin_verification.log` |

### 4.3 Kelompok C — Parallel & Benchmark (PAR-01 s.d. PAR-04)

| ID | Test Case | Expected Result | Dev Owner | Validator | Status | Tanggal | Bukti / Catatan |
|---|---|---|---|---|---|---|---|
| PAR-01 | Sequential baseline | Waktu & hasil agregasi tercatat | Farelly | Kenjiro | PASS | 13 Nov 2026 | `docs/week7/evidence/par01_par02_regression.log` |
| PAR-02 | Parallel multi-worker partition/reduce | Hasil sama dengan sequential | Farelly | Kenjiro | PASS | 13 Nov 2026 | `docs/week7/evidence/par01_par02_regression.log` |
| PAR-03 | Hitung throughput & speedup | `speedup = T_seq / T_par`, throughput valid | Kenjiro + Farelly | Feeandri | PASS | 13 Nov 2026 | `docs/week7/evidence/par03_par04_regression.log` |
| PAR-04 | Ketahanan konkurensi | Tidak ada race condition/duplikasi; agregasi utuh | Kenjiro + Farelly | Yasmin | PASS | 13 Nov 2026 | `docs/week7/evidence/par03_par04_regression.log` |

### 4.4 Ringkasan Test Case

| Status | Jumlah | % dari 21 |
|---|---|---|
| Not Started | 0 | 0% |
| In Progress | 0 | 0% |
| PASS | **21** | **100%** |
| FAIL | 0 | 0% |

> **Catatan rekonsiliasi:** 21 test mengikuti basis escrow `DOKUMENTASI_PROJECT_DAN_PERAN.md`, lalu fitur duplicate hash, operator, dan cancel digabungkan ke 12 slot SC. Kontrak tetap `HSportStore.sol` (extend), bukan rewrite ke `HSportOrder`. Definisi final ada di `docs/week1/07_TEST_PLAN.md`. Ringkasan eksekusi: `docs/week8/evidence/final_regression.log` (21/21 PASS, 47,19 detik).

---

## 5. Weekly Progress Log

### 5.1 Template log per mahasiswa

| Field | Isian |
|---|---|
| Minggu | |
| Nama mahasiswa | |
| Role utama | |
| Target minggu ini | |
| Pekerjaan selesai | |
| Commit / PR / Issue | |
| Test / hasil | |
| Kendala | |
| Keputusan teknis | |
| Cross-role contribution | |
| Rencana minggu berikut | |
| Status log | Kosong / Draft / Final |

### 5.2 Rekap kelengkapan log (9 mahasiswa × 8 minggu)

| Mahasiswa | W1 | W2 | W3 | W4 | W5 | W6 | W7 | W8 | Total |
|---|---|---|---|---|---|---|---|---|---|
| Muhammad Feeandri Syahbani | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | **8/8** |
| Yasmin Akmaliyah | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | **8/8** |
| Arby Putra | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | **8/8** |
| Agustina Elsi Bunga | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | **8/8** |
| Muhammad Farrel | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | **8/8** |
| Luis | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | **8/8** |
| Ihsanul Farrel | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | **8/8** |
| Farelly De Rossi | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | **8/8** |
| Kenjiro B.H.G | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | **8/8** |
| **Total** | 9 | 9 | 9 | 9 | 9 | 9 | 9 | 9 | **72/72** |

> **Sumber rekap:** [`docs/week8/08_WEEKLY_LOG.md`](week8/08_WEEKLY_LOG.md) — log konsolidasi per minggu + entri W8 per anggota, disusun dari commit dan artefak `docs/week*/`.

### 5.3 Contoh pengisian (W8 — Feeandri)

| Field | Isian |
|---|---|
| Minggu | 8 |
| Nama mahasiswa | Muhammad Feeandri Syahbani |
| Role utama | Requirement, Scope & Documentation Lead |
| Target minggu ini | Final report, traceability, weekly log, code freeze |
| Pekerjaan selesai | Laporan final, traceability 15/15, weekly log 72/72, tag `v1.0.0`, sinkronisasi GAP/ROADMAP |
| Commit / PR / Issue | Commit M8 + tag `v1.0.0`; `W8-ALL-01`, `W8-ALL-03`, `W8-FEE-13` |
| Test / hasil | 21/21 regresi PASS; clean install 5/5; UAT 16/16 |
| Kendala | Rekonsiliasi angka M7 dengan bukti JSON |
| Keputusan teknis | Setiap klaim dokumen wajib punya file bukti |
| Cross-role contribution | Review bersama Yasmin dan Kenjiro |
| Rencana minggu berikut | — (proyek selesai) |
| Status log | Final |

### 5.4 Aturan pengisian

1. Diisi setiap **Jumat** sebelum update dashboard tracker.
2. Satu baris per mahasiswa per minggu; tidak boleh kosong dua minggu berturut-turut.
3. Wajib memuat minimal satu link **commit/PR/issue** dan satu **bukti test**.
4. Status log hanya boleh `Final` jika sudah direview minimal satu anggota lain.
5. Log kosong tidak dihitung dalam komponen penilaian individu (weekly log 3 poin).
6. Rekap 5.2 diperbarui bersamaan dengan pengisian log.
7. Pada M8, rekap individual dirangkum pada `docs/week8/08_WEEKLY_LOG.md` dan direview pada rehearsal 16 November 2026.
