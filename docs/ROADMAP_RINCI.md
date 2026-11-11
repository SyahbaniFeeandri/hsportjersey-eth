# ROADMAP RINCI 8 MINGGU — H-SPORT FINANCIAL dAPP

**Mata Kuliah:** Financial Distributed and Parallel Computing
**Semester:** Gasal 2026/2027
**Durasi:** 8 Minggu
**Sumber acuan:** `docs/Progress Tracker.docx`, `docs/DOKUMENTASI_PROJECT_DAN_PERAN.md`, `Minggu 1.docx`
**Status dokumen:** Draft kerja — diperbarui setiap Jumat bersama tracker
**Gap analysis:** lihat [GAP_ANALYSIS.md](GAP_ANALYSIS.md) - 44 selesai, 11 sebagian, 57 belum dari 112 issue.
**Artefak Minggu 1:** lihat [docs/week1/README.md](week1/README.md) - 17/17 issue selesai.
**Artefak Minggu 2:** lihat [docs/week2/README.md](week2/README.md) - 15 selesai, 1 sebagian (`W2-FRL-05`).
**Artefak Minggu 3:** lihat [docs/week3/README.md](week3/README.md) - 12/12 issue selesai (exit gate teknis terpenuhi).

---

## 1. Cara Menggunakan Dokumen Ini

1. Setiap minggu memiliki **satu milestone**, **satu lead**, **exit gate**, dan **daftar issue per mahasiswa**.
2. Format issue: `W<mgg>-<PIC>-<nomor>`, contoh `W3-ARB-04`.
3. Sebuah issue hanya boleh ditutup jika memenuhi:
   - ada commit/PR yang bisa ditelusuri,
   - ada bukti test/validasi (log, screenshot, tx hash, atau output benchmark),
   - direview minimal satu anggota lain,
   - tautan bukti dicatat di `Progress Tracker` dan weekly log.
4. Status milestone mengikuti tracker: `Belum Mulai`, `Berjalan`, `Selesai`, `Terlambat`, `Diblokir`.
5. Jika exit gate tidak terpenuhi, milestone **tidak boleh** ditandai selesai walaupun kode sudah ada.

**Legenda PIC:**
FEE = Feeandri, YAS = Yasmin, ARB = Arby, AGU = Agustina, FRL = Farrel,
LUI = Luis, IHS = Ihsanul, FDR = Farelly, KEN = Kenjiro.

---

## 2. Baseline Kondisi Repo Saat Ini

Tracker mencatat seluruh milestone 0%, tetapi repo sudah memiliki sebagian implementasi. Roadmap ini melakukan **re-baseline** pada Minggu 1.

| Area | Status riil | Bukti |
|---|---|---|
| Web e-commerce (katalog, cart, checkout, admin, review) | Sudah ada | `index.php`, `products.php`, `cart.php`, `checkout.php`, `admin/*`, `migration_reviews.sql` |
| Smart contract escrow | Ada V1 | `blockchain/contracts/HSportStore.sol`, sudah dideploy ke Ganache |
| Environment blockchain | Sebagian | `truffle-config.js`, `config/contract_address.txt`, `config/contract_abi.json`, `scripts/checkGanache.js` |
| Integrasi Web3 | Sebagian (client-side) | `crypto-payment.php`, `orders.php`, `admin/orders.php`, `api/blockchain-api.php` |
| Backend read/receipt/event/verification | Belum | API hanya menyimpan `tx_hash` dan `wallet_address` |
| 21 test case otomatis | Harness ada, 0/21 aktif | `blockchain/test/` (smoke 4 PASS, skeleton 6 pending) |
| Parallel processing (queue/worker/partition) | Belum | Baru disebut di dokumen |
| Benchmark & dashboard performa | Belum | — |
| Dokumentasi & tracker | Ada, tetapi status belum akurat | Tracker masih 0% untuk semua milestone |

**Gap yang dibereskan di Minggu 1:**

1. ✅ `Progress Tracker.docx` sudah memiliki Bagian 4 (Test Case) dan 5 (Weekly Log), dan sudah di-re-baseline.
2. ✅ Nama integration engineer dikunci: **Luis** (Sahrul tidak dipakai lagi).
3. ✅ Stack dikunci: **PHP native** (bukan Laravel).
4. ✅ `blockchain/README.md` sudah memakai **Truffle** (bukan Hardhat).
5. ✅ `migration_blockchain.sql` dan `migration_parallel.sql` sudah dijalankan dan diverifikasi di MySQL 8.0.30.
6. ✅ Kontrak tetap `HSportStore` (extend), target interface didokumentasikan di `docs/week1/04_CONTRACT_AND_PARALLEL_DESIGN.md`.

