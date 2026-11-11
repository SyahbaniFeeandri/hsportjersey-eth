# 05 — SKEMA DATABASE PARALEL: FINAL

**Issue:** `W3-FDR-04`
**Pemilik:** Farelly De Rossi
**Reviewer:** Kenjiro, Feeandri
**Status:** **Final** — sudah diterapkan dan diverifikasi di MySQL 8.0.30
**Tanggal:** 11 November 2026

---

## 1. Ringkasan

Skema paralel yang didraft di Minggu 1 (`W1-FDR-02`) difinalkan dan diterapkan ke database `h_sport_db`. Skema ini menjadi fondasi verifikasi order DB-vs-chain secara paralel di Minggu 6–7.

| Item | Nilai |
|---|---|
| File migration | `migration_parallel.sql` |
| Database | `h_sport_db` |
| MySQL | 8.0.30 |
| Tabel | 4 (`job_batches`, `verification_jobs`, `checkpoints`, `worker_logs`) |
| View | 1 (`v_benchmark_summary`, 23 kolom) |
| Status tabel | Terpasang, 0 baris (belum ada batch) |
| Sifat migration | Idempotent; tidak ada `DROP TABLE` |

Bukti verifikasi:

```text
checkpoints          BASE TABLE
job_batches          BASE TABLE
v_benchmark_summary  VIEW
verification_jobs    BASE TABLE
worker_logs          BASE TABLE
view_cols            23
```

---

## 2. Tabel Final

### 2.1 `job_batches`

Satu baris = satu batch benchmark/verifikasi.

| Kolom | Tipe | Peran |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | Identitas batch |
| `batch_code` | VARCHAR(64) UNIQUE | Kode unik batch |
| `mode` | ENUM(`ALGORITHM`,`INTEGRATION`) | Mode benchmark |
| `dataset_name`, `dataset_size`, `dataset_hash` | VARCHAR/INT/CHAR(64) | Identitas dataset |
| `worker_count` | TINYINT UNSIGNED | 1 = baseline sequential |
| `is_baseline`, `parent_batch_id` | TINYINT(1), BIGINT FK | Relasi batch paralel ke baseline |
| `status` | ENUM(`PENDING`,`RUNNING`,`DONE`,`FAILED`,`CANCELLED`) | Status batch |
| `total_orders`, `verified_count`, `mismatch_count`, `error_count` | INT UNSIGNED | Agregat |
| `revenue_all`, `revenue_verified`, `aov` | DECIMAL(18,2/4) | Agregat finansial |
| `checksum` | CHAR(64) | Correctness gate |
| `duration_ms`, `started_at`, `finished_at` | BIGINT/DATETIME | Pengukuran waktu |
| `notes`, `created_at`, `updated_at` | TEXT/TIMESTAMP | Audit |

Index: `status`, (`dataset_name`,`dataset_size`), `worker_count`, `parent_batch_id`.

### 2.2 `verification_jobs`

Satu baris = satu partisi order yang diklaim satu worker.

| Kolom | Tipe | Peran |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | Identitas job |
| `batch_id`, `partition_no` | BIGINT FK, SMALLINT | **UNIQUE** (`batch_id`,`partition_no`) |
| `range_start`, `range_end` | BIGINT UNSIGNED | Rentang order |
| `status` | ENUM(`PENDING`,`RUNNING`,`DONE`,`FAILED`,`DEAD`,`CANCELLED`) | Status klaim |
| `worker_id` | VARCHAR(64) | Pemilik lease |
| `attempts`, `max_attempts` | TINYINT UNSIGNED | Retry (default maks. 3) |
| `leased_at`, `started_at`, `finished_at` | DATETIME | Lease & eksekusi |
| `error_message` | TEXT | Diagnostik |

Index: `status`, `worker_id`, (`batch_id`,`status`). FK `batch_id` → `job_batches` (`ON DELETE CASCADE`).

### 2.3 `checkpoints`

Hasil parsial per partisi; dibaca reducer.

| Kolom | Tipe | Peran |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | Identitas |
| `batch_id`, `job_id` | FK | Relasi batch/job |
| `partition_no` | SMALLINT | Nomor partisi |
| `processed_count`, `verified_count`, `mismatch_count`, `error_count` | INT UNSIGNED | Hasil partisi |
| `revenue_all`, `revenue_verified` | DECIMAL(18,2) | Agregat |
| `checksum` | CHAR(64) | Checksum partisi |
| `payload` | JSON | Detail opsional |
| `created_at` | TIMESTAMP | Audit |

**UNIQUE** `job_id` — satu checkpoint per job. Index (`batch_id`, `partition_no`). FK cascade ke batch dan job.

### 2.4 `worker_logs`

Log per batch/job/worker untuk audit dan troubleshooting.

| Kolom | Tipe | Peran |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | Identitas |
| `batch_id` | BIGINT FK | Batch |
| `job_id` | BIGINT FK NULL | Job (opsional) |
| `worker_id` | VARCHAR(64) | Worker |
| `level` | ENUM(`DEBUG`,`INFO`,`WARN`,`ERROR`) | Level log |
| `message` | TEXT | Pesan |
| `context` | JSON | Konteks terstruktur |
| `created_at` | TIMESTAMP | Waktu |

