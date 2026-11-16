# Minggu 6 — Parallel Processing

**Milestone:** M6 — Parallel Processing
**Periode:** 12–13 November 2026
**Lead:** Farelly De Rossi
**Status milestone:** **Selesai** — 13/13 issue, PAR-01/PAR-02 24/24 CHECK PASS
**Exit gate:** ✅ output sequential = parallel; worker 1/2/4/8 memproses job berbeda

---

## 1. Ringkasan

M6 mengubah verifikasi order dari satu proses sequential menjadi pipeline
paralel berbasis queue di MySQL:

```text
order / dataset
      │
      ▼
enqueue_batch.php  ──►  job_batches (1 batch, P partisi)
      │                       │
      │                       ▼
      │                verification_jobs (1 job per partisi)
      │                       │
      ▼                       ▼
parallel_runner.php ──►  worker 1..P  ──►  checkpoints (1 per job)
                                                  │
                                                  ▼
                                          reduce_batch.php
                                                  │
                                                  ▼
                              agregasi + checksum vs baseline sequential
                                                  │
                                                  ▼
                                    admin/performance.php (dashboard)
```

Dua mode yang didukung:

| Mode | Sumber order | Verifikasi | Dipakai untuk |
|---|---|---|---|
| `ALGORITHM` | Dataset mock deterministik (`benchmark/datasets/`) | `MockChainAdapter` + latensi deterministik | PAR-01/PAR-02, speedup |
| `INTEGRATION` | Tabel `orders` nyata | `VerificationService` + kontrak/Ganache | smoke integrasi worker |

Pada Mode A, latensi per pembacaan order dibuat deterministik
(`--latency-us`) supaya speedup terukur tanpa bergantung pada Ganache. Pada
Mode B, pekerjaan nyata (RPC + DB) yang menjadi sumber biaya.

---

## 2. Status Issue

| ID | Issue | Status | Bukti |
|---|---|---|---|
| `W6-FDR-07` | Job/queue batch verification | ✅ | [`workers/enqueue_batch.php`](../../workers/enqueue_batch.php); 1 batch → P job |
| `W6-FDR-08` | Multi-worker 1/2/4/8 | ✅ | [`benchmark/parallel_runner.php`](../../benchmark/parallel_runner.php); `distinct_workers = jobs_total` untuk P=1/2/4/8 |
| `W6-FDR-09` | Partition/reduce | ✅ | [`PartitionPlanner.php`](../../benchmark/lib/PartitionPlanner.php), [`Reducer.php`](../../benchmark/lib/Reducer.php) |
| `W6-FDR-10` | Agregasi finansial | ✅ | [`02_PAR_EVIDENCE.md`](02_PAR_EVIDENCE.md) §4 |
| `W6-FDR-11` | PAR-01/PAR-02 evidence | ✅ | [`02_PAR_EVIDENCE.md`](02_PAR_EVIDENCE.md); 24/24 PASS |
| `W6-KEN-06` | Verifikasi independen + race condition | ✅ | [`03_RACE_CONDITION_REVIEW.md`](03_RACE_CONDITION_REVIEW.md); RACE-01/02 PASS |
| `W6-IHS-07` | Performance dashboard awal | ✅ | [`05_DASHBOARD.md`](05_DASHBOARD.md); `admin/performance.php` |
| `W6-LUI-12` | Dukungan verification service untuk worker | ✅ | [`07_WORKER_INTEGRATION.md`](07_WORKER_INTEGRATION.md) |
| `W6-FEE-11` | Dokumentasi arsitektur paralel | ✅ | [`01_PARALLEL_ARCHITECTURE.md`](01_PARALLEL_ARCHITECTURE.md) |
| `W6-YAS-10` | Acceptance criteria hasil agregasi | ✅ | [`04_ACCEPTANCE_CRITERIA.md`](04_ACCEPTANCE_CRITERIA.md) |
| `W6-ARB-10` | Support bug fixing | ✅ | [`06_SUPPORT_LOG.md`](06_SUPPORT_LOG.md) (tidak ada bug kontrak) |
| `W6-AGU-10` | Support bug fixing | ✅ | [`06_SUPPORT_LOG.md`](06_SUPPORT_LOG.md) (tidak ada bug kontrak) |
| `W6-FRL-10` | Maintenance environment/deploy ulang | ✅ | [`06_SUPPORT_LOG.md`](06_SUPPORT_LOG.md) §3 |

