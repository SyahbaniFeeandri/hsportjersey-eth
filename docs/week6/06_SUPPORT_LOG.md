# Support Log — Bug Fixing & Maintenance Environment

**Issue:** `W6-ARB-10`, `W6-AGU-10` (support bug fixing), `W6-FRL-10` (maintenance environment)
**Pemilik:** Arby, Agustina, Farrel
**Kontributor implementasi:** Farelly, Ihsanul, Kenjiro
**Periode:** 12–13 November 2026
**Status:** Selesai — 3 bug internal diperbaiki, tidak ada bug kontrak

---

## 1. Ringkasan

| Kategori | Jumlah | Status |
|---|---:|---|
| Bug pipeline paralel (reducer/runner) | 3 | ✅ Diperbaiki & diuji ulang |
| Bug skema/view | 1 | ✅ Diperbaiki & migrasi diterapkan |
| Bug kontrak (SC-01..SC-12) | 0 | ✅ Tidak ada |
| Masalah environment | 2 | ✅ Diselesaikan |

Catatan: bug pipeline dan view dihitung dalam satu kelompok perbaikan M6;
daftar di bawah memisahkan temuan agar jejak audit jelas.

---

## 2. Bug pipeline & perbaikan

### BUG-M6-01 — `SKIPPED` dihitung sebagai `ERROR`

| Field | Isi |
|---|---|
| Ditemukan | Saat smoke integrasi batch #39 |
| Gejala | `metrics_consistent=false`; order tanpa `blockchain_order_id` masuk `error_count` |
| Penyebab | `Reducer` hanya membedakan `VERIFIED`, `MISMATCH`, dan "sisanya error" |
| Dampak | Agregasi integrasi tidak mencerminkan kondisi nyata; gate bisa gagal palsu |
| Perbaikan | Cabang eksplisit `SKIPPED`; tambah `skipped_count` di ringkasan reducer dan output CLI |
| Verifikasi | Batch #39: `MISMATCH=1`, `SKIPPED=20`, `ERROR=0`, `metrics_consistent=yes` |

### BUG-M6-02 — `duration_ms` tertimpa saat reduce ulang

| Field | Isi |
|---|---|
| Ditemukan | Saat uji idempotensi reducer |
| Gejala | Reduce kedua menimpa wall-clock asli dengan durasi sejak `started_at` |
| Penyebab | Reducer selalu menghitung ulang durasi bila override tidak diberikan |
| Dampak | Speedup/efficiency berubah hanya karena reducer dijalankan ulang |
| Perbaikan | Pertahankan `duration_ms` batch yang sudah tersimpan |
| Verifikasi | RACE-02: checksum dan checkpoint tetap; durasi tidak berubah |

### BUG-M6-03 — Worker bisa menganggur saat job tersedia

| Field | Isi |
|---|---|
| Ditemukan | Saat merancang spawning worker di runner |
| Gejala | Satu proses worker bisa mengambil lebih dari satu job, sehingga worker lain idle dan bukti "job berbeda" melemah |
| Penyebab | Default worker melakukan loop sampai antrean kosong |
| Dampak | Jumlah worker unik tidak sama dengan jumlah partisi |
| Perbaikan | Runner men-spawn satu proses per partisi dengan `--max-jobs=1`; loop tetap tersedia untuk operasi normal |
| Verifikasi | P=1/2/4/8: `distinct_workers = jobs_total`, `max_attempts = 1` |

---

## 3. Bug skema & view

### BUG-M6-04 — Baris ganda pada `v_benchmark_summary`

| Field | Isi |
|---|---|
| Ditemukan | Saat menghubungkan dashboard ke view |
| Gejala | Satu batch muncul beberapa kali ketika ada lebih dari satu baseline untuk dataset yang sama |
| Penyebab | Join langsung ke seluruh baris `is_baseline = 1` |
| Dampak | Speedup bisa tampil ganda/ambigu di dashboard |
| Perbaikan | Derived table `MAX(id)` per `(dataset_name, dataset_size, mode)` lalu join ke satu baseline |
| Verifikasi | Query view mengembalikan satu baris per batch; dashboard menampilkan speedup konsisten |

