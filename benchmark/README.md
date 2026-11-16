# Benchmark — Sequential & Parallel

**Issue:** `W4-FDR-05` (baseline), `W6-FDR-07..11` (parallel)
**Pemilik:** Farelly De Rossi
**Status:** Selesai (baseline sequential + parallel 1/2/4/8)
**Mode:** A — Algorithm benchmark (mock chain adapter deterministik)

Direktori ini berisi baseline sequential dan pipeline paralel untuk
verifikasi order DB vs state on-chain beserta agregasi finansial. Baseline
sequential menjadi pembanding checksum hasil parallel (exit gate M6).

## Struktur

| File | Fungsi |
|---|---|
| `generate_dataset.php` | Membangkitkan dataset mock deterministik (D100 … D5000) |
| `sequential_baseline.php` | Verifikasi sequential 1 worker + agregasi |
| `parallel_runner.php` | Orkestrator baseline + worker paralel + reducer + gate |
| `lib/DatasetGenerator.php` | Generator order mock (70% VERIFIED / 20% MISMATCH / 10% ERROR) |
| `lib/MockChainAdapter.php` | State on-chain semu yang deterministik (+ latensi buatan) |
| `lib/SequentialVerifier.php` | Aturan verifikasi + metrik + checksum |
| `lib/PartitionPlanner.php` | Partisi contiguous 1..N untuk P worker |
| `lib/Reducer.php` | Agregasi checkpoint, checksum, gate, idempotensi |
| `lib/Checksum.php` | Format baris + checksum tunggal sequential/parallel |
| `datasets/` | Dataset JSON hasil generate + index hash |

## Cara pakai

```bash
# 1. Generate dataset
php benchmark/generate_dataset.php --sizes=100,500,1000,5000

# 2. Jalankan baseline tanpa DB
php benchmark/sequential_baseline.php --size=1000 --no-db

# 3. Jalankan baseline dan simpan ke job_batches/checkpoints (MySQL aktif)
php benchmark/sequential_baseline.php --size=1000 --db

# 4. Pakai dataset yang sudah ada + simpan ringkasan JSON
php benchmark/sequential_baseline.php --dataset=benchmark/datasets/D500.json --json-out=hasil.json

# 5. Benchmark paralel 1/2/4/8 worker (baseline otomatis dijalankan)
php benchmark/parallel_runner.php \
  --dataset=benchmark/datasets/D1000.json \
  --workers=1,2,4,8 --latency-us=5000 \
  --json-out=docs/week6/evidence/par_d1000.json

# 6. Suite PAR-01/PAR-02 + race condition (24 check)
php tests/parallel/run_par.php --latency-us=5000 --workers=1,2,4,8

# 7. Mode B: batch order nyata → worker → reducer
php workers/enqueue_batch.php --workers=2 --from-id=1 --to-id=40
php workers/verify_worker.php --batch=39 --worker=w1 --loop
php workers/verify_worker.php --batch=39 --worker=w2 --loop
php workers/reduce_batch.php --batch=39
```

## Opsi CLI

| Opsi | Default | Keterangan |
|---|---|---|
| `--size=N` | `100` | Ukuran dataset bila `--dataset` tidak dipakai |
| `--sizes=a,b,c` | — | Khusus generator: beberapa ukuran sekaligus |
| `--seed=S` | `HSPORT-2026` | Seed deterministik |
| `--dataset=path` | — | Memakai dataset JSON yang sudah ada |
| `--db` | mati | Simpan batch ke MySQL |
| `--no-db` | — | Paksa lewati DB |
| `--batch=CODE` | `SEQ-<dataset>-<timestamp>` | Kode unik batch |
| `--json-out=path` | — | Simpan ringkasan JSON (tanpa detail per order) |
| `--include-results` | mati | Sertakan seluruh hasil per order di JSON |
| `--workers=a,b,c` | `1,2,4,8` | Jumlah worker yang diuji (parallel runner) |
| `--latency-us=N` | `1000` | Latensi buatan per pembacaan order (Mode A) |
| `--no-baseline-run` | — | Jangan buat baseline baru; pakai yang ada |

## Parallel runner

Alur `parallel_runner.php`:

1. Pastikan baseline sequential tersedia (kecuali `--no-baseline-run`).
2. Untuk setiap P, buat batch + P partisi contiguous.
3. Spawn tepat satu proses `workers/benchmark_worker.php --max-jobs=1` per
   partisi, sehingga setiap worker memproses job berbeda.
4. Reduce checkpoint, hitung checksum, bandingkan dengan baseline.
5. Cetak tabel speedup/efficiency dan status exit gate.

Nilai `--latency-us` membuat pekerjaan per order deterministik. Tanpa
Ganache, speedup tetap terukur karena biaya dominan berasal dari latensi
buatan tersebut. Mode B (`verify_worker.php`) memakai RPC nyata.

## Reducer & checksum

- Format baris: `<id>|<verification>|<amount_wei>|<differences>`.
- Checksum = `sha256` dari baris terurut `id`, sehingga urutan partisi tidak
  memengaruhi hasil.
- `Reducer` menjaga idempotensi: reduce ulang tidak menambah checkpoint dan
  tidak menimpa `duration_ms`.
- Gate: `MATCH` bila checksum sama dengan baseline, `DIFFERENT` bila beda,
  `NO-BASELINE` bila batch tidak punya pembanding.

## Dashboard

Hasil benchmark dapat dilihat di `admin/performance.php` (menu
**Performance** pada sidebar admin). Halaman membaca view
`v_benchmark_summary` dan menampilkan throughput, speedup, efficiency,
serta correctness gate.

## Determinisme

- Dataset dibangkitkan dari seed + ID order, sehingga `dataset_hash` stabil.
- Adapter mock tidak memakai RNG global, waktu, atau jaringan.
- Checksum dihitung dari urutan hasil yang diurutkan berdasarkan `id`.
- Menjalankan ulang dataset yang sama menghasilkan checksum yang sama.

## Catatan

- Mode A **tidak** memanggil Ganache. Validasi adapter terhadap kontrak nyata
  dilakukan pada Mode B (D100) di Minggu 6/7.
- `--db` membutuhkan `job_batches`, `verification_jobs`, `checkpoints`, dan
  `worker_logs` dari `migration_parallel.sql`.
- Bila MySQL tidak aktif, CLI tetap menyelesaikan baseline dan hanya memberi
  peringatan bahwa batch tidak disimpan.
