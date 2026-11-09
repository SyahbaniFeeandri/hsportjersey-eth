# 00 — KEPUTUSAN & SCOPE STATEMENT

**Milestone:** Minggu 1 — Analisis & Scope Freeze
**Issue terkait:** `W1-FEE-01`, `W1-FEE-02`, `W1-YAS-02`
**Pemilik:** Muhammad Feeandri Syahbani (lead), Yasmin Akmaliyah
**Reviewer:** Kenjiro B.H.G., Arby Putra
**Status:** Final v1.0
**Tanggal:** 9 November 2026

---

## 1. Ringkasan Eksekutif

Dokumen ini mengunci lima keputusan teknis yang sebelumnya menggantung, menetapkan batas proyek (in-scope/out-of-scope), dan menjadi **Requirement Addendum v1.1** untuk H-Sport Financial dApp. Semua dokumen Minggu 1 lainnya mengacu pada ID requirement di sini.

Keputusan utama:

1. Aplikasi tetap **PHP native**.
2. Smart contract **extend `HSportStore`**, bukan rewrite ke `HSportOrder`.
3. Definisi 21 test memakai **basis escrow** dari `DOKUMENTASI_PROJECT_DAN_PERAN.md`, lalu **menggabungkan** test cancel, duplicate hash, dan operator ke dalam 12 slot SC.
4. Tooling tetap **Truffle + Ganache**.
5. **Luis** adalah integration engineer, bukan Sahrul.

---

## 2. Keputusan yang Dikunci

| # | Topik | Keputusan | Alasan | Konsekuensi | PIC | Freeze |
|---|---|---|---|---|---|---|
| **D1** | Framework aplikasi | **Tetap PHP native** | Seluruh web app, admin, dan API sudah berjalan di PHP native. Migrasi ke Laravel tidak menambah nilai demo dan berisiko merusak flow checkout. | Semua service integrasi dibuat sebagai kelas/helper PHP native, bukan Controller/Job Laravel. Queue memakai worker CLI PHP + MySQL. | Feeandri | Minggu 1 |
| **D2** | Nama & evolusi kontrak | **Extend `HSportStore.sol`**; tidak membuat `HSportOrder.sol` baru. Interface dibekukan akhir Minggu 3. | Flow escrow `createOrder`/`shipOrder`/`confirmReceived` sudah berjalan dan sudah dideploy. Rewrite total membuang waktu dan memutus integrasi. | File tetap `blockchain/contracts/HSportStore.sol`. Enum/struct/fungsi target ditambahkan bertahap di Minggu 3–4. Nama `HSportOrder` hanya dipakai sebagai istilah desain di dokumen. | Arby, Agustina | Interface: akhir Minggu 3 |
| **D3** | Definisi 21 test | **12 SC berbasis escrow + cancel/duplicate/operator**, 5 INT, 4 PAR. Total tetap 21. | Menghormati definisi escrow yang sudah dipakai dosen, tetapi tetap menguji fitur baru yang diminta panduan. | SC-01 s.d. SC-12 didefinisikan ulang di `07_TEST_PLAN.md`. Beberapa test lama digabung agar jumlah tetap 21. | Kenjiro | Minggu 1 |
| **D4** | Tooling blockchain | **Truffle + Ganache** | Truffle, `truffle-config.js`, migration, ABI, dan address sudah terpasang. Hardhat hanya disebut di dokumen lama. | Semua test memakai `truffle test`; README dan panduan wajib menyebut Truffle, bukan Hardhat. | Farrel | Minggu 1 |
| **D5** | Integration engineer | **Luis** | Tracker dan `DOKUMENTASI_PROJECT_DAN_PERAN.md` memakai Luis. Sahrul hanya muncul di `Minggu 1.docx`. | Semua issue `W5-LUI-*` dan deliverable integrasi menjadi milik Luis. Nama Sahrul tidak dipakai lagi. | Feeandri | Minggu 1 |

### 2.1 Keputusan turunan