---

## 3. Keputusan yang Dikunci di Minggu 1

| # | Keputusan | Rekomendasi | Konsekuensi jika salah |
|---|---|---|---|
| 1 | PHP native atau migrasi ke Laravel? | **Tetap PHP native** | Migrasi framework memakan waktu dan berisiko merusak demo |
| 2 | Extend `HSportStore` atau rewrite ke `HSportOrder`? | **Extend** dan freeze interface di akhir Minggu 3 | Rewrite total membahayakan flow pembayaran yang sudah jalan |
| 3 | Definisi 21 test case ikut yang mana? | **Ikuti `DOKUMENTASI_PROJECT_DAN_PERAN.md`** (alur escrow) dan tambahkan test cancel/duplicate hash/operator | Dua sumber definisi membuat status PASS ambigu |
| 4 | Truffle atau Hardhat? | **Truffle** (sudah terpasang dan berjalan) | Pindah tooling di tengah proyek membuang waktu |
| 5 | Sahrul atau Luis? | **Luis** (sesuai tracker) | Nama pemilik deliverable tidak jelas saat penilaian |

---

## 4. Roadmap per Minggu

Target progress kumulatif mengikuti dashboard tracker:
**10% → 22% → 38% → 52% → 68% → 82% → 93% → 100%**.

### Minggu 1 — Analisis & Scope Freeze

**Status saat ini:** **Selesai** — 17/17 issue selesai. Artefak di [docs/week1/](week1/README.md). Lihat [GAP_ANALYSIS.md](GAP_ANALYSIS.md).
**Lead:** Feeandri + Yasmin · **Pendukung:** Farelly, Arby, Agustina · **Target:** 10%

| PIC | Issue | Output / Evidence |
|---|---|---|
| FEE | `W1-FEE-01` Re-baseline tracker: isi status riil web app, kontrak, integrasi | Dashboard tracker ter-update + daftar gap |
| FEE | `W1-FEE-02` Finalkan requirement addendum v1.1 + scope in/out | Dokumen requirement |
| FEE | `W1-FEE-03` On/off-chain matrix + state diagram 8 state + cancel | Matriks + diagram final |
| FEE | `W1-FEE-04` Traceability matrix skeleton (REQ → implementasi → test → owner) | Sheet traceability v0.1 |
| FEE | `W1-FEE-05` Repo governance: branch, label issue, PR template, weekly log | Template + konvensi |
| YAS | `W1-YAS-01` Business process map: checkout → escrow → ship → confirm → release | Diagram alur |
| YAS | `W1-YAS-02` Acceptance criteria per milestone | Daftar AC |
| YAS | `W1-YAS-03` UAT scenario v0.1 | Skenario UAT |
| YAS | `W1-YAS-04` Review state diagram + aktor | Catatan review |
| FDR | `W1-FDR-01` Definisi workload paralel + dataset benchmark 100/500/1.000/5.000 | Dokumen desain paralel |
| FDR | `W1-FDR-02` Draft schema `verification_jobs`, `job_batches`, `checkpoints`, `worker_logs` | Draft migration |
| ARB | `W1-ARB-01` Audit kontrak existing vs desain target, daftar gap | Gap analysis kontrak |
| AGU | `W1-AGU-01` Review state machine + revert rules | Matriks transisi & revert |
| FRL | `W1-FRL-01` Inventory environment: Ganache, address, ABI, Truffle | Baseline environment |
| LUI | `W1-LUI-01` Audit alur integrasi existing (client-side) + desain service | Desain integrasi |
| IHS | `W1-IHS-01` Audit UI wallet/tx/proof existing + daftar gap | Gap analysis UI |
| KEN | `W1-KEN-01` Test plan 21 case + rencana test harness | Test plan v0.1 |

**Exit gate:** scope v1.0 disetujui, 5 keputusan terkunci, backlog 9 role dibuat, tracker sudah mencerminkan kondisi nyata.

---

### Minggu 2 — Blockchain Environment

