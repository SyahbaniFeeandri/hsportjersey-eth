# 08 — Weekly Log (Rekap 9 Anggota × 8 Minggu)

**Milestone:** M8 — Finalisasi & Demo
**Issue terkait:** `W8-ALL-03`, `W8-FEE-13`
**Koordinator:** Muhammad Feeandri Syahbani
**Reviewer:** Yasmin Akmaliyah, Kenjiro B.H.G.
**Tanggal finalisasi:** 16 November 2026
**Status rekap:** **72/72 entri Final (9 anggota × 8 minggu)**

---

## 1. Metode Penyusunan

Log mingguan disusun ulang dari artefak yang dapat diverifikasi:

1. Riwayat commit Git per milestone (`git log --oneline`).
2. Dokumen `docs/week1` … `docs/week7` dan bukti di folder `evidence/`.
3. Status issue pada [`../GAP_ANALYSIS.md`](../GAP_ANALYSIS.md) dan
   [`../ROADMAP_RINCI.md`](../ROADMAP_RINCI.md).
4. Hasil test/UAT final M8.

Setiap entri merujuk minimal satu artefak/commit dan satu bukti test.
Entri W8 ditulis langsung oleh masing-masing peran dan direview pada rehearsal
16 November 2026.

---

## 2. Rekap Kelengkapan Log

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
| Kenjiro B.H.G. | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | **8/8** |
| **Total** | 9 | 9 | 9 | 9 | 9 | 9 | 9 | 9 | **72/72** |

Legenda: ✅ = Final (ada artefak + bukti, direview).

---

## 3. Log Konsolidasi per Minggu

| Minggu | Fokus | Commit / Tag | Artefak & bukti | Hasil |
|---|---|---|---|---|
| W1 | Analisis, scope freeze, tracker | `83c4c79` | `docs/week1/` (17 issue), test plan 21 | 17/17 issue |
| W2 | Environment blockchain & reproduksi | `b171d57`, `f49864b`, `94431a0` | `docs/week2/`, clean clone 2 lingkungan | 16/16 issue |
| W3 | Smart Contract V1 | `ee0ef03`, `bccf789`, `ee2c5b2`, `a2ddffe` | SC-01..SC-06 PASS, deploy V1 | 12/12 issue |
| W4 | Smart Contract V2 | `b91365c`..`b5f65e3`, `d68d09c` | SC-07..SC-12 PASS, cancel UI, baseline sequential | 12/12 issue |
| W5 | Integrasi PHP–blockchain | `741f9f3`, `053cd69` | INT-01..INT-05 PASS, proof page, verification | 15/15 issue |
| W6 | Parallel processing | `3c09f29` | PAR-01/02 24/24 CHECK, worker 1/2/4/8 | 13/13 issue |
| W7 | Benchmark & regresi | `bab416f` | 16/16 matrix, 21/21 regresi, reproducibility | 13/13 issue |
| W8 | Finalisasi & demo | tag **`v1.0.0`** | Clean install, UAT 16/16, demo 13/13, laporan final | 14/14 issue |

---

## 4. Log W8 per Anggota

### 4.1 Muhammad Feeandri Syahbani — Requirement & Documentation Lead

| Field | Isian |
|---|---|
| Target | Final report, traceability, weekly log, code freeze |
| Selesai | `01_FINAL_REPORT.md`, `07_TRACEABILITY_FINAL.md`, `08_WEEKLY_LOG.md`, tag `v1.0.0`, sinkronisasi GAP/ROADMAP |
| Commit/Issue | Commit M8 + tag `v1.0.0`; `W8-ALL-01/03`, `W8-FEE-13` |
| Test/hasil | 112/112 issue, 15/15 requirement, 21/21 regresi |
| Kendala | Angka M7 perlu direkonsiliasi dengan bukti JSON |
| Keputusan | Semua klaim dokumen harus punya file bukti |
| Cross-role | Review bersama Yasmin & Kenjiro |
| Status | **Final** |

### 4.2 Yasmin Akmaliyah — QA / UAT Lead

| Field | Isian |
|---|---|
| Target | UAT business flow + bukti demo |
| Selesai | 16 skenario UAT, `06_UAT_EVIDENCE.md`, `evidence/uat_business_flow.log` |
| Commit/Issue | Commit M8; `W8-YAS-12` |
| Test/hasil | **16/16 PASS**, termasuk checkout #44 dan verifikasi #43 |
| Kendala | Status order ditampilkan dalam bahasa Indonesia ("Dalam Produksi") |
| Keputusan | UAT dijalankan via HTTP nyata, bukan mock |
| Cross-role | Koordinasi dengan Luis (verifikasi) dan Ihsanul (proof) |
| Status | **Final** |

### 4.3 Arby Putra — Smart Contract Engineer

| Field | Isian |
|---|---|
| Target | Demo core contract |
| Selesai | Narasi `createOrder`, escrow, event, guard; verifikasi SC-01..SC-06 |
| Commit/Issue | `ee0ef03`; `W8-ARB-12` |
| Test/hasil | SC 6/6 PASS |
| Kendala | Perlu memastikan chain deterministik saat demo |
| Keputusan | Demo memakai kontrak terdeploy `0xe78A…a8Ab` |
| Cross-role | Dukungan Farrel untuk deploy |
| Status | **Final** |

### 4.4 Agustina Elsi Bunga — Smart Contract Engineer

