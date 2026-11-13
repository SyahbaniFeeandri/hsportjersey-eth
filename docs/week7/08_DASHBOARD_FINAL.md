# 08 — Performance & Verification Dashboard Final (M7)

**Milestone:** M7 — Benchmark & Integration Test
**Issue terkait:** `W7-IHS-08`
**Pemilik:** Ihsanul · **Pendukung:** Kenjiro B.H.G., Farelly De Rossi
**Tanggal:** 13 November 2026

---

## 1. Akses

| Item | Nilai |
|---|---|
| URL | `/admin/performance.php` |
| Hak akses | Admin (`isAdmin()`), non-admin diarahkan ke login |
| Data sumber | `v_benchmark_summary` (MySQL) + artefak `docs/week7/evidence/` |
| Refresh | Tombol **Refresh** (reload halaman) |

---

## 2. Isi Dashboard

### 2.1 Kartu ringkasan (atas)

| Kartu | Isi |
|---|---|
| Batch DONE | Jumlah batch selesai di `v_benchmark_summary` |
| Speedup terbaik | Speedup maksimum dari batch historis |
| Rata-rata efficiency (P>1) | Rata-rata efficiency batch paralel |
| Correctness gate MATCH | `MATCH / total gate` |

### 2.2 Benchmark Matrix Final — 4 Dataset × 4 Worker (baru di M7)

Membaca `docs/week7/evidence/benchmark_matrix.json` dan menampilkan:

- Badge `16/16 konfigurasi PASS`
- Kolom: Dataset, P, Baseline, Wall-clock, Speedup, Efficiency, Throughput,
  Order terproses, Worker unik, Checksum (12 karakter pertama), Gate
- Footer: latensi simulasi, seed, dan definisi gate MATCH

Angka di dashboard identik dengan [`01_BENCHMARK_REPORT.md`](01_BENCHMARK_REPORT.md)
karena keduanya membaca file yang sama — tidak ada perhitungan ulang yang bisa
menyimpang.

### 2.3 Grafik Speedup vs Jumlah Worker (baru di M7)

Menampilkan `docs/week7/evidence/benchmark_chart.svg` secara inline:
satu garis per dataset, garis putus-putus `ideal = P`, sumbu X jumlah worker,
sumbu Y speedup.

### 2.4 Regresi 21 Test (baru di M7)

Membaca `docs/week7/evidence/regression_summary.json`:

- Badge `21/21 PASS`
- Baris per level: Smart contract 12/12, Integrasi 5/5, Parallel 4/4
- Daftar test yang gagal (bila ada)
- Tanggal eksekusi, durasi, alamat kontrak

### 2.5 Speedup per Batch (M6, dipertahankan)

Bar chart horizontal dari `v_benchmark_summary` (12 batch terbaru) untuk
melihat tren antar-run.

### 2.6 Riwayat Benchmark (M6, dipertahankan)

Tabel 60 batch terbaru: batch code, mode, dataset, P, status, order, V/M/E,
revenue, AOV, durasi, throughput, speedup, efficiency, gate.

---

## 3. Sumber Data & Fallback

| Artefak | Bila tidak ada |
|---|---|
| `benchmark_matrix.json` | Kartu matrix menampilkan pesan "Jalankan `php benchmark/run_matrix.php`" |
| `benchmark_chart.svg` | Pesan grafik belum tersedia |
| `regression_summary.json` | Pesan jalankan `php tests/regression/run_all.php` |
| `v_benchmark_summary` | Alert kuning "View benchmark belum tersedia. Jalankan `migration_parallel.sql`" |

Dashboard tidak pernah menampilkan error fatal: setiap bagian gagal secara
independen dan menjelaskan cara memperbaikinya.

---

## 4. Verifikasi Eksekusi (13 November 2026)

Diuji lewat HTTP dengan sesi admin nyata
([`evidence/uat_http.log`](evidence/uat_http.log)):

| Pemeriksaan | Hasil |
|---|---|
| `GET /admin/performance.php` sebagai admin | **200** |
| Kartu "Benchmark Matrix Final" | tampil |
| Kartu "Regresi 21 Test" | tampil |
| Badge `21/21 PASS` | tampil |
| Grafik SVG inline (`<svg`) | tampil |
| Baris D100..D5000 | tampil |
| Akses sebagai customer | ditolak ke halaman login |

---

## 5. Catatan & Batasan

1. Tabel riwayat menampilkan **semua** batch historis (termasuk batch uji
   PAR/race). Ini disengaja untuk audit; angka rujukan final ada di kartu
   matrix M7, bukan di tabel riwayat.
2. `v_benchmark_summary` menghitung speedup terhadap baseline terbaru per
   dataset. Bila baseline dijalankan ulang, batch lama dapat terlihat
   berbeda; gunakan kartu matrix untuk angka yang dibekukan.
3. Dashboard memerlukan session admin; untuk demo, login sebagai
   `admin@hsport.com`.
4. Grafik dirender sebagai SVG statis dari file, sehingga tidak membutuhkan
   JavaScript tambahan dan aman saat presentasi offline.

---

## 6. Rencana M8

- Menambahkan filter dataset/worker pada tabel riwayat.
- Menampilkan status verifikasi order (`VERIFIED`/`MISMATCH`) secara agregat
  per batch, melengkapi `REQ-11`.
- Menyediakan tautan unduh CSV langsung dari dashboard.