**Status saat ini:** Selesai (artefak) — 15 selesai, 1 sebagian, 0 belum dari 16 issue. Spesifikasi kontrak final, wireframe, template, dan simulasi clean clone (ABI identik) tersedia; uji di komputer anggota lain belum.
**Lead:** Farrel · **Pendukung:** Arby, Agustina, Luis · **Target:** 22%

| PIC | Issue | Output / Evidence |
|---|---|---|
| FRL | `W2-FRL-01` Finalkan Ganache: chain ID, akun, RPC 7545, snapshot | `docs/week2/00_ENVIRONMENT_BASELINE.md` |
| FRL | `W2-FRL-02` Panduan MetaMask: add network, import akun | `docs/PANDUAN_SETUP_DAN_OPERASIONAL.md` Langkah 4 |
| FRL | `W2-FRL-03` Truffle config + test harness + perbaiki README | `blockchain/test/`, baseline smoke 4 PASS |
| FRL | `W2-FRL-04` Deploy script + ABI/address history + `.env.example` | `blockchain/scripts/logDeployment.js`, `blockchain/deployments/` |
| FRL | `W2-FRL-05` Uji reproduksi di komputer anggota lain | Sebagian: clean clone PASS di mesin sendiri + `docs/week2/evidence/reproduction_clean_clone.log` |
| ARB | `W2-ARB-02` Finalkan struct/enum/mapping/modifier | `docs/week2/02_CONTRACT_SPEC_FINAL.md` |
| ARB | `W2-ARB-03` Skeleton unit test SC-01 s.d. SC-06 | `blockchain/test/01_sc01_sc06.skeleton.test.js` |
| AGU | `W2-AGU-02` Finalkan transition table + event list | `docs/week2/02` bagian 7 & 9 |
| AGU | `W2-AGU-03` Revert rules matrix | `docs/week2/02` bagian 12 (R01–R20) |
| LUI | `W2-LUI-02` Desain service read/write/receipt/verification | `docs/week1/05` bagian B |
| LUI | `W2-LUI-03` Daftar field DB proof + index | `migration_blockchain.sql` (sudah dijalankan) |
| FDR | `W2-FDR-03` Finalkan schema queue/verification + index | `migration_parallel.sql` (sudah dijalankan) |
| FEE | `W2-FEE-06` Update traceability dengan 21 test case | `docs/week1/03_TRACEABILITY_MATRIX.md` |
| YAS | `W2-YAS-05` Requirement-to-test mapping v1 | `docs/week1/02` bagian 6 + `03` |
| IHS | `W2-IHS-02` Wireframe wallet, tx status, proof page, dashboard | `docs/week2/01_UI_WIREFRAMES.md` |
| KEN | `W2-KEN-02` Template test report, bug report, benchmark sheet | `docs/templates/` |

**Exit gate:** clean clone → compile → deploy berhasil di mesin anggota lain; ABI/address konsisten.
**Status gate:** ABI konsisten dan clean clone PASS di mesin sendiri; uji di mesin anggota lain **belum** (`W2-FRL-05` sebagian).

---

### Minggu 3 — Smart Contract V1

**Status saat ini:** **Selesai** — 12 selesai, 0 sebagian, 0 belum dari 12 issue. [Artefak M3](week3/README.md); sign-off reviewer menunggu PR.
**Lead:** Arby · **Pendukung:** Agustina, Farrel, Kenjiro · **Target:** 38%

| PIC | Issue | Output / Evidence |
|---|---|---|
| ARB | `W3-ARB-04` Implement enum 8 state, struct + `externalOrderHash`, mapping, modifier | Kontrak V1 |
| ARB | `W3-ARB-05` `createOrder`, `getOrder`, `isOperator`, duplicate hash, amount > 0 | Fungsi kontrak |
| ARB | `W3-ARB-06` NatSpec + unit test SC-01 s.d. SC-06 | 6/6 PASS |
| ARB | `W3-ARB-07` Export ABI + catat address v1 | ABI/address |
| AGU | `W3-AGU-04` Review V1 + siapkan skenario SC-07 s.d. SC-12 | Review + test plan V2 |
| FRL | `W3-FRL-06` Deploy V1 ke Ganache + deployment log | Address v1 |
| KEN | `W3-KEN-03` Review & jalankan ulang SC-01 s.d. SC-06, bug report | Test report V1 |
| FEE | `W3-FEE-08` Update on/off-chain matrix + traceability kontrak | Dokumen update |
| YAS | `W3-YAS-07` Review acceptance criteria kontrak core | Catatan review |
| LUI | `W3-LUI-04` Prototipe contract instance read-only | Service read-only |
| FDR | `W3-FDR-04` Finalkan migration schema paralel | Migration |
| IHS | `W3-IHS-03` Mockup proof page + komponen tx status | Mockup |

