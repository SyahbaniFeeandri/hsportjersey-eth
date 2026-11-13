# 03 — Bug Report & Retest (M7)

**Milestone:** M7 — Benchmark & Integration Test
**Issue terkait:** `W7-KEN-10`, `W7-LUI-13`, `W7-ARB-11`, `W7-AGU-11`
**Pemilik:** Kenjiro B.H.G. · **Fix:** Farelly De Rossi, Kenjiro B.H.G., Luis
**Tanggal:** 13 November 2026

| ID | Severity | Judul | Status |
|---|---|---|---|
| BUG-07-01 | **S2 High** | Worker idle race → partisi PENDING, checksum DIFFERENT | Closed (retest PASS) |
| BUG-07-02 | S3 Medium | Harness regresi salah mengutip `npm test` → SC 0/12 | Closed (retest PASS) |
| BUG-07-03 | S4 Low | Kredensial customer di README/panduan tidak cocok | Closed (login PASS) |

Tidak ada bug **Sev-1**. Tidak ada bug kontrak baru (`W7-ARB-11`, `W7-AGU-11`).

---

## BUG-07-01 — Worker idle race pada benchmark multi-worker

### 1. Identitas

| Field | Isi |
|---|---|
| Bug ID | `BUG-07-01` |
| Ditemukan oleh | Kenjiro B.H.G. (saat run matrix pertama) |
| Tanggal | 13 November 2026, ±03:31 WIB |
| Test terkait | PAR-02, PAR-03, benchmark matrix |
| Environment | Windows, PHP 8.1.10, MySQL 8.0.30, mode `ALGORITHM` |
| Severity / Prioritas | S2 / P1 |
| Owner fix | Farelly De Rossi + Kenjiro B.H.G. |
| Status | Closed |

### 2. Ringkasan

Pada run matrix pertama, dua konfigurasi menghasilkan hasil salah:

- **D100 P=4**: gate `DIFFERENT` (checksum paralel ≠ baseline).
- **D1000 P=8**: hanya 875/1000 order diproses; partisi 8 tetap `PENDING`;
  `verified/mismatch/error` lebih kecil dari baseline.

Penyebabnya bukan algoritma verifikasi, melainkan **penjadwalan worker**:
proses worker yang dijadwalkan terakhir bisa menemukan antrean job kosong
karena worker lain sudah mengklaim semua job, lalu keluar tanpa memproses
partisinya.

### 3. Precondition

| Item | Nilai |
|---|---|
| Dataset | `benchmark/datasets/D100.json`, `D1000.json` |
| Latensi | 5.000 µs/order |
| Worker | D100: P=4; D1000: P=8 |
| Queue | 1 batch, P job `PENDING` (satu per partisi) |

### 4. Langkah Reproduksi

1. Jalankan `php benchmark/run_matrix.php --sizes=100,1000 --workers=4,8`.
2. Amati output P=4 D100 dan P=8 D1000.
3. Periksa `docs/week7/evidence/parallel_D1000.json` → `runs[P=8]`.

**Tingkat reproduksi:** Sering (bergantung urutan start proses; muncul pada
2 dari 8 konfigurasi di run pertama).

### 5. Expected vs Actual

| | Isi |
|---|---|
| **Expected** | Setiap partisi diproses tepat satu worker; `processed_orders = dataset_size`; checksum MATCH |
| **Actual** | Partisi 8 `PENDING`; `processed_orders = 875`; checksum DIFFERENT |
| **Pesan error** | Tidak ada error eksplisit — worker keluar dengan exit 0 karena `claimJob()` mengembalikan `null` |

```text
--- P=8 | batch #54 (PAR-D1000-W8-...) | 8 partisi ---
[par-8-w1] Job #148 partisi 1 (1..125)
...
[par-8-w7] Job #154 partisi 7 (751..875)
# partisi 8 tidak pernah diproses; job #155 tetap PENDING
```

### 6. Dampak

| Aspek | Dampak |
|---|---|
| Finansial | Agregasi revenue tidak lengkap (hanya 87,5% order) |
| Keamanan | Tidak ada |
| Data | Checkpoint/agregasi tidak lengkap untuk batch tersebut |
| Pengguna | Angka benchmark menyesatkan bila tidak diperiksa |
| Milestone | Menghambat exit gate M7 (benchmark tidak reproducible) |

