# 01 — Benchmark Report: Sequential vs Parallel

**Milestone:** M7 — Benchmark & Integration Test
**Issue terkait:** `W7-KEN-07`, `W7-KEN-08`, `W7-FDR-12`
**Pemilik:** Kenjiro B.H.G.
**Pendukung:** Farelly De Rossi
**Reviewer:** Feeandri, Yasmin
**Tanggal eksekusi:** 13 November 2026, 03:33–03:35 WIB
**Mesin:** Windows 10/11, PHP 8.1.10, MySQL 8.0.30, Node.js 24.20.0

---

## 1. Tujuan

1. Mengukur execution time, speedup, efficiency, dan throughput pipeline
   verifikasi paralel pada empat ukuran dataset.
2. Membuktikan hasil paralel identik dengan baseline sequential
   (checksum MATCH) untuk setiap konfigurasi.
3. Menyediakan tabel + grafik yang dapat dipakai di laporan dan demo.

---

## 2. Metodologi

| Aspek | Nilai / Keputusan |
|---|---|
| Mode | `ALGORITHM` (dataset mock deterministik + `MockChainAdapter`) |
| Latensi simulasi | 5.000 µs per order (5 ms) |
| Dataset | D100, D500, D1000, D5000 — seed `HSPORT-2026` |
| Worker | P = 1, 2, 4, 8 (partisi contiguous 1..N) |
| Baseline | Satu run sequential per dataset, dipakai ulang untuk semua P |
| Gate | Checksum paralel = checksum sequential **dan** agregasi metrik sama |
| Pengulangan | 1 run utama + 1 run reproduksi di workspace kedua |

Alur satu konfigurasi:

```text
sequential_baseline.php ──► checksum + durasi + agregasi (V/M/E, revenue)
        │
        ▼
parallel_runner.php ──► enqueue batch + P partisi
        │                 pre-assign 1 job per worker (--job-id)
        ▼
P worker (benchmark_worker.php) ──► checkpoints
        │
        ▼
Reducer ──► checksum paralel + agregasi
        │
        ▼
bandingkan checksum & metrik ──► MATCH / DIFFERENT
```

Checksum tunggal (`benchmark/lib/Checksum.php`) memakai urutan order yang sama
untuk sequential dan paralel, sehingga perbedaan urutan penyelesaian worker
tidak mempengaruhi hasil agregasi.

---

## 3. Hasil Matrix 4 × 4

**Sumber:** [`evidence/benchmark_matrix.json`](evidence/benchmark_matrix.json)
· **Ringkasan:** 16/16 konfigurasi PASS · durasi total 104,07 detik

| Dataset | P | Baseline (ms) | Wall-clock (ms) | Speedup | Efficiency | Throughput (order/s) | Gate |
|---|---:|---:|---:|---:|---:|---:|---|
| D100 | 1 | 529 | 629 | 0,841× | 84,1% | 158,98 | MATCH |
| D100 | 2 | 529 | 385 | 1,374× | 68,7% | 259,74 | MATCH |
| D100 | 4 | 529 | 297 | 1,781× | 44,5% | 336,70 | MATCH |
| D100 | 8 | 529 | 305 | 1,734× | 21,7% | 327,87 | MATCH |
| D500 | 1 | 2.647 | 2.726 | 0,971× | 97,1% | 183,42 | MATCH |
| D500 | 2 | 2.647 | 1.432 | 1,849× | 92,4% | 349,16 | MATCH |
| D500 | 4 | 2.647 | 817 | 3,240× | 81,0% | 612,00 | MATCH |
| D500 | 8 | 2.647 | 567 | 4,668× | 58,4% | 881,83 | MATCH |
| D1000 | 1 | 5.271 | 5.373 | 0,981× | 98,1% | 186,12 | MATCH |
| D1000 | 2 | 5.271 | 2.758 | 1,911× | 95,6% | 362,58 | MATCH |
| D1000 | 4 | 5.271 | 1.493 | 3,531× | 88,3% | 669,79 | MATCH |
| D1000 | 8 | 5.271 | 917 | 5,748× | 71,9% | 1.090,51 | MATCH |
| D5000 | 1 | 26.387 | 26.569 | 0,993× | 99,3% | 188,19 | MATCH |
| D5000 | 2 | 26.387 | 13.379 | 1,972× | 98,6% | 373,72 | MATCH |
| D5000 | 4 | 26.387 | 6.798 | 3,882× | 97,0% | 735,51 | MATCH |
| D5000 | 8 | 26.387 | 3.606 | **7,318×** | **91,5%** | **1.386,58** | MATCH |

Grafik: [`evidence/benchmark_chart.svg`](evidence/benchmark_chart.svg)
· CSV: [`evidence/benchmark_matrix.csv`](evidence/benchmark_matrix.csv)
· Tabel Markdown: [`evidence/benchmark_matrix.md`](evidence/benchmark_matrix.md)

### Checksum per dataset

