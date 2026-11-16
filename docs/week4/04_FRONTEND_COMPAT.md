# 04 — KOMPATIBILITAS FRONTEND DENGAN KONTRAK V2

**Issue:** `W4-FRL-08`, `W4-IHS-04`
**Pemilik:** Farrelly De Rossi, Ihsanul
**Reviewer:** Luis, Arby, Kenjiro
**Status:** V1 tetap kompatibel; UI cancel **sudah dibuat** (lihat `07_CANCEL_UI.md`)
**Tanggal:** 11 November 2026

---

## 1. Ringkasan

Kontrak V2 bersifat **aditif**: seluruh signature, struct, enum, dan event V1 dipertahankan, dan hanya `cancelOrder(uint256,string)` yang ditambahkan. Halaman pembayaran yang sudah ada (`crypto-payment.php`) memuat ABI dan address langsung dari `blockchain/config/`, sehingga setelah config diperbarui, frontend otomatis berbicara dengan V2 tanpa perubahan kode halaman.

| Aspek | Status |
|---|---|
| `createOrder` | ✅ Signature sama, tetap berfungsi |
| `getOrder` / `getOrderStatus` | ✅ Tidak berubah |
| `recordPayment` / `updateOrderStatus` / `shipOrder` | ✅ Tidak berubah |
| `confirmReceived` | ✅ Tidak berubah |
| `cancelOrder` | ✅ Tersedia di kontrak **dan UI** (`order-detail.php`) |
| ABI aktif | 29 entri (V2) |
| Address aktif | `0x2f3efA6bbDC5fAf4dC1a600765c7B7829e47bE10` |

---

## 2. Titik Integrasi Frontend

| File | Peran | Perubahan yang diperlukan |
|---|---|---|
| `crypto-payment.php` | Membaca address + ABI, memanggil `createOrder` | Tidak ada kode; cukup config V2 |
| `blockchain/config/contract_address.txt` | Sumber address | ✅ Sudah diperbarui |
| `blockchain/config/contract_abi.json` | Sumber ABI untuk `new web3.eth.Contract(...)` | ✅ Sudah diperbarui |
| `blockchain/scripts/read_contract.php` | Read-only PHP | ✅ Sudah terbukti membaca V2 |

Halaman membaca config saat render PHP:

```php
$contract_address = trim(file_get_contents('blockchain/config/contract_address.txt'));
$contract_abi     = trim(file_get_contents('blockchain/config/contract_abi.json'));
```

lalu diteruskan ke JavaScript:

```js
const contractAddress = '<?php echo trim($contract_address); ?>';
const contractABI = <?php echo !empty($contract_abi) ? $contract_abi : '[]'; ?>;
contract = new web3.eth.Contract(contractABI, contractAddress);
```

Karena itu **tidak ada breaking change** untuk alur bayar yang sudah ada.

---

## 3. Perubahan Konfigurasi

| Item | Sebelum | Sesudah |
|---|---|---|
| Address | `0x59d3631c86BbE35EF041872d502F218A39FBa150` (V1) | `0x2f3efA6bbDC5fAf4dC1a600765c7B7829e47bE10` (V2) |
| ABI | 28 entri (V1) | 29 entri (V2) |
| Fungsi baru | — | `cancelOrder(uint256,string)` |

Address V1 tetap tercatat sebagai historis di [`03_DEPLOYMENT_V2.md`](03_DEPLOYMENT_V2.md).

---

## 4. Dampak ke UI

### 4.1 Yang tidak berubah

- Form checkout dan encoding hash kanonik (`keccak256(abi.encode(orderNumber, buyer, amount))`).
- Pemanggilan `createOrder` dan pembacaan status order.
- Event yang didengarkan untuk alur bayar.

### 4.2 Implementasi UI cancel (sudah tersedia)

UI pembatalan order tersedia di `order-detail.php` dan mencakup:

1. Tombol **Batalkan Order** tampil hanya untuk order kripto yang terhubung ke kontrak dan belum final.
2. Otorisasi dibaca dari **state on-chain** (`getOrder`), bukan hanya status DB:
   - `Created` / `AwaitingPayment` → pembeli atau operator,
   - `Paid` / `InProduction` / `ReadyToShip` → operator,
   - `Shipped` / `Completed` / `Cancelled` → tidak dapat dibatalkan.
3. Mengirim `cancelOrder(orderId, reason)` dengan validasi `reason` ≤ 64 byte.
4. Menampilkan event `OrderCancelled` dan `EscrowRefunded` beserta tx hash.
5. Menyimpan bukti cancel ke `blockchain_transactions` melalui API `cancel_order`.

Detail lengkap: [`07_CANCEL_UI.md`](07_CANCEL_UI.md).

### 4.3 Batasan input reason

`reason` dibatasi **64 byte** (bukan 64 karakter). Karakter multibyte memakai lebih dari satu byte. UI sebaiknya memotong berdasarkan UTF-8 byte, misalnya:

```js
const bytes = new TextEncoder().encode(reason).length;
if (bytes > 64) { /* tolak sebelum kirim */ }
```

Jika tidak dicek, transaksi akan revert dengan `Reason too long`.

---

## 5. Matriks Kompatibilitas

| Skenario | V1 | V2 | Catatan |
|---|---|---|---|
| Buka halaman pembayaran | ✅ | ✅ | Config dibaca saat render |
| Hubungkan MetaMask | ✅ | ✅ | Tidak terkait kontrak |
| `createOrder` | ✅ | ✅ | Signature sama |
| Baca status order | ✅ | ✅ | Enum sama |
| `confirmReceived` | ✅ | ✅ | Perilaku sama |
| `cancelOrder` | ❌ tidak ada | ✅ kontrak + UI |
| Event `OrderCancelled` / `EscrowRefunded` | ❌ | ✅ ditampilkan setelah transaksi |

---

## 6. Rekomendasi Lanjutan (M5)

1. Eksekusi UAT browser dan ambil screenshot (UI tersedia).
2. Arahkan cancel order kripto di halaman admin ke jalur on-chain agar escrow direfund.
3. Simpan `tx_hash` cancel ke DB agar proof dan verifikasi (INT-05) mencakup refund.
4. Tandai order V1 lama sebagai historis; address aktif sudah V2 sehingga order V1 tidak terbaca lewat config saat ini.
5. Uji end-to-end di browser setelah UI cancel tersedia (lihat [`05_UAT_CANCEL.md`](05_UAT_CANCEL.md)).

---

## 7. Kesimpulan

Frontend existing **kompatibel penuh** dengan V2 karena perubahan bersifat aditif dan config sudah menunjuk kontrak baru. UI cancel on-chain kini tersedia di `order-detail.php`, telah melewati smoke test statis 12/12, dan siap diuji end-to-end di browser.

---

## 8. Tautan

- Kontrak V2: [`00_CONTRACT_V2.md`](00_CONTRACT_V2.md)
- Deployment: [`03_DEPLOYMENT_V2.md`](03_DEPLOYMENT_V2.md)
- Interface freeze: [`../week3/01_INTERFACE_FREEZE.md`](../week3/01_INTERFACE_FREEZE.md)
- Prototipe read-only: [`../week3/03_READONLY_PROTOTYPE.md`](../week3/03_READONLY_PROTOTYPE.md)
- Desain integrasi UI: [`../week1/05_INTEGRATION_AND_UI_DESIGN.md`](../week1/05_INTEGRATION_AND_UI_DESIGN.md)
