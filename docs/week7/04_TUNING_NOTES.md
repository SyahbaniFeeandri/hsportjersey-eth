# 04 — Tuning Worker & Queue (M7)

**Milestone:** M7 — Benchmark & Integration Test
**Issue terkait:** `W7-FDR-12`, `W7-KEN-07`
**Pemilik:** Farelly De Rossi · **Pendukung:** Kenjiro B.H.G.
**Tanggal:** 13 November 2026

---

## 1. Tujuan

Mencatat keputusan tuning yang diambil agar benchmark M7 stabil dan
reproducible, beserta alasan dan dampak terukurnya.

---

## 2. Konfigurasi Aktif

Sumber tunggal: [`config/parallel.php`](../../config/parallel.php).

| Parameter | Nilai | Alasan |
|---|---:|---|
| `worker_counts` | 1, 2, 4, 8 | Batas lingkungan uji (CPU/IO) dan requirement matrix |
| `default_partition_size` | 500 order/job | Menjaga jumlah job wajar; partisi dihitung ulang oleh `PartitionPlanner` agar menutup 1..N |
| `lease_seconds` | 300 | Worker mati → job dapat dipulihkan setelah 5 menit; cukup longgar untuk dataset 5.000 |
| `max_attempts` | 3 | Retry terkendali sebelum `DEAD` |
| `algorithm.default_latency_us` | 1.000 (default), 5.000 (benchmark) | Simulasi biaya baca order; 5 ms memberi ruang pengukuran paralel |
| `integration.skip_without_chain_id` | `true` | Order lama tanpa `blockchain_order_id` dihitung SKIPPED, bukan MISMATCH |
| `lease_recovery_on_start` | `false` | Recovery eksplisit (`--lease-recovery`) agar tidak ada efek samping saat benchmark |

---

## 3. Tuning #1 — Pre-assign satu job per worker

**Masalah:** spawn P worker serentak dengan `--max-jobs=1` membuat worker
terakhir kehabisan job (lihat BUG-07-01).

**Keputusan:** runner memetakan `partition_no → job_id` lalu men-spawn
`benchmark_worker.php --job-id=<id>`.

| Aspek | Sebelum | Sesudah |
|---|---|---|
| Klaim job | `ORDER BY partition_no LIMIT 1` (bebas) | `AND id = ?` bila `--job-id` diberikan |
| Worker idle | Bisa terjadi | Tidak terjadi (1 proses = 1 job) |
| `distinct_workers = jobs_total` | Tidak terjamin | Terjamin |
| Race dua worker satu job | — | Tetap aman (`FOR UPDATE SKIP LOCKED`), dibuktikan PAR-04.a |

Trade-off: runner harus tahu `job_id` setelah enqueue. Ini murah karena batch
dan job dibuat dalam transaksi yang sama sebelum worker di-spawn.

---

## 4. Tuning #2 — Jumlah worker per ukuran dataset

Berdasarkan matrix (latensi 5 ms/order):

| Ukuran | Rekomendasi P | Alasan |
|---|---:|---|
| ≤ 100 order | 2 (maks. 4) | Biaya spawn + reduce mendominasi; P=8 tidak menambah throughput |
| 500 | 4–8 | Speedup 3,24× (P=4) / 4,67× (P=8), efficiency masih 58–81% |
| 1.000 | 4–8 | Speedup 3,53× / 5,75×; efficiency 72–88% |
| 5.000 | 8 | Speedup 7,32×, efficiency 91,5% — mendekati linear |

Aturan praktis: `P ≈ min(8, ceil(N / 500))` untuk Mode A. Untuk Mode B
(RPC nyata), biaya per order jauh lebih besar sehingga P=4–8 menguntungkan
bahkan pada dataset kecil.

---

## 5. Tuning #3 — Ukuran partisi

- Partisi dibuat **contiguous** (1..N) agar checksum dapat dihitung tanpa
  pengurutan ulang.
- Untuk matrix, jumlah partisi = jumlah worker (satu job per worker) supaya
  setiap worker mendapat porsi sama dan `distinct_workers = jobs_total`.
- `default_partition_size = 500` dipakai untuk batch non-benchmark
  (mis. `enqueue_batch.php`), di mana jumlah job tidak harus sama dengan
  jumlah worker.

Dampak: partisi kecil (mis. 12–13 order pada D100/P=8) membuat biaya tetap
per proses lebih besar dari pekerjaan; ini terlihat pada P=8 D100 yang lebih
lambat dari P=4.

---

## 6. Tuning #4 — Lease & recovery

| Skenario | Perilaku |
|---|---|
| Worker normal selesai | `status=DONE`, `leased_at` tidak relevan |
| Worker mati saat `RUNNING` | Job tetap `RUNNING` sampai lease 300 detik lewat |
| Worker baru dengan `--lease-recovery` | Job kadaluarsa dikembalikan ke `PENDING`, lalu diklaim (attempts naik) |
| Retry berlebih | Setelah 3 attempt → `DEAD`, tidak diklaim lagi |

Bukti: PAR-04.d — job `RUNNING` dengan lease 600 detik lalu dipulihkan,
`attempts` menjadi 2, `checkpoints=1` (tidak dobel).

Catatan: `lease_recovery_on_start=false` agar benchmark tidak "menyembuhkan"
antrean secara diam-diam saat mengukur. Recovery dijalankan eksplisit di
skenario uji.

---

## 7. Tuning #5 — Baseline & gate

- Baseline sequential dijalankan **sekali per dataset** lalu dipakai ulang
  untuk P=1/2/4/8 (`--no-baseline-run` pada run berikutnya). Ini menghindari
  variasi baseline yang membuat speedup tidak sebanding.
- Gate tidak hanya membandingkan checksum, tetapi juga:
  `processed_orders`, `jobs_done = jobs_total`, `distinct_workers`,
  `max_attempts = 1`, `duplicate_count = 0`, dan kecocokan revenue/AOV.
- Efek samping yang diterima: speedup P=1 sedikit < 1,0 karena P=1 tetap
  memakai jalur queue (overhead orkestrasi). Ini transparan dan dilaporkan.

---

## 8. Hasil Setelah Tuning

| Indikator | Sebelum tuning | Sesudah tuning |
|---|---|---|
| Konfigurasi PASS | 14/16 (2 gagal karena worker idle) | **16/16** |
| Checksum MATCH | 14/16 | **16/16** |
| Partisi `PENDING` setelah run | Ada (D1000 P=8) | **0** |
| Repeat P=8 checksum | Tidak stabil | Identik (`1258b0c3…`) |
| Regresi | — | 21/21 PASS |

---

## 9. Batasan & Rekomendasi Lanjutan

1. Matrix dijalankan pada satu mesin; angka absolut tidak dapat digeneralisasi.
2. Untuk Mode B (Ganache nyata), ukur ulang karena latensi RPC tidak konstan.
3. Jika jumlah order tumbuh jauh di atas 5.000, pertimbangkan:
   - worker `--loop` (bukan satu job per proses) untuk mengurangi biaya spawn;
   - partisi lebih besar (mis. 1.000) agar overhead per job turun;
   - indeks tambahan pada `verification_jobs(batch_id, status, partition_no)`
     bila antrean > 100 job.
4. Monitoring `worker_logs` dan `v_benchmark_summary` sudah tersedia untuk
   operasi; tidak ada perubahan skema pada M7.
