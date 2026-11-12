# 06 — SEQUENTIAL BASELINE CLI DENGAN DATA MOCK

**Issue:** `W4-FDR-05`
**Pemilik:** Farelly De Rossi
**Reviewer:** Kenjiro, Luis
**Status:** ✅ Selesai
**Tanggal:** 12 November 2026

---

## 1. Ringkasan

Baseline sequential diimplementasikan sebagai CLI PHP di direktori `benchmark/`.
Tujuannya menyediakan angka pembanding (single worker) untuk verifikasi order
DB vs state on-chain dan agregasi finansial sebelum masuk ke pemrosesan paralel
di Minggu 6.

| Aspek | Implementasi |
|---|---|
| Bahasa | PHP 8.1 CLI |
| Mode | A — Algorithm benchmark (mock chain adapter deterministik) |
| Worker | 1 (sequential) |
| Dataset | D100, D500, D1000, D5000 |
| Sumber state chain | Mock deterministik (bukan Ganache) |
| Output | Ringkasan konsol, JSON, dan opsional tabel MySQL |

Artefak utama:

- `benchmark/generate_dataset.php`
- `benchmark/sequential_baseline.php`
- `benchmark/lib/*.php`
- `benchmark/datasets/*.json`
- Bukti: [`evidence/sequential_baseline_D100.log`](evidence/sequential_baseline_D100.log) s.d. D5000

---

## 2. Komposisi Dataset

Mengikuti `docs/week1/04_CONTRACT_AND_PARALLEL_DESIGN.md` §7:

| Kategori | Proporsi | Perilaku mock |
|---|---:|---|
| `VERIFIED` | 70% | State on-chain sama dengan DB |
| `MISMATCH` | 20% | Salah satu field berbeda (amount, wallet, status, hash) |
| `ERROR` | 10% | Adapter melempar error (order tidak ditemukan / RPC gagal) |

Dataset bersifat deterministik: seed `HSPORT-2026`, tanpa RNG global, tanpa
ketergantungan waktu atau jaringan. Setiap dataset mencatat `dataset_hash`.

Contoh hasil generate:

| Dataset | Orders | V / M / E | Dataset hash (16 char) |
|---|---:|---|---|
| D100 | 100 | 70 / 20 / 10 | `bf5073f852ccf622` |
| D500 | 500 | 350 / 100 / 50 | `734ddd14ea450a82` |
| D1000 | 1.000 | 700 / 200 / 100 | `253275fa15d85313` |
| D5000 | 5.000 | 3.500 / 1.000 / 500 | `2bed91dfbc1608b5` |

---

## 3. Hasil Baseline

Dijalankan pada 12 November 2026 dengan `--no-db` (MySQL tidak aktif saat eksekusi).

| Dataset | Total | Verified | Mismatch | Error | Duration | Throughput | Checksum (16 char) |
|---|---:|---:|---:|---:|---:|---:|---|
| D100 | 100 | 70 | 20 | 10 | 0 ms (259 µs) | 386.100 order/dtk | `6ae585e5f5bda473` |
| D500 | 500 | 350 | 100 | 50 | 1 ms | 464.252 order/dtk | `61cfe1d0d9769ea1` |
| D1000 | 1.000 | 700 | 200 | 100 | 4 ms | 257.003 order/dtk | `1258b0c3790fa9c4` |
| D5000 | 5.000 | 3.500 | 1.000 | 500 | 16 ms | 303.342 order/dtk | `972e6a4d1fe260f2` |

Detail metrik finansial (contoh D5000):

- Revenue all: **Rp 6.445.560.000**
- Revenue verified: **Rp 3.879.907.000**
- AOV: **Rp 1.289.112**
- Paid / Unpaid: **4.285 / 715**

Angka ini adalah baseline Mode A; bukan klaim performa Ganache. Validasi
adapter terhadap kontrak nyata dijadwalkan sebagai Mode B (D100) di Minggu 6/7.

---

## 4. Integrasi Database

Dengan `--db`, CLI menulis ke skema `migration_parallel.sql`:

| Tabel | Isi |
|---|---|
| `job_batches` | Satu baris batch: `mode='ALGORITHM'`, `worker_count=1`, `is_baseline=1`, metrik, `checksum`, `duration_ms` |
| `verification_jobs` | Satu partisi (`partition_no=1`, range `1..N`, status `DONE`) |
| `checkpoints` | Metrik + `payload` JSON (hasil per order) + checksum |
| `worker_logs` | Log `INFO` akhir eksekusi |

Karena MySQL tidak aktif saat bukti diambil, bukti pada `docs/week4/evidence/`
berisi output `--no-db`. Jalankan `php benchmark/sequential_baseline.php --size=1000 --db`
setelah MySQL aktif untuk mengisi keempat tabel di atas.

---

## 5. Cara Mereproduksi

```bash
php benchmark/generate_dataset.php --sizes=100,500,1000,5000
php benchmark/sequential_baseline.php --size=1000 --no-db --json-out=hasil.json
```

Hasil yang diharapkan: komposisi **70/20/10** dan checksum yang sama untuk
dataset dan seed yang sama.

---

## 6. Batasan

1. Baseline memakai mock adapter (Mode A), bukan kontrak nyata.
2. Duration D100 tercatat 259 µs sehingga `duration_ms` dibulatkan menjadi 0;
   gunakan `duration_us` pada JSON untuk presisi.
3. Dataset belum diuji dengan MySQL contention/parallel worker; itu scope M6.
4. Mock `external_order_hash` memakai SHA-256 (bukan keccak256) karena hanya
   dipakai untuk perbandingan internal; integrasi nyata tetap memakai
   `keccak256(abi.encode(...))`.

---

## 7. Tindak Lanjut M6

1. Worker paralel 1/2/4/8 membaca `verification_jobs` dengan `SKIP LOCKED`.
2. Reducer membandingkan `checksum` paralel dengan baseline ini.
3. Mode B: validasi 100 order nyata di Ganache.
4. Lengkapi benchmark sheet dan grafik speedup/efficiency.

---

## 8. Tautan

- Desain paralel: [`../week1/04_CONTRACT_AND_PARALLEL_DESIGN.md`](../week1/04_CONTRACT_AND_PARALLEL_DESIGN.md)
- Skema queue: `migration_parallel.sql`
- README benchmark: [`../../benchmark/README.md`](../../benchmark/README.md)
- Roadmap: [`../ROADMAP_RINCI.md`](../ROADMAP_RINCI.md)
