# Bukti PAR-01 & PAR-02

**Issue:** `W6-FDR-11` (eksekusi), `W6-KEN-06` (verifikasi independen)
**Pemilik:** Farelly De Rossi
**Validator:** Kenjiro B.H.G.
**Tanggal eksekusi:** 13 November 2026
**Status:** ✅ **24/24 CHECK PASS**

---

## 1. Definisi uji

| Test | Definisi | Kriteria lulus |
|---|---|---|
| **PAR-01** | Sequential baseline: waktu & agregasi | 500 order diproses 1 worker; komposisi, revenue, paid/unpaid, AOV, dan checksum valid |
| **PAR-02** | Parallel multi-worker partition/reduce | Untuk P=1/2/4/8: checksum = sequential, partisi menutup 1..N, setiap job diklaim tepat satu worker, `max_attempts=1`, tanpa duplikasi |

Perintah:

```bash
php tests/parallel/run_par.php --latency-us=5000 --workers=1,2,4,8
```

Dataset: `benchmark/datasets/D500.json`
(`dataset_hash = 734ddd14ea450a82…`, 500 order, seed `HSPORT-2026`).

---

## 2. PAR-01 — Sequential baseline

| Properti | Nilai |
|---|---|
| Total order | 500 |
| Worker | 1 |
| Durasi | 2.693 ms |
| VERIFIED / MISMATCH / ERROR | 350 / 100 / 50 |
| Paid / Unpaid | 428 / 72 |
| Revenue all | Rp 612.423.000 |
| AOV | Rp 1.224.846 |
| Checksum | `61cfe1d0d9769ea1d3a737adc39e9a41c56ffbaadbb7f8855507f6bacb309939` |

Komposisi di atas sama persis dengan metadata dataset (350/100/50), sehingga
baseline tidak kehilangan atau menambah order.

| Check | Status |
|---|---|
| `PAR-01.a` Baseline selesai & tersimpan | ✅ PASS |
| `PAR-01.b` Komposisi sesuai dataset | ✅ PASS |
| `PAR-01.c` Agregasi finansial konsisten | ✅ PASS |
| `PAR-01.d` Checksum sha256 valid | ✅ PASS |

---

## 3. PAR-02 — Parallel 1/2/4/8 worker (D500)

| P | Partisi | Jobs | Worker unik | Max attempts | Durasi | Speedup | Checksum | Duplikat |
|---:|---|---:|---:|---:|---:|---:|---|---:|
| 1 | 1..500 | 1/1 | 1 | 1 | 2.836 ms | 0,955 | `61cfe1d0…` MATCH | 0 |
| 2 | 1..250, 251..500 | 2/2 | 2 | 1 | 1.473 ms | 1,838 | `61cfe1d0…` MATCH | 0 |
| 4 | 1..125 … 376..500 | 4/4 | 4 | 1 | 811 ms | 3,338 | `61cfe1d0…` MATCH | 0 |
| 8 | 1..63 … 439..500 | 8/8 | 8 | 1 | 574 ms | 4,716 | `61cfe1d0…` MATCH | 0 |

Semua run menghasilkan checksum yang sama dengan baseline sequential. Partisi
P=8 membagi 500 menjadi 8 rentang (63 order untuk empat partisi pertama, 62
untuk sisanya) dan tetap menutup 1..500 tanpa gap/overlap.

| Check per P | Status |
|---|---|
| `PAR-02.a` Runner selesai & semua gate lulus | ✅ PASS |
| `PAR-02.b` Checksum parallel = sequential untuk setiap P | ✅ PASS |
| `PAR-02.Pn.a` Checksum & jumlah order sama | ✅ PASS (4/4) |
| `PAR-02.Pn.b` Partisi menutup 1..500 | ✅ PASS (4/4) |
| `PAR-02.Pn.c` Metrik finansial & bebas duplikasi | ✅ PASS (4/4) |
| `PAR-02.Pn.d` Tidak ada job diklaim dua kali | ✅ PASS (4/4) |

