# GAP ANALYSIS — H-SPORT FINANCIAL dAPP

**Sumber:** `docs/ROADMAP_RINCI.md`
**Tanggal analisis:** 9 November 2026
**Cakupan:** 112 issue roadmap Minggu 1–8

Dokumen ini menandai status eksekusi setiap issue. Status dinilai dari artefak yang benar-benar ada di repo, bukan dari klaim di dokumen.

**Update 9 November 2026:** Minggu 1 selesai — 17/17 issue memiliki artefak di `docs/week1/`.

**Update 9 November 2026 (sesi lanjutan):** MySQL, `migration_blockchain.sql`, `migration_parallel.sql`, Ganache (`chainId 1337`, `networkId 5777`), deploy kontrak, deployment log, dan `.env.example` sudah diverifikasi.

## Legenda Status

| Status | Arti |
|---|---|
| **Selesai** | Artefak ada dan memenuhi output issue |
| **Sebagian** | Sebagian artefak/kode/dokumen sudah ada, tetapi belum lengkap |
| **Belum** | Belum ada artefak atau implementasi |

## 1. Ringkasan

| Status | Jumlah | % dari 112 |
|---|---:|---:|
| Selesai | 31 | 27.7% |
| Sebagian | 12 | 10.7% |
| Belum | 69 | 61.6% |
| **Total** | **112** | **100%** |

### Ringkasan per Milestone

| Minggu | Milestone | Selesai | Sebagian | Belum | Total |
|---|---|---:|---:|---:|---:|
| 1 | Analisis & Scope Freeze (Feeandri + Yasmin) | 17 | 0 | 0 | 17 |
| 2 | Blockchain Environment (Farrel) | 13 | 1 | 2 | 16 |
| 3 | Smart Contract V1 (Arby) | 1 | 2 | 9 | 12 |
| 4 | Smart Contract V2 (Agustina) | 0 | 2 | 10 | 12 |
| 5 | Integrasi PHP–Blockchain (Luis) | 0 | 3 | 12 | 15 |
| 6 | Parallel Processing (Farelly) | 0 | 0 | 13 | 13 |
| 7 | Benchmark & Integration Test (Kenjiro) | 0 | 0 | 13 | 13 |
| 8 | Finalisasi & Demo (semua) | 0 | 4 | 10 | 14 |

> **Catatan:** jumlah issue yang belum dikerjakan tinggi karena roadmap memecah pekerjaan secara rinci. Angka ini adalah checklist eksekusi, bukan penilaian kualitas.

## 2. Checklist per Minggu

### Minggu 1 — Analisis & Scope Freeze (Feeandri + Yasmin)

**Status:** 17 selesai, 0 sebagian, 0 belum dari 17 issue.