Index: `batch_id`, `job_id`, `worker_id`, `level`. FK batch cascade; FK job `ON DELETE SET NULL`.

### 2.5 `v_benchmark_summary`

View agregat yang menghitung:

| Kolom turunan | Rumus |
|---|---|
| `throughput_per_sec` | `total_orders / (duration_ms / 1000)` |
| `speedup` | `baseline.duration_ms / batch.duration_ms` |
| `efficiency` | `speedup / worker_count` |
| `correctness_gate` | `MATCH` bila checksum sama dengan baseline `DONE`, selain itu `DIFFERENT` |

Baseline di-join berdasarkan (`dataset_name`, `dataset_size`, `mode`, `is_baseline=1`, `status='DONE'`).

---

## 3. Klaim Job oleh Worker

Pola yang dipakai (MySQL 8, `SKIP LOCKED`):

```sql
START TRANSACTION;

SELECT id
FROM verification_jobs
WHERE batch_id = :batch_id
  AND status = 'PENDING'
ORDER BY partition_no
LIMIT 1
FOR UPDATE SKIP LOCKED;

UPDATE verification_jobs
SET status      = 'RUNNING',
    worker_id   = :worker_id,
    leased_at   = NOW(),
    started_at  = COALESCE(started_at, NOW()),
    attempts    = attempts + 1
WHERE id = :job_id;

COMMIT;
```

Aturan:

1. Klaim dan update berada dalam satu transaksi.
2. `SKIP LOCKED` mencegah dua worker mengambil partisi yang sama.
3. `attempts` dinaikkan saat klaim; job dengan `attempts >= max_attempts` dipindah ke `DEAD` oleh supervisor.
4. Job `RUNNING` yang lease-nya kedaluwarsa dikembalikan ke `PENDING` (atau `DEAD` bila habis retry).
5. Hasil akhir ditulis sebagai satu baris `checkpoints` unik per `job_id`, lalu job menjadi `DONE`.

---

## 4. Reduksi dan Correctness Gate

1. Reducer hanya membaca checkpoint dari job berstatus `DONE` pada batch terkait.
2. Checkpoint diurutkan berdasarkan `partition_no` sebelum checksum digabung — menjaga determinisme.
3. Agregat batch dijumlahkan ke `job_batches` (`verified_count`, `mismatch_count`, `revenue_*`, `checksum`, `duration_ms`).
4. `v_benchmark_summary.correctness_gate` harus `MATCH` terhadap baseline sequential.
5. Bila `DIFFERENT`, benchmark dihentikan dan tidak dilaporkan sebagai hasil valid.

---

## 5. Perubahan dari Draft Minggu 1

| Perubahan | Alasan |
|---|---|
| Tambah `mode`, `dataset_name`, `is_baseline`, `parent_batch_id` | Memisahkan Mode A/B dan mengaitkan batch paralel ke baseline |
| Tambah `max_attempts`, `leased_at` | Mendukung lease, retry, dan pemulihan worker mati |
| Tambah `payload` pada `checkpoints` | Menyimpan detail mismatch tanpa tabel tambahan |
| Tambah `notes`, `updated_at` | Audit operasional |
| Tambah `v_benchmark_summary` | Speedup/efficiency/correctness dihitung konsisten, tidak manual |
| Index komposit (`batch_id`,`status`) | Mempercepat klaim job |
| Nama kolom agregat diselaraskan (`verified_count`, `mismatch_count`) | Konsisten dengan metrik benchmark |

Yang **tidak berubah**: empat tabel inti, peran `checkpoints` sebagai hasil partisi, dan pemisahan log.

---

## 6. Verifikasi

Perintah:

```bash
mysql -u root -e "USE h_sport_db; SHOW TABLES LIKE '%job%'; SHOW TABLES LIKE 'checkpoints'; SHOW TABLES LIKE 'worker_logs'; SELECT COUNT(*) FROM job_batches;"
```

Hasil: empat tabel ada, view ada (23 kolom), `job_batches` dan `verification_jobs` berisi 0 baris karena benchmark belum dijalankan.

Yang belum diverifikasi di M3 (memang di luar lingkup):

- Klaim paralel nyata dengan >1 worker.
- Perilaku lease kedaluwarsa.
- Reduksi checksum end-to-end.

Semua itu masuk M6/M7 dan akan memakai skema ini tanpa perubahan struktur.

---

## 7. Catatan Operasional

1. Jalankan baseline sequential lebih dulu (`worker_count=1`, `is_baseline=1`).
2. Batch paralel mengisi `parent_batch_id` ke baseline dengan dataset yang sama.
3. Dataset harus deterministik: seed + `dataset_hash` dicatat.
4. Jangan mengubah `worker_count` pada batch yang sudah `RUNNING`.
5. Migration aman dijalankan ulang; view dibuat ulang dengan `DROP VIEW IF EXISTS`, tabel tidak di-drop.

---

## 8. Tautan

- Draft desain: [`../week1/04_CONTRACT_AND_PARALLEL_DESIGN.md`](../week1/04_CONTRACT_AND_PARALLEL_DESIGN.md) §10
- Migration: [`../../migration_parallel.sql`](../../migration_parallel.sql)
- Test plan paralel: [`../week1/07_TEST_PLAN.md`](../week1/07_TEST_PLAN.md) §7.4
