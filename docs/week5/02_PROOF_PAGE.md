# 02 — HALAMAN BUKTI BLOCKCHAIN

**Issue:** `W5-IHS-06`
**Pemilik:** Ihsanul
**Reviewer:** Luis, Yasmin
**Status:** ✅ Selesai
**Tanggal:** 12 November 2026

---

## 1. Tujuan

Menyediakan halaman audit yang menampilkan bukti kriptografis sebuah pesanan
kripto: transaksi pembayaran, blok, wallet, kontrak, hash eksternal, riwayat
event, dan state on-chain terkini.

| Aspek | Implementasi |
|---|---|
| Halaman | `blockchain-proof.php` |
| Otorisasi | Order hanya bisa dibuka pemiliknya; admin dapat membuka semua |
| Sumber data | MySQL (`orders`, `blockchain_transactions`) + RPC `getOrder` |
| Aksi | Tombol **Verifikasi Ulang** memanggil `verify_order` |
| Tautan masuk | `order-detail.php` → **Lihat Bukti Blockchain** |

---

## 2. Isi Halaman

### 2.1 Daftar pesanan
Bila `order_id` tidak diberikan, halaman menampilkan maksimal 50 pesanan kripto
milik pengguna (atau semua pesanan untuk admin) beserta status verifikasi,
order ID on-chain, dan potongan tx hash.

### 2.2 Detail bukti
| Bagian | Data |
|---|---|
| Status verifikasi | `VERIFIED` / `MISMATCH` / `PENDING` + waktu verifikasi |
| Order on-chain | `blockchain_order_id` + status kontrak saat ini |
| Transaction hash | Tx pembayaran/cancel yang tercatat |
| External order hash | Hash relasi DB ↔ chain |
| Block & wallet | Block number dan alamat pembeli |
| Contract | Address kontrak yang dipakai |
| State on-chain | Status, buyer, amount ETH, `paymentRecorded` |
| Riwayat event | Semua baris `blockchain_transactions`, payload dapat dibuka |

### 2.3 Verifikasi ulang
Tombol memanggil API `verify_order` dengan `force=true`. Hasil dan daftar
perbedaan ditampilkan sebelum halaman dimuat ulang.

---

## 3. Keamanan & Ketahanan

1. **Ownership check** pada query order (kecuali admin).
2. Semua output di-`htmlspecialchars` (tx hash, hash, alamat, payload).
3. RPC gagal tidak mematikan halaman: state on-chain diganti peringatan,
   data DB tetap tampil.
4. Halaman hanya membaca; satu-satunya aksi tulis adalah verifikasi ulang
   yang memperbarui `verification_status` order tersebut.

---

## 4. Bukti

- Halaman: `blockchain-proof.php` (lint PHP bersih).
- Tautan dari detail pesanan: `order-detail.php` blok **Informasi Pesanan**.
- Data uji INT: order #34 dengan tx
  `0x2639d90018532fd4b528501aaa15b89ef37b4a9d0285f985678c8b7f7b973b91`.

Screenshot browser belum diambil pada sesi ini; halaman siap diuji pada UAT M5
ketika sesi login + Ganache aktif.