| ID | Issue | Output | Status | Bukti / Catatan |
|---|---|---|---|---|
| `W1-FEE-01` | Re-baseline tracker: isi status riil web app, kontrak, integrasi | Dashboard tracker ter-update + daftar gap | Selesai | Tracker + GAP_ANALYSIS diperbarui; status riil 8 milestone terdokumentasi |
| `W1-FEE-02` | Finalkan requirement addendum v1.1 + scope in/out | Dokumen requirement | Selesai | docs/week1/00_DECISIONS_AND_SCOPE.md |
| `W1-FEE-03` | On/off-chain matrix + state diagram 8 state + cancel | Matriks + diagram final | Selesai | docs/week1/01_ON_OFF_CHAIN_AND_STATE.md |
| `W1-FEE-04` | Traceability matrix skeleton (REQ ke implementasi ke test ke owner) | Sheet traceability v0.1 | Selesai | docs/week1/03_TRACEABILITY_MATRIX.md |
| `W1-FEE-05` | Repo governance: branch, label issue, PR template, weekly log | Template + konvensi | Selesai | docs/week1/08_GIT_GOVERNANCE.md + .github/PULL_REQUEST_TEMPLATE.md |
| `W1-YAS-01` | Business process map: checkout ke escrow ke ship ke confirm ke release | Diagram alur | Selesai | docs/week1/02_BUSINESS_PROCESS_AND_UAT.md |
| `W1-YAS-02` | Acceptance criteria per milestone | Daftar AC | Selesai | 00_DECISIONS_AND_SCOPE.md + 02_BUSINESS_PROCESS_AND_UAT.md |
| `W1-YAS-03` | UAT scenario v0.1 | Skenario UAT | Selesai | 02_BUSINESS_PROCESS_AND_UAT.md (UAT-01 s.d. UAT-17) |
| `W1-YAS-04` | Review state diagram + aktor | Catatan review | Selesai | 01_ON_OFF_CHAIN_AND_STATE.md bagian 7 |
| `W1-FDR-01` | Definisi workload paralel + dataset benchmark 100/500/1.000/5.000 | Dokumen desain paralel | Selesai | 04_CONTRACT_AND_PARALLEL_DESIGN.md bagian B |
| `W1-FDR-02` | Draft schema verification_jobs, job_batches, checkpoints, worker_logs | Draft migration | Selesai | migration_parallel.sql |
| `W1-ARB-01` | Audit kontrak existing vs desain target, daftar gap | Gap analysis kontrak | Selesai | 04_CONTRACT_AND_PARALLEL_DESIGN.md bagian A (C01-C17) |
| `W1-AGU-01` | Review state machine + revert rules | Matriks transisi & revert | Selesai | 01_ON_OFF_CHAIN_AND_STATE.md bagian 4-5 |
| `W1-FRL-01` | Inventory environment: Ganache, address, ABI, Truffle | Baseline environment | Selesai | 06_ENVIRONMENT_INVENTORY.md |
| `W1-LUI-01` | Audit alur integrasi existing (client-side) + desain service | Desain integrasi | Selesai | 05_INTEGRATION_AND_UI_DESIGN.md bagian A-B |
| `W1-IHS-01` | Audit UI wallet/tx/proof existing + daftar gap | Gap analysis UI | Selesai | 05_INTEGRATION_AND_UI_DESIGN.md bagian C |
| `W1-KEN-01` | Test plan 21 case + rencana test harness | Test plan v0.1 | Selesai | 07_TEST_PLAN.md |

### Minggu 2 — Blockchain Environment (Farrel)

**Status:** 13 selesai, 1 sebagian, 2 belum dari 16 issue.

| ID | Issue | Output | Status | Bukti / Catatan |
|---|---|---|---|---|
| `W2-FRL-01` | Finalkan Ganache: chain ID, akun, RPC 7545, snapshot | Config + panduan | Selesai | Ganache 7.9.1: chainId 1337, networkId 5777, 10 akun deterministik, RPC 7545; tercatat di 06_ENVIRONMENT_INVENTORY.md |
| `W2-FRL-02` | Panduan MetaMask: add network, import akun | Dokumen setup | Selesai | docs/PANDUAN_SETUP_DAN_OPERASIONAL.md Langkah 4 |
| `W2-FRL-03` | Truffle config + test harness + perbaiki README | Repo blockchain siap | Selesai | README diperbaiki; truffle-config siap; test/ harness + baseline smoke 4 PASS |
| `W2-FRL-04` | Deploy script + ABI/address history + .env.example | Script + deployment log | Selesai | scripts/logDeployment.js + deployments/DEPLOYMENTS.md + .env.example |
| `W2-FRL-05` | Uji reproduksi di komputer anggota lain | Screenshot/log | Belum | - |
| `W2-ARB-02` | Finalkan struct/enum/mapping/modifier | Spesifikasi kontrak | Selesai | Target interface final di docs/week1/04 bagian 3 |
| `W2-ARB-03` | Skeleton unit test SC-01 s.d. SC-06 | Test file awal | Selesai | test/01_sc01_sc06.skeleton.test.js (6 pending) + baseline smoke 4 PASS |
| `W2-AGU-02` | Finalkan transition table + event list | Dokumen state & event | Selesai | docs/week1/01 bagian 4 + 04 event list |
| `W2-AGU-03` | Revert rules matrix | Matriks revert | Selesai | docs/week1/01 bagian 5 (R01-R16) |
| `W2-LUI-02` | Desain service read/write/receipt/verification | Dokumen desain service | Selesai | docs/week1/05 bagian B |
| `W2-LUI-03` | Daftar field DB proof + index | Draft migration | Selesai | migration_blockchain.sql sudah dijalankan; kolom & tabel terverifikasi |
| `W2-FDR-03` | Finalkan schema queue/verification + index | Migration | Selesai | migration_parallel.sql sudah dijalankan; 4 tabel + view terverifikasi |
| `W2-FEE-06` | Update traceability dengan 21 test case | Traceability v0.2 | Selesai | docs/week1/03_TRACEABILITY_MATRIX.md |
| `W2-YAS-05` | Requirement-to-test mapping v1 | Matriks REQ-Test | Selesai | docs/week1/02 bagian 6 + 03 bagian 2-3 |
| `W2-IHS-02` | Wireframe wallet, tx status, proof page, dashboard | Mockup | Belum | - |
| `W2-KEN-02` | Template test report, bug report, benchmark sheet | Template | Sebagian | Format bug report ada di 07 bagian 9; benchmark sheet belum |

