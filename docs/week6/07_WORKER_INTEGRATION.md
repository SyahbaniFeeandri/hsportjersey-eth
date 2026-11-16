# Dukungan Verification Service untuk Worker

**Issue:** `W6-LUI-12`
**Pemilik:** Luis (Integration Engineer)
**Pendukung:** Farelly (worker/queue)
**Status:** Selesai — smoke integrasi 2 worker PASS

Dokumen ini menjelaskan bagaimana worker paralel memakai layanan verifikasi
yang sudah dibangun di M5, termasuk perbedaan Mode A dan Mode B serta
semantik status `SKIPPED`.

---

## 1. Posisi worker dalam arsitektur

```text
enqueue_batch.php
      │  membuat batch INTEGRATION + P job
      ▼
verification_jobs ──► verify_worker.php (1 proses per worker)
                            │
                            ├── ContractService      (baca kontrak)
                            ├── ProofRepository      (baca bukti DB)
                            └── VerificationService  (VERIFIED / MISMATCH)
                            │
                            ▼
                      checkpoints (1 per job)
                            │
                            ▼
                      reduce_batch.php ──► job_batches (DONE/FAILED)
```

Worker tidak mengimplementasikan aturan verifikasi sendiri. Ia memanggil
`VerificationService::verifyOrder()` agar hasilnya konsisten dengan halaman
bukti dan API M5.

---

## 2. Layanan yang dipakai

| Layanan | Metode | Peran di worker |
|---|---|---|
| `BlockchainClient` | `call()` | Membaca state kontrak via JSON-RPC |
| `ContractService` | `getOrder()`, `isOperator()` | Mengambil data on-chain |
| `ProofRepository` | `findByOrderId()` | Mengambil bukti tersimpan (tx hash, order ID chain) |
| `VerificationService` | `verifyOrder()` | Membandingkan DB vs chain, menghasilkan status + `differences` |

Worker menambahkan lapisan orkestrasi: klaim job, iterasi rentang ID,
normalisasi `differences`, agregasi metrik, checksum, dan checkpoint.

---

## 3. Alur Mode B

### 3.1 Membuat batch

```bash
php workers/enqueue_batch.php --workers=2 --from-id=1 --to-id=40 --code=INT-M6-SMOKE-001
```

Opsi:

| Opsi | Default | Keterangan |
|---|---|---|
| `--workers=P` | 4 | Jumlah partisi |
| `--from-id` | MIN(id) | Awal rentang order |
| `--to-id` | MAX(id) | Akhir rentang order |
| `--code=CODE` | `INT-<timestamp>` | Kode batch unik |

Batch dicatat sebagai `mode=INTEGRATION`, `dataset_name=orders-live`,
`is_baseline=0`, `status=PENDING`. Setiap partisi menjadi satu baris
`verification_jobs` berstatus `PENDING`.

### 3.2 Menjalankan worker

```bash
php workers/verify_worker.php --batch=39 --worker=int-2-w1 --max-jobs=1
php workers/verify_worker.php --batch=39 --worker=int-2-w2 --max-jobs=1
```

Opsi:

| Opsi | Default | Keterangan |
|---|---|---|
| `--batch=ID` | semua batch | Batasi ke satu batch |
| `--worker=ID` | `w-<pid>` | Identitas worker untuk audit |
| `--loop` | mati | Ambil job berikutnya sampai antrean kosong |
| `--max-jobs=N` | 0 (tanpa batas) | Berhenti setelah N job |
| `--lease-recovery` | mati | Pulihkan job RUNNING yang lease-nya kadaluarsa |
| `--dry-run` | — | Cetak konfigurasi tanpa menyentuh DB |

### 3.3 Reduksi

```bash
php workers/reduce_batch.php --batch=39 --json-out=docs/week6/evidence/int_m6_smoke.json
```

Reducer menghitung ulang agregat dari payload, membandingkan checksum dengan
baseline bila ada, lalu menandai batch `DONE` atau `FAILED`.

---

## 4. Semantik status

| Status | Arti | Perlakuan agregasi |
|---|---|---|
| `VERIFIED` | DB dan chain konsisten | Masuk `verified_count`; revenue verified bila paid |
| `MISMATCH` | Ada field berbeda | Masuk `mismatch_count`, `differences` disimpan |
| `SKIPPED` | Order belum punya relasi on-chain | **Bukan** error; dihitung terpisah |
| `ERROR` | Kegagalan teknis (RPC, data korup) | Masuk `error_count` |

