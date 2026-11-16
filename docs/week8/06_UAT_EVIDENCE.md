# 06 — UAT Evidence & Business Flow

**Milestone:** M8 — Finalisasi & Demo
**Issue terkait:** `W8-YAS-12`, `W8-ALL-05`
**Pemilik:** Yasmin Akmaliyah
**Validator:** Feeandri, Kenjiro
**Tanggal eksekusi:** 16 November 2026, 01:13 WIB
**Status:** **16/16 PASS (100%)**

---

## 1. Tujuan UAT

Memvalidasi bahwa alur bisnis utama dapat diselesaikan pengguna nyata
(customer & admin) pada environment hasil instalasi, bukan hanya lulus test
otomatis. Setiap skenario dijalankan melalui HTTP dengan sesi terpisah.

---

## 2. Environment

| Komponen | Nilai |
|---|---|
| URL | `http://127.0.0.1:8080` |
| Database | `h_sport_db` (13 produk) |
| Kontrak | `0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab` |
| Akun customer | `customer@example.com` / `customer123` |
| Akun admin | `admin@hsport.com` / `admin123` |
| Order UAT | **#44** (`INV-20261115-5879`), status `production` |
| Order ber-chain | **#43** (`INV-INT-1813047357`), `VERIFIED` |

Bukti mentah: [`evidence/uat_business_flow.log`](evidence/uat_business_flow.log)

---

## 3. Business Flow yang Diuji

```text
Login customer
   ↓
Katalog & filter → detail produk
   ↓
Keranjang → checkout (JNE, QRIS)
   ↓
Order dibuat → halaman pembayaran
   ↓
Riwayat & detail order
   ↓
Admin: dashboard → manajemen order → ubah status
   ↓
Customer melihat status terbaru
   ↓
Admin: verifikasi DB vs chain → proof page → laporan
```

---

## 4. Hasil per Skenario

| ID | Skenario | Ekspektasi | Hasil | Status |
|---|---|---|---|---|
| UAT-01 | Login customer dengan kredensial seed | Masuk ke sesi customer | Sesi aktif | ✅ PASS |
| UAT-02 | Katalog menampilkan produk | Minimal 13 produk tampil | 13 produk (DB), produk unggulan tampil | ✅ PASS |
| UAT-03 | Filter kategori Basket | Hanya produk Basket | Basket tampil, MU tidak tampil | ✅ PASS |
| UAT-04 | Detail produk | Nama, harga, ukuran | "Jersey Sepeda Ravina" + Rp 900.000 | ✅ PASS |
| UAT-05 | Tambah ke keranjang | `success=true`, cart_count naik | `success=true`, count ≥ 1 | ✅ PASS |
| UAT-06 | Halaman keranjang | Item & total tampil | Item + total tampil | ✅ PASS |
| UAT-07 | Checkout | Order dibuat + redirect pembayaran | Order **#44** dibuat | ✅ PASS |
| UAT-08 | Halaman pembayaran | QRIS + nominal tampil | Halaman QR tampil | ✅ PASS |
| UAT-09 | Riwayat order customer | Order baru tampil | Order #44 tampil | ✅ PASS |
| UAT-10 | Login admin & dashboard | Ringkasan tampil | "Total Produk"/"Total Pesanan" | ✅ PASS |
| UAT-11 | Manajemen order admin | Order customer terlihat | Order #44 tampil | ✅ PASS |
| UAT-12 | Ubah status order | Status berubah di DB | `production` tersimpan | ✅ PASS |
| UAT-13 | Customer lihat status terbaru | Status "Dalam Produksi" | Tampil di detail order | ✅ PASS |
| UAT-14 | Verifikasi DB vs chain | `VERIFIED` | Order #43 `VERIFIED` | ✅ PASS |
| UAT-15 | Halaman bukti blockchain | Data on-chain tampil | Tx/block/order tampil | ✅ PASS |
| UAT-16 | Laporan penjualan | Halaman terbuka | HTTP 200 | ✅ PASS |

**Rekap:** 16 PASS / 0 FAIL.

---

## 5. Pemetaan ke Requirement

| Requirement | Skenario UAT | Status |
|---|---|---|
| REQ-01 (checkout DB + chain) | UAT-07, UAT-14 | ✅ |
| REQ-04/05/06 (state & akses) | UAT-12, UAT-13 | ✅ |
| REQ-09 (proof transaksi) | UAT-14, UAT-15 | ✅ |
| REQ-10 (verifikasi) | UAT-14 | ✅ |
| REQ-11 (admin retry verifikasi) | UAT-14 | ✅ |
| REQ-12 (halaman proof) | UAT-15 | ✅ |
| NFR usability (alur toko) | UAT-01..UAT-11, UAT-16 | ✅ |

---

## 6. Kriteria Penerimaan

| Kriteria | Target | Hasil |
|---|---|---|
| Skenario wajib lulus | 100% | 16/16 PASS |
| Customer dapat menyelesaikan checkout | Ya | Ya (#44) |
| Admin dapat mengelola order | Ya | Ya (status `production`) |
| Verifikasi DB vs chain berfungsi | Ya | Ya (`VERIFIED`) |
| Bukti on-chain dapat dilihat | Ya | Ya (#43) |
| Tidak ada error 500 pada alur utama | 0 | 0 |

---

## 7. Catatan Pengujian

1. UAT dijalankan pada database kerja `h_sport_db`; clean install HTTP diuji
   terpisah pada `h_sport_clean` (lihat `evidence/clean_install_http.log`).
2. Order #44 sengaja ditinggalkan berstatus `production` sebagai bukti UAT-12/13.
3. Order #43 adalah order hasil INT terbaru dan satu-satunya order ber-chain
   setelah Ganache di-restart; semua verifikasi demo mengacu ke order ini.
4. Pengujian pembayaran crypto end-to-end dengan MetaMask bersifat manual;
   bukti otomatisnya adalah INT-02..INT-05 dan halaman proof.

---

## 8. Kesimpulan

Seluruh 16 skenario UAT business flow **PASS**. Alur customer (katalog →
keranjang → checkout → pembayaran → riwayat) dan alur admin (dashboard →
manajemen order → verifikasi → proof → laporan) berjalan pada environment
hasil instalasi dan siap didemokan.
