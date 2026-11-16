# 07 — Traceability Final (REQ-01..REQ-15)

**Milestone:** M8 — Finalisasi & Demo
**Issue terkait:** `W8-ALL-03`, `W8-FEE-13`
**Pemilik:** Muhammad Feeandri Syahbani
**Validator:** Yasmin Akmaliyah, Kenjiro B.H.G.
**Tanggal:** 16 November 2026
**Status:** **15/15 requirement Selesai**

---

## 1. Ringkasan

| Status | Jumlah | Requirement |
|---|---:|---|
| Selesai | **15** | REQ-01 .. REQ-15 |
| Sebagian | 0 | — |
| Belum | 0 | — |

Matriks awal M1: [`../week1/03_TRACEABILITY_MATRIX.md`](../week1/03_TRACEABILITY_MATRIX.md).
Dokumen ini adalah status final setelah M8.

---

## 2. Matriks Requirement Final

| REQ | Requirement | Implementasi utama | Test | UAT | Bukti | Status |
|---|---|---|---|---|---|---|
| **REQ-01** | Checkout membuat order off-chain + on-chain terhubung | `checkout.php`, `crypto-payment.php`, `ContractService.php` | INT-02 | UAT-07 | `final_int_suite.log` | ✅ Selesai |
| **REQ-02** | `createOrder` validasi amount & lock escrow | `HSportStore.sol` (`createOrder`) | SC-02, SC-03 | UAT-07 | `sc_regression.log` | ✅ Selesai |
| **REQ-03** | Tolak `externalOrderHash` duplikat | `HSportStore.sol` (`orderIdByHash`) | SC-04 | UAT-07 | `sc_regression.log` | ✅ Selesai |
| **REQ-04** | Hanya operator; double payment ditolak | `recordPayment`, `operators` | SC-07, SC-08 | UAT-12 | `sc_regression.log` | ✅ Selesai |
| **REQ-05** | 8 state + transisi tidak sah ditolak | `updateOrderStatus`, enum state | SC-09, SC-10 | UAT-12, UAT-13 | `sc_regression.log` | ✅ Selesai |
| **REQ-06** | Cancel/refund oleh aktor berwenang | `cancelOrder` | SC-11 | UAT-12 | `sc_regression.log` | ✅ Selesai |
| **REQ-07** | Konfirmasi terima merilis escrow sekali | `confirmReceived`, guard `released` | SC-12 | UAT-13 | `sc_regression.log` | ✅ Selesai |
| **REQ-08** | Event audit lengkap | 9 event kontrak | SC-02/08/09/11/12 | UAT-01..UAT-16 | `sc_regression.log` | ✅ Selesai |
| **REQ-09** | Simpan proof transaksi lengkap | `api/services/*`, `migration_blockchain.sql` | INT-01, INT-03, INT-04 | UAT-14 | `final_int_suite.log` | ✅ Selesai |
| **REQ-10** | Verifikasi DB vs chain `VERIFIED`/`MISMATCH` | `VerificationService.php` | INT-05 | UAT-14 | `final_int_suite.log` | ✅ Selesai |
| **REQ-11** | Admin lihat status + retry verifikasi | `admin/orders.php` (kolom + tombol) | INT-05 | UAT-14 | `req11_admin_verification.log` | ✅ Selesai |
| **REQ-12** | Halaman proof blockchain pembeli | `blockchain-proof.php` | Demo langkah 12 | UAT-15 | `demo_http.log` | ✅ Selesai |
| **REQ-13** | Parallel partition/reduce = sequential | `parallel_runner.php`, `verify_worker.php` | PAR-01, PAR-02 | — | `par01_par02_regression.log` | ✅ Selesai |
| **REQ-14** | Throughput, speedup, efficiency | `benchmark/run_matrix.php` | PAR-03, PAR-04 | — | `benchmark_matrix.md` | ✅ Selesai |
| **REQ-15** | Reproducible + kontribusi tertelusur | README, migrasi, seed, tag `v1.0.0` | Clean install + regresi | UAT-01..16 | `clean_install*.log`, `08_WEEKLY_LOG.md` | ✅ Selesai |

