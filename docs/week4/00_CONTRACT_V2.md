# 00 — KONTRAK V2: CANCEL & REFUND

**Issue:** `W4-AGU-06`, `W4-AGU-07`, `W4-ARB-08`
**Pemilik:** Agustina Elsi Bunga, Arby Putra
**Reviewer:** Kenjiro, Luis, Feeandri
**Status:** Terimplementasi, terdeploy, 12/12 SC PASS
**Tanggal:** 11 November 2026

---

## 1. Ringkasan

V2 menambahkan `cancelOrder(uint256,string)` dan refund escrow ke kontrak `HSportStore`. Seluruh signature, struct, enum, dan event V1 dipertahankan; perubahan bersifat **aditif**.

| Item | Nilai |
|---|---|
| Compiler | Solidity 0.8.19, optimizer 200 runs |
| Ukuran bytecode (creation) | 8.107 byte |
| Ukuran bytecode (deployed) | 8.057 byte |
| Address V2 | `0x2f3efA6bbDC5fAf4dC1a600765c7B7829e47bE10` |
| Tx deploy | `0x5b18d347ba2561276890faa3bdcdd204a502721fd6e83d1895d1f13e2aad90ef` |
| Block | 225 |
| Gas deploy | 1.816.066 |
| Biaya deploy | 0.004540165014528528 ETH |
| ABI V2 SHA-256 | `23A73D09E75661576D3E42C932132C63242B33DCB0966608BDDA5CC86014606F` |
| ABI V1 SHA-256 (arsip) | `9E0C2F61154A362D7388AFB0185676370B6F483F9E37E55FF480AFBF0409AE8D` |

---

## 2. Delta dari V1

| Elemen | V1 | V2 |
|---|---|---|
| Fungsi eksplisit | 12 | 13 (+`cancelOrder`) |
| Entri ABI | 28 | 29 (+1 fungsi) |
| Event dipancarkan | 8 | 9 (+`OrderCancelled`, `EscrowRefunded` mulai dipakai) |
| Refund escrow | — | ✅ |
| Signature/struct/enum V1 | Final | Tidak berubah |

---

## 3. Aturan `cancelOrder`

```solidity
function cancelOrder(uint256 _orderId, string calldata _reason)
    external orderExists(_orderId) notFinal(_orderId)
```

| State | Pembeli | Operator / Owner | Escrow |
|---|---|---|---|
| `Created` | ✅ | ✅ | Refund bila `amount > 0` |
| `AwaitingPayment` | ✅ | ✅ | Refund bila `amount > 0` |
| `Paid` | ❌ `Not authorized to cancel` | ✅ | Refund |
| `InProduction` | ❌ | ✅ | Refund |
| `ReadyToShip` | ❌ | ✅ | Refund |
| `Shipped` | ❌ `Invalid state transition` | ❌ | Sengketa manual |
| `Completed` | ❌ `Order is final` | ❌ | Selesai |
| `Cancelled` | ❌ `Order is final` | ❌ | Terminal |

**Batasan:** `reason` maksimum **64 byte**; lebih dari itu revert `Reason too long`.

---

## 4. Urutan Eksekusi

```text
1. orderExists + notFinal
2. validasi panjang reason (<= 64 byte)
3. validasi aktor berdasarkan state
4. _setStatus(Cancelled)                  // emit OrderStatusUpdated
5. jika escrow.amount > 0 && !released && !refunded:
       escrow.refunded = true             // effects
       payable(buyer).transfer(amount)    // interaction
       emit EscrowRefunded
6. emit OrderCancelled
```

Urutan event aktual: `OrderStatusUpdated` → `EscrowRefunded` → `OrderCancelled`. Test memeriksa keberadaan dan isi event, bukan urutan ketat.

---

## 5. Keputusan Implementasi

| # | Keputusan | Alasan |
|---|---|---|
| 1 | `Shipped` tidak dapat dibatalkan | Sesuai T13 spesifikasi; sengketa ditangani manual |
| 2 | `reason` dibatasi 64 byte | Mencegah penyalahgunaan storage/gas dan menjaga event tetap kecil |
| 3 | Refund memakai `transfer` 2300 gas | Konsisten dengan V1; pembeli adalah EOA |
| 4 | `escrow.refunded = true` sebelum transfer | Checks-effects-interactions, mencegah double refund |
| 5 | Tidak mengubah urutan require `confirmReceived` | R-01 diselesaikan dengan menyesuaikan ekspektasi test, bukan mengubah perilaku |
| 6 | Event cancel tetap dideklarasikan sejak V1 | ABI V2 hanya menambah fungsi, bukan event |

---

## 6. ABI V2

**19 fungsi:**

`owner`, `orderCount`, `orders`, `escrows`, `orderIdByHash`, `operators`, `createOrder`, `getOrder`, `getOrderInfo`, `getOrderStatus`, `getOrderIdByHash`, `isOperator`, `recordPayment`, `updateOrderStatus`, `shipOrder`, `confirmReceived`, `cancelOrder`, `addOperator`, `removeOperator`.

**9 event:** `OrderCreated`, `PaymentRecorded`, `OrderStatusUpdated`, `OrderShipped`, `OrderCompleted`, `EscrowReleased`, `OrderCancelled`, `EscrowRefunded`, `OperatorUpdated`.

Total entri ABI: 29 (19 fungsi + 9 event + constructor).

---

## 7. Bukti

| Item | Lokasi |
|---|---|
| Source | `blockchain/contracts/HSportStore.sol` |
| Test | `blockchain/test/02_sc07_sc12.test.js` |
| Test log | [`evidence/sc07_sc12_test.log`](evidence/sc07_sc12_test.log) |
| ABI V2 | `blockchain/config/contract_abi.json` |
| Arsip ABI | `blockchain/config/archive/contract_abi.v2.json` |
| Deployment | [`03_DEPLOYMENT_V2.md`](03_DEPLOYMENT_V2.md) |
| Security review | [`02_SECURITY_REVIEW.md`](02_SECURITY_REVIEW.md) |
| Test report | [`01_TEST_REPORT_V2.md`](01_TEST_REPORT_V2.md) |

---

## 8. Tautan

- Spesifikasi: [`../week2/02_CONTRACT_SPEC_FINAL.md`](../week2/02_CONTRACT_SPEC_FINAL.md) §9–§12
- Kontrak V1: [`../week3/00_CONTRACT_V1.md`](../week3/00_CONTRACT_V1.md)
- Review M3: [`../week3/04_REVIEW_NOTES.md`](../week3/04_REVIEW_NOTES.md)
- Interface freeze V1: [`../week3/01_INTERFACE_FREEZE.md`](../week3/01_INTERFACE_FREEZE.md)