### 7. Root Cause

| Item | Isi |
|---|---|
| File | `benchmark/parallel_runner.php`, `workers/benchmark_worker.php` |
| Fungsi | Spawn worker + `claimJob()` |
| Penyebab | Runner men-spawn P proses sekaligus; setiap worker memakai `--max-jobs=1` dan mengklaim **job PENDING pertama** (`ORDER BY partition_no LIMIT 1 FOR UPDATE SKIP LOCKED`). Karena startup proses tidak serempak, worker yang lebih dulu jalan bisa mengklaim job milik partisi lain; worker terakhir menemukan antrean kosong dan idle. |
| Mengapa lolos dari test | M6 menguji P=1/2/4/8 dengan suite yang sama, tetapi waktu itu job tersedia berurutan dan seluruh worker panjang umur (`--loop`). Pola satu-proses-satu-job belum pernah diuji dengan spawn serentak. |

### 8. Perbaikan

| Item | Isi |
|---|---|
| Pendekatan fix | **Pre-assign satu job per proses.** Runner membaca `verification_jobs` setelah enqueue, memetakan `partition_no → job_id`, lalu men-spawn worker dengan `--job-id=<id>`. Worker hanya boleh mengklaim job tersebut. |
| File berubah | `benchmark/parallel_runner.php`, `workers/benchmark_worker.php`, `workers/verify_worker.php` |
| Detail worker | `claimJob(PDO, batchId, workerId, ?int $jobId)` menambah filter `AND id = ?` bila `--job-id` diberikan; tetap memakai `FOR UPDATE SKIP LOCKED` sehingga dua worker dengan job sama tetap aman (dibuktikan PAR-04.a). |
| Test baru | `tests/parallel/run_par03_par04.php` → PAR-04.a (dua worker satu job) |
| Risiko regresi | Rendah: jalur tanpa `--job-id` (worker loop/manual) tidak berubah; PAR-01/PAR-02 dijalankan ulang dan tetap 24/24 PASS. |

### 9. Retest

| Tanggal | Oleh | Environment | Hasil | Bukti |
|---|---|---|---|---|
| 13 Nov 2026 | Kenjiro | Matrix 4×4 | **16/16 PASS** | `evidence/benchmark_matrix.json`, `evidence/benchmark_matrix.log` |
| 13 Nov 2026 | Kenjiro | Workspace kedua | **16/16 PASS**, checksum identik | `evidence/repro_matrix.json`, `evidence/reproducibility_check.json` |
| 13 Nov 2026 | Kenjiro | PAR-04 race | **PASS** (`attempts=1`, `checkpoints=1`) | `evidence/par03_par04_test.json` |
| 13 Nov 2026 | Kenjiro | Regresi penuh | **21/21 PASS** | `evidence/regression_summary.json` |

**Regression dijalankan:** SC-01–SC-12, INT-01–INT-05, PAR-01–PAR-04.

### 10. Verifikasi Penutupan

- [x] Fix ada di working tree M7 (commit M7).
- [x] Test yang gagal sebelumnya sekarang PASS.
- [x] Tidak ada test lain yang rusak (21/21).
- [x] Bukti retest dilampirkan.
- [x] Direview saat consistency review M7.
- [x] Status `Closed`.

---

## BUG-07-02 — Harness regresi salah mengutip `npm test`

### 1. Identitas

| Field | Isi |
|---|---|
| Bug ID | `BUG-07-02` |
| Ditemukan oleh | Kenjiro B.H.G. |
| Tanggal | 13 November 2026, 03:36 WIB |
| Test terkait | SC-01..SC-12 (regression runner) |
| Severity / Prioritas | S3 / P2 |
| Owner fix | Kenjiro B.H.G. |
| Status | Closed |

### 2. Ringkasan

Run pertama `php tests/regression/run_all.php` melaporkan **9/21 PASS**:
seluruh SC gagal karena suite tidak mencetak hasil. Log berisi:

```text
'"npm test' is not recognized as an internal or external command,
operable program or batch file.
```

Perintah Truffle yang benar tetap berjalan saat dijalankan manual; masalahnya
ada pada cara runner memanggilnya.

### 3. Root Cause