### Minggu 3 — Smart Contract V1 (Arby)

**Status:** 1 selesai, 2 sebagian, 9 belum dari 12 issue.

| ID | Issue | Output | Status | Bukti / Catatan |
|---|---|---|---|---|
| `W3-ARB-04` | Implement enum 8 state, struct + externalOrderHash, mapping, modifier | Kontrak V1 | Belum | Kontrak masih 4 state, tanpa externalOrderHash |
| `W3-ARB-05` | createOrder, getOrder, isOperator, duplicate hash, amount > 0 | Fungsi kontrak | Sebagian | createOrder/getOrderInfo/amount>0 ada; isOperator/duplicate hash belum |
| `W3-ARB-06` | NatSpec + unit test SC-01 s.d. SC-06 | 6/6 PASS | Belum | Tidak ada folder test |
| `W3-ARB-07` | Export ABI + catat address v1 | ABI/address | Selesai | config/contract_abi.json + config/contract_address.txt |
| `W3-AGU-04` | Review V1 + siapkan skenario SC-07 s.d. SC-12 | Review + test plan V2 | Belum | - |
| `W3-FRL-06` | Deploy V1 ke Ganache + deployment log | Address v1 | Sebagian | Sudah pernah deploy; deployment log belum ada |
| `W3-KEN-03` | Review & jalankan ulang SC-01 s.d. SC-06, bug report | Test report V1 | Belum | - |
| `W3-FEE-08` | Update on/off-chain matrix + traceability kontrak | Dokumen update | Belum | - |
| `W3-YAS-07` | Review acceptance criteria kontrak core | Catatan review | Belum | - |
| `W3-LUI-04` | Prototipe contract instance read-only | Service read-only | Belum | Frontend Web3 bukan service backend |
| `W3-FDR-04` | Finalkan migration schema paralel | Migration | Belum | - |
| `W3-IHS-03` | Mockup proof page + komponen tx status | Mockup | Belum | - |

### Minggu 4 — Smart Contract V2 (Agustina)

**Status:** 0 selesai, 2 sebagian, 10 belum dari 12 issue.

