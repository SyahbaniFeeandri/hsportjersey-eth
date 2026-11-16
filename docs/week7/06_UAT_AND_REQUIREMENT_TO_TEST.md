# 06 — UAT Execution & Requirement-to-Test Final (M7)

**Milestone:** M7 — Benchmark & Integration Test
**Issue terkait:** `W7-YAS-11`
**Pemilik:** Yasmin Akmaliyah
**Pendukung:** Kenjiro B.H.G., Ihsanul, Luis
**Tanggal eksekusi:** 13 November 2026

---

## 1. Metode Eksekusi UAT

UAT dijalankan dengan tiga lapis bukti:

| Lapis | Cara | Cakupan |
|---|---|---|
| Otomatis kontrak | `npm test` (Truffle) | UAT-02..UAT-12 |
| Otomatis integrasi | `php tests/integration/run_all.php` | UAT-01, UAT-13 |
| Otomatis paralel | `run_par.php`, `run_par03_par04.php`, `run_matrix.php` | UAT-15, UAT-16 |
| HTTP/manual | Sesi admin & customer via PHP built-in server | UAT-14, UAT-16, UAT-17 |

Kredensial uji:

| Peran | Email | Password |
|---|---|---|
| Admin | `admin@hsport.com` | `admin123` |
| Customer | `customer@example.com` | `customer123` |

Bukti HTTP: [`evidence/uat_http.log`](evidence/uat_http.log)

---

## 2. Hasil UAT-01 s.d. UAT-17

| ID | Skenario | Aktor | Hasil | Bukti / Catatan |
|---|---|---|---|---|
| UAT-01 | Checkout kripto happy path | Pembeli | **PASS** | INT-02/INT-03: order DB #35 terhubung ke on-chain #1, tx & block tersimpan |
| UAT-02 | Amount salah ditolak | Pembeli | **PASS** | SC-03 revert |
| UAT-03 | Duplicate order hash ditolak | QA | **PASS** | SC-04 revert |
| UAT-04 | Operator catat pembayaran | Operator | **PASS** | SC-08 + event `PaymentRecorded` |
| UAT-05 | Non-operator catat pembayaran | Pihak luar | **PASS** | SC-07 revert `Not operator` |
| UAT-06 | Double payment ditolak | Operator | **PASS** | SC-08 revert |
| UAT-07 | Transisi produksi & pengiriman | Operator | **PASS** | SC-09 event + state benar |
| UAT-08 | Transisi tidak sah ditolak | Operator | **PASS** | SC-10 state tidak berubah |
| UAT-09 | Pembeli cancel di `Created` | Pembeli | **PASS** | SC-11 refund + event |
| UAT-10 | Pihak luar cancel | Pihak luar | **PASS** | SC-11 revert `Not authorized` |
| UAT-11 | Konfirmasi terima & release | Pembeli | **PASS** | SC-12 release escrow |
| UAT-12 | Release ganda ditolak | Pembeli | **PASS** | SC-12 revert |
| UAT-13 | Admin verifikasi DB vs chain | Admin | **PASS** | INT-05: VERIFIED → manipulasi → MISMATCH → restore VERIFIED |
| UAT-14 | Pembeli lihat proof | Pembeli | **PASS** | `GET /blockchain-proof.php?order_id=11` → 200, tx hash `0x3d8b9c89…` tampil |
| UAT-15 | Parallel = sequential | QA | **PASS** | PAR-01/02 checksum identik; matrix 16/16 MATCH |
| UAT-16 | Benchmark dashboard | Dosen/QA | **PASS** | Login admin → `/admin/performance.php` 200; kartu matrix, grafik SVG, dan badge 21/21 tampil |
| UAT-17 | Clean setup | Anggota lain | **PASS (dengan catatan)** | Workspace kedua dibuat dari salinan repo tanpa `node_modules`/dataset, matrix 16/16 PASS; lihat [`07_REPRODUCIBILITY.md`](07_REPRODUCIBILITY.md) |

**Rekap:** 17/17 PASS (UAT-17 PASS dengan catatan bahwa mesin fisik masih sama).

---

## 3. Catatan Eksekusi UAT

1. **UAT-01..UAT-13** tidak memerlukan klik MetaMask pada M7 karena langkah
   on-chain sudah diotomasi lewat Truffle + service integrasi. UAT manual
   berbasis UI pernah dijalankan pada M4/M5 dan hasilnya tetap valid karena
   kontrak tidak berubah.
2. **UAT-14** diuji lewat sesi customer nyata. Order 11 milik
   `demo_customer` (user id 2) dan menampilkan tx hash lengkap.
3. **UAT-16** diuji lewat sesi admin nyata. Halaman memuat data dari
   `v_benchmark_summary`, `benchmark_matrix.json`, `benchmark_chart.svg`, dan
   `regression_summary.json`.
4. **UAT-17** dijalankan sebagai reproduksi di workspace kedua pada mesin yang
   sama. Uji di komputer anggota lain dijadwalkan pada M8 (rehearsal).
5. Percobaan login customer dengan password lama (`password`) **gagal** dan
   tercatat sebagai BUG-07-03; dokumentasi sudah diperbaiki.