| Dataset | Dataset hash (SHA-256) | Checksum agregasi |
|---|---|---|
| D100 | `bf5073f852ccf622…` | `6ae585e5f5bda4739162ad9607336c107d803cc024457429af2c2fa330cfe34a` |
| D500 | `734ddd14ea450a82…` | `61cfe1d0d9769ea1d3a737adc39e9a41c56ffbaadbb7f8855507f6bacb309939` |
| D1000 | `253275fa15d85313…` | `1258b0c3790fa9c47007b2ef279fae38754a8b9f1e089bf912f9a731a5867814` |
| D5000 | `2bed91dfbc1608b5…` | `972e6a4d1fe260f2d0c8126f8c52befe28563f3d09fae0e7f6209e161221547e` |

Nilai checksum D500 dan D1000 **sama** dengan hasil M6 (`61cfe1d0…`,
`1258b0c3…`), bukti bahwa pipeline M7 memakai mesin yang sama dan tidak
mengubah semantik agregasi.

---

## 4. Analisis

### 4.1 Overhead proses dominan pada dataset kecil

D100: P=1 sudah 19% lebih lambat dari baseline (629 ms vs 529 ms), dan P=8
(305 ms) tidak lebih cepat dari P=4 (297 ms). Penyebabnya biaya tetap per
konfigurasi: spawn proses PHP, koneksi MySQL, pembuatan batch/partisi, dan
reduce. Pada 100 order, biaya tetap ini lebih besar dari pekerjaan verifikasi
(±5 ms × 100 = 500 ms).

Implikasi: jumlah worker optimal bergantung pada ukuran beban.
Untuk dataset < 500 order, P=2–4 sudah cukup; P=8 hanya menambah overhead.

### 4.2 Skalabilitas baik pada dataset besar

| Dataset | Speedup P=2 | P=4 | P=8 | Efficiency P=8 |
|---|---:|---:|---:|---:|
| D500 | 1,849× | 3,240× | 4,668× | 58,4% |
| D1000 | 1,911× | 3,531× | 5,748× | 71,9% |
| D5000 | 1,972× | 3,882× | 7,318× | 91,5% |

- P=2 mendekati linear (±1,9×) untuk semua dataset ≥ 500.
- Efficiency naik seiring ukuran dataset karena biaya tetap teramortisasi.
- D5000 P=8 mencapai 91,5% efficiency dengan 1.386 order/detik, mendekati
  batas atas teoretis 8× (perfect scaling = 8,000×).

### 4.3 Baseline P=1 bukan pembanding sempurna

P=1 memakai jalur queue + worker (bukan `sequential_baseline.php`), sehingga
speedup P=1 sedikit di bawah 1,0 (0,841–0,993). Ini disengaja: angka speedup
dihitung terhadap baseline sequential murni, bukan terhadap P=1, agar
overhead orkestrasi terlihat jujur.

### 4.4 Konsistensi hasil

- 16/16 checksum MATCH; tidak ada order duplikat/hilang
  (`processed_orders = dataset_size`, `duplicate_count = 0`).
- `distinct_workers = jobs_total` dan `max_attempts = 1` di semua konfigurasi.
- Seluruh gate metrik (`metrics_match`) bernilai true.

---

## 5. Validasi PAR-03

PAR-03 memvalidasi angka di tabel, bukan hanya exit code:

| Check | Isi | Hasil |
|---|---|---|
| PAR-03.a | 16 baris (4 dataset × 4 worker) | PASS |
| PAR-03.b | `throughput = order / (wall/1000)` dan `speedup = baseline/wall` | PASS (0 error) |
| PAR-03.c | `efficiency = speedup / P`, 0 < efisiensi ≤ 1,05 | PASS (0 error) |
| PAR-03.d | Speedup naik monoton P=1→2→4 pada dataset ≥ 500 | PASS |
| PAR-03.e | Semua konfigurasi checksum MATCH dan gate pass | PASS |

Bukti: [`evidence/par03_par04_test.log`](evidence/par03_par04_test.log) ·
[`evidence/par03_par04_test.json`](evidence/par03_par04_test.json)

---

## 6. Reproduksibilitas

Run ulang di workspace kedua (dataset dibangkitkan ulang dari seed yang sama)
menghasilkan **16/16 checksum identik** dan delta metrik maksimum 8,03%
(speedup) / 6,73% (wall-clock) — di bawah toleransi 20%. Detail:
[`07_REPRODUCIBILITY.md`](07_REPRODUCIBILITY.md).

---

## 7. Batasan

1. Mode `ALGORITHM`: latensi disimulasikan konstan 5 ms/order; bukan pengukuran
   RPC Ganache nyata. Mode `INTEGRATION` diuji terpisah (INT-01..05) tetapi
   tidak di-benchmark pada matrix ini.
2. Pengukuran pada satu mesin; angka absolut bergantung pada CPU/IO mesin.
   Yang dapat direproduksi adalah **checksum** dan **pola** speedup.
3. Jumlah worker maksimum yang diuji 8 (sesuai batas core/lingkungan).
4. Dataset dibuat oleh `DatasetGenerator` dengan seed tetap sehingga hash
   dataset dapat dibandingkan lintas mesin.