Migrasi sudah diterapkan ulang ke MySQL `h_sport_db`.

---

## 4. Dukungan kontrak (ARB/AGU)

Pipeline M6 **tidak mengubah** kontrak `HSportStore`. Verifikasi Mode B memakai
`VerificationService` dan `ContractService` yang sudah ada dari M5.

| Pemeriksaan | Hasil |
|---|---|
| Perubahan ABI/signature | Tidak ada |
| Perubahan state machine | Tidak ada |
| Revert rule baru | Tidak ada |
| Regresi SC-01..SC-12 | Tidak dijalankan ulang di M6; hasil M4 tetap berlaku (12/12 PASS) |
| Bug kontrak dilaporkan worker | Tidak ada |

Alasan: seluruh kegagalan M6 berasal dari orkestrasi/reduksi, bukan dari
kontrak. Bila M7 menemukan anomali chain, kontrak akan diuji ulang saat itu.

---

## 5. Maintenance environment (FRL)

### 5.1 Kondisi lingkungan

| Komponen | Nilai |
|---|---|
| OS | Windows (Laragon) |
| PHP | 8.1.10 (`C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe`) |
| MySQL | 8.0.30, `127.0.0.1:3306`, user `root`, DB `h_sport_db` |
| Ganache | Tidak diperlukan untuk Mode A |
| Dataset | D100, D500, D1000, D5000 di `benchmark/datasets/` |

### 5.2 Tindakan maintenance

1. **Menyalakan MySQL** yang sempat mati:
   ```powershell
   Start-Process -FilePath "C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqld.exe" `
     -ArgumentList "--defaults-file=C:\laragon\bin\mysql\mysql-8.0.30-winx64\my.ini" `
     -WindowStyle Hidden
   Test-NetConnection 127.0.0.1 -Port 3306 -InformationLevel Quiet
   ```
2. **Menerapkan ulang `migration_parallel.sql`** untuk memperbaiki view dan
   memastikan empat tabel tersedia.
3. **Membersihkan `job_batches`** sebelum suite final agar evidence hanya
   memuat run yang relevan.
4. **Menjalankan ulang suite PAR-01/PAR-02** dan benchmark D1000.
5. **Menjalankan smoke integrasi Mode B** (2 worker, batch #39).

### 5.3 Checklist kesehatan

| Cek | Perintah | Harapan |
|---|---|---|
| MySQL hidup | `Test-NetConnection 127.0.0.1 -Port 3306` | `True` |
| Tabel ada | `SHOW TABLES LIKE 'job_batches'` | 1 baris |
| View valid | `SELECT * FROM v_benchmark_summary LIMIT 1` | Tidak error |
| Suite lulus | `php tests/parallel/run_par.php` | 24/24 PASS |
| Dashboard | Buka `admin/performance.php` | Data tampil |

### 5.4 Catatan operasional

- `migration_parallel.sql` bersifat idempotent untuk tabel
  (`CREATE TABLE IF NOT EXISTS`), tetapi view selalu `DROP` lalu dibuat ulang.
- Menjalankan runner tanpa `--no-baseline-run` akan membuat baseline baru;
  ini disengaja agar perbandingan selalu memakai baseline segar.
- `.log` diabaikan `.gitignore`; evidence log ditambahkan dengan
  `git add -f`.
- Ganache hanya perlu dinyalakan untuk Mode B ketat atau demo integrasi.

---

## 6. Kesimpulan

| Issue | Hasil |
|---|---|
| `W6-ARB-10` | ✅ Tidak ada bug kontrak; dukungan diberikan pada analisis kegagalan pipeline |
| `W6-AGU-10` | ✅ Tidak ada perubahan state/revert; kontrak stabil |
| `W6-FRL-10` | ✅ Environment dipulihkan, migrasi diterapkan ulang, suite dijalankan ulang |

Seluruh bug yang ditemukan sudah diperbaiki dan diverifikasi lewat suite
otomatis serta smoke integrasi. Tidak ada bug terbuka yang menghalangi exit
gate M6.
