# Review Race Condition & Integritas Data

**Issue:** `W6-KEN-06`
**Pemilik review:** Kenjiro B.H.G.
**Implementasi:** Farelly De Rossi
**Tanggal:** 13 November 2026
**Status:** ✅ Tidak ditemukan race condition yang menggagalkan gate; RACE-01/02 PASS

Dokumen ini mencatat verifikasi independen terhadap pipeline paralel M6:
skenario konkurensi yang diuji, mekanisme pengaman, dan bukti hasilnya.

---

## 1. Skenario yang diperiksa

| # | Skenario | Risiko | Pengaman |
|---|---|---|---|
| R1 | Dua worker mengklaim job yang sama | Order diproses dua kali, checkpoint ganda | `SELECT … FOR UPDATE SKIP LOCKED` + transaksi klaim |
| R2 | Worker mati setelah klaim, job tetap RUNNING | Partisi tidak selesai, batch macet | Lease `leased_at` + `--lease-recovery` |
| R3 | Worker mengulang job yang sudah DONE | Checkpoint dobel | `UNIQUE(job_id)` + upsert |
| R4 | Dua partisi tumpang tindih | Order dobel / hilang | Partisi contiguous + validasi `coversAll` |
| R5 | Reducer dijalankan dua kali | Checksum berubah / checkpoint bertambah | Reduce idempoten, tidak menulis checkpoint |
| R6 | Checkpoint korup/tidak lengkap | Metrik tidak sesuai payload | `metrics_consistent` + `duplicate_ids` |

---

## 2. Mekanisme pengamanan

### 2.1 Klaim atomik

```sql
SELECT * FROM verification_jobs
WHERE status = 'PENDING' AND batch_id = ?
ORDER BY partition_no ASC
LIMIT 1
FOR UPDATE SKIP LOCKED;
```

Baris dikunci di dalam transaksi, lalu diubah menjadi `RUNNING` dan
`attempts = attempts + 1` sebelum commit. Worker kedua yang berjalan
bersamaan akan melewati baris terkunci dan mengambil job berikutnya.

### 2.2 Batas unik di level database

| Constraint | Efek |
|---|---|
| `uq_verification_jobs_partition (batch_id, partition_no)` | Satu partisi hanya boleh punya satu job per batch |
| `uq_checkpoints_job (job_id)` | Satu job maksimal punya satu checkpoint |
| `uq_job_batches_code (batch_code)` | Tidak ada batch kembar |

Dengan constraint ini, bahkan bila logika aplikasi salah, database menolak
duplikasi struktural.

### 2.3 Lease recovery

Saat worker dijalankan dengan `--lease-recovery`:

```sql
UPDATE verification_jobs
SET status = 'PENDING', worker_id = NULL, leased_at = NULL,
    error_message = CONCAT(..., ' [lease recovered by ? at NOW()]')
WHERE status = 'RUNNING'
  AND leased_at IS NOT NULL
  AND leased_at < (NOW() - INTERVAL ? SECOND);
```

Job yang ditinggal worker mati kembali ke antrean dan dapat dikerjakan ulang.
`attempts` tetap bertambah, sehingga percobaan berulang terlihat di audit.

### 2.4 Integritas partisi

`PartitionPlanner::plan()` membagi rentang secara berurutan dari ID 1.
Harness memeriksa ulang seluruh rentang hasil run: harus mulai dari 1,
bersambung, dan berakhir tepat di N. Pemeriksaan ini menangkap gap maupun
overlap tanpa mempercayai metadat partisi.

### 2.5 Idempotensi reducer

Reducer tidak pernah menulis ke `checkpoints`; ia hanya membaca. Penulisan
hanya terjadi ke `job_batches` (UPDATE) dan `worker_logs` (INSERT audit).
`duration_ms` dipertahankan dari run pertama agar reduce ulang tidak
mengubah wall-clock.

---

## 3. Bukti eksekusi

### RACE-01 — Lease recovery

Setup: satu job `RUNNING` dengan `leased_at = NOW() - 600 SECOND`, worker
`crashed-worker`, `attempts = 1`.

Hasil:

```text
status=DONE  worker=recovery-test  attempts=2  checkpoints=1
```

Job dipulihkan ke PENDING, diklaim worker baru, diselesaikan, dan hanya
menghasilkan **satu** checkpoint. ✅ PASS

### RACE-02 — Idempotensi reducer

Reducer dijalankan dua kali pada batch yang sama (P=8, D500).

```text
checksum1=61cfe1d0d9769ea1
checksum2=61cfe1d0d9769ea1
checkpoints=8 -> 8
```

Checksum tidak berubah dan jumlah checkpoint tetap. ✅ PASS

### Pemeriksaan per jumlah worker

Untuk setiap P ∈ {1, 2, 4, 8}:

| Pemeriksaan | Hasil |
|---|---|
| `jobs_done == jobs_total` | ✅ |
| `distinct_workers == jobs_total` (setiap job oleh worker berbeda) | ✅ |
| `max_attempts == 1` (tidak ada job diulang) | ✅ |
| `COUNT(checkpoints) == jobs_total` | ✅ |
| `COUNT(jobs WHERE attempts > 1) == 0` | ✅ |
| `COUNT(DISTINCT worker_id) == jobs_total` | ✅ |
| `duplicate_ids` kosong | ✅ |

---

## 4. Temuan & residual risk

| # | Temuan | Tingkat | Tindakan |
|---|---|---|---|
| T1 | Lease recovery hanya berjalan bila worker dipanggil dengan `--lease-recovery` atau config `lease_recovery_on_start=true` | Rendah | Didokumentasikan; default aman untuk benchmark yang diawasi |
| T2 | `skipped_count` tidak punya kolom khusus di `job_batches` | Informasi | Disimpan di `notes`/payload reducer; tidak memengaruhi gate |
| T3 | Reducer mengandalkan payload JSON berisi seluruh hasil order | Rendah | Ukuran masih wajar untuk D5000; bila membesar, pertimbangkan tabel hasil terpisah di M7 |
| T4 | Job `DEAD` tidak otomatis di-retry oleh runner | Rendah | Sesuai desain: kegagalan permanen harus terlihat, bukan disembunyikan |

Tidak ada temuan tingkat **Critical**, **High**, atau **Medium** pada pipeline
M6. Tidak ada order hilang, dobel, atau berubah status antar-run.

---

## 5. Kesimpulan

1. Klaim job atomik terbukti: `distinct_workers = jobs_total` dan
   `max_attempts = 1` pada semua P.
2. Partisi menutup 1..N tanpa gap/overlap.
3. Checkpoint unik per job; reduce ulang idempoten.
4. Lease recovery memulihkan job yang ditinggal worker mati.

**Status W6-KEN-06: PASS.** Bukti mentah ada di
[`evidence/par01_par02_test.log`](evidence/par01_par02_test.log) dan
[`evidence/par01_par02_test.json`](evidence/par01_par02_test.json).
