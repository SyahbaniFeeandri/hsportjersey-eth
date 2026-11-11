# 04 — KOMPATIBILITAS FRONTEND DENGAN KONTRAK V2

**Issue:** `W4-FRL-08`, `W4-IHS-04`
**Pemilik:** Farrelly De Rossi, Ihsanul
**Reviewer:** Luis, Arby, Kenjiro
**Status:** V1 tetap kompatibel; UI cancel **belum dibuat**
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
| `cancelOrder` | ⚠️ Tersedia di kontrak; **belum ada tombol/form di UI** |
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

### 4.2 Yang belum ada

UI pembatalan order **belum diimplementasikan** (`W4-IHS-04` masih sebagian). Agar fitur cancel dapat dipakai pengguna, frontend perlu:

1. Menampilkan tombol **Batalkan Order** hanya untuk state yang diizinkan:
   - `Created` / `AwaitingPayment` → pembeli atau operator,
   - `Paid` / `InProduction` / `ReadyToShip` → operator.
2. Mengirim `cancelOrder(orderId, reason)` dengan `reason` ≤ 64 byte.
3. Menampilkan event `OrderCancelled` dan `EscrowRefunded` beserta tx hash.
4. Menyembunyikan tombol untuk `Shipped`, `Completed`, dan `Cancelled`.

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
| `cancelOrder` | ❌ tidak ada | ✅ kontrak | UI belum ada |
| Event `OrderCancelled` / `EscrowRefunded` | ❌ | ✅ | Butuh listener baru bila UI dibuat |

---

## 6. Rekomendasi untuk M5

1. Buat komponen UI cancel beserta validasi state dan panjang reason.
2. Tambahkan listener event `OrderCancelled`/`EscrowRefunded` pada halaman detail order.
3. Simpan `tx_hash` cancel ke DB agar proof dan verifikasi (INT-05) mencakup refund.
4. Tandai order V1 lama sebagai historis; address aktif sudah V2 sehingga order V1 tidak terbaca lewat config saat ini.
5. Uji end-to-end di browser setelah UI cancel tersedia (lihat [`05_UAT_CANCEL.md`](05_UAT_CANCEL.md)).

---

## 7. Kesimpulan

Frontend existing **kompatibel penuh** dengan V2 karena perubahan bersifat aditif dan config sudah menunjuk kontrak baru. Satu-satunya kekurangan adalah belum adanya UI untuk memanggil `cancelOrder`; ini dicatat sebagai pekerjaan M5 dan tidak menghalangi exit gate kontrak M4.

---

## 8. Tautan

- Kontrak V2: [`00_CONTRACT_V2.md`](00_CONTRACT_V2.md)
- Deployment: [`03_DEPLOYMENT_V2.md`](03_DEPLOYMENT_V2.md)
- Interface freeze: [`../week3/01_INTERFACE_FREEZE.md`](../week3/01_INTERFACE_FREEZE.md)
- Prototipe read-only: [`../week3/03_READONLY_PROTOTYPE.md`](../week3/03_READONLY_PROTOTYPE.md)
- Desain integrasi UI: [`../week1/05_INTEGRATION_AND_UI_DESIGN.md`](../week1/05_INTEGRATION_AND_UI_DESIGN.md)