| # | Keputusan | Turunan dari |
|---|---|---|
| D6 | Kontrak menyimpan `externalOrderHash` dan menolak hash duplikat | D2, D3 |
| D7 | Operator dikelola owner melalui `addOperator`/`removeOperator` | D2, D3 |
| D8 | Escrow dirilis hanya oleh `confirmReceived` pembeli dan tidak bisa dua kali | D2, D3 |
| D9 | Pembayaran kripto tetap memakai MetaMask di sisi pembeli; backend hanya membaca/memverifikasi, tidak menyimpan private key pembeli | D1, D5 |
| D10 | Worker paralel memakai CLI PHP + tabel antrean MySQL, bukan Laravel Queue | D1 |

---

## 3. Baseline vs Target

| Area | Baseline riil (9 Nov 2026) | Target akhir proyek | Minggu |
|---|---|---|---|
| Web e-commerce | Katalog, cart, checkout, admin, review sudah ada | Tetap berfungsi, tidak ada regresi | 1–8 |
| Smart contract | `HSportStore` 4 state, escrow dasar | 8 state, hash, operator, cancel, payment, event lengkap | 3–4 |
| Environment | Ganache + Truffle + ABI/address ada, belum ada folder test | Reproducible, ada test harness, deployment log, `.env.example` | 2 |
| Integrasi | Client-side MetaMask, hanya simpan `tx_hash` + `wallet_address` | Service backend read/write, receipt/event parser, proof lengkap, verifikasi | 5 |
| Database | Kolom proof belum ada di dump; migration belum dijalankan | Kolom proof + tabel transaksi + tabel antrean | 1–2 |
| Parallel | Belum ada | Queue, worker 1/2/4/8, partition/reduce, baseline sequential | 6 |
| Benchmark | Belum ada | Dataset 100/500/1.000/5.000, metrik speedup/efficiency/throughput | 7 |
| Test | 0/21 PASS | 21/21 PASS atau FAIL terdokumentasi | 3–7 |
| Dokumentasi | Tracker 0%, tanpa traceability | Tracker akurat, traceability, weekly log, final report | 1–8 |

---

## 4. Scope Statement

### 4.1 In-Scope

1. **Web app H-Sport existing** tetap dipakai: katalog, kustomisasi, cart, checkout, pembayaran QRIS, pembayaran kripto, admin order, laporan.
2. **Smart contract escrow** `HSportStore.sol` yang di-extend:
   - 8 state,
   - `externalOrderHash` + proteksi duplikat,
   - operator management,
   - `recordPayment`, `updateOrderStatus`, `cancelOrder`,
   - event lengkap,
   - validasi transisi dan revert.
3. **Integrasi PHP–blockchain**:
   - service read/write,
   - receipt/event reader,
   - penyimpanan proof ke MySQL,
   - verification `VERIFIED`/`MISMATCH`,
   - error handling RPC/network/revert.
4. **Database**:
   - kolom proof pada `orders`,
   - tabel `blockchain_transactions`,
   - tabel antrean `verification_jobs`, `job_batches`, `checkpoints`, `worker_logs`.
5. **Parallel processing**:
   - sequential baseline,
   - Job/Queue,
   - multi-worker 1/2/4/8,
   - partition/reduce,
   - agregasi finansial.
6. **Benchmark & QA**:
   - dataset 100/500/1.000/5.000,
   - 21 test case,
   - speedup/efficiency/throughput,
   - bug report, UAT.
7. **UI bukti & dashboard**:
   - wallet connect/network guard,
   - transaction status,
   - blockchain proof page,
   - verification dashboard,
   - performance dashboard.
8. **Dokumentasi & governance**:
   - requirement addendum, scope, on/off-chain matrix, state diagram,
   - traceability matrix,
   - test plan,
   - Git governance, weekly log,
   - final report dan demo.

### 4.2 Out-of-Scope

