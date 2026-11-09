# LAMPIRAN PROGRESS TRACKER — BAGIAN 4 & 5

Dokumen ini adalah draft isi untuk melengkapi `docs/Progress Tracker.docx` yang saat ini melompat dari Bagian 3 ke Bagian 6.

- **Bagian 4** menggantikan/menambahkan bagian Test Case Tracker yang hilang.
- **Bagian 5** menggantikan/menambahkan bagian Weekly Progress Log yang hilang.
- Isi ini sudah disisipkan ke `Progress Tracker.docx` sebelum Bagian 6.

---

## 4. Test Case Tracker (21 Wajib)

**Status:** `Not Started` / `In Progress` / `PASS` / `FAIL`
**Warna status:** abu-abu = Not Started, kuning = In Progress, hijau = PASS, merah = FAIL.

### 4.1 Kelompok A — Smart Contract (SC-01 s.d. SC-12)

| ID | Test Case | Expected Result | Dev Owner | Validator | Status | Tanggal | Bukti / Catatan |
|---|---|---|---|---|---|---|---|
| SC-01 | Inisialisasi contract, owner, akses owner | `owner` = deployer; non-owner `addOperator` REVERT `Not owner` | Arby + Farrel | Kenjiro | Not Started | | Lihat `docs/week1/07_TEST_PLAN.md` |
| SC-02 | `createOrder` valid | Escrow terkunci, state `Created`, event `OrderCreated` | Arby | Kenjiro | Not Started | | Lihat `docs/week1/07_TEST_PLAN.md` |
| SC-03 | `createOrder` amount tidak valid | REVERT: `Amount must be > 0` / `Incorrect amount` | Arby | Kenjiro | Not Started | | Lihat `docs/week1/07_TEST_PLAN.md` |
| SC-04 | Duplicate `externalOrderHash` | REVERT: `Duplicate order hash` | Arby | Kenjiro | Not Started | | Lihat `docs/week1/07_TEST_PLAN.md` |
| SC-05 | `getOrder` ID valid | Data order sesuai input | Arby | Kenjiro | Not Started | | Lihat `docs/week1/07_TEST_PLAN.md` |
| SC-06 | `getOrder` ID tidak ada | REVERT: `Order not found` | Arby | Kenjiro | Not Started | | Lihat `docs/week1/07_TEST_PLAN.md` |
| SC-07 | Access control | Non-operator/non-buyer/non-owner REVERT sesuai modifier | Arby + Agustina | Kenjiro | Not Started | | Lihat `docs/week1/07_TEST_PLAN.md` |
| SC-08 | `recordPayment` valid + double payment | `Paid` + event `PaymentRecorded`; double REVERT `Payment already recorded` | Agustina | Kenjiro | Not Started | | Lihat `docs/week1/07_TEST_PLAN.md` |
| SC-09 | Transisi valid + `shipOrder` | `InProduction → ReadyToShip → Shipped` + event | Agustina | Kenjiro | Not Started | | Lihat `docs/week1/07_TEST_PLAN.md` |
| SC-10 | Transisi tidak sah | REVERT: `Invalid state transition` | Agustina | Kenjiro | Not Started | | Lihat `docs/week1/07_TEST_PLAN.md` |
| SC-11 | Cancel valid/unauthorized/terminal | `Cancelled` + refund; unauthorized/terminal REVERT | Agustina | Kenjiro | Not Started | | Lihat `docs/week1/07_TEST_PLAN.md` |
| SC-12 | `confirmReceived` + release escrow | `Completed`, escrow released, saldo seller naik; double release REVERT | Agustina | Kenjiro | Not Started | | Lihat `docs/week1/07_TEST_PLAN.md` |

### 4.2 Kelompok B — Integrasi (INT-01 s.d. INT-05)

| ID | Test Case | Expected Result | Dev Owner | Validator | Status | Tanggal | Bukti / Catatan |
|---|---|---|---|---|---|---|---|
| INT-01 | Koneksi RPC backend ke node | RPC merespons, `eth_chainId` sesuai | Luis + Farrel | Kenjiro | Not Started | | |
| INT-02 | Checkout DB + `createOrder` on-chain | Order DB dan order on-chain terhubung | Luis | Kenjiro | Not Started | | |
| INT-03 | Ambil tx hash & receipt asinkron | `tx_hash`, `block_number`, status receipt terbaca | Luis | Kenjiro | Not Started | | |
| INT-04 | Simpan proof ke DB | Tx hash, block number, wallet, contract address persisten | Luis | Kenjiro | Not Started | | |
| INT-05 | Verifikasi DB vs chain | Hasil `VERIFIED` / `MISMATCH` benar | Luis | Kenjiro | Not Started | | |