| ID | Issue | Output | Status | Bukti / Catatan |
|---|---|---|---|---|
| `W4-AGU-06` | recordPayment, updateOrderStatus, cancelOrder, operator management | Kontrak V2 | Belum | Kontrak existing hanya shipOrder/confirmReceived |
| `W4-AGU-07` | Event lengkap + revert rules + security check | Event & revert | Sebagian | 4 event escrow ada; event target & security check belum |
| `W4-AGU-08` | Unit test SC-07 s.d. SC-12 | 6/6 PASS | Belum | - |
| `W4-ARB-08` | Review V2 + regresi SC-01 s.d. SC-06 | Regression PASS | Belum | - |
| `W4-FRL-07` | Deploy V2 + update ABI/address history | Address v2 | Belum | V2 belum ada |
| `W4-FRL-08` | Cek kompatibilitas frontend dengan interface V2 | Catatan kompatibilitas | Belum | - |
| `W4-KEN-04` | Security/revert test independen + review SC-07 s.d. SC-12 | Security report | Belum | - |
| `W4-LUI-05` | Sesuaikan service dengan ABI V2 | Service update | Belum | - |
| `W4-IHS-04` | Implement wallet connect + tx status UI (mock) | UI berjalan | Sebagian | Wallet connect + modal tx ada di crypto-payment.php; belum general/proof |
| `W4-FDR-05` | Sequential baseline CLI dengan data mock | Baseline script | Belum | - |
| `W4-FEE-09` | Update traceability + dokumentasi kontrak | Dokumen | Belum | - |
| `W4-YAS-08` | UAT scenario payment/status/cancel | Skenario UAT | Belum | - |

### Minggu 5 — Integrasi PHP–Blockchain (Luis)

**Status:** 0 selesai, 3 sebagian, 12 belum dari 15 issue.

| ID | Issue | Output | Status | Bukti / Catatan |
|---|---|---|---|---|
| `W5-LUI-06` | Contract instance read/write dari backend | Service | Belum | - |
| `W5-LUI-07` | Receipt & event parser | Parser | Belum | - |
| `W5-LUI-08` | Persist proof: tx_hash, block_number, contract_address, wallet, blockchain_order_id | DB proof | Sebagian | tx_hash + wallet_address disimpan; field lain belum |
| `W5-LUI-09` | Verification service VERIFIED/MISMATCH | Service verifikasi | Belum | - |
| `W5-LUI-10` | Error handling: RPC down, wrong network, revert, order not found | Error handler | Sebagian | Frontend menangani sebagian error; backend belum |
| `W5-LUI-11` | INT-01 s.d. INT-05 evidence | 5/5 PASS | Belum | 0/5 |
| `W5-FRL-09` | Dukungan RPC/network + troubleshooting | Log dukungan | Belum | - |
| `W5-IHS-05` | Wallet UI + tx status: approval, pending, success, failed | UI | Sebagian | Modal tx di crypto-payment.php sudah ada; belum menyeluruh |
| `W5-IHS-06` | Blockchain proof page | Halaman proof | Belum | - |
| `W5-YAS-09` | Review error/UX flow + UAT integrasi | Catatan UX | Belum | - |
| `W5-FEE-10` | Traceability REQ ke implementasi ke test | Traceability v1 | Belum | - |
| `W5-FDR-06` | Finalisasi schema + queue config + worker skeleton | Worker skeleton | Belum | - |
| `W5-KEN-05` | Integration test independen INT-01 s.d. INT-05 | Test report | Belum | - |
| `W5-ARB-09` | Support bug fixing kontrak | Fix | Belum | Baru relevan setelah INT |
| `W5-AGU-09` | Support bug fixing kontrak | Fix | Belum | Baru relevan setelah INT |

### Minggu 6 — Parallel Processing (Farelly)

**Status:** 0 selesai, 0 sebagian, 13 belum dari 13 issue.

| ID | Issue | Output | Status | Bukti / Catatan |
|---|---|---|---|---|
| `W6-FDR-07` | Job/Queue batch verification | Queue | Belum | Belum ada implementasi queue |
| `W6-FDR-08` | Multi-worker 1/2/4/8 | Worker | Belum | - |
| `W6-FDR-09` | Partition/reduce | Algoritma | Belum | - |
| `W6-FDR-10` | Agregasi finansial: revenue, count, AOV, paid/unpaid, verified/mismatch | Output agregasi | Belum | - |
| `W6-FDR-11` | PAR-01/PAR-02 evidence | 2/2 PASS | Belum | - |
| `W6-KEN-06` | Verifikasi PAR-01/02 + cek race condition & duplikasi | Test report | Belum | Menunggu PAR-01/02 |
| `W6-IHS-07` | Performance dashboard awal | Dashboard | Belum | - |
| `W6-LUI-12` | Dukungan verification service untuk worker | Integrasi worker | Belum | Menunggu worker |
| `W6-FEE-11` | Dokumentasi arsitektur paralel | Dokumen | Belum | - |
| `W6-YAS-10` | Acceptance criteria hasil agregasi | Catatan AC | Belum | - |
| `W6-ARB-10` | Support bug fixing | Fix | Belum | Menunggu bug report |
| `W6-AGU-10` | Support bug fixing | Fix | Belum | Menunggu bug report |
| `W6-FRL-10` | Maintenance environment/deploy ulang | Log | Belum | - |