---

## 3. Bukti Penutupan REQ-11

**Sebelum M8:** `admin/orders.php` hanya menampilkan status order; belum ada
status verifikasi DB vs chain dan belum ada aksi retry.

**Setelah M8:**

| Komponen | Perubahan |
|---|---|
| `admin/orders.php` | Kolom **Verifikasi** dengan badge `VERIFIED`/`MISMATCH`/`PENDING` + tanggal verifikasi |
| `admin/orders.php` | Tombol retry `POST verify_order` memanggil `VerificationService::verifyOrder($id, true)` |
| Feedback | Pesan sukses/gagal + jumlah perbedaan |

Bukti uji:

```text
Data cocok         -> VERIFIED
total_amount +10000 -> MISMATCH (1 perbedaan)
Restore             -> VERIFIED
```

Sumber: [`evidence/req11_admin_verification.log`](evidence/req11_admin_verification.log).

---

## 4. Bukti Penutupan REQ-15

| Kriteria | Bukti |
|---|---|
| Dapat diinstal dari nol | `clean_install.log` — 5/5 langkah exit 0 |
| Seed konsisten & idempotent | 2 akun + 13 produk; re-run tetap 13 |
| Aplikasi berjalan dari DB bersih | `clean_install_http.log` — 7/7 HTTP 200 |
| Test dapat diulang | `final_regression.log` — 21/21 PASS |
| Kontribusi tertelusur | `08_WEEKLY_LOG.md`, riwayat commit git |
| Tag rilis | `v1.0.0` (lihat `02_RELEASE_NOTES_CODE_FREEZE.md`) |
| Reproducibility benchmark | `docs/week7/evidence/reproducibility_check.json` — 16/16 checksum identik |

---

## 5. Requirement → Issue → Owner

| REQ | Issue utama | Owner | Milestone |
|---|---|---|---|
| REQ-01 | W4-AGU-04, W5-LUI-06/07 | Luis, Agustina | M4–M5 |
| REQ-02 | W3-ARB-02, W3-ARB-03 | Arby | M3 |
| REQ-03 | W3-ARB-04 | Arby | M3 |
| REQ-04 | W4-AGU-02, W4-AGU-03 | Agustina | M4 |
| REQ-05 | W4-AGU-04, W4-AGU-05 | Agustina | M4 |
| REQ-06 | W4-AGU-06, W4-IHS-04 | Agustina, Ihsanul | M4 |
| REQ-07 | W4-AGU-07 | Agustina | M4 |
| REQ-08 | W4-AGU-08 | Agustina | M4 |
| REQ-09 | W5-LUI-06/07/08 | Luis | M5 |
| REQ-10 | W5-LUI-09 | Luis | M5 |
| REQ-11 | W8-LUI-14 | Luis, Ihsanul | M8 |
| REQ-12 | W5-IHS-06 | Ihsanul | M5 |
| REQ-13 | W6-FDR-07/08, W6-KEN-06 | Farelly, Kenjiro | M6 |
| REQ-14 | W7-KEN-07/08, W7-FDR-12 | Kenjiro, Farelly | M7 |
| REQ-15 | W8-ALL-01/02/03 | Feeandri, semua | M8 |

---

## 6. Aturan Status

1. `Selesai` = implementasi ada **dan** test/UAT PASS **dan** bukti tersimpan.
2. `Sebagian` = implementasi/artefak ada tetapi test belum lengkap.
3. `Belum` = tidak ada implementasi atau bukti.
4. Tidak ada requirement `Must` tanpa test pada rilis `v1.0.0`.

---

## 7. Kesimpulan

Seluruh 15 requirement berstatus **Selesai** dengan bukti test otomatis,
UAT, atau clean install. Tidak ada orphan requirement pada rilis `v1.0.0`.