| Item | Isi |
|---|---|
| File | `tests/regression/run_all.php` · fungsi `regRun()` |
| Penyebab | `proc_open()` mode **array** mengutip setiap argumen. Bentuk `['cmd.exe','/c','npm test']` menjadi `cmd.exe /c "npm test"`, dan `cmd.exe` memperlakukan `"npm test"` sebagai satu nama program sehingga gagal. |
| Mengapa lolos | Skrip baru; belum pernah dijalankan sebelum M7. |

### 4. Perbaikan

| Item | Isi |
|---|---|
| Pendekatan | Tambah `regRunShell(string $command, ...)` yang memakai `proc_open()` mode **string**, lalu panggil `cmd.exe /c npm test`. |
| File berubah | `tests/regression/run_all.php` |
| Risiko | Rendah; hanya jalur SC. Suite PHP lain tetap memakai mode array. |

### 5. Retest

| Tanggal | Hasil | Bukti |
|---|---|---|
| 13 Nov 2026 | SC **12/12** terdeteksi, exit 0 | `evidence/sc_regression.log` |
| 13 Nov 2026 | Regresi penuh **21/21 PASS** | `evidence/regression_summary.md` |

Status: **Closed**.

---

## BUG-07-03 — Kredensial akun customer tidak cocok dengan dokumentasi

### 1. Identitas

| Field | Isi |
|---|---|
| Bug ID | `BUG-07-03` |
| Ditemukan oleh | Kenjiro B.H.G. saat UAT HTTP |
| Tanggal | 13 November 2026, 03:45 WIB |
| Test terkait | UAT-01, UAT-14, UAT-17 (manual) |
| Severity / Prioritas | S4 / P3 |
| Owner fix | Kenjiro B.H.G. |
| Status | Closed |

### 2. Ringkasan

`README.md` dan `docs/PANDUAN_SETUP_DAN_OPERASIONAL.md` mencantumkan akun
customer `customer@example.com` / `password`. Login gagal (POST tetap di
halaman login). Hash di DB untuk akun tersebut adalah
`md5('customer123')`, bukan `md5('password')`.

### 3. Root Cause

| Item | Isi |
|---|---|
| File | `README.md`, `docs/PANDUAN_SETUP_DAN_OPERASIONAL.md` |
| Penyebab | Password seed berubah saat data demo diperbarui, dokumentasi tidak ikut diperbarui. |

### 4. Perbaikan & Retest

| Item | Isi |
|---|---|
| Fix | Dokumentasi diubah menjadi `customer123` (akun `demo_customer`, user id 2, punya riwayat order 11–16). |
| File berubah | `README.md`, `docs/PANDUAN_SETUP_DAN_OPERASIONAL.md` |
| Retest | Login `customer@example.com` / `customer123` → 302 ke `index.php`; `orders.php` 200 menampilkan order; `blockchain-proof.php?order_id=11` 200 menampilkan tx hash lengkap. Bukti: `evidence/uat_http.log`. |

Status: **Closed**.

---

## 4. Perbaikan integrasi dari bug report (`W7-LUI-13`)

Bug BUG-07-01 juga berdampak pada worker Mode B. Perbaikan yang menyentuh
integrasi:

| Perubahan | Dampak |
|---|---|
| `workers/verify_worker.php` menerima `--job-id` | Worker dapat diarahkan ke job tertentu; dipakai untuk retest lease recovery dan penugasan deterministik |
| Klaim job memakai `AND id = ?` bila target diberikan | Tidak ada lagi worker yang memproses partisi milik worker lain |
| Lease recovery tetap berjalan | Job `RUNNING` dengan lease kadaluarsa dipulihkan (PAR-04.d PASS) |

Bukti integrasi ulang: `evidence/int_regression.log` — INT-01..INT-05 **5/5 PASS**
terhadap Ganache + MySQL nyata, dengan kontrak `0xe78A…a8Ab`.

---

## 5. Perbaikan kontrak dari bug report (`W7-ARB-11`, `W7-AGU-11`)

Tidak ada bug kontrak baru pada M7.

| Item | Hasil |
|---|---|
| Perubahan `HSportStore.sol` | Tidak ada |
| Alamat kontrak | Tetap `0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab` (deploy deterministik) |
| SC-01..SC-12 | 12/12 PASS |
| Kesimpulan | Tidak diperlukan perbaikan kontrak; isu M7 murni pada orkestrasi benchmark dan harness test |
