# 01 — INTERFACE FREEZE KONTRAK V1

**Issue:** `W3-ARB-06`, `W3-AGU-04`, `W3-YAS-07`
**Pemilik:** Arby Putra, Agustina Elsi Bunga
**Reviewer:** Farrel, Kenjiro, Luis, Feeandri
**Status:** **Frozen untuk V1** — satu pengecualian terdokumentasi (`cancelOrder` di V2)
**Tanggal:** 11 November 2026

---

## 1. Pernyataan Freeze

Mulai 11 November 2026, elemen berikut **tidak boleh berubah** tanpa versi ABI baru dan persetujuan reviewer:

1. Urutan dan nilai enum 8 state.
2. Field struct `Order` dan `PaymentEscrow`.
3. Signature 12 fungsi eksternal yang sudah terimplementasi.
4. Signature 9 event.
5. Pesan revert yang sudah diuji.

**Pengecualian yang disepakati:** `cancelOrder(uint256,string)` dan pemancaran `OrderCancelled`/`EscrowRefunded` ditambahkan di V2. Signature-nya sudah dibekukan di [`../week2/02_CONTRACT_SPEC_FINAL.md`](../week2/02_CONTRACT_SPEC_FINAL.md) §8, sehingga V2 hanya menambah fungsi, tidak mengubah yang ada.

---

## 2. Enum (Frozen)

```solidity
enum OrderStatus {
    Created,          // 0
    AwaitingPayment,  // 1
    Paid,             // 2
    InProduction,     // 3
    ReadyToShip,      // 4
    Shipped,          // 5
    Completed,        // 6
    Cancelled         // 7
}
```

---

## 3. Signature Fungsi (Frozen)

| # | Signature | Mutability | Status V1 |
|---:|---|---|---|
| 1 | `createOrder(bytes32,string,uint256)` | payable | ✅ |
| 2 | `getOrder(uint256)` | view | ✅ |
| 3 | `getOrderInfo(uint256)` | view | ✅ alias |
| 4 | `getOrderStatus(uint256)` | view | ✅ |
| 5 | `getOrderIdByHash(bytes32)` | view | ✅ |
| 6 | `isOperator(address)` | view | ✅ |
| 7 | `recordPayment(uint256)` | nonpayable | ✅ |
| 8 | `updateOrderStatus(uint256,uint8)` | nonpayable | ✅ |
| 9 | `shipOrder(uint256)` | nonpayable | ✅ |
| 10 | `confirmReceived(uint256)` | nonpayable | ✅ |
| 11 | `addOperator(address)` | nonpayable | ✅ |
| 12 | `removeOperator(address)` | nonpayable | ✅ |
| 13 | `cancelOrder(uint256,string)` | nonpayable | ✅ V2 (11 Nov 2026) |

Auto-getter publik (ikut ABI, bukan bagian interface bisnis): `owner()`, `orderCount()`, `orders(uint256)`, `escrows(uint256)`, `orderIdByHash(bytes32)`, `operators(address)`.

---

## 4. Event (Frozen)

| # | Signature | Dipancarkan di V1 |
|---:|---|---|
| 1 | `OrderCreated(uint256 indexed,address indexed,bytes32 indexed,uint256,string)` | ✅ |
| 2 | `PaymentRecorded(uint256 indexed,address indexed,uint256)` | ✅ |
| 3 | `OrderStatusUpdated(uint256 indexed,uint8,uint8,address indexed)` | ✅ |
| 4 | `OrderShipped(uint256 indexed,address indexed)` | ✅ |
| 5 | `OrderCompleted(uint256 indexed,address indexed)` | ✅ |
| 6 | `EscrowReleased(uint256 indexed,address indexed,uint256)` | ✅ |
| 7 | `OperatorUpdated(address indexed,bool)` | ✅ |
| 8 | `OrderCancelled(uint256 indexed,address indexed,string)` | ✅ V2 |
| 9 | `EscrowRefunded(uint256 indexed,address indexed,uint256)` | ✅ V2 |

---

## 5. Pesan Revert (Frozen)

