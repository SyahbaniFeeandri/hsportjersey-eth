# Minggu 7 — Benchmark & Integration Test

**Milestone:** M7 — Benchmark & Integration Test
**Periode:** 13 November 2026
**Lead:** Kenjiro B.H.G.
**Status milestone:** **Selesai** — 13/13 issue, benchmark 16/16 PASS, regresi 21/21 PASS
**Exit gate:** ✅ benchmark reproducible · ✅ tidak ada bug Sev-1 · ✅ seluruh 21 test punya status

---

## 1. Ringkasan

M7 mengukur performa pipeline paralel yang dibangun pada M6 dan menutup siklus
kualitas proyek: seluruh 21 test case punya status PASS yang dapat direproduksi.

```text
4 dataset (D100/D500/D1000/D5000)
        │
        ▼
run_matrix.php ──► 1 baseline sequential + P=1/2/4/8 per dataset
        │                     │
        │                     ▼
        │            checksum vs sequential (gate MATCH)
        │                     │
        ▼                     ▼
CSV / Markdown / SVG   benchmark_matrix.json
        │
        ▼
PAR-03 (validasi metrik) + PAR-04 (konkurensi, repeatability)
        │
        ▼
regression/run_all.php ──► 12 SC + 5 INT + 4 PAR = 21/21 PASS
```

Hasil utama:

| Metrik | Nilai |
|---|---|
| Konfigurasi benchmark | 16 (4 dataset × 4 jumlah worker) |
| Konfigurasi PASS | **16/16** pada run utama dan run reproduksi |
| Speedup D5000 P=8 | **7,318×** (efficiency 91,5%) |
| Throughput D5000 P=8 | **1.386,58 order/detik** |
| Regression test | **21/21 PASS** (SC 12, INT 5, PAR 4) |
| Bug Sev-1/Sev-2 terbuka | **0** (2 bug ditemukan & ditutup, 1 bug dokumen) |

---

## 2. Status Issue

| ID | Issue | Status | Bukti |
|---|---|---|---|
| `W7-KEN-07` | Benchmark dataset 100/500/1.000/5.000 × worker 1/2/4/8 | ✅ | [`01_BENCHMARK_REPORT.md`](01_BENCHMARK_REPORT.md); 16/16 PASS |
| `W7-KEN-08` | Hitung execution time, speedup, efficiency, throughput | ✅ | [`01_BENCHMARK_REPORT.md`](01_BENCHMARK_REPORT.md) §3; tabel + `benchmark_chart.svg` |
| `W7-KEN-09` | Full regression 21 test case | ✅ | [`02_TEST_REPORT_FINAL.md`](02_TEST_REPORT_FINAL.md); 21/21 PASS |
| `W7-KEN-10` | Bug report + retest | ✅ | [`03_BUG_REPORT_AND_RETEST.md`](03_BUG_REPORT_AND_RETEST.md); BUG-07-01..03 |
| `W7-KEN-11` | Test report final | ✅ | [`02_TEST_REPORT_FINAL.md`](02_TEST_REPORT_FINAL.md) |
| `W7-FDR-12` | Tuning worker/queue + dukungan benchmark | ✅ | [`04_TUNING_NOTES.md`](04_TUNING_NOTES.md) |
| `W7-IHS-08` | Finalisasi performance + verification dashboard | ✅ | [`08_DASHBOARD_FINAL.md`](08_DASHBOARD_FINAL.md); `admin/performance.php` |
| `W7-FEE-12` | Review konsistensi benchmark + draft final doc | ✅ | [`05_CONSISTENCY_REVIEW.md`](05_CONSISTENCY_REVIEW.md) |
| `W7-YAS-11` | UAT execution + requirement-to-test final | ✅ | [`06_UAT_AND_REQUIREMENT_TO_TEST.md`](06_UAT_AND_REQUIREMENT_TO_TEST.md); 17 skenario |
| `W7-LUI-13` | Perbaikan integrasi dari bug report | ✅ | [`03_BUG_REPORT_AND_RETEST.md`](03_BUG_REPORT_AND_RETEST.md) §4; `verify_worker.php` |
| `W7-FRL-11` | Reproducibility check di mesin lain | ✅ | [`07_REPRODUCIBILITY.md`](07_REPRODUCIBILITY.md); `reproducibility_check.json` PASS |
| `W7-ARB-11` | Perbaikan kontrak dari bug report | ✅ | [`03_BUG_REPORT_AND_RETEST.md`](03_BUG_REPORT_AND_RETEST.md) §5; tidak ada bug kontrak |
| `W7-AGU-11` | Perbaikan kontrak dari bug report | ✅ | [`03_BUG_REPORT_AND_RETEST.md`](03_BUG_REPORT_AND_RETEST.md) §5; kontrak tidak berubah |