**Rekap:** 13 selesai, 0 sebagian, 0 belum → **100%**.

---

## 3. Hasil utama

| Uji | Dataset | Hasil |
|---|---|---|
| PAR-01 | D500 | 500 order; V/M/E = 350/100/50; revenue Rp 612.423.000; checksum `61cfe1d0…` |
| PAR-02 | D500 | Checksum MATCH untuk P=1/2/4/8; partisi menutup 1..500; `max_attempts=1` |
| PAR-02 | D1000 | Checksum `1258b0c3…` MATCH; speedup 0,989 / 1,957 / 3,636 / 6,095 |
| RACE-01 | — | Lease recovery memulihkan job `RUNNING` kadaluarsa |
| RACE-02 | — | Reducer idempoten (checksum sama, checkpoint tidak bertambah) |
| Integrasi | 21 order | 2 worker berbeda; MISMATCH=1, SKIPPED=20, ERROR=0 |

Bukti mentah ada di [`evidence/`](evidence/):

- `par01_par02_test.log` / `.json` — suite 24/24 PASS
- `par01_sequential.json` / `par02_parallel.json` — ringkasan per run
- `par_d1000.log` / `.json` — benchmark D1000 P=1/2/4/8
- `int_m6_smoke.json` — reducer batch integrasi #39

---

## 4. Cara Menjalankan

Prasyarat: PHP 8.1+, MySQL aktif, dan `migration_parallel.sql` sudah
dijalankan. Ganache **tidak** diperlukan untuk Mode A.

```bash
# Suite PAR-01/PAR-02 + race condition (24 check)
php tests/parallel/run_par.php --latency-us=5000 --workers=1,2,4,8

# Benchmark D1000 saja
php benchmark/parallel_runner.php \
  --dataset=benchmark/datasets/D1000.json \
  --workers=1,2,4,8 --latency-us=5000 \
  --json-out=docs/week6/evidence/par_d1000.json

# Mode B: batch dari order nyata lalu jalankan worker + reducer
php workers/enqueue_batch.php --workers=2 --from-id=1 --to-id=40
php workers/verify_worker.php --batch=39 --worker=w1 --loop
php workers/verify_worker.php --batch=39 --worker=w2 --loop
php workers/reduce_batch.php --batch=39
```

Dashboard: buka `admin/performance.php` sebagai admin.

---

## 5. Struktur artefak

| Path | Isi |
|---|---|
| `benchmark/lib/Checksum.php` | Format checksum tunggal sequential/parallel |
| `benchmark/lib/PartitionPlanner.php` | Partisi contiguous 1..N |
| `benchmark/lib/Reducer.php` | Agregasi, checksum, idempotensi |
| `benchmark/parallel_runner.php` | Orkestrator baseline + spawn worker + reduce |
| `workers/benchmark_worker.php` | Worker Mode A |
| `workers/verify_worker.php` | Worker Mode B (DB vs chain) |
| `workers/enqueue_batch.php` | Pembuat batch + partisi |
| `workers/reduce_batch.php` | Reducer CLI |
| `tests/parallel/run_par.php` | Harness PAR-01/02 + race |
| `admin/performance.php` | Dashboard performa |
| `config/parallel.php` | Lease, max attempts, ukuran partisi, mode |
| `migration_parallel.sql` | Skema queue + view `v_benchmark_summary` |
