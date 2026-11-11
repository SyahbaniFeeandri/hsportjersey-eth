# 00 — KONTRAK V1: IMPLEMENTASI & DEPLOY

**Issue:** `W3-ARB-04`, `W3-ARB-05`, `W3-ARB-07`, `W3-FRL-06`
**Pemilik:** Arby Putra
**Reviewer:** Agustina, Farrel, Kenjiro
**Status:** Terimplementasi, terdeploy, 6/6 SC PASS
**Tanggal:** 11 November 2026

---

## 1. Ringkasan

`blockchain/contracts/HSportStore.sol` diperluas dari V0 (4 state) ke V1 (8 state) tanpa mengganti nama kontrak, sesuai keputusan **D2**. V1 mencakup seluruh alur kripto sampai `confirmReceived`; `cancelOrder` dan refund dijadwalkan di V2 (Minggu 4).

| Item | Nilai |
|---|---|
| Compiler | Solidity 0.8.19, optimizer 200 runs |
| Ukuran bytecode (creation) | 6.924 byte |
| Ukuran bytecode (deployed) | 6.874 byte |
| Address V1 | `0x59d3631c86BbE35EF041872d502F218A39FBa150` |
| Tx deploy | `0xd796d9d36bb19e921be8d3bffa5c78b1ececfad433c2a1083e20d2e512506228` |
| Block | 12 |
| Gas deploy | 1.560.496 |
| Biaya deploy | 0.004247818713352592 ETH |
| ABI SHA-256 V1 | `9E0C2F61154A362D7388AFB0185676370B6F483F9E37E55FF480AFBF0409AE8D` |
| ABI SHA-256 V0 (arsip) | `FC5622FE41D0C2F9BF12BDAD8243BE56BA07074AE227FCAC877700A64A9D6252` |

---

## 2. Perubahan dari V0

| Elemen | V0 | V1 |
|---|---|---|
| Enum | 4 state | 8 state |
| `Order` | 7 field | 9 field (+`externalOrderHash`, `paymentRecorded`) |
| `PaymentEscrow` | 4 field | 5 field (+`refunded`) |
| Mapping | `orders`, `escrows` | +`orderIdByHash`, `operators` |
| Modifier | `onlyOwner`, `onlyBuyer`, `orderExists` | +`onlyOperator`, `notFinal` |
| Fungsi eksternal | 5 | 18 (termasuk 6 auto-getter) |
| Event | 4 | 9 |
| Guard duplikat | — | `orderIdByHash` |
| Hak akses | owner saja | owner + operator |

---

## 3. Isi ABI V1

**18 fungsi:** `owner`, `orderCount`, `orders`, `escrows`, `orderIdByHash`, `operators`, `createOrder`, `getOrder`, `getOrderInfo`, `getOrderStatus`, `getOrderIdByHash`, `isOperator`, `recordPayment`, `updateOrderStatus`, `shipOrder`, `confirmReceived`, `addOperator`, `removeOperator`.

**9 event:** `OrderCreated`, `PaymentRecorded`, `OrderStatusUpdated`, `OrderShipped`, `OrderCompleted`, `EscrowReleased`, `OrderCancelled`, `EscrowRefunded`, `OperatorUpdated`.

Total entri ABI: 28 (18 fungsi + 9 event + constructor).

> `OrderCancelled` dan `EscrowRefunded` sudah dideklarasikan agar daftar event final stabil, tetapi belum dipancarkan karena `cancelOrder` baru ada di V2.

---

## 4. Keputusan Implementasi