Mengapa `SKIPPED` penting: pada data nyata, tidak semua order sudah
diintegrasikan ke chain. Menghitungnya sebagai error akan membuat batch
terlihat gagal padahal pipeline bekerja normal. Karena `job_batches` belum
punya kolom `skipped_count`, nilai ini disimpan di ringkasan reducer dan
payload/notes.

---

## 5. Bukti smoke integrasi (batch #39)

Perintah dijalankan pada 13 November 2026 dengan dua proses worker paralel.

```text
[int-m6-w2] Job #106 batch #39 partisi 1 (1..20)
[int-m6-w1] Job #107 batch #39 partisi 2 (21..40)
[int-m6-w2] Selesai: 14 order | VERIFIED=0 MISMATCH=0 ERROR=0 SKIPPED=14 | 1 ms
[int-m6-w1] Selesai: 7 order | VERIFIED=0 MISMATCH=1 ERROR=0 SKIPPED=6 | 2005 ms

REDUCER - BATCH #39 (INT-M6-SMOKE-001)
  Mode             : INTEGRATION / worker_count=2
  Dataset          : orders-live (21 order)
  Status           : DONE (complete=yes)
  Processed        : 21 / 21
  VERIFIED         : 0
  MISMATCH         : 1
  ERROR            : 0
  SKIPPED          : 20
  Revenue all      : Rp 53.139.000
  Checksum         : 1b924c4a00b9e5dfe7f7a47da428e3c98b4515a3e86f18927839103f8f893d66
  Baseline         : -
  Correctness gate : NO-BASELINE
  Metrics konsisten: yes
  Duplikat order   : 0
```

Interpretasi:

- Dua worker memproses **partisi berbeda** (job #106 dan #107).
- Hanya 21 order nyata pada rentang 1..40; 19 slot ID kosong tidak dihitung.
- Satu order MISMATCH karena data DB tidak cocok dengan state chain saat itu.
- 20 order `SKIPPED` karena belum punya `blockchain_order_id`.
- Tidak ada error dan tidak ada duplikasi; `metrics_consistent=yes`.
- Gate `NO-BASELINE` wajar karena batch integrasi tidak punya baseline
  sequential dataset yang sama.

Bukti: [`evidence/int_m6_smoke.json`](evidence/int_m6_smoke.json).

---

## 6. Perbedaan Mode A vs Mode B

| Aspek | Mode A | Mode B |
|---|---|---|
| Worker | `benchmark_worker.php` | `verify_worker.php` |
| Sumber order | Dataset JSON mock | Tabel `orders` |
| Chain | `MockChainAdapter` deterministik | Kontrak nyata via RPC |
| Latensi | `--latency-us` | Waktu nyata RPC |
| `SKIPPED` | Tidak muncul | Order tanpa relasi on-chain |
| Baseline | Wajib untuk gate MATCH | Umumnya `NO-BASELINE` |
| Dipakai untuk | PAR-01/PAR-02, speedup | Smoke integrasi, demo |

Keduanya memakai skema antrean, mekanisme klaim, dan reducer yang sama,
sehingga perilaku paralelnya identik.

---

## 7. Checklist operator

1. Pastikan MySQL aktif dan migrasi sudah dijalankan.
2. Untuk Mode B, pastikan Ganache hidup dan kontrak ter-deploy.
3. Buat batch dengan `enqueue_batch.php`.
4. Jalankan satu proses per worker; gunakan `--max-jobs=1` bila ingin
   memastikan setiap worker mengambil tepat satu partisi.
5. Jalankan `reduce_batch.php` dan periksa `complete`, `metrics_consistent`,
   serta `duplicate_ids`.
6. Bila ada job tertinggal `RUNNING`, jalankan worker dengan
   `--lease-recovery`.

---

## 8. Catatan untuk M7

- Tambahkan retry otomatis untuk job `DEAD` dengan batas yang terlihat.
- Dukung batch lintas rentang ID yang tidak kontigu (saat ini partisi
  memakai rentang ID; ID kosong dilewati).
- Tambahkan metrik latency RPC per order agar Mode B dapat dibandingkan
  dengan Mode A secara adil.
- Hubungkan hasil `MISMATCH` ke halaman verifikasi admin untuk drill-down.
