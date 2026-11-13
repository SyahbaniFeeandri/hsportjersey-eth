# Arsitektur Parallel Processing

**Issue:** `W6-FEE-11`
**Pemilik:** Feeandri
**Kontributor teknis:** Farelly (desain & implementasi), Kenjiro (verifikasi)
**Status:** Selesai

Dokumen ini menjelaskan desain paralelisasi verifikasi order, batas
konsistensinya, dan alasan di balik keputusan teknis M6.

---

## 1. Tujuan & ruang lingkup

Verifikasi order membandingkan data DB dengan state on-chain. Pekerjaan ini
terdiri dari banyak pembacaan independen per order, sehingga cocok
diparalelkan. Target M6:

1. Membagi N order menjadi P partisi tanpa gap/overlap.
2. Menjalankan P worker yang **tidak** mengerjakan order yang sama.
3. Menggabungkan hasil menjadi satu ringkasan finansial yang identik dengan
   eksekusi sequential.
4. Membuktikan kesamaan hasil lewat checksum, bukan hanya hitungan agregat.

Yang **tidak** termasuk ruang lingkup M6: tuning performa produksi
(M7), benchmark dataset besar 5.000 order (M7), dan cluster multi-mesin.

---

## 2. Komponen

| Komponen | Tanggung jawab |
|---|---|
| `benchmark/lib/PartitionPlanner.php` | Membagi 1..N menjadi P partisi contiguous |
| `workers/enqueue_batch.php` | Membuat `job_batches` + `verification_jobs` dari rentang order nyata |
| `benchmark/parallel_runner.php` | Menjalankan baseline, membuat batch, spawn worker, reduce, gate |
| `workers/benchmark_worker.php` | Worker Mode A (dataset mock deterministik) |
| `workers/verify_worker.php` | Worker Mode B (DB vs kontrak nyata) |
| `benchmark/lib/Reducer.php` | Menggabungkan checkpoint, agregasi, checksum, gate |
| `benchmark/lib/Checksum.php` | Format baris + checksum tunggal |
| `workers/reduce_batch.php` | CLI reducer |
| `admin/performance.php` | Visualisasi hasil benchmark |

Semua worker berbagi skema antrean yang sama di `migration_parallel.sql`.

---

## 3. Model data antrean

```text
job_batches
  id, batch_code, mode, dataset_name, dataset_size, dataset_hash,
  worker_count, is_baseline, status, total_orders,
  verified_count, mismatch_count, error_count,
  revenue_all, revenue_verified, aov, checksum, duration_ms,
  started_at, finished_at, notes

verification_jobs
  id, batch_id, partition_no, range_start, range_end,
  status, worker_id, attempts, max_attempts, leased_at, started_at, finished_at

checkpoints
  id, batch_id, job_id, partition_no,
  processed_count, verified_count, mismatch_count, error_count,
  revenue_all, revenue_verified, checksum, payload,
  UNIQUE(job_id)

worker_logs
  id, batch_id, job_id, worker_id, level, message, context, created_at
```

Aturan penting:

- **Satu job = satu partisi = satu checkpoint** (`UNIQUE(job_id)`), sehingga
  pekerjaan ulang tidak menggandakan hasil.
- `job_batches` menyimpan **satu** baris ringkasan; `skipped_count` disimpan
  di `notes`/payload karena skema awal tidak memilikinya.
- `is_baseline = 1` menandai run sequential pembanding.
- Lease (`leased_at`) mencegah job terkunci permanen bila worker mati.

---

## 4. Algoritma partisi

`PartitionPlanner::plan(N, P)`:

```text
base      = floor(N / P)
remainder = N % P
partisi i (1..P) menerima base + (i <= remainder ? 1 : 0) order
partisi dimulai dari id 1 dan berlanjut tanpa jeda
```

Contoh N=500, P=4 → 125/125/125/125. N=1000, P=8 → 125 masing-masing.
Sisa selalu dibagikan ke partisi awal sehingga `coversAll()` dapat memastikan
`1..N` tertutup penuh.

Keputusan: partisi **contiguous berbasis ID**, bukan round-robin. Alasannya:

- rentang mudah diverifikasi (`range_start`/`range_end` tersimpan di DB);
- tidak ada order yang terlewat atau dobel secara struktural;
- cocok dengan query rentang DB;
- beban cukup merata karena dataset mock tersebar per ID.

---

## 5. Klaim job & konkurensi

Worker mengklaim satu job dengan transaksi:

```sql
SELECT ... FROM verification_jobs
WHERE batch_id = ? AND status = 'PENDING'
ORDER BY partition_no
LIMIT 1
FOR UPDATE SKIP LOCKED;
```

Lalu di transaksi yang sama:

```sql
UPDATE verification_jobs
SET status = 'RUNNING', worker_id = ?, attempts = attempts + 1,
    leased_at = NOW(), started_at = COALESCE(started_at, NOW())
WHERE id = ?;
```

`SKIP LOCKED` membuat dua worker tidak pernah mendapat baris yang sama.
`attempts` naik setiap klaim; reducer memeriksa `maxAttempts` untuk mendeteksi
pekerjaan ganda. Pada benchmark normal, setiap job selesai pada
`attempts = 1`.

**Lease recovery:** job `RUNNING` yang `leased_at`-nya lebih lama dari
`lease_seconds` dikembalikan ke `PENDING` (dengan catatan di
`error_message`) saat worker dijalankan dengan `--lease-recovery`.

---

## 6. Eksekusi worker

### Mode A — `benchmark_worker.php`