### Minggu 7 — Benchmark & Integration Test (Kenjiro)

**Status:** 0 selesai, 0 sebagian, 13 belum dari 13 issue.

| ID | Issue | Output | Status | Bukti / Catatan |
|---|---|---|---|---|
| `W7-KEN-07` | Benchmark dataset 100/500/1.000/5.000 x worker 1/2/4/8 | Dataset + hasil | Belum | Menunggu W6 |
| `W7-KEN-08` | Hitung execution time, speedup, efficiency, throughput | Tabel + grafik | Belum | - |
| `W7-KEN-09` | Full regression 21 test case | 21/21 atau FAIL terdokumentasi | Belum | 0/21 |
| `W7-KEN-10` | Bug report + retest | Bug log | Belum | - |
| `W7-KEN-11` | Test report final | Laporan | Belum | - |
| `W7-FDR-12` | Tuning worker/queue + dukungan benchmark | Catatan tuning | Belum | - |
| `W7-IHS-08` | Finalisasi performance + verification dashboard | Dashboard final | Belum | - |
| `W7-FEE-12` | Review konsistensi benchmark + draft final doc | Catatan review | Belum | - |
| `W7-YAS-11` | UAT execution + requirement-to-test final | Bukti UAT | Belum | - |
| `W7-LUI-13` | Perbaikan integrasi dari bug report | Fix | Belum | - |
| `W7-FRL-11` | Reproducibility check di mesin lain | Bukti | Belum | - |
| `W7-ARB-11` | Perbaikan kontrak dari bug report | Fix | Belum | - |
| `W7-AGU-11` | Perbaikan kontrak dari bug report | Fix | Belum | - |

### Minggu 8 — Finalisasi & Demo (semua)

**Status:** 0 selesai, 4 sebagian, 10 belum dari 14 issue.

| ID | Issue | Output | Status | Bukti / Catatan |
|---|---|---|---|---|
| `W8-ALL-01` | Code freeze + tag rilis | Tag repo | Belum | - |
| `W8-ALL-02` | README clean setup + seed data | README | Sebagian | README ada; seed & uji clean-install belum |
| `W8-ALL-03` | Final report + traceability + weekly log | Dokumen | Sebagian | Dokumen ada; final report/traceability/weekly log belum |
| `W8-ALL-04` | Rehearsal demo per role | Jadwal + slide | Belum | - |
| `W8-ALL-05` | Demo end-to-end | Bukti demo | Belum | - |
| `W8-FEE-13` | Final documentation | Laporan final | Sebagian | Dokumentasi ada; belum final |
| `W8-YAS-12` | UAT evidence + demo business flow | Bukti | Belum | - |
| `W8-FRL-12` | Deployment guide + environment | Panduan | Sebagian | Panduan setup + README blockchain ada; reproducibility belum diuji |
| `W8-ARB-12` | Demo core contract | Demo | Belum | - |
| `W8-AGU-12` | Demo state/payment/revert | Demo | Belum | - |
| `W8-LUI-14` | Demo checkout ke chain ke DB ke verification | Demo | Belum | Verification belum ada |
| `W8-FDR-13` | Demo sequential vs parallel | Demo | Belum | - |
| `W8-KEN-12` | Demo test result + benchmark | Demo | Belum | - |
| `W8-IHS-09` | Demo proof page + dashboard | Demo | Belum | - |

## 3. Keputusan yang Sudah Diambil & Blocker Aktif

