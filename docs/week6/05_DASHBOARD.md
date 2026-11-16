# Performance Dashboard

**Issue:** `W6-IHS-07`
**Pemilik:** Ihsanul
**File:** [`admin/performance.php`](../../admin/performance.php)
**Status:** Selesai

Dashboard performa menampilkan hasil benchmark sequential vs parallel untuk
admin. Sumber datanya adalah view `v_benchmark_summary` pada
`migration_parallel.sql`.

---

## 1. Cara mengakses

1. Login sebagai admin.
2. Buka **Admin → Performance** (`admin/performance.php`).
3. Halaman juga ditautkan dari sidebar `admin/dashboard.php`.

Akses dibatasi `isAdmin()`; pengguna non-admin diarahkan ke halaman login.

---

## 2. Sumber data

```text
job_batches (batch + baseline)
        │
        ▼
v_benchmark_summary
   ├── throughput_per_sec = total_orders / (duration_ms / 1000)
   ├── speedup            = baseline.duration_ms / batch.duration_ms
   ├── efficiency         = speedup / worker_count
   └── correctness_gate   = MATCH | DIFFERENT | NULL
        │
        ▼
admin/performance.php
```

Baseline dipilih dari **satu** baris terbaru per kombinasi
`(dataset_name, dataset_size, mode)` dengan `is_baseline = 1` dan
`status = 'DONE'`. Sebelumnya join langsung dapat menghasilkan baris ganda
saat ada lebih dari satu baseline; view sekarang memakai derived table
`MAX(id)` sehingga setiap batch muncul tepat sekali.

Catatan: view tidak memilih kolom `checksum`; dashboard hanya memakai
`correctness_gate`, bukan checksum mentah.

---

## 3. Komponen halaman

### Kartu ringkasan

| Kartu | Isi |
|---|---|
| Batch DONE | Jumlah batch selesai pada 60 baris terbaru |
| Speedup terbaik | Nilai speedup maksimum |
| Rata-rata efficiency (P>1) | Rata-rata efficiency batch paralel |
| Correctness gate MATCH | `pass/total` batch yang punya gate |

### Grafik batang

Menampilkan hingga 12 batch `DONE` yang punya nilai speedup. Panjang batang
dinormalisasi terhadap speedup tertinggi. Batch baseline diberi badge
`baseline`.

### Tabel riwayat

Kolom: batch, mode, dataset, P, status, order, V/M/E, revenue, AOV, durasi,
throughput, speedup, efficiency, gate.

Badge status: `DONE` hijau, `RUNNING` biru, `PENDING` kuning, `FAILED` merah.
Badge gate: `MATCH` hijau, `DIFFERENT` merah, `-` abu-abu.

---

## 4. Contoh tampilan data

Diambil dari view setelah benchmark D500 dan D1000:

| Batch | P | Durasi | Speedup | Efficiency | Gate |
|---|---:|---:|---:|---:|---|
| `PAR-D1000-W8-…` | 8 | 899 ms | 6,0489 | 0,7561 | MATCH |
| `PAR-D1000-W4-…` | 4 | 1.521 ms | 3,5753 | 0,8938 | MATCH |
| `PAR-D1000-W2-…` | 2 | 2.859 ms | 1,9021 | 0,9510 | MATCH |
| `PAR-D1000-W1-…` | 1 | 5.581 ms | 0,9744 | 0,9744 | MATCH |
| `SEQ-D1000-L5000-…` | 1 | 5.438 ms | 1,0000 | 1,0000 | MATCH |

Angka dapat berubah antar-run karena durasi bersifat wall-clock, tetapi
checksum dan gate harus tetap `MATCH`.

---

## 5. Penanganan error

| Kondisi | Perilaku UI |
|---|---|
| View belum ada / migrasi belum jalan | Alert kuning + pesan error, tabel kosong |
| Belum ada batch | Pesan "Belum ada data benchmark" |
| Batch tanpa speedup (baseline belum ada) | Kolom speedup/efficiency menampilkan `-` |
| Batch integrasi | Gate `NO-BASELINE`, tetap tampil di riwayat |

Halaman tidak melakukan operasi tulis; seluruh aksi hanya baca dan refresh.

---

## 6. Catatan pengembangan M7

1. Tambahkan filter dataset/mode dan rentang tanggal.
2. Tambahkan grafik garis speedup vs jumlah worker.
3. Tampilkan `skipped_count` (saat ini hanya di payload reducer).
4. Ekspor CSV untuk laporan benchmark final.
5. Hubungkan ke halaman verifikasi order agar admin bisa drill-down dari
   angka agregat ke order yang MISMATCH.

Item-item di atas bersifat penyempurnaan; dashboard M6 sudah memenuhi
kebutuhan pemantauan awal.