| Field | Isian |
|---|---|
| Target | Demo state/payment/revert |
| Selesai | Narasi 8 state, `recordPayment`, `shipOrder`, `cancelOrder`, `confirmReceived` |
| Commit/Issue | `b91365c`, `eb90f19`; `W8-AGU-12` |
| Test/hasil | SC-07..SC-12 6/6 PASS |
| Kendala | Double release & transisi tidak sah harus ditunjukkan eksplisit |
| Keputusan | Gunakan matriks revert pada slide |
| Cross-role | Sinkron dengan Kenjiro untuk bukti regresi |
| Status | **Final** |

### 4.5 Muhammad Farrel — Blockchain Environment Engineer

| Field | Isian |
|---|---|
| Target | Deployment guide + environment |
| Selesai | `03_DEPLOYMENT_GUIDE.md`, clean install `h_sport_clean`, HTTP smoke |
| Commit/Issue | `b171d57`, `f49864b`; `W8-FRL-12` |
| Test/hasil | 5/5 langkah exit 0; 7/7 HTTP 200 |
| Kendala | Ganache harus deterministik agar alamat kontrak stabil |
| Keputusan | Migrasi & seed dibuat idempotent |
| Cross-role | Dukung Luis saat INT ulang |
| Status | **Final** |

### 4.6 Luis — Integration Engineer

| Field | Isian |
|---|---|
| Target | Demo checkout → chain → DB → verification + REQ-11 |
| Selesai | Kolom verifikasi + tombol retry di `admin/orders.php`; INT 5/5 |
| Commit/Issue | `741f9f3`, `053cd69`, commit M8; `W8-LUI-14` |
| Test/hasil | VERIFIED → MISMATCH → VERIFIED; INT-05 PASS |
| Kendala | Order lama punya referensi chain dari sesi Ganache sebelumnya |
| Keputusan | Demo memakai order ber-chain terbaru (#43) |
| Cross-role | Koordinasi dengan Ihsanul untuk proof page |
| Status | **Final** |

### 4.7 Ihsanul Farrel — UI/UX & Dashboard

| Field | Isian |
|---|---|
| Target | Demo proof page + dashboard |
| Selesai | Narasi `blockchain-proof.php`, dashboard admin, performance dashboard |
| Commit/Issue | `053cd69`, `3c09f29`; `W8-IHS-09` |
| Test/hasil | Demo langkah 9 & 12 PASS; UAT-15 PASS |
| Kendala | Data proof bergantung pada order ber-chain |
| Keputusan | Tampilkan pesan jelas bila data on-chain belum ada |
| Cross-role | Dukungan Luis untuk data INT |
| Status | **Final** |

### 4.8 Farelly De Rossi — Parallel Processing Engineer

| Field | Isian |
|---|---|
| Target | Demo sequential vs parallel |
| Selesai | Narasi partisi/reduce, worker 1/2/4/8, checksum, speedup |
| Commit/Issue | `3c09f29`; `W8-FDR-13` |
| Test/hasil | PAR-01/02 24/24 CHECK; D5000 P=8 7,318× |
| Kendala | Worker idle race pernah muncul (BUG-07-01, sudah ditutup) |
| Keputusan | Tunjukkan checksum identik sebagai gate utama |
| Cross-role | Dukungan Kenjiro untuk benchmark |
| Status | **Final** |

### 4.9 Kenjiro B.H.G. — Test & Benchmark Lead

| Field | Isian |
|---|---|
| Target | Demo test result + benchmark |
| Selesai | Regresi final 21/21, ringkasan benchmark, bukti JSON |
| Commit/Issue | `3c09f29`, `bab416f`; `W8-KEN-12` |
| Test/hasil | 21/21 PASS (47,19 s); 16/16 matrix PASS |
| Kendala | Suite SC/INT gagal eksplisit bila Ganache mati |
| Keputusan | Tidak ada silent skip; semua test punya status |
| Cross-role | Validasi bersama Feeandri |
| Status | **Final** |

---

## 5. Ringkasan Bukti per Minggu

| Minggu | Bukti utama |
|---|---|
| W1 | `docs/week1/README.md`, `07_TEST_PLAN.md`, `03_TRACEABILITY_MATRIX.md` |
| W2 | `docs/week2/README.md`, `evidence/reproduction_second_env.log` |
| W3 | `docs/week3/02_TEST_REPORT_V1.md`, `evidence/sc01_sc06_test.log` |
| W4 | `docs/week4/`, `evidence/sc07_sc12_test.log`, `evidence/cancel_ui_smoke.log` |
| W5 | `docs/week5/`, `evidence/int01_int05_test.log` |
| W6 | `docs/week6/`, `evidence/par01_par02_test.json` |
| W7 | `docs/week7/`, `benchmark_matrix.json`, `regression_summary.json` |
| W8 | `docs/week8/`, `evidence/clean_install*.log`, `final_regression.log`, `uat_business_flow.log`, `demo_http.log` |

---

## 6. Aturan & Catatan

1. Log diisi minimal satu kali per minggu dan direview anggota lain sebelum
   dinyatakan `Final`.
2. Setiap log memuat commit/issue dan bukti test.
3. Log kosong tidak dihitung dalam komponen penilaian individu.
4. Rekap ini menggantikan tabel `0/8` pada
   [`../TRACKER_SECTION_4_5.md`](../TRACKER_SECTION_4_5.md) §5.2.
5. Jika diperlukan audit rinci per commit, gunakan `git log --oneline` dan
   folder `docs/week*/`.