### 3.1 Keputusan final Minggu 1

| # | Keputusan | Nilai | Bukti |
|---|---|---|---|
| 1 | Framework aplikasi | PHP native | `docs/week1/00_DECISIONS_AND_SCOPE.md` (D1) |
| 2 | Evolusi kontrak | Extend `HSportStore`, interface freeze akhir M3 | `00` (D2) + `04` |
| 3 | Definisi 21 test | Basis escrow + cancel/duplicate/operator, tetap 21 | `00` (D3) + `07` |
| 4 | Tooling blockchain | Truffle + Ganache | `00` (D4) + `06` |
| 5 | Integration engineer | Luis | `00` (D5) |

### 3.2 Blocker aktif

| # | Blocker | Dampak | Aksi | Owner |
|---|---|---|---|---|
| ✅ B1 | MySQL di `localhost:3306` — **selesai** | `migration_blockchain.sql` dan `migration_parallel.sql` sudah dijalankan; kolom/tabel terverifikasi | — | Farrel |
| ✅ B2 | Ganache port 7545 — **selesai** | chainId 1337, networkId 5777, deploy sukses di `0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab` | — | Farrel |
| B3 | Tidak ada `blockchain/test/` | 0/21 test dijalankan | Buat harness Truffle + SC-01 s.d. SC-06 | Arby/Kenjiro |
| B4 | PHP 8.1.10 tidak di PATH; Composer tidak ada | Setup integrasi kurang reproducible | Pakai path absolut + JSON-RPC cURL | Farrel/Luis |
| B5 | Kontrak masih 4 state | W3–W4 belum bisa mulai | Implement target interface, freeze akhir M3 | Arby/Agustina |
| B6 | Integrasi masih client-side | Proof tidak lengkap dan tidak tervalidasi backend | Bangun service backend di M5 | Luis |

## 4. Prioritas 10 Aksi Berikutnya

1. Nyalakan MySQL lalu jalankan `migration_blockchain.sql` dan `migration_parallel.sql`.
2. Nyalakan Ganache, catat chain ID/network ID, dan buat deployment log.
3. Buat `blockchain/test/` + harness Truffle; tulis SC-01 s.d. SC-06.
4. Refactor kontrak ke interface target dan freeze di akhir Minggu 3.
5. Selesaikan SC-07 s.d. SC-12 + security/revert test.
6. Bangun service backend read/write + receipt/event reader + verification.
7. Selesaikan INT-01 s.d. INT-05 dengan bukti tx hash dan block number.
8. Bangun queue/worker paralel + sequential baseline; jalankan PAR-01/PAR-02.
9. Jalankan benchmark PAR-03/PAR-04, lalu regression 21 test.
10. Final report, weekly log, code freeze, dan demo end-to-end.

## 5. Catatan Eksekusi

- Minggu 1 selesai: 17/17 issue memiliki artefak di `docs/week1/` + `migration_parallel.sql` + PR template.
- **Database:** MySQL 8.0.30 aktif; `migration_blockchain.sql` dan `migration_parallel.sql` berhasil dijalankan.
- **Blockchain:** Ganache 7.9.1 aktif di `127.0.0.1:7545` (chainId 1337, networkId 5777); `HSportStore` dideploy di `0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab`; log di `blockchain/deployments/`.
- `Progress Tracker.docx` sudah di-re-baseline: Milestone 1 = Selesai 100%; milestone lain memakai progress berbasis issue.
- `blockchain/README.md` sudah diperbaiki dari Hardhat ke Truffle.
- Kontrak saat ini masih `HSportStore` 4 state; target interface ada di `docs/week1/04_CONTRACT_AND_PARALLEL_DESIGN.md`.
- **Test harness:** `blockchain/test/` sudah ada; baseline smoke 4 PASS, skeleton SC-01..SC-06 6 pending. Belum ada test SC yang diaktifkan; 0/21 test wajib PASS.
- PHP 8.1.10 tidak ada di PATH; Composer tidak terpasang; integrasi direncanakan memakai JSON-RPC cURL.