| # | Keputusan | Alasan |
|---|---|---|
| 1 | `onlyOperator` = owner **atau** operator terdaftar | Owner tidak perlu mendaftarkan diri saat deploy |
| 2 | `orderExists` dijalankan **sebelum** `onlyBuyer` | Order tidak ada harus revert `Order not found`, bukan `Not buyer` |
| 3 | `updateOrderStatus` hanya menerima `AwaitingPayment`, `InProduction`, `ReadyToShip` | Mencegah bypass `recordPayment`/`shipOrder` dan event yang hilang |
| 4 | `createOrder` menolak `orderNumber` kosong dan > 64 byte | Menjaga batas penyimpanan dan konsistensi hash |
| 5 | `confirmReceived` memakai checks-effects-interactions | Mencegah reentrancy saat transfer escrow |
| 6 | `cancelOrder` tidak ada di V1 | Sesuai pembagian milestone; ABI V2 menambah, bukan mengubah |
| 7 | Escrow hanya dirilis ke `owner` | Tidak menerima alamat tujuan dari pemanggil |

**Deviasi dari `docs/week2/02_CONTRACT_SPEC_FINAL.md` §14:** rencana awal V1 memakai stub `Not implemented`. Keputusan akhir: V1 mengimplementasikan happy path penuh dan menunda hanya cancel/refund. Konsekuensi: alur checkout → bayar → produksi → kirim → konfirmasi sudah berfungsi di V1; ABI V2 menambah `cancelOrder` dan dua event pemakaiannya.

---

## 5. Perilaku Penting

| Fungsi | Perilaku |
|---|---|
| `createOrder` | `amount > 0`, `msg.value == amount`, `orderNumber` 1–64 byte, hash unik; status `Created`; escrow terisi |
| `recordPayment` | Operator/owner; status `Created`/`AwaitingPayment`; `paymentRecorded=false`; escrow = totalAmount; status `Paid` |
| `updateOrderStatus` | Operator/owner; hanya `AwaitingPayment` (dari `Created`), `InProduction` (dari `Paid`), `ReadyToShip` (dari `InProduction`) |
| `shipOrder` | Operator/owner; hanya dari `ReadyToShip`; status `Shipped` |
| `confirmReceived` | Pembeli; hanya dari `Shipped`; `released` sekali; transfer escrow; status `Completed` |
| `addOperator` | Owner; alamat bukan nol; belum aktif |
| `removeOperator` | Owner; bukan owner; harus aktif |

---

## 6. Kompatibilitas Frontend

| File | Dampak | Status |
|---|---|---|
| `crypto-payment.php` | `createOrder` kini 3 argumen; hash dihitung di browser | ✅ diperbarui |
| `orders.php` | `getOrderInfo`, `confirmReceived` signature tidak berubah | ✅ aman |
| `admin/orders.php` | `shipOrder` signature tidak berubah | ✅ aman |
| Simpan `external_order_hash` ke DB | Belum dilakukan (INT-04, M5) | ⏳ M5 |

> Catatan: order on-chain lama (V0) tidak lagi dapat diakses setelah redeploy. Order DB lama harus dianggap tanpa proof on-chain (sesuai Q5: ditangani UI).

---

## 7. Bukti

| Item | Lokasi |
|---|---|
| Source kontrak | `blockchain/contracts/HSportStore.sol` |
| ABI V1 | `blockchain/config/contract_abi.json` |
| Arsip ABI V0/V1 | `blockchain/config/archive/contract_abi.v0.json`, `contract_abi.v1.json` |
| Address aktif | `blockchain/config/contract_address.txt` |
| Deployment record | `blockchain/deployments/deployment_2026-11-11T03-59-13-663Z.json` |
| Deployment log | `blockchain/deployments/DEPLOYMENTS.md` |
| Test log | [`evidence/sc01_sc06_test.log`](evidence/sc01_sc06_test.log) |
| Test report | [`02_TEST_REPORT_V1.md`](02_TEST_REPORT_V1.md) |

---

## 8. Tautan

- Spesifikasi final: [`../week2/02_CONTRACT_SPEC_FINAL.md`](../week2/02_CONTRACT_SPEC_FINAL.md)
- State machine: [`../week1/01_ON_OFF_CHAIN_AND_STATE.md`](../week1/01_ON_OFF_CHAIN_AND_STATE.md)
- Interface freeze: [`01_INTERFACE_FREEZE.md`](01_INTERFACE_FREEZE.md)
