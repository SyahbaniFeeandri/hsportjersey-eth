# MINGGU 1 — ANALISIS & SCOPE FREEZE

**Status:** Selesai (exit gate terpenuhi)
**Tanggal:** 9 November 2026
**Lead:** Muhammad Feeandri Syahbani + Yasmin Akmaliyah

> **Update sesi lanjutan:** MySQL, kedua migration, Ganache (`chainId 1337`, `networkId 5777`), deploy kontrak, deployment log, dan `.env.example` sudah dieksekusi dan diverifikasi. Detail: [`06_ENVIRONMENT_INVENTORY.md`](06_ENVIRONMENT_INVENTORY.md) bagian 11.1.

---

## 1. Daftar Artefak

| # | File | Isi | Issue yang ditutup |
|---|---|---|---|
| 00 | [`00_DECISIONS_AND_SCOPE.md`](00_DECISIONS_AND_SCOPE.md) | 5 keputusan, scope in/out, requirement addendum v1.1, sign-off | `W1-FEE-01`, `W1-FEE-02`, `W1-YAS-02` |
| 01 | [`01_ON_OFF_CHAIN_AND_STATE.md`](01_ON_OFF_CHAIN_AND_STATE.md) | On/off-chain matrix, 8 state, transition & revert rules, review Yasmin/Agustina | `W1-FEE-03`, `W1-AGU-01`, `W1-YAS-04` |
| 02 | [`02_BUSINESS_PROCESS_AND_UAT.md`](02_BUSINESS_PROCESS_AND_UAT.md) | Business process map, acceptance criteria, UAT v0.1, error/UX flow | `W1-YAS-01`, `W1-YAS-02`, `W1-YAS-03` |
| 03 | [`03_TRACEABILITY_MATRIX.md`](03_TRACEABILITY_MATRIX.md) | REQ → implementasi → test → owner, 21 test mapping | `W1-FEE-04` |
| 04 | [`04_CONTRACT_AND_PARALLEL_DESIGN.md`](04_CONTRACT_AND_PARALLEL_DESIGN.md) | Gap kontrak, target interface, workload paralel, dataset & benchmark | `W1-ARB-01`, `W1-FDR-01`, `W1-FDR-02` |
| 05 | [`05_INTEGRATION_AND_UI_DESIGN.md`](05_INTEGRATION_AND_UI_DESIGN.md) | Audit integrasi, service design, API contract, gap UI & desain halaman | `W1-LUI-01`, `W1-IHS-01` |
| 06 | [`06_ENVIRONMENT_INVENTORY.md`](06_ENVIRONMENT_INVENTORY.md) | Inventory environment, versi, port, gap, reproducibility checklist | `W1-FRL-01` |
| 07 | [`07_TEST_PLAN.md`](07_TEST_PLAN.md) | Definisi 21 test, harness, test data, defect management, jadwal | `W1-KEN-01` |
| 08 | [`08_GIT_GOVERNANCE.md`](08_GIT_GOVERNANCE.md) | Branch, commit, label, PR, review, release, weekly log | `W1-FEE-05` |
| — | [`../../migration_parallel.sql`](../../migration_parallel.sql) | Tabel `job_batches`, `verification_jobs`, `checkpoints`, `worker_logs`, view benchmark | `W1-FDR-02` |
| — | [`../../.github/PULL_REQUEST_TEMPLATE.md`](../../.github/PULL_REQUEST_TEMPLATE.md) | Template PR | `W1-FEE-05` |

---

## 2. Keputusan yang Dikunci

| # | Keputusan |
|---|---|
| D1 | Tetap **PHP native** |
| D2 | **Extend `HSportStore`**, interface freeze akhir Minggu 3 |
| D3 | 21 test = 12 SC berbasis escrow + cancel/duplicate/operator, 5 INT, 4 PAR |
| D4 | **Truffle + Ganache** |
| D5 | **Luis** sebagai integration engineer |

Detail: [`00_DECISIONS_AND_SCOPE.md`](00_DECISIONS_AND_SCOPE.md).

---

## 3. Exit Gate Minggu 1

| # | Kriteria | Status |
|---|---|---|
| 1 | 5 keputusan tertulis dan disetujui | ✅ |
| 2 | Scope in/out final | ✅ |
| 3 | State diagram 8 state + cancel | ✅ |
| 4 | Business process map + AC + UAT | ✅ |
| 5 | Traceability matrix v0.1 | ✅ |
| 6 | Desain kontrak + workload paralel + dataset | ✅ |
| 7 | Draft schema paralel | ✅ |
| 8 | Audit integrasi + UI | ✅ |
| 9 | Inventory environment | ✅ |
| 10 | Test plan 21 case | ✅ |
| 11 | Git governance + PR template | ✅ |
| 12 | Tracker di-re-baseline | ✅ |

---

## 4. Cara Menggunakan

1. Baca `00_DECISIONS_AND_SCOPE.md` untuk memahami batas proyek.
2. Sebelum menulis kode kontrak, baca `01` dan `04`.
3. Sebelum menulis test, baca `07` dan `03`.
4. Sebelum menulis service/UI, baca `05`.
5. Sebelum setup environment, baca `06`.
6. Sebelum membuka PR, baca `08` dan isi template PR.

---

## 5. Yang Belum Selesai di Minggu 1

Minggu 1 hanya menghasilkan **dokumen desain**. Yang belum dieksekusi:

- ~~Migration database belum dijalankan (MySQL mati saat audit).~~ **Sudah dijalankan.**
- ~~Ganache belum berjalan.~~ **Sudah berjalan; deploy dan deployment log selesai.**
- ~~Belum ada folder `blockchain/test/`.~~ **Harness sudah ada; baseline smoke 4 PASS, SC-01..SC-06 masih pending.**
- Kontrak masih 4 state.
- Belum ada service integrasi backend.
- Belum ada worker/queue.
- Belum ada benchmark.
- Belum ada UI proof/verification/performance dashboard.

Semua item yang belum selesai masuk Minggu 2–7 dan tercatat di [`../GAP_ANALYSIS.md`](../GAP_ANALYSIS.md).

---

## 6. Langkah Berikutnya (Minggu 2)

1. ~~Nyalakan MySQL dan jalankan `migration_blockchain.sql` + `migration_parallel.sql`.~~ **Selesai.**
2. ~~Nyalakan Ganache, catat chain ID/network ID, buat deployment log.~~ **Selesai.**
3. ~~Buat `blockchain/test/` + harness Truffle.~~ **Selesai (smoke + skeleton).**
4. ~~Buat `.env.example`.~~ **Selesai.**
5. Uji reproduksi di komputer anggota lain.
6. Aktifkan SC-01 s.d. SC-06 setelah kontrak target diimplementasikan.

---

## 7. Tautan

- Roadmap: [`../ROADMAP_RINCI.md`](../ROADMAP_RINCI.md)
- Gap analysis: [`../GAP_ANALYSIS.md`](../GAP_ANALYSIS.md)
- Tracker: [`../Progress Tracker.docx`](../Progress%20Tracker.docx)
- Dokumentasi proyek: [`../DOKUMENTASI_PROJECT_DAN_PERAN.md`](../DOKUMENTASI_PROJECT_DAN_PERAN.md)