| # | Di luar scope | Alasan |
|---|---|---|
| 1 | Deploy ke Ethereum mainnet / testnet publik | Demo memakai Ganache lokal; biaya gas dan risiko jaringan publik tidak diperlukan. |
| 2 | Uang nyata / payment gateway produksi | Proyek akademik; tidak ada settlement fiat nyata. |
| 3 | Migrasi ke Laravel | Keputusan D1. |
| 4 | Rewrite kontrak menjadi `HSportOrder.sol` terpisah | Keputusan D2. |
| 5 | Aplikasi mobile native | Fokus web responsif. |
| 6 | KYC/AML, rekening bank, pajak | Di luar cakupan mata kuliah. |
| 7 | Token/ERC-20/NFT/tokenomics | Tidak diminta dan menambah kompleksitas. |
| 8 | IPFS / penyimpanan desain terdesentralisasi | File desain tetap off-chain. |
| 9 | Autoscaling cloud, Kubernetes, load balancer produksi | Worker lokal sudah cukup untuk benchmark. |
| 10 | Multi-tenant / multi-toko | Satu instance H-Sport. |
| 11 | Integrasi kurir/realtime tracking | Nomor resi tetap manual. |
| 12 | Refund otomatis penuh di luar `cancelOrder` | Cancel mengembalikan escrow sesuai state yang diizinkan; kasus sengketa ditangani manual. |

---

## 5. Requirement Addendum v1.1

### 5.1 Functional Requirements

| ID | Requirement | Prioritas | Acceptance summary | Implementasi target | Test | Owner |
|---|---|---|---|---|---|---|
| **REQ-01** | Pembeli dapat checkout dan sistem membuat order off-chain serta order on-chain yang terhubung | Must | Order MySQL dan `blockchain_order_id` terisi | `cart.php`/`checkout.php` + service create | INT-02 | Luis, Arby |
| **REQ-02** | `createOrder` memvalidasi `totalAmount > 0` dan mengunci ETH di escrow | Must | Amount 0/salah revert; escrow tersimpan | `createOrder` | SC-02, SC-03 | Arby |
| **REQ-03** | Sistem menolak `externalOrderHash` duplikat | Must | Hash sama kedua kali revert | `orderIdByHash`, `createOrder` | SC-04 | Arby |
| **REQ-04** | Hanya operator yang boleh mencatat pembayaran; pembayaran ganda ditolak | Must | Non-operator dan double payment revert | `recordPayment`, `operators` | SC-07, SC-08 | Agustina |
| **REQ-05** | Status order mengikuti 8 state dan transisi tidak sah ditolak | Must | Transisi valid sukses; invalid revert | `updateOrderStatus`, `shipOrder` | SC-09, SC-10 | Agustina |
| **REQ-06** | Pembeli/aktor berwenang dapat membatalkan order pada state yang diizinkan; aktor lain ditolak | Must | Cancel valid sukses; unauthorized revert | `cancelOrder` | SC-11 | Agustina |
| **REQ-07** | Pembeli mengonfirmasi penerimaan dan escrow dirilis tepat satu kali | Must | Status `Completed`, saldo seller naik, release ganda revert | `confirmReceived` | SC-12 | Agustina |
| **REQ-08** | Setiap perubahan state memancarkan event yang dapat dibaca ulang | Must | Event memuat orderId, aktor, status, timestamp | event contract | SC-08, SC-09, SC-11, SC-12 | Agustina |
| **REQ-09** | Sistem menyimpan proof transaksi: `tx_hash`, `block_number`, `contract_address`, `wallet_address`, `blockchain_order_id`, `external_order_hash` | Must | Semua field persisten dan dapat dibaca | service + `migration_blockchain.sql` | INT-03, INT-04 | Luis |
| **REQ-10** | Backend membandingkan order DB dengan state on-chain dan menghasilkan `VERIFIED`/`MISMATCH` | Must | Hasil verifikasi benar untuk data cocok dan tidak cocok | verification service | INT-05 | Luis |
| **REQ-11** | Admin dapat melihat status verifikasi dan memicu verifikasi ulang | Must | Kolom status tampil; retry bekerja | `admin/orders.php` | UAT-07 | Ihsanul, Luis |
| **REQ-12** | Pembeli dapat melihat halaman bukti blockchain per order | Should | Tx hash, block, contract, status on-chain tampil | proof page | UAT-08 | Ihsanul |
| **REQ-13** | Verifikasi batch dapat berjalan sequential dan paralel dengan hasil identik | Must | Agregasi sequential = parallel | worker + partition/reduce | PAR-01, PAR-02 | Farelly |
| **REQ-14** | Sistem mengukur execution time, speedup, efficiency, throughput pada dataset dan jumlah worker yang ditentukan | Must | Tabel/grafik lengkap, correctness gate lolos | benchmark script | PAR-03, PAR-04 | Kenjiro, Farelly |
| **REQ-15** | Proyek dapat dijalankan ulang dari README; kontribusi tiap mahasiswa dapat ditelusuri | Must | Clean setup berhasil; issue/PR/log ada | README, Git, weekly log | UAT-10 | Feeandri, semua |

