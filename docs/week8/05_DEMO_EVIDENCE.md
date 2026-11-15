# 05 — Demo End-to-End & Bukti

**Milestone:** M8 — Finalisasi & Demo
**Issue terkait:** `W8-ALL-05`, `W8-LUI-14`, `W8-IHS-09`, `W8-KEN-12`
**Pelaksana:** Yasmin (customer), Luis (integrasi/verifikasi), Ihsanul (proof/dashboard), Feeandri (admin)
**Tanggal eksekusi:** 16 November 2026, 01:13 WIB
**Status:** **13/13 langkah PASS**

---

## 1. Lingkungan Demo

| Komponen | Nilai |
|---|---|
| Web server | `php -S 127.0.0.1:8080 -t C:\laragon\www\project_jersey` |
| Database | `h_sport_db` (MySQL 8.0.30) |
| Ganache | CLI 7.9.1, deterministik, `127.0.0.1:7545`, chainId 1337, networkId 5777 |
| Kontrak | `HSportStore` V2 `0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab` |
| Akun customer | `customer@example.com` |
| Akun admin | `admin@hsport.com` |
| Order ber-chain | **#43** (`INV-INT-1813047357`), blockchain order #1 |
| Order checkout demo | **#45** (`INV-20261115-9716`), Rp 900.000 |

Bukti mentah: [`evidence/demo_http.log`](evidence/demo_http.log)

---

## 2. Hasil Regression & Benchmark (dipakai pada demo)

### 2.1 Regression final

| Suite | Jumlah | Hasil |
|---|---:|---|
| SC-01..SC-12 | 12 | PASS |
| INT-01..INT-05 | 5 | PASS |
| PAR-01..PAR-04 | 4 | PASS |
| **Total** | **21** | **21/21 PASS (100%)** |

- Perintah: `php tests/regression/run_all.php`
- Durasi: 47,19 detik
- Bukti: [`evidence/final_regression.log`](evidence/final_regression.log),
  [`evidence/final_regression_summary.json`](evidence/final_regression_summary.json)

### 2.2 Integration suite final (setelah restart environment)

| Test | Hasil | Detail |
|---|---|---|
| INT-01 | PASS | Koneksi RPC & bytecode kontrak |
| INT-02 | PASS | Order DB ↔ on-chain terhubung |
| INT-03 | PASS | Tx hash, receipt, block |
| INT-04 | PASS | Proof tersimpan di `orders` + `blockchain_transactions` |
| INT-05 | PASS | VERIFIED → MISMATCH (manipulasi) → VERIFIED (restore) |

Bukti: [`evidence/final_int_suite.log`](evidence/final_int_suite.log).

### 2.3 Benchmark (reuse M7)

16/16 konfigurasi PASS; D5000 P=8 = **7,318×**, throughput **1.386,58 order/detik**,
efficiency **91,5%**, seluruh checksum identik dengan baseline.

Bukti: [`evidence/final_benchmark_matrix.md`](evidence/final_benchmark_matrix.md),
[`evidence/final_benchmark_matrix.json`](evidence/final_benchmark_matrix.json).

---

## 3. Langkah Demo & Hasil

| # | Langkah | Peran | Hasil | Status |
|---:|---|---|---|---|
| 1 | Login customer | Customer | HTTP 200 | ✅ |
| 2 | Buka katalog, produk seed tampil | Customer | HTTP 200; produk tampil | ✅ |
| 3 | Tambah produk #1 ke keranjang | Customer | `success=true`, `cart_count=1` | ✅ |
| 4 | Buka keranjang | Customer | Item "Jersey Sepeda Ravina" tampil | ✅ |
| 5 | Checkout JNE + QRIS | Customer | Order **#45** dibuat, redirect ke pembayaran | ✅ |
| 6 | Riwayat order | Customer | Order #45 tampil | ✅ |
| 7 | Detail order | Customer | Alamat "Jl. Demo UAT No. 8" tampil | ✅ |
| 8 | Login admin | Admin | Berhasil | ✅ |
| 9 | Dashboard admin | Admin | Ringkasan "Total Produk" tampil | ✅ |
| 10 | Manajemen order | Admin | Order #45 tampil; kolom Verifikasi ada | ✅ |
| 11 | Retry verifikasi order #43 | Admin | Pesan `Verifikasi order #43: VERIFIED` | ✅ |
| 12 | Halaman bukti blockchain #43 | Admin | Data on-chain tampil | ✅ |
| 13 | Laporan penjualan | Admin | HTTP 200 | ✅ |

---

## 4. Bukti On-Chain Order #43

| Field | Nilai |
|---|---|
| Order DB | `#43` / `INV-INT-1813047357` |
| Blockchain order ID | `1` |
| Tx hash | `0x70befd575f3435c503f2bb05f8ab954398699c9d8c9ae9a9296d81247737a108` |
| Block number | `2` |
| Buyer wallet | `0xffcf8fdee72ac11b5c542428b35eef5769c409f0` |
| Contract | `0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab` |
| Amount on-chain | 1 ETH (setara Rp 10.000.000 pada kurs demo) |
| Status verifikasi | `VERIFIED` |
| Event | `OrderCreated` (tabel `blockchain_transactions`) |

---

## 5. Bukti Verifikasi Admin (REQ-11)

Alur retry verifikasi melalui UI `/admin/orders.php`:

| Kondisi data | Hasil | Keterangan |
|---|---|---|
| DB cocok dengan chain | `VERIFIED` (0 perbedaan) | Tombol verifikasi mengembalikan pesan sukses |
| `total_amount` +Rp 10.000 | `MISMATCH` (1 perbedaan) | Field `total_amount` dilaporkan |
| Data direstore | `VERIFIED` | Status kembali konsisten |

Bukti: [`evidence/req11_admin_verification.log`](evidence/req11_admin_verification.log).

---

## 6. Ringkasan PASS/FAIL

| Kelompok | Jumlah | PASS | FAIL |
|---|---:|---:|---:|
| Regression | 21 | 21 | 0 |
| Integration final | 5 | 5 | 0 |
| Benchmark matrix | 16 | 16 | 0 |
| Demo HTTP | 13 | 13 | 0 |
| UAT business flow | 16 | 16 | 0 |
| **Total** | **71** | **71** | **0** |

---

## 7. Catatan & Kendala Saat Demo

1. **Ganache di-restart** sebelum demo final → chain kosong. Solusi: `npm run migrate`
   lalu `php tests/integration/run_all.php` untuk membuat order ber-chain baru (#43).
2. **Order lama** (#40) masih menyimpan referensi chain dari sesi sebelumnya;
   demo memakai order terbaru (#43) agar data on-chain valid.
3. **PHP built-in server** dijalankan ulang setelah restart mesin; tidak ada
   perubahan konfigurasi aplikasi.
4. CDN Bootstrap/FontAwesome memerlukan internet; tampilan tetap terbaca tanpa CDN.

---

## 8. Cara Mereproduksi Demo

```bash
# 1) Pastikan environment siap
php tests/integration/run_all.php     # 5/5 PASS, membuat order ber-chain

# 2) Jalankan web server
php -S 127.0.0.1:8080 -t .

# 3) Ikuti 13 langkah pada tabel §3
#    Login customer -> katalog -> cart -> checkout -> admin -> verifikasi -> proof
```

Log otomatis dapat dibuat ulang dengan skrip HTTP yang sama seperti pada
`evidence/demo_http.log` (PowerShell `Invoke-WebRequest`, sesi cookie terpisah
untuk customer dan admin).
