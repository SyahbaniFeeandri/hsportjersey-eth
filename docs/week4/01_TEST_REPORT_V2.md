# 01 — TEST REPORT KONTRAK V2 (21 TEST: 12/12 SC PASS)

**Issue:** `W4-AGU-08`, `W4-ARB-08`, `W4-KEN-04`
**Pemilik:** Kenjiro B.H.G., Agustina Elsi Bunga
**Reviewer:** Arby, Farrel, Feeandri
**Status:** **12/12 SC PASS** + 4 smoke PASS (16 passing)
**Tanggal eksekusi:** 11 November 2026
**Kontrak:** `HSportStore` V2 — `0x2f3efA6bbDC5fAf4dC1a600765c7B7829e47bE10`

---

## 1. Ringkasan Eksekusi

| Item | Nilai |
|---|---|
| Perintah | `npm test` (menyetel `HSPORT_TEST=1`, network `ganache`) |
| Lokasi | `blockchain/` |
| Compiler | Solidity 0.8.19+commit.7dd6d404 |
| Network | Ganache 7.9.1, chain ID 1337, network ID 5777 |
| Node.js | v24.20.0 |
| Hasil | **16 passing (6 detik)** — 12 test SC + 4 smoke |
| Kegagalan | 0 |
| Evidence | [`evidence/sc07_sc12_test.log`](evidence/sc07_sc12_test.log) |

---

## 2. Hasil SC-01 s.d. SC-06 (regresi)

| Test | Hasil | Catatan |
|---|---|---|
| SC-01 | ✅ PASS | Owner, operator, dan akses owner |
| SC-02 | ✅ PASS | `createOrder` valid, escrow, event |
| SC-03 | ✅ PASS | Amount 0 / mismatch revert |
| SC-04 | ✅ PASS | Duplicate hash revert |
| SC-05 | ✅ PASS | `getOrder` + alias + status |
| SC-06 | ✅ PASS | ID tidak ada revert |

Regresi dijalankan pada kontrak V2; tidak ada perubahan perilaku V1.

---

## 3. Hasil SC-07 s.d. SC-12 (baru)

| Test | Nama | Hasil | Assertion utama |
|---|---|---|---|
| **SC-07** | Access control | ✅ PASS | `recordPayment`/`updateOrderStatus`/`shipOrder` non-operator revert `Not operator`; `confirmReceived` non-buyer `Not buyer`; `addOperator` non-owner `Not owner`; `removeOperator(owner)` `Cannot remove owner`; state tidak berubah |
| **SC-08** | `recordPayment` + double payment | ✅ PASS | Status `Paid`; `paymentRecorded=true`; event `PaymentRecorded` (orderId, operator, amount); panggilan kedua `Payment already recorded`; `AwaitingPayment → Paid` berhasil |
| **SC-09** | Transisi valid + `shipOrder` | ✅ PASS | `Paid → InProduction → ReadyToShip → Shipped`; event `OrderStatusUpdated` old/new benar; event `OrderShipped`; `updatedAt` tidak mundur |
| **SC-10** | Transisi tidak sah | ✅ PASS | `shipOrder` dari `Created` revert `Not ready to ship`; target `Completed`/`Paid` lewat `updateOrderStatus` revert `Invalid state transition`; order `Completed` revert `Order is final`; state tetap |
| **SC-11** | Cancel + refund + terminal | ✅ PASS | Buyer cancel `Created` → `Cancelled`, escrow `refunded=true`, saldo kontrak turun sebesar escrow, event `OrderCancelled` + `EscrowRefunded`; buyer cancel `Paid` revert `Not authorized to cancel`; operator cancel `Paid` berhasil; cancel `Shipped` revert `Invalid state transition`; cancel `Completed`/`Cancelled` revert `Order is final`; reason 64 byte diterima, 65 byte revert `Reason too long`; non-buyer/outsider ditolak; ID 999 `Order not found` |
| **SC-12** | `confirmReceived` + release | ✅ PASS | Status `Completed`; `released=true`; saldo owner naik tepat `amount`; event `OrderCompleted` + `EscrowReleased`; panggilan kedua revert **`Not shipped yet`**; order `Paid` revert `Not shipped yet`; non-buyer `Not buyer` |

---

## 4. Catatan R-01

Sesuai keputusan review, kontrak **tidak diubah**. Urutan guard `confirmReceived` tetap:

```text
require(status == Shipped, "Not shipped yet");
require(!escrow.released, "Already released");
```

Konsekuensinya panggilan kedua pada order `Completed` menghasilkan `Not shipped yet`. Ekspektasi SC-12 dan `docs/week1/07_TEST_PLAN.md` sudah disesuaikan. Pesan `Already released` tetap ada di kontrak, tetapi tidak tercapai lewat alur normal.

---

## 5. Cakupan

| Kelompok | Jumlah | Status |
|---|---:|---|
| SC-01 s.d. SC-12 | 12 | ✅ 12 PASS |
| INT-01 s.d. INT-05 | 5 | ⏳ M5 |
| PAR-01 s.d. PAR-04 | 4 | ⏳ M6–M7 |
| **Total wajib** | **21** | **12 PASS (57%)** |

---

## 6. Kesimpulan

- Exit gate M4 untuk test **terpenuhi**: 12/12 SC PASS, 0 gagal.
- `cancelOrder` + refund terbukti secara otomatis, termasuk batas reason dan double refund.
- Regresi V1 bersih.
- Bukti tersimpan di [`evidence/sc07_sc12_test.log`](evidence/sc07_sc12_test.log).

---

## 7. Sign-off

| Peran | Nama | Status |
|---|---|---|
| Test owner / validator | Kenjiro B.H.G. | ⏳ menunggu review PR |
| Dev kontrak V2 | Agustina Elsi Bunga | ⏳ menunggu review PR |
| Review kontrak | Arby Putra | ⏳ menunggu review PR |
| Deployment | Farrelly De Rossi | ⏳ menunggu review PR |

---

## 8. Tautan

- Kontrak V2: [`00_CONTRACT_V2.md`](00_CONTRACT_V2.md)
- Security review: [`02_SECURITY_REVIEW.md`](02_SECURITY_REVIEW.md)
- Deployment: [`03_DEPLOYMENT_V2.md`](03_DEPLOYMENT_V2.md)
- Test plan: [`../week1/07_TEST_PLAN.md`](../week1/07_TEST_PLAN.md) §7.1