**Exit gate:** kontrak V1 compile & deploy, 6/6 SC PASS, **interface kontrak freeze**.
**Status gate:** terpenuhi 11 November 2026 (compile, deploy `0x59d3…a150`, 6/6 SC PASS, freeze teknis). Sign-off reviewer di PR masih terbuka.

---

### Minggu 4 — Smart Contract V2

**Status saat ini:** Sebagian — 0 selesai, 3 sebagian, 9 belum dari 12 issue.
**Lead:** Agustina · **Pendukung:** Arby, Farrel, Kenjiro · **Target:** 52%

| PIC | Issue | Output / Evidence |
|---|---|---|
| AGU | `W4-AGU-06` `recordPayment`, `updateOrderStatus`, `cancelOrder`, operator management | Kontrak V2 |
| AGU | `W4-AGU-07` Event lengkap + revert rules + security check | Event & revert |
| AGU | `W4-AGU-08` Unit test SC-07 s.d. SC-12 | 6/6 PASS |
| ARB | `W4-ARB-08` Review V2 + regresi SC-01 s.d. SC-06 | Regression PASS |
| FRL | `W4-FRL-07` Deploy V2 + update ABI/address history | Address v2 |
| FRL | `W4-FRL-08` Cek kompatibilitas frontend dengan interface V2 | Catatan kompatibilitas |
| KEN | `W4-KEN-04` Security/revert test independen + review SC-07 s.d. SC-12 | Security report |
| LUI | `W4-LUI-05` Sesuaikan service dengan ABI V2 | Service update |
| IHS | `W4-IHS-04` Implement wallet connect + tx status UI (mock) | UI berjalan |
| FDR | `W4-FDR-05` Sequential baseline CLI dengan data mock | Baseline script |
| FEE | `W4-FEE-09` Update traceability + dokumentasi kontrak | Dokumen |
| YAS | `W4-YAS-08` UAT scenario payment/status/cancel | Skenario UAT |

**Exit gate:** 12/12 SC PASS, security test selesai, ABI/address v2 dipublikasikan.

---

### Minggu 5 — Integrasi PHP–Blockchain

**Status saat ini:** Sebagian — 0 selesai, 3 sebagian, 12 belum dari 15 issue.
**Lead:** Luis · **Pendukung:** Farrel, Ihsanul, Yasmin · **Target:** 68%

| PIC | Issue | Output / Evidence |
|---|---|---|
| LUI | `W5-LUI-06` Contract instance read/write dari backend | Service |
| LUI | `W5-LUI-07` Receipt & event parser | Parser |
| LUI | `W5-LUI-08` Persist proof: `tx_hash`, `block_number`, `contract_address`, wallet, `blockchain_order_id` | DB proof |
| LUI | `W5-LUI-09` Verification service `VERIFIED`/`MISMATCH` | Service verifikasi |
| LUI | `W5-LUI-10` Error handling: RPC down, wrong network, revert, order not found | Error handler |
| LUI | `W5-LUI-11` INT-01 s.d. INT-05 evidence | 5/5 PASS |
| FRL | `W5-FRL-09` Dukungan RPC/network + troubleshooting | Log dukungan |
| IHS | `W5-IHS-05` Wallet UI + tx status: approval, pending, success, failed | UI |
| IHS | `W5-IHS-06` Blockchain proof page | Halaman proof |
| YAS | `W5-YAS-09` Review error/UX flow + UAT integrasi | Catatan UX |
| FEE | `W5-FEE-10` Traceability REQ → implementasi → test | Traceability v1 |
| FDR | `W5-FDR-06` Finalisasi schema + queue config + worker skeleton | Worker skeleton |
| KEN | `W5-KEN-05` Integration test independen INT-01 s.d. INT-05 | Test report |
| ARB | `W5-ARB-09` Support bug fixing kontrak | Fix |
| AGU | `W5-AGU-09` Support bug fixing kontrak | Fix |

**Exit gate:** checkout → chain → DB → read-back berhasil; 5/5 INT PASS.