Total check PAR-02: 2 + 4×4 = 18, seluruhnya PASS.

---

## 4. Benchmark tambahan — D1000

Untuk memastikan perilaku tetap benar pada dataset lebih besar:

```bash
php benchmark/parallel_runner.php \
  --dataset=benchmark/datasets/D1000.json \
  --workers=1,2,4,8 --latency-us=5000 \
  --json-out=docs/week6/evidence/par_d1000.json
```

| P | Durasi | Speedup | Efficiency | Throughput | Checksum |
|---:|---:|---:|---:|---:|---|
| 1 | 5.560 ms | 0,989 | 98,9% | 179,86 order/s | `1258b0c3…` MATCH |
| 2 | 2.810 ms | 1,957 | 97,8% | 355,87 order/s | `1258b0c3…` MATCH |
| 4 | 1.512 ms | 3,636 | 90,9% | 661,38 order/s | `1258b0c3…` MATCH |
| 8 | 902 ms | 6,095 | 76,2% | 1.108,65 order/s | `1258b0c3…` MATCH |

Baseline sequential D1000: **5.438 ms**. Checksum seluruh P identik
(`1258b0c3790fa9c4…`), jobs `8/8`, worker unik `8`, `maxAttempts=1`.

Penurunan efficiency pada P=8 wajar: overhead spawn proses, klaim job, dan
reduce mulai dominan ketika durasi kerja per partisi mengecil.

---

## 5. Race condition & idempotensi

| Check | Hasil | Detail |
|---|---|---|
| `RACE-01` Lease recovery | ✅ PASS | Job `RUNNING` dengan lease 600 detik dikembalikan; `status=DONE`, `attempts=2`, `checkpoints=1` |
| `RACE-02` Reducer idempoten | ✅ PASS | Reduce dua kali → checksum sama (`61cfe1d0…`), checkpoint tetap 8 |

Rincian analisis ada di [`03_RACE_CONDITION_REVIEW.md`](03_RACE_CONDITION_REVIEW.md).

---

## 6. Ringkasan suite

```text
===============================================================
   HASIL AKHIR: 24 / 24 CHECK PASS
   Status: ALL PASS (100%)
===============================================================
```

Rincian 24 check: 4 (PAR-01) + 2 (PAR-02.a/b) + 16 (PAR-02 per P)
+ 2 (RACE) = 24.

---

## 7. Artefak

| File | Isi |
|---|---|
| [`evidence/par01_par02_test.log`](evidence/par01_par02_test.log) | Log lengkap suite 24 check |
| [`evidence/par01_par02_test.json`](evidence/par01_par02_test.json) | Hasil terstruktur + checksum + partisi |
| [`evidence/par01_sequential.json`](evidence/par01_sequential.json) | Ringkasan baseline PAR-01 |
| [`evidence/par02_parallel.json`](evidence/par02_parallel.json) | Ringkasan PAR-02 per P |
| [`evidence/par_d1000.log`](evidence/par_d1000.log) | Log benchmark D1000 |
| [`evidence/par_d1000.json`](evidence/par_d1000.json) | Hasil D1000 terstruktur |
| [`evidence/int_m6_smoke.json`](evidence/int_m6_smoke.json) | Smoke integrasi Mode B (batch #39) |

### Cara mereproduksi

```bash
# MySQL harus aktif; migration_parallel.sql sudah dijalankan
php tests/parallel/run_par.php --latency-us=5000 --workers=1,2,4,8

# Bandingkan checksum
php -r '$j=json_decode(file_get_contents("docs/week6/evidence/par01_par02_test.json"),true); echo $j["par01"]["checksum"], PHP_EOL;'
```

Checksum bersifat deterministik: dataset yang sama + aturan verifikasi yang
sama selalu menghasilkan nilai yang sama, terlepas dari jumlah worker.
