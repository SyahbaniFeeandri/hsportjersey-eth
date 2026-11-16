# 07 — Reproducibility Check (M7)

**Milestone:** M7 — Benchmark & Integration Test
**Issue terkait:** `W7-FRL-11`
**Pemilik:** Farelly De Rossi · **Validator:** Kenjiro B.H.G.
**Tanggal:** 13 November 2026, 03:38–03:40 WIB
**Status:** **PASS** — 16/16 checksum identik

---

## 1. Tujuan

Membuktikan benchmark M7 dapat direproduksi: dataset, hasil verifikasi, dan
gate checksum tidak bergantung pada state workspace sebelumnya.

---

## 2. Prosedur

1. Salin repo ke workspace kedua:
   `C:\Users\Feeandri\AppData\Local\Temp\hsp_repro`
   (tanpa `.git`, `node_modules`, `vendor`, `benchmark/datasets`, dan
   `docs/week7/evidence`).
2. Biarkan MySQL yang sama aktif; queue hanya berisi batch baru dari run ini.
3. Jalankan matrix dengan parameter identik:

   ```bash
   php benchmark/run_matrix.php \
     --sizes=100,500,1000,5000 \
     --workers=1,2,4,8 \
     --latency-us=5000
   ```

   Dataset **dibangkitkan ulang** dari seed `HSPORT-2026` (folder dataset
   sengaja tidak disalin).
4. Simpan hasil sebagai `docs/week7/evidence/repro_matrix.json`.
5. Bandingkan dengan run utama memakai:

   ```bash
   php tests/regression/check_reproducibility.php
   ```

---

## 3. Hasil

| Item | Run A (utama) | Run B (workspace kedua) | Status |
|---|---|---|---|
| Dataset hash D100 | `bf5073f8…` | `bf5073f8…` | sama |
| Dataset hash D500 | `734ddd14…` | `734ddd14…` | sama |
| Dataset hash D1000 | `253275fa…` | `253275fa…` | sama |
| Dataset hash D5000 | `2bed91df…` | `2bed91df…` | sama |
| Checksum 16 konfigurasi | — | — | **16/16 identik** |
| Checksum baseline D1000 | `1258b0c3…` | `1258b0c3…` | sama |
| Konfigurasi PASS | 16/16 | 16/16 | sama |
| Delta speedup maksimum | — | — | **8,03%** |
| Delta wall-clock maksimum | — | — | **6,73%** |
| Toleransi yang dipakai | — | — | 20% |

**Bukti:**
[`evidence/reproducibility_check.json`](evidence/reproducibility_check.json)
· [`evidence/reproducibility_check.md`](evidence/reproducibility_check.md)
· [`evidence/repro_matrix.json`](evidence/repro_matrix.json)

---

## 4. Perbandingan Speedup

| Konfigurasi | Speedup A | Speedup B | Δ speedup | Δ wall |
|---|---:|---:|---:|---:|
| D100 P=1 | 0,841 | 0,856 | 1,72% | 0,95% |
| D100 P=2 | 1,374 | 1,464 | 6,57% | 5,45% |
| D100 P=4 | 1,781 | 1,924 | 8,03% | 6,73% |
| D100 P=8 | 1,734 | 1,832 | 5,60% | 4,59% |
| D500 P=1 | 0,971 | 0,966 | 0,47% | 0,33% |
| D500 P=2 | 1,849 | 1,848 | 0,01% | 0,14% |
| D500 P=4 | 3,240 | 3,251 | 0,34% | 0,49% |
| D500 P=8 | 4,668 | 4,711 | 0,92% | 1,06% |
| D1000 P=1 | 0,981 | 0,983 | 0,21% | 0,00% |
| D1000 P=2 | 1,911 | 1,919 | 0,42% | 0,22% |
| D1000 P=4 | 3,531 | 3,574 | 1,22% | 1,00% |
| D1000 P=8 | 5,748 | 5,895 | 2,56% | 2,29% |
| D5000 P=1 | 0,993 | 0,996 | 0,28% | 0,03% |
| D5000 P=2 | 1,972 | 1,966 | 0,30% | 0,55% |
| D5000 P=4 | 3,882 | 3,862 | 0,50% | 0,75% |
| D5000 P=8 | 7,318 | 7,370 | 0,72% | 0,47% |

Pola yang terlihat:

- Dataset besar (≥1.000) sangat stabil: delta < 3%.
- Dataset kecil (D100) paling sensitif karena biaya tetap proses mendominasi;
  delta hingga 8% masih jauh di bawah toleransi 20%.
- **Checksum tidak pernah berubah** — inilah bukti utama reproducibility,
  karena checksum merangkum seluruh hasil verifikasi (status, revenue, AOV).

---

## 5. Interpretasi

| Pertanyaan | Jawaban |
|---|---|
| Apakah dataset deterministik? | Ya — hash identik meski dibangkitkan ulang di folder lain |
| Apakah hasil verifikasi deterministik? | Ya — 16/16 checksum identik |
| Apakah metrik waktu stabil? | Cukup — delta ≤ 8,03% pada mesin yang sama |
| Apakah gate benchmark konsisten? | Ya — 16/16 MATCH di kedua run |

---

## 6. Batasan

1. **Mesin fisik sama.** Workspace berbeda, tetapi CPU/disk/MySQL yang dipakai
   sama. Uji di komputer anggota lain dijadwalkan pada M8 (UAT-17 rehearsal).
2. **Mode A saja.** Benchmark memakai latensi simulasi; reproducibility Mode B
   (RPC Ganache) bergantung pada Ganache yang juga deterministik.
3. **Beban latar.** Run dilakukan tanpa beban berat lain; angka absolut dapat
   berbeda bila mesin sibuk. Karena itu gate utama adalah checksum, bukan
   waktu absolut.
4. **Seed tetap.** Mengganti `--seed` akan mengubah dataset hash dan checksum;
   reproducibility hanya berlaku untuk seed yang sama.

---

## 7. Cara Mengulang di Mesin Lain

```bash
# 1) Siapkan environment (PHP, MySQL, migration_parallel.sql)
# 2) Jalankan matrix
php benchmark/run_matrix.php --sizes=100,500,1000,5000 --workers=1,2,4,8 --latency-us=5000

# 3) Simpan hasil sebagai pembanding run A bila perlu
cp docs/week7/evidence/benchmark_matrix.json docs/week7/evidence/benchmark_matrix_runA.json

# 4) Jalankan ulang matrix di state bersih, lalu bandingkan
php tests/regression/check_reproducibility.php \
  --a=docs/week7/evidence/benchmark_matrix_runA.json \
  --b=docs/week7/evidence/benchmark_matrix.json \
  --tolerance=0.20
```

Kriteria lulus: seluruh checksum identik, semua gate `MATCH`, dan delta metrik
di bawah toleransi. Skrip akan menulis `reproducibility_check.json/.md` dan
keluar dengan kode 0 hanya bila lulus.
