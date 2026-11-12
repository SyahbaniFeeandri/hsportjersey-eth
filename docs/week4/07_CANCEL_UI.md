# 07 — UI PEMBATALAN ORDER ON-CHAIN (CANCEL)

**Issue:** `W4-IHS-04`
**Pemilik:** Ihsanul
**Reviewer:** Luis, Yasmin, Kenjiro
**Status:** ✅ Selesai (implementasi + smoke test; UAT browser manual tersedia)
**Tanggal:** 12 November 2026

---

## 1. Ringkasan

Halaman detail pesanan (`order-detail.php`) kini memiliki panel dan modal
**Batalkan Order** untuk order kripto yang sudah terhubung ke smart contract.
Pembatalan dikirim langsung ke `HSportStore.cancelOrder(uint256,string)` melalui
MetaMask, lalu bukti transaksi disimpan ke database.

| Aspek | Status |
|---|---|
| Wallet connect + cek jaringan Ganache (1337) | ✅ |
| Tombol/panel cancel berbasis state on-chain | ✅ |
| Validasi alasan maksimal 64 **byte** UTF-8 | ✅ |
| Konfirmasi MetaMask + status transaksi | ✅ |
| Event `OrderCancelled` / `EscrowRefunded` ditampilkan | ✅ |
| Bukti tx + alasan disimpan ke DB | ✅ |
| Smoke test statis | ✅ 12/12 PASS |
| UAT browser manual (UAT-09/UAT-10) | ⏳ perlu dijalankan di mesin dengan Ganache + MetaMask |

---

## 2. Perubahan Kode

| File | Perubahan |
|---|---|
| `order-detail.php` | Panel cancel, modal, validasi byte, integrasi MetaMask, pemanggilan `cancelOrder`, tampilan bukti cancel |
| `api/blockchain-api.php` | Action baru `cancel_order` (validasi input + delegasi service) |
| `api/services/OrderIntegrationService.php` | `processCancellation()`: validasi receipt, status on-chain, simpan bukti, verifikasi |
| `api/services/ProofRepository.php` | `recordCancellation()` dan `getLatestEvent()` |
| `api/services/ReceiptService.php` | Parsing `reason` dari event `OrderCancelled` |
| `tests/ui/cancel_ui_smoke.php` | Gate statis untuk memastikan komponen cancel tidak hilang |

---

## 3. Alur UI

1. Panel cancel hanya tampil bila:
   - `payment_type = crypto`,
   - order memiliki `blockchain_order_id` atau `external_order_hash`,
   - status DB bukan `cancelled`, `completed`, atau `shipped`.
2. Pengguna klik **Batalkan Order** → modal terbuka → MetaMask dihubungkan.
3. UI membaca state on-chain `getOrder(orderId)` dan menampilkan statusnya.
4. Otorisasi dihitung dari state on-chain, bukan hanya status DB:

| State on-chain | Pembeli | Operator/Owner | Tombol aktif |
|---|---|---|---|
| `Created` / `AwaitingPayment` | ✅ | ✅ | Ya, sesuai akun |
| `Paid` / `InProduction` / `ReadyToShip` | ❌ | ✅ | Hanya operator |
| `Shipped` | ❌ | ❌ | Tidak |
| `Completed` / `Cancelled` | ❌ | ❌ | Tidak |

5. Alasan wajib diisi dan diperiksa dengan `TextEncoder().encode(reason).length ≤ 64`.
6. Transaksi dikirim: `contract.methods.cancelOrder(orderId, reason).send(...)`.
7. Setelah receipt diterima:
   - tx hash ditampilkan,
   - jumlah refund dari `EscrowRefunded` ditampilkan bila ada,
   - UI memanggil `api/blockchain-api.php` action `cancel_order`.
8. Backend memverifikasi receipt dan memastikan status on-chain benar-benar
   `Cancelled` sebelum mengubah DB.
9. Halaman dimuat ulang; order berstatus **Dibatalkan** dan panel
   **Bukti Pembatalan** menampilkan alasan, wallet, block, dan tx hash.

---

## 4. Validasi dan Keamanan

| Kontrol | Implementasi |
|---|---|
| Jaringan salah | `wallet_switchEthereumChain` / `wallet_addEthereumChain` ke chain `0x539` |
| Bukan pembeli/operator | Ditolak sebelum transaksi; kontrak juga menolak (`Not authorized to cancel`) |
| Order final | Tombol tidak tampil; kontrak menolak (`Order is final`) |
| Alasan terlalu panjang | Ditolak di UI dan API (≤ 64 byte) |
| DB final | `recordCancellation` hanya mengubah status bila belum `completed`/`cancelled` |
| TX palsu/gagal | Backend membaca receipt dan wajib `status = 1` |
| TX bukan cancel | Backend membaca `getOrder` dan wajib status on-chain `Cancelled` |
| Double refund | Dicegah kontrak (`escrow.refunded`) dan state final |

Catatan: pembatalan **tidak menimpa** `orders.tx_hash` milik pembayaran.
Transaksi cancel dicatat sebagai baris terpisah di `blockchain_transactions`
dengan `event_name = 'OrderCancelled'`.

---

## 5. Bukti

Smoke test:

```bash
php tests/ui/cancel_ui_smoke.php
```

Output tersimpan di [`evidence/cancel_ui_smoke.log`](evidence/cancel_ui_smoke.log):
**12 PASS, 0 FAIL**.

Contoh tx dan screenshot browser belum dilampirkan karena eksekusi browser
memerlukan Ganache + MetaMask aktif. Langkah UAT tersedia di
[`05_UAT_CANCEL.md`](05_UAT_CANCEL.md).

---

## 6. UAT Browser Manual (ringkas)

Prasyarat: Ganache berjalan (chain ID 1337), kontrak V2 aktif, order uji
memiliki `blockchain_order_id`.

1. Sebagai pembeli, buat order kripto hingga `createOrder` tercatat.
2. Buka `order-detail.php?id=<order_id>`.
3. Klik **Batalkan Order**, hubungkan MetaMask akun pembeli.
4. Isi alasan ≤ 64 byte, klik **Kirim Pembatalan**, konfirmasi di MetaMask.
5. Pastikan halaman menampilkan tx hash + refund (bila escrow terisi), lalu
   status berubah menjadi **Dibatalkan**.
6. Buka lagi order tersebut: panel **Bukti Pembatalan** menampilkan alasan,
   wallet, block, dan tx hash.
7. Ulangi cancel → harus ditolak `Order is final` di tingkat kontrak.
8. Sebagai akun non-operator pada state `Paid`, tombol harus nonaktif dengan
   pesan "hanya operator".

---

## 7. Batasan

1. UAT browser belum dieksekusi pada sesi ini (Ganache/MySQL/MetaMask tidak aktif).
2. Halaman admin (`admin/orders.php`) masih memiliki aksi cancel berbasis DB;
   untuk order kripto, pembatalan sebaiknya diarahkan ke halaman detail agar
   escrow ikut direfund. Ini dicatat sebagai penyempurnaan M5.
3. Refund memakai `transfer` 2300 gas sesuai kontrak V2.

---

## 8. Tautan

- Kompatibilitas frontend: [`04_FRONTEND_COMPAT.md`](04_FRONTEND_COMPAT.md)
- Skenario UAT: [`05_UAT_CANCEL.md`](05_UAT_CANCEL.md)
- Kontrak V2: [`00_CONTRACT_V2.md`](00_CONTRACT_V2.md)
