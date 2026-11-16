# 05 — Review Konsistensi Benchmark & Dokumen (M7)

**Milestone:** M7 — Benchmark & Integration Test
**Issue terkait:** `W7-FEE-12`
**Reviewer:** Muhammad Feeandri Syahbani
**Pendukung:** Kenjiro B.H.G., Yasmin Akmaliyah
**Tanggal:** 13 November 2026

---

## 1. Ruang Lingkup Review

Review ini memeriksa apakah angka, status, dan artefak M7 konsisten satu sama
lain sebelum dipakai di laporan final dan demo:

1. Angka benchmark di laporan = isi JSON/CSV.
2. Checksum di laporan = checksum di evidence.
3. Status 21 test = output suite + `regression_summary.json`.
4. Dashboard menampilkan angka yang sama dengan laporan.
5. Tracker (`GAP_ANALYSIS`, `ROADMAP_RINCI`, traceability) = kondisi repo.
6. Tidak ada klaim PASS tanpa bukti yang dapat dibuka.

---

## 2. Checklist Konsistensi

| # | Yang diperiksa | Metode | Hasil |
|---:|---|---|---|
| 1 | Jumlah konfigurasi benchmark | `rows` di JSON vs tabel laporan (4×4) | ✅ 16 = 16 |
| 2 | Speedup & throughput | Hitung ulang dari `baseline_duration_ms`, `duration_ms`, `processed_orders` | ✅ 0 selisih (PAR-03.b) |
| 3 | Efficiency | `speedup / workers` | ✅ 0 selisih (PAR-03.c) |
| 4 | Checksum | Bandingkan kolom `checksum` vs `baseline_checksum` | ✅ 16/16 MATCH |
| 5 | Checksum D500/D1000 vs M6 | Bandingkan dengan `docs/week6/02_PAR_EVIDENCE.md` | ✅ sama (`61cfe1d0…`, `1258b0c3…`) |
| 6 | Regression | 12 SC + 5 INT + 4 PAR di `regression_summary.json` | ✅ 21/21 PASS |
| 7 | Bukti test | Setiap suite punya log di `docs/week7/evidence/` | ✅ 4 log suite + 2 JSON ringkasan |
| 8 | Dashboard | `admin/performance.php` memuat matrix, chart, dan 21/21 | ✅ HTTP 200, kartu & SVG tampil |
| 9 | Kontrak | Alamat di environment vs `blockchain/config/contract_address.txt` | ✅ `0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab` |
| 10 | Skema DB | `migration_parallel.sql` tidak berubah di M7 | ✅ tidak ada perubahan |
| 11 | Config | `config/parallel.php` vs nilai yang dipakai matrix | ✅ konsisten (latensi via flag, worker 1/2/4/8) |
| 12 | Tracker | Status M7 di `GAP_ANALYSIS`/`ROADMAP_RINCI`/traceability | ✅ 13/13 selesai; 21/21 PASS |
| 13 | Dokumentasi kredensial | README & panduan vs hash DB | ✅ diperbaiki (`customer123`) |

---

## 3. Temuan Review

| ID | Temuan | Tingkat | Tindakan |
|---|---|---|---|
| R-07-01 | Worker idle race membuat 2 konfigurasi matrix gagal | Tinggi | Diperbaiki (BUG-07-01), matrix diulang 16/16 |
| R-07-02 | Harness regresi salah memanggil `npm test` | Sedang | Diperbaiki (BUG-07-02), 21/21 |
| R-07-03 | Kredensial customer di dokumen salah | Rendah | Diperbaiki (BUG-07-03) |
| R-07-04 | Dashboard menampilkan seluruh batch historis (termasuk batch uji) | Rendah | Diterima: kartu M7 memakai matrix final sebagai rujukan; riwayat batch tetap berguna untuk audit |
| R-07-05 | `REQ-11` (retry verifikasi admin) masih `Sebagian` | Rendah | Di luar scope M7; dicatat untuk M8 |
| R-07-06 | Speedup P=1 < 1,0 berpotensi ditanya saat demo | Rendah | Dijelaskan di laporan §4.3 (overhead queue, baseline sequential murni) |

---

## 4. Pemeriksaan Angka Silang

### 4.1 Benchmark

| Dataset | Baseline (JSON) | Baseline (laporan) | Checksum (evidence) | Checksum (laporan) |
|---|---:|---:|---|---|
| D100 | 529 ms | 529 ms | `6ae585e5…` | `6ae585e5…` |
| D500 | 2.647 ms | 2.647 ms | `61cfe1d0…` | `61cfe1d0…` |
| D1000 | 5.271 ms | 5.271 ms | `1258b0c3…` | `1258b0c3…` |
| D5000 | 26.387 ms | 26.387 ms | `972e6a4d…` | `972e6a4d…` |

### 4.2 Test

| Level | Output suite | `regression_summary.json` | Laporan |
|---|---|---|---|
| SC | 16 passing (4 smoke + 12 SC) | 12/12 PASS | 12/12 PASS |
| INT | 5/5 PASS | 5/5 PASS | 5/5 PASS |
| PAR-01/02 | 24/24 CHECK PASS | PAR-01, PAR-02 PASS | PASS |
| PAR-03/04 | 9/9 CHECK PASS | PAR-03, PAR-04 PASS | PASS |

### 4.3 Dashboard

Diuji lewat HTTP dengan sesi admin
([`evidence/uat_http.log`](evidence/uat_http.log)):

- `GET /admin/performance.php` → 200
- Kartu "Benchmark Matrix Final" tampil
- Kartu "Regresi 21 Test" tampil dengan badge **21/21 PASS**
- Grafik SVG ter-embed
- Baris matrix D100..D5000 muncul

---

## 5. Konsistensi dengan Milestone Sebelumnya

| Milestone | Klaim | Verifikasi M7 |
|---|---|---|
| M4 | Kontrak V2, SC 12/12 | Dijalankan ulang → 12/12 PASS |
| M5 | INT 5/5, proof lengkap | Dijalankan ulang → 5/5 PASS |
| M6 | PAR-01/02 24/24, checksum D500/D1000 | Dijalankan ulang → 24/24; checksum sama |
| M7 | Benchmark + PAR-03/04 | 16/16 matrix; 9/9 PAR-03/04 |

Tidak ada klaim lama yang terbantahkan oleh hasil M7.

---

## 6. Rekomendasi untuk M8

1. Sertakan angka matrix final (bukan angka run pertama) di slide demo.
2. Saat demo, tunjukkan `benchmark_matrix.json` dan dashboard, bukan hanya slide.
3. Selesaikan `REQ-11` (aksi retry verifikasi di admin) sebelum demo bisnis.
4. Bekukan tag rilis setelah dokumentasi final selesai.
5. Jalankan ulang `tests/regression/run_all.php` pada mesin demo (bukan hanya
   laptop pengembang) sebagai bagian rehearsal.

---

## 7. Kesimpulan

| Pertanyaan review | Jawaban |
|---|---|
| Apakah benchmark reproducible? | **Ya** — 16/16 checksum identik pada run kedua; delta metrik < 9% |
| Apakah seluruh test punya status? | **Ya** — 21/21 PASS dengan log per suite |
| Apakah ada bug Sev-1? | **Tidak** |
| Apakah angka laporan sama dengan evidence? | **Ya** — diverifikasi silang |
| Apakah tracker sinkron? | **Ya** — M7 13/13 selesai |

**Status review: DISETUJUI** untuk dilanjutkan ke dokumentasi final dan M8.
