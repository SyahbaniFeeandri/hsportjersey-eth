# Benchmark — Sequential Baseline

**Issue:** `W4-FDR-05`
**Pemilik:** Farelly De Rossi
**Status:** Selesai (baseline sequential dengan data mock)
**Mode:** A — Algorithm benchmark (mock chain adapter deterministik)

Direktori ini berisi baseline sequential untuk verifikasi order DB vs state on-chain
dan agregasi finansial. Baseline ini menjadi pembanding hasil parallel di Minggu 6.

## Struktur

| File | Fungsi |
|---|---|
| `generate_dataset.php` | Membangkitkan dataset mock deterministik (D100 … D5000) |
| `sequential_baseline.php` | Menjalankan verifikasi sequential 1 worker + agregasi |
| `lib/DatasetGenerator.php` | Generator order mock (70% VERIFIED / 20% MISMATCH / 10% ERROR) |
| `lib/MockChainAdapter.php` | State on-chain semu yang deterministik |
| `lib/SequentialVerifier.php` | Aturan verifikasi + metrik + checksum |
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