**Rekap:** 13 selesai, 0 sebagian, 0 belum → **100%**.

---

## 3. Hasil Benchmark (ringkas)

| Dataset | Baseline | P=1 | P=2 | P=4 | P=8 |
|---|---:|---:|---:|---:|---:|
| D100 | 529 ms | 0,841× | 1,374× | 1,781× | 1,734× |
| D500 | 2.647 ms | 0,971× | 1,849× | 3,240× | 4,668× |
| D1000 | 5.271 ms | 0,981× | 1,911× | 3,531× | 5,748× |
| D5000 | 26.387 ms | 0,993× | 1,972× | 3,882× | **7,318×** |

Seluruh 16 konfigurasi menghasilkan checksum yang identik dengan baseline
sequential dataset terkait (`MATCH`). Detail lengkap: [`01_BENCHMARK_REPORT.md`](01_BENCHMARK_REPORT.md).

---

## 4. Cara Menjalankan

Prasyarat: PHP 8.1+, MySQL aktif, `migration_parallel.sql` sudah dijalankan.
Mode A (benchmark) **tidak** memerlukan Ganache. Regression SC/INT memerlukan
Ganache deterministik di `127.0.0.1:7545` dengan kontrak V2 ter-deploy.

```bash
# 1) Benchmark matrix 4 dataset x 4 worker (~105 detik)
php benchmark/run_matrix.php --sizes=100,500,1000,5000 --workers=1,2,4,8 --latency-us=5000

# 2) PAR-03 & PAR-04 (metrik + konkurensi)
php tests/parallel/run_par03_par04.php

# 3) Regresi penuh 21 test (SC + INT + PAR)
php tests/regression/run_all.php

# 4) Reproducibility check (butuh matrix kedua di workspace lain)
php tests/regression/check_reproducibility.php
```

Ganache deterministik:

```bash
cd blockchain
npx ganache --server.port 7545 --wallet.deterministic --chain.chainId 1337 --chain.networkId 5777
npm run migrate    # address tetap 0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab
```

---

## 5. Struktur Artefak

| Path | Isi |
|---|---|
| `benchmark/run_matrix.php` | Orkestrator matrix + CSV/Markdown/SVG |
| `benchmark/parallel_runner.php` | Baseline + spawn worker (pre-assign `--job-id`) + reduce |
| `workers/benchmark_worker.php` | Worker Mode A (`--job-id` opsional) |
| `workers/verify_worker.php` | Worker Mode B (`--job-id` opsional) |
| `tests/parallel/run_par03_par04.php` | Harness PAR-03/PAR-04 (9 check) |
| `tests/regression/run_all.php` | Regression 21 test + `regression_summary.json/.md` |
| `tests/regression/check_reproducibility.php` | Pembanding dua matrix + toleransi |
| `admin/performance.php` | Dashboard final (matrix, chart, regresi) |
| `docs/week7/evidence/` | Seluruh log/JSON/SVG bukti |

---

## 6. Tautan Cepat

- [`01_BENCHMARK_REPORT.md`](01_BENCHMARK_REPORT.md) — tabel & analisis performa
- [`02_TEST_REPORT_FINAL.md`](02_TEST_REPORT_FINAL.md) — status 21 test
- [`03_BUG_REPORT_AND_RETEST.md`](03_BUG_REPORT_AND_RETEST.md) — bug, root cause, retest
- [`04_TUNING_NOTES.md`](04_TUNING_NOTES.md) — tuning worker/queue
- [`05_CONSISTENCY_REVIEW.md`](05_CONSISTENCY_REVIEW.md) — review angka & dokumen
- [`06_UAT_AND_REQUIREMENT_TO_TEST.md`](06_UAT_AND_REQUIREMENT_TO_TEST.md) — UAT-01..17 + REQ
- [`07_REPRODUCIBILITY.md`](07_REPRODUCIBILITY.md) — bukti reproducible
- [`08_DASHBOARD_FINAL.md`](08_DASHBOARD_FINAL.md) — panduan dashboard
- [`evidence/`](evidence/) — log mentah, JSON, CSV, grafik