---

## 4. Requirement-to-Test Final

| REQ | Requirement (ringkas) | Test otomatis | UAT | Status akhir | Bukti utama |
|---|---|---|---|---|---|
| REQ-01 | Checkout membuat order off-chain + on-chain terhubung | INT-02 | UAT-01 | **Selesai** | `int_regression.log` |
| REQ-02 | `createOrder` validasi amount & lock escrow | SC-02, SC-03 | UAT-01, UAT-02 | **Selesai** | `sc_regression.log` |
| REQ-03 | Tolak `externalOrderHash` duplikat | SC-04 | UAT-03 | **Selesai** | `sc_regression.log` |
| REQ-04 | Hanya operator boleh catat pembayaran; double payment ditolak | SC-07, SC-08 | UAT-04..06 | **Selesai** | `sc_regression.log` |
| REQ-05 | 8 state + transisi tidak sah ditolak | SC-09, SC-10 | UAT-07, UAT-08 | **Selesai** | `sc_regression.log` |
| REQ-06 | Cancel oleh aktor berwenang; aktor lain ditolak | SC-11 | UAT-09, UAT-10 | **Selesai** | `sc_regression.log` |
| REQ-07 | Konfirmasi terima merilis escrow tepat sekali | SC-12 | UAT-11, UAT-12 | **Selesai** | `sc_regression.log` |
| REQ-08 | Event lengkap setiap perubahan state | SC-02/08/09/11/12 | UAT-04/07/09/11 | **Selesai** | `sc_regression.log` |
| REQ-09 | Simpan proof transaksi lengkap | INT-01, INT-03, INT-04 | UAT-01, UAT-04 | **Selesai** | `int_regression.log` |
| REQ-10 | Verifikasi DB vs chain `VERIFIED`/`MISMATCH` | INT-05 | UAT-13 | **Selesai** | `int_regression.log` |
| REQ-11 | Admin lihat status verifikasi + retry | INT-05 (sebagian) | UAT-13 | **Sebagian** | Kolom verifikasi ada; aksi retry manual belum diotomasi |
| REQ-12 | Halaman proof blockchain pembeli | — (manual) | UAT-14 | **Selesai** | `uat_http.log` |
| REQ-13 | Sequential & parallel menghasilkan hasil identik | PAR-01, PAR-02 | UAT-15 | **Selesai** | `par01_par02_regression.log` |
| REQ-14 | Ukur execution time, speedup, efficiency, throughput | PAR-03, PAR-04 | UAT-16 | **Selesai** | `benchmark_matrix.json`, `par03_par04_test.json` |
| REQ-15 | Reproducible + kontribusi tertelusur | — (manual) | UAT-17 | **Sebagian** | `reproducibility_check.json` (16/16 checksum identik); tag rilis baru di M8 |

### Ringkasan

| Status | Jumlah | REQ |
|---|---:|---|
| Selesai | 13 | REQ-01..REQ-10, REQ-12..REQ-14 |
| Sebagian | 2 | REQ-11, REQ-15 |
| Belum | 0 | — |
| **Total** | **15** | |

Perubahan dari M6: **REQ-14** naik dari `Belum` → `Selesai`; **REQ-15** tetap
`Sebagian` karena reproduksi sudah terbukti tetapi tag rilis/clean setup di
komputer anggota lain baru dikerjakan pada M8.

---

## 5. Test-to-Requirement (21 test)

| Test | REQ | Status |
|---|---|---|
| SC-01 | REQ-02 | PASS |
| SC-02 | REQ-01, REQ-02, REQ-08 | PASS |
| SC-03 | REQ-02 | PASS |
| SC-04 | REQ-03 | PASS |
| SC-05 | REQ-01, REQ-10 | PASS |
| SC-06 | REQ-10 | PASS |
| SC-07 | REQ-04, REQ-06 | PASS |
| SC-08 | REQ-04, REQ-08 | PASS |
| SC-09 | REQ-05, REQ-08 | PASS |
| SC-10 | REQ-05 | PASS |
| SC-11 | REQ-06, REQ-08 | PASS |
| SC-12 | REQ-07, REQ-08 | PASS |
| INT-01 | REQ-09 | PASS |
| INT-02 | REQ-01, REQ-09 | PASS |
| INT-03 | REQ-09 | PASS |
| INT-04 | REQ-09 | PASS |
| INT-05 | REQ-10, REQ-11 | PASS |
| PAR-01 | REQ-13 | PASS |
| PAR-02 | REQ-13 | PASS |
| PAR-03 | REQ-14 | PASS |
| PAR-04 | REQ-14, NFR-01 | PASS |

Tidak ada orphan test; tidak ada requirement Must tanpa verifikasi.

---

## 6. Kesimpulan UAT

| Kriteria lulus | Target | Hasil |
|---|---|---|
| Seluruh UAT Must (UAT-01..13) PASS | 13/13 | ✅ 13/13 |
| UAT disetujui/diobservasi | Ada bukti | ✅ log + JSON + HTTP |
| Requirement tanpa verifikasi | 0 | ✅ 0 |
| Test PASS | 21/21 | ✅ 21/21 |