1. Muat dataset sekali per proses (bukan per order).
2. `MockChainAdapter` memberi state on-chain deterministik dengan latensi
   buatan `--latency-us` per pembacaan.
3. Untuk setiap ID pada rentang partisi, jalankan aturan yang sama dengan
   baseline (`SequentialVerifier`).
4. Hitung metrik partisi + checksum partisi.
5. Simpan satu checkpoint (upsert berdasarkan `job_id`).
6. Tandai job `DONE`, lepaskan lease.
7. Loop ke job berikutnya sampai tidak ada job PENDING atau `--max-jobs`
   tercapai.

Runner men-spawn **tepat satu proses per partisi** dengan `--max-jobs=1`.
Konsekuensinya, jumlah worker unik yang mengerjakan job sama dengan jumlah
partisi (`distinct_workers = jobs_total`), yang menjadi bukti eksplisit
"worker 1/2/4/8 memproses job berbeda".

### Mode B — `verify_worker.php`

Sama, tetapi verifikasi memakai `VerificationService` (DB vs kontrak nyata).
Order tanpa `blockchain_order_id` ditandai `SKIPPED`, bukan `ERROR` atau
`MISMATCH`, karena order tersebut memang belum punya representasi on-chain.

---

## 7. Reduksi & checksum

Reducer membaca seluruh checkpoint batch, lalu:

1. Menjumlahkan metrik checkpoint (processed/verified/mismatch/error/revenue).
2. Menggabungkan `payload.results` per order ke dalam map `id → result`.
   ID duplikat dicatat di `duplicate_ids` dan tidak dihitung dua kali.
3. Mengurutkan hasil berdasarkan `id`.
4. Menghitung checksum dengan format yang sama seperti sequential.
5. Membandingkan checksum dengan baseline terbaru dataset yang sama.
6. Menulis ringkasan ke `job_batches` dan log ke `worker_logs`.

### Format checksum

```text
baris = "<id>|<verification>|<amount_wei>|<differences dipisah koma>"
checksum = sha256(join("\n", baris terurut id))
```

Karena urutan dinormalisasi berdasarkan `id`, hasil P=1, 2, 4, dan 8 harus
identik. Checksum inilah exit gate utama M6: agregat bisa kebetulan sama,
tetapi checksum per order tidak bisa "kebetulan" sama bila ada satu order
hilang, dobel, atau berubah status.

### Idempotensi

- `checkpoints` memakai `UNIQUE(job_id)` + upsert, jadi checkpoint tidak
  bertambah saat job diulang.
- Reducer mempertahankan `duration_ms` wall-clock yang sudah tersimpan,
  sehingga reduce ulang tidak menimpa waktu eksekusi asli.
- Reduce dua kali menghasilkan checksum dan jumlah checkpoint yang sama
  (dibuktikan RACE-02).

---

## 8. Agregasi finansial

| Metrik | Definisi |
|---|---|
| `total_orders` / `processed_orders` | Jumlah order yang benar-benar diproses |
| `verified_count` | `verification = VERIFIED` |
| `mismatch_count` | `verification = MISMATCH` |
| `error_count` | Selain VERIFIED/MISMATCH/SKIPPED |
| `skipped_count` | Order tanpa relasi on-chain (Mode B) |
| `paid_count` / `unpaid_count` | Berdasarkan `payment_status` |
| `revenue_all` | Total `amount_idr` seluruh order terproses |
| `revenue_verified` | Total `amount_idr` order VERIFIED **dan** paid |
| `aov` | `revenue_all / processed_orders` |
| `throughput_per_sec` | `processed_orders / (duration_ms / 1000)` |

`metrics_consistent` bernilai true bila jumlah metrik yang dihitung ulang dari
payload sama dengan jumlah metrik checkpoint. Ini mendeteksi checkpoint yang
korup atau tidak lengkap.

---

## 9. Failure handling

| Kondisi | Perilaku |
|---|---|
| Worker mati saat job RUNNING | Lease kadaluarsa → `--lease-recovery` mengembalikan ke PENDING |
| Job gagal | `attempts` naik; setelah `max_attempts` (3) job menjadi `DEAD` |
| Checkpoint hilang | Batch ditandai `FAILED` (`processed != expected`) |
| Checksum beda baseline | Batch ditandai `FAILED` + `checksum_match=false` |
| Order duplikat antar partisi | Dicatat di `duplicate_ids`, batch tidak `DONE` |
| Baseline belum ada | Gate checksum `NO-BASELINE`; batch integrasi tetap boleh DONE |

---

## 10. Trade-off yang disadari

1. **Partisi statis per P.** Jumlah worker ditentukan di awal, bukan
   work-stealing dinamis. Untuk skala M6 ini cukup dan membuat bukti
   "job berbeda" mudah diverifikasi. Work-stealing dapat dipertimbangkan di M7.
2. **Payload checkpoint menyimpan semua hasil per order.** Ini membuat
   reducer dapat menghitung checksum penuh, dengan konsekuensi ukuran baris
   lebih besar. Untuk dataset 5.000 order masih wajar di MySQL.
3. **Mode A memakai latensi buatan.** Ini bukan pengukuran performa chain
   nyata; tujuannya mengisolasi overhead koordinasi paralel. Mode B digunakan
   untuk validasi integrasi, sedangkan benchmark chain nyata masuk M7.
4. **`skipped_count` tidak punya kolom sendiri.** Disimpan di ringkasan
   reducer dan payload agar tidak perlu migrasi tambahan di tengah milestone.
