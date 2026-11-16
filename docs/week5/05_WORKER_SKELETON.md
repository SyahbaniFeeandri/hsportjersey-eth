# 05 — SKEMA QUEUE & WORKER SKELETON

**Issue:** `W5-FDR-06`
**Pemilik:** Farelly De Rossi
**Status:** ✅ Selesai (skeleton; paralel penuh di M6)
**Tanggal:** 12 November 2026

---

## 1. Skema

Empat tabel dari `migration_parallel.sql`:

| Tabel | Peran |
|---|---|
| `job_batches` | Satu batch benchmark/verifikasi; `worker_count`, metrik, checksum |
| `verification_jobs` | Satu partisi order per worker; status, lease, attempts |
| `checkpoints` | Hasil parsial per job; dibaca reducer |
| `worker_logs` | Audit log per worker/job |

Konfigurasi terpusat: `config/parallel.php`.

| Parameter | Default |
|---|---|
| `worker_counts` | 1, 2, 4, 8 |
| `default_partition_size` | 500 order |
| `lease_seconds` | 300 |
| `max_attempts` | 3 |
| `modes` | `ALGORITHM`, `INTEGRATION` |

---

## 2. Worker Skeleton

`workers/verify_worker.php`:

1. Connect MySQL (env `DB_*`).
2. Claim satu job atomik:
   ```sql
   SELECT * FROM verification_jobs
   WHERE status = 'PENDING'
   ORDER BY partition_no
   LIMIT 1
   FOR UPDATE SKIP LOCKED;
   ```
3. Tandai `RUNNING`, catat `worker_id`, `attempts+1`, `leased_at`.
4. Verifikasi setiap order dalam `range_start..range_end` memakai
   `VerificationService::verifyOrder()`.
5. Tulis `checkpoints` (idempoten per `job_id`) dan `worker_logs`.
6. Tandai job `DONE`; bila error, kembalikan ke `PENDING` atau `DEAD`
   setelah `max_attempts`.

Perintah:

```bash
php workers/verify_worker.php --dry-run
php workers/verify_worker.php --worker=w1
php workers/verify_worker.php --worker=w2 --batch=12
```

---

## 3. Bukti Smoke

Batch uji `WK-SMOKE-*` dengan dua partisi dijalankan dua worker:

| Job | Partisi | Worker | Status | Processed | Verified |
|---|---|---|---|---:|---:|
| #1 | 1 (1..20) | `w-smoke-1` | DONE | 14 | 0 |
| #2 | 2 (21..40) | `w-smoke-2` | DONE | 7 | 1 |

Checkpoint dan `worker_logs` terisi; batch uji dihapus setelah verifikasi
(cascade). Order tanpa `blockchain_order_id` dihitung `PENDING` — perilaku ini
diharapkan dan akan dipisahkan sebagai `SKIPPED` pada M6.

---

## 4. Yang Ditinggalkan untuk M6

1. Menjalankan beberapa worker paralel dan mengukur speedup.
2. Reducer agregasi + perbandingan checksum dengan baseline sequential.
3. Lease recovery untuk worker yang mati (`leased_at` kadaluarsa).
4. Partisi berbasis jumlah order (bukan hanya rentang ID).
5. Klasifikasi `SKIPPED` terpisah dari `PENDING`.
6. Dashboard performa membaca `v_benchmark_summary`.