### 5.2 Non-Functional Requirements

| ID | Requirement | Target | Cara verifikasi |
|---|---|---|---|
| **NFR-01** | Correctness lebih penting daripada speedup | Hasil agregasi sequential dan parallel identik 100% | PAR-01/PAR-02 |
| **NFR-02** | Keamanan akses kontrak | Semua fungsi sensitif memakai modifier; unauthorized revert | SC-07 s.d. SC-12 |
| **NFR-03** | Tidak ada private key di repo | Hanya address publik; private key di MetaMask/env lokal | Code review + `git grep` |
| **NFR-04** | Reproducibility | Anggota lain dapat setup dan deploy dalam ≤ 30 menit | UAT-10, W2-FRL-05 |
| **NFR-05** | Observability | Setiap job/worker punya log dan status | `worker_logs`, `verification_jobs` |
| **NFR-06** | UX transaksi | Waiting approval, pending, success, failed/revert dibedakan | UAT-05, UAT-06 |
| **NFR-07** | Kompatibilitas environment | PHP 8.1, MySQL 8.0, Node 24, Truffle 5, Ganache, MetaMask | `06_ENVIRONMENT_INVENTORY.md` |
| **NFR-08** | Traceability | Tidak ada requirement Must tanpa implementasi + test | `03_TRACEABILITY_MATRIX.md` |
| **NFR-09** | Data minimization | Data personal dan file desain tidak masuk on-chain | Review on/off-chain matrix |
| **NFR-10** | Benchmark fairness | Dataset, mesin, dan kondisi run dicatat; warm-up dan pengulangan dijelaskan | `07_TEST_PLAN.md` |

---

## 6. Asumsi, Kendala, dan Dependensi

### 6.1 Asumsi

1. Ganache berjalan lokal di `127.0.0.1:7545` saat demo.
2. Setiap anggota memakai MetaMask dan mengimpor akun Ganache untuk pengujian.
3. MySQL 8 tersedia via Laragon.
4. Dataset benchmark dibangkitkan dari data order nyata/sintetis yang deterministik.
5. Demo dilakukan di satu mesin atau mesin dengan spesifikasi serupa; perbedaan mesin dicatat.

### 6.2 Kendala

1. Waktu 8 minggu; tidak ada waktu untuk rewrite total.
2. MySQL sempat tidak aktif saat audit, sehingga migration belum dijalankan.
3. Kontrak existing belum memiliki fungsi target; integrasi harus menunggu interface stabil.
4. Tidak ada CI/CD; verifikasi dilakukan manual + log.

### 6.3 Dependensi

| Dari | Ke | Artefak yang dibutuhkan |
|---|---|---|
| Arby/Agustina | Farrel | Kontrak compile sebelum deploy |
| Farrel | Luis | ABI, address, RPC, chain ID stabil |
| Luis | Ihsanul | Output read/write, error code, status verifikasi |
| Luis | Farelly | Fungsi verifikasi sebagai unit kerja worker |
| Farelly | Kenjiro | Sequential dan parallel selesai sebelum benchmark |
| Kenjiro | Semua | Bug report dan hasil test untuk perbaikan |
| Feeandri/Yasmin | Semua | Traceability dan acceptance criteria |