---

### Minggu 6 — Parallel Processing

**Status saat ini:** Belum mulai — 0 selesai, 0 sebagian, 13 belum dari 13 issue.
**Lead:** Farelly · **Pendukung:** Luis, Ihsanul, Kenjiro · **Target:** 82%

| PIC | Issue | Output / Evidence |
|---|---|---|
| FDR | `W6-FDR-07` Job/Queue batch verification | Queue |
| FDR | `W6-FDR-08` Multi-worker 1/2/4/8 | Worker |
| FDR | `W6-FDR-09` Partition/reduce | Algoritma |
| FDR | `W6-FDR-10` Agregasi finansial: revenue, count, AOV, paid/unpaid, verified/mismatch | Output agregasi |
| FDR | `W6-FDR-11` PAR-01/PAR-02 evidence | 2/2 PASS |
| KEN | `W6-KEN-06` Verifikasi PAR-01/02 + cek race condition & duplikasi | Test report |
| IHS | `W6-IHS-07` Performance dashboard awal | Dashboard |
| LUI | `W6-LUI-12` Dukungan verification service untuk worker | Integrasi worker |
| FEE | `W6-FEE-11` Dokumentasi arsitektur paralel | Dokumen |
| YAS | `W6-YAS-10` Acceptance criteria hasil agregasi | Catatan AC |
| ARB | `W6-ARB-10` Support bug fixing | Fix |
| AGU | `W6-AGU-10` Support bug fixing | Fix |
| FRL | `W6-FRL-10` Maintenance environment/deploy ulang | Log |

**Exit gate:** output sequential = parallel; 1/2/4/8 worker memproses job berbeda.

---

### Minggu 7 — Benchmark & Integration Test

**Status saat ini:** Belum mulai — 0 selesai, 0 sebagian, 13 belum dari 13 issue. Benchmark belum ada.
**Lead:** Kenjiro · **Pendukung:** Farelly, Ihsanul, Feeandri · **Target:** 93%

| PIC | Issue | Output / Evidence |
|---|---|---|
| KEN | `W7-KEN-07` Benchmark dataset 100/500/1.000/5.000 × worker 1/2/4/8 | Dataset + hasil |
| KEN | `W7-KEN-08` Hitung execution time, speedup, efficiency, throughput | Tabel + grafik |
| KEN | `W7-KEN-09` Full regression 21 test case | 21/21 atau FAIL terdokumentasi |
| KEN | `W7-KEN-10` Bug report + retest | Bug log |
| KEN | `W7-KEN-11` Test report final | Laporan |
| FDR | `W7-FDR-12` Tuning worker/queue + dukungan benchmark | Catatan tuning |
| IHS | `W7-IHS-08` Finalisasi performance + verification dashboard | Dashboard final |
| FEE | `W7-FEE-12` Review konsistensi benchmark + draft final doc | Catatan review |
| YAS | `W7-YAS-11` UAT execution + requirement-to-test final | Bukti UAT |
| LUI | `W7-LUI-13` Perbaikan integrasi dari bug report | Fix |
| FRL | `W7-FRL-11` Reproducibility check di mesin lain | Bukti |
| ARB | `W7-ARB-11` Perbaikan kontrak dari bug report | Fix |
| AGU | `W7-AGU-11` Perbaikan kontrak dari bug report | Fix |

**Exit gate:** benchmark reproducible, tidak ada bug Sev-1, seluruh test punya status.

---

### Minggu 8 — Finalisasi & Demo

**Status saat ini:** Sebagian kecil — 0 selesai, 4 sebagian, 10 belum dari 14 issue.
**Lead:** semua · **Pendukung:** semua · **Target:** 100%

| PIC | Issue | Output / Evidence |
|---|---|---|
| ALL | `W8-ALL-01` Code freeze + tag rilis | Tag repo |
| ALL | `W8-ALL-02` README clean setup + seed data | README |
| ALL | `W8-ALL-03` Final report + traceability + weekly log | Dokumen |
| ALL | `W8-ALL-04` Rehearsal demo per role | Jadwal + slide |
| ALL | `W8-ALL-05` Demo end-to-end | Bukti demo |
| FEE | `W8-FEE-13` Final documentation | Laporan final |
| YAS | `W8-YAS-12` UAT evidence + demo business flow | Bukti |
| FRL | `W8-FRL-12` Deployment guide + environment | Panduan |
| ARB | `W8-ARB-12` Demo core contract | Demo |
| AGU | `W8-AGU-12` Demo state/payment/revert | Demo |
| LUI | `W8-LUI-14` Demo checkout → chain → DB → verification | Demo |
| FDR | `W8-FDR-13` Demo sequential vs parallel | Demo |
| KEN | `W8-KEN-12` Demo test result + benchmark | Demo |
| IHS | `W8-IHS-09` Demo proof page + dashboard | Demo |