### 4.3 Kelompok C — Parallel & Benchmark (PAR-01 s.d. PAR-04)

| ID | Test Case | Expected Result | Dev Owner | Validator | Status | Tanggal | Bukti / Catatan |
|---|---|---|---|---|---|---|---|
| PAR-01 | Sequential baseline | Waktu & hasil agregasi tercatat | Farelly | Kenjiro | Not Started | | |
| PAR-02 | Parallel multi-worker partition/reduce | Hasil sama dengan sequential | Farelly | Kenjiro | Not Started | | |
| PAR-03 | Hitung throughput & speedup | `speedup = T_seq / T_par`, throughput valid | Kenjiro + Farelly | Feeandri | Not Started | | |
| PAR-04 | Ketahanan konkurensi | Tidak ada race condition/duplikasi; agregasi utuh | Kenjiro + Farelly | Yasmin | Not Started | | |

### 4.4 Ringkasan Test Case

| Status | Jumlah | % dari 21 |
|---|---|---|
| Not Started | 21 | 100% |
| In Progress | 0 | 0% |
| PASS | 0 | 0% |
| FAIL | 0 | 0% |

> **Catatan rekonsiliasi:** 21 test mengikuti basis escrow `DOKUMENTASI_PROJECT_DAN_PERAN.md`, lalu fitur duplicate hash, operator, dan cancel digabungkan ke 12 slot SC. Kontrak tetap `HSportStore.sol` (extend), bukan rewrite ke `HSportOrder`. Definisi final ada di `docs/week1/07_TEST_PLAN.md`.

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
| Muhammad Feeandri Syahbani | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | 0/8 |
| Yasmin Akmaliyah | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | 0/8 |
| Arby Putra | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | 0/8 |
| Agustina Elsi Bunga | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | 0/8 |
| Muhammad Farrel | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | 0/8 |
| Luis | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | 0/8 |
| Ihsanul Farrel | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | 0/8 |
| Farelly De Rossi | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | 0/8 |
| Kenjiro B.H.G | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | 0/8 |

### 5.3 Contoh pengisian

| Field | Isian |
|---|---|
| Minggu | 1 |
| Nama mahasiswa | Muhammad Feeandri Syahbani |
| Role utama | Requirement, Scope & Documentation Lead |
| Target minggu ini | Re-baseline tracker, finalkan scope, traceability skeleton |
| Pekerjaan selesai | Requirement addendum v1.1, on/off-chain matrix, state diagram 8 state, traceability v0.1 |
| Commit / PR / Issue | Artefak `docs/week1/` (commit menyusul), issue `W1-FEE-01` s.d. `W1-FEE-05` |
| Test / hasil | Baseline smoke Truffle 4 PASS; skeleton SC-01..SC-06 6 pending; 0/21 test wajib |
| Kendala | Test SC sebenarnya menunggu kontrak target; uji reproduksi anggota lain belum |
| Keputusan teknis | Tetap PHP native; extend `HSportStore`; Truffle dipertahankan |
| Cross-role contribution | Review desain kontrak bersama Arby |
| Rencana minggu berikut | Implementasi kontrak target, aktifkan SC-01 s.d. SC-06, uji reproduksi |
| Status log | Final |

### 5.4 Aturan pengisian

1. Diisi setiap **Jumat** sebelum update dashboard tracker.
2. Satu baris per mahasiswa per minggu; tidak boleh kosong dua minggu berturut-turut.
3. Wajib memuat minimal satu link **commit/PR/issue** dan satu **bukti test**.
4. Status log hanya boleh `Final` jika sudah direview minimal satu anggota lain.
5. Log kosong tidak dihitung dalam komponen penilaian individu (weekly log 3 poin).
6. Rekap 5.2 diperbarui bersamaan dengan pengisian log.