---

## 7. Change Control

1. Perubahan scope harus lewat issue baru dan disetujui Feeandri + Yasmin.
2. Perubahan requirement Must harus memperbarui `03_TRACEABILITY_MATRIX.md`.
3. Perubahan interface kontrak setelah akhir Minggu 3 harus disetujui Arby + Agustina + Luis, dan dicatat di `04_CONTRACT_AND_PARALLEL_DESIGN.md`.
4. Perubahan definisi 21 test harus disetujui Kenjiro dan dicatat di `07_TEST_PLAN.md`.
5. Keputusan yang berubah harus diberi tanggal dan alasan; keputusan lama tidak dihapus, tetapi ditandai **superseded**.

---

## 8. Exit Gate Minggu 1

| # | Kriteria | Bukti | Status |
|---|---|---|---|
| 1 | 5 keputusan tertulis dan disetujui | Bagian 2 dokumen ini | ✅ |
| 2 | Scope in/out final | Bagian 4 dokumen ini | ✅ |
| 3 | Requirement addendum v1.1 | Bagian 5 dokumen ini | ✅ |
| 4 | On/off-chain matrix + state diagram 8 state + cancel | `01_ON_OFF_CHAIN_AND_STATE.md` | ✅ |
| 5 | Business process map, AC, UAT | `02_BUSINESS_PROCESS_AND_UAT.md` | ✅ |
| 6 | Traceability matrix v0.1 | `03_TRACEABILITY_MATRIX.md` | ✅ |
| 7 | Desain kontrak + workload paralel + dataset | `04_CONTRACT_AND_PARALLEL_DESIGN.md` | ✅ |
| 8 | Draft schema paralel | `migration_parallel.sql` | ✅ |
| 9 | Audit integrasi + UI | `05_INTEGRATION_AND_UI_DESIGN.md` | ✅ |
| 10 | Inventory environment | `06_ENVIRONMENT_INVENTORY.md` | ✅ |
| 11 | Test plan 21 case | `07_TEST_PLAN.md` | ✅ |
| 12 | Git governance + PR template | `08_GIT_GOVERNANCE.md`, `.github/PULL_REQUEST_TEMPLATE.md` | ✅ |
| 13 | Tracker di-re-baseline | `docs/Progress Tracker.docx`, `docs/GAP_ANALYSIS.md` | ✅ |

---

## 9. Tautan

- Roadmap: [`../ROADMAP_RINCI.md`](../ROADMAP_RINCI.md)
- Gap analysis: [`../GAP_ANALYSIS.md`](../GAP_ANALYSIS.md)
- State & on/off-chain: [`01_ON_OFF_CHAIN_AND_STATE.md`](01_ON_OFF_CHAIN_AND_STATE.md)
- Traceability: [`03_TRACEABILITY_MATRIX.md`](03_TRACEABILITY_MATRIX.md)
- Test plan: [`07_TEST_PLAN.md`](07_TEST_PLAN.md)

---

## 10. Sign-off

| Peran | Nama | Status | Tanggal |
|---|---|---|---|
| Requirement & Scope Lead | Muhammad Feeandri Syahbani | Disetujui | 9 Nov 2026 |
| Business Process & Acceptance | Yasmin Akmaliyah | Disetujui | 9 Nov 2026 |
| QA & Security | Kenjiro B.H.G. | Review | 9 Nov 2026 |
| Solidity Core | Arby Putra | Review | 9 Nov 2026 |
| Solidity Payment/State | Agustina Elsi Bunga | Review | 9 Nov 2026 |
| Infrastructure | Muhammad Farrel | Review | 9 Nov 2026 |
| Integration | Luis | Review | 9 Nov 2026 |
| UI/UX | Ihsanul Farrel | Review | 9 Nov 2026 |
| Parallel Processing | Farelly De Rossi | Review | 9 Nov 2026 |