**Exit gate:** demo berjalan dari clean setup; setiap anggota menjelaskan `input → process → output → error → test → dependency`.

---

## 5. Jadwal 21 Test Case

| Minggu | Test yang harus PASS | Owner |
|---|---|---|
| 2 | INT-01 (smoke test koneksi RPC) | Farrel + Luis |
| 3 | SC-01 s.d. SC-06 | Arby |
| 4 | SC-07 s.d. SC-12 | Agustina |
| 5 | INT-01 s.d. INT-05 | Luis |
| 6 | PAR-01, PAR-02 | Farelly |
| 7 | PAR-03, PAR-04 + rerun seluruh 21 | Kenjiro |
| 8 | Final regression 21/21 | Kenjiro |

> **Penting:** definisi test di `DOKUMENTASI_PROJECT_DAN_PERAN.md` (alur escrow) berbeda dari `Minggu 1.docx` (operator/cancel). Keputusan Minggu 1 menentukan definisi mana yang dipakai. Jangan mencampur keduanya.

---

## 6. Jalur Kritis, Paralelisasi, dan Risiko

### 6.1 Jalur kritis

```text
Scope & interface kontrak (W1)
  → Environment (W2)
  → SC V1 (W3)
  → SC V2 (W4)
  → Integrasi (W5)
  → Parallel verification (W6)
  → Benchmark (W7)
  → Demo (W8)
```

Jika Minggu 3 atau Minggu 5 slip, Minggu 7 dan 8 ikut mundur.

### 6.2 Strategi paralelisasi

- **Minggu 3:** interface kontrak wajib freeze, walau V2 belum selesai.
- **Minggu 4–5:** Farelly mulai sequential baseline dan worker skeleton dengan data mock agar Minggu 6 tidak menjadi bottleneck.
- **Minggu 5–8:** UI/proof/dashboard berjalan paralel dengan backend, tidak menunggu semuanya selesai.
- **Minggu 1–8:** dokumentasi, traceability, dan weekly log berjalan terus.

### 6.3 Risiko utama

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Kontrak di-refactor total di W3–W4 | Flow demo yang sudah jalan rusak | Extend, bukan rewrite; freeze interface W3; adapter untuk flow lama |
| Tidak ada test harness | 21 test tidak selesai di W7 | Siapkan `truffle test` di W2, tulis test bersamaan dengan kontrak |
| Dokumen vs kode tidak sinkron | Kebingungan saat demo & penilaian | Kunci keputusan W1, perbaiki README dan tracker |
| Kolom blockchain belum ada di SQL dump | Setup ulang gagal | Jalankan `migration_blockchain.sql` |
| Parallel processing baru mulai W6 | Benchmark W7 mepet | Mulai prototipe worker dengan mock data di W4–W5 |
| Semua milestone tracker 0% | Progress tidak terlihat | Re-baseline W1, update setiap Jumat |

---

## 7. Ritme Tracking & Definition of Done

### 7.1 Ritme mingguan

| Hari | Aktivitas |
|---|---|
| Senin | Update target minggu, bagi issue, cek dependency |
| Rabu | Review PR lintas peran, cek blocker |
| Jumat | Update tracker (status, % progress, catatan), isi weekly log, lampirkan bukti test |

### 7.2 Definition of Done

Sebuah deliverable baru dianggap selesai jika:

1. Ada di repo dan bisa dijalankan.
2. Ada bukti test/validasi PASS.
3. Direview minimal satu anggota lain.
4. Tercatat di tracker dengan link bukti.
5. Bisa direproduksi dari README.

### 7.3 Update tracker

Setiap Jumat, perbarui:

- status dan % progress milestone,
- status deliverable per mahasiswa,
- status 21 test case (Not Started / In Progress / PASS / FAIL),
- weekly log masing-masing mahasiswa,
- catatan blocker dan keputusan teknis.