| Pesan | Fungsi | Diuji |
|---|---|---|
| `Amount must be > 0` | `createOrder` | SC-03 |
| `Incorrect amount` | `createOrder`, `recordPayment` | SC-03 |
| `Order number required` | `createOrder` | SC-02 |
| `Duplicate order hash` | `createOrder` | SC-04 |
| `Order not found` | semua fungsi order | SC-06 |
| `Not owner` | `addOperator`, `removeOperator` | SC-01 |
| `Not operator` | `recordPayment`, `updateOrderStatus`, `shipOrder` | SC-07 (M4) |
| `Not buyer` | `confirmReceived` | SC-07 (M4) |
| `Invalid operator` | `addOperator` | SC-01 |
| `Operator already active` | `addOperator` | SC-01 |
| `Cannot remove owner` | `removeOperator` | SC-01 |
| `Operator not active` | `removeOperator` | SC-01 |
| `Payment already recorded` | `recordPayment` | SC-08 (M4) |
| `Invalid state transition` | `updateOrderStatus` | SC-10 (M4) |
| `Not ready to ship` | `shipOrder` | SC-10 (M4) |
| `Not shipped yet` | `confirmReceived` | SC-12 (M4) |
| `Already released` | `confirmReceived` | SC-12 (M4) |
| `Order is final` | `updateOrderStatus`, `cancelOrder` (V2) | SC-10/SC-11 |
| `Not authorized to cancel` | `cancelOrder` | SC-11 (M4) |
| `Reason too long` | `cancelOrder` | SC-11 (M4) |

---

## 6. Checklist Freeze

- [x] Enum 8 state final dan urutannya tidak berubah.
- [x] Struct `Order` dan `PaymentEscrow` final.
- [x] 12 signature fungsi V1 final.
- [x] Signature `cancelOrder` final di dokumen (terimplementasi V2).
- [x] 9 event final.
- [x] Pesan revert final dan unik.
- [x] ABI V0 diarsipkan (`config/archive/contract_abi.v0.json`).
- [x] ABI V1 diarsipkan (`config/archive/contract_abi.v1.json`).
- [x] ABI V2 diarsipkan (`config/archive/contract_abi.v2.json`).
- [x] SC-01 s.d. SC-12 PASS (12/12).
- [ ] Persetujuan tertulis Arby + Agustina + Luis + Kenjiro.

> Item terakhir menunggu tanda tangan reviewer di PR [#6](https://github.com/SyahbaniFeeandri/hsportjersey-eth/pull/6). Freeze teknis sudah berlaku; perubahan hanya boleh lewat PR baru dengan catatan perubahan ABI.

---

## 7. Delta V2 (Terimplementasi 11 November 2026)

| Elemen | Perubahan | Dampak ABI |
|---|---|---|
| `cancelOrder(uint256,string)` | Fungsi baru + refund | Aditif |
| `OrderCancelled`, `EscrowRefunded` | Mulai dipancarkan | Tidak berubah |
| `notFinal` pada `cancelOrder` | Guard terminal | Internal |

**Tidak direncanakan berubah di V2:** enum, struct, signature 12 fungsi V1, 9 event, pesan revert.

---

## 8. Kebijakan Perubahan

| Perubahan | Butuh ABI baru? | Butuh persetujuan |
|---|---|---|
| Tambah fungsi | ✅ | Arby + Agustina + Luis |
| Ubah signature/struct/enum | ✅ | Semua reviewer + Feeandri |
| Ubah pesan revert | ✅ | Kenjiro (test owner) |
| Tambah NatSpec | ❌ | — |
| Optimasi gas tanpa ubah perilaku | ❌ | — |
| Tambah event | ✅ | Agustina |

---

## 9. Tautan

- Implementasi & deploy: [`00_CONTRACT_V1.md`](00_CONTRACT_V1.md)
- Spesifikasi final: [`../week2/02_CONTRACT_SPEC_FINAL.md`](../week2/02_CONTRACT_SPEC_FINAL.md)
- Test report: [`02_TEST_REPORT_V1.md`](02_TEST_REPORT_V1.md)
