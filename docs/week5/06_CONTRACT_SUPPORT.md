# 06 — LOG DUKUNGAN BUG FIXING KONTRAK

**Issue:** `W5-ARB-09`, `W5-AGU-09`
**Pemilik:** Arby, Agustina
**Status:** ✅ Selesai — tidak ada bug kontrak yang ditemukan pada M5
**Tanggal:** 12 November 2026

---

## 1. Lingkup

Dukungan diberikan saat integrasi backend (INT-01..05) dan UI cancel
dijalankan terhadap kontrak V2.

| Pemeriksaan | Hasil |
|---|---|
| `createOrder` menerima `bytes32,string,uint256` dan `msg.value` | ✅ Sesuai ABI |
| `getOrder` mengembalikan struct 9 field (termasuk `orderNumber`) | ✅ Terbaca backend |
| Event `OrderCreated` memuat `orderId`, `buyer`, `externalOrderHash` | ✅ Terparse |
| `cancelOrder` + `EscrowRefunded` | ✅ Terbukti SC-11 dan UI M4 |
| Revert state tidak valid (`Order is final`, `Not authorized`) | ✅ Sesuai desain |
| Regresi SC-01..SC-12 | ✅ 12/12 PASS |

## 2. Temuan

Tidak ada perubahan kontrak yang diperlukan. Dua kegagalan awal M5 berasal dari
sisi infrastruktur/back-end, bukan kontrak:

1. **Gas default Ganache terlalu rendah** untuk `createOrder` ber-`value`
   → diperbaiki di `BlockchainClient` (gas eksplisit).
2. **Data uji INT-05 duplikat** → diperbaiki di harness (isolasi data).

Kontrak tetap pada versi V2 yang di-freeze pada M4; tidak ada perubahan ABI
maupun bytecode pada M5.

## 3. Rekomendasi M6

- Pertahankan regresi SC-01..SC-12 setiap kali kontrak disentuh.
- Tambahkan test kontrak untuk jalur `recordPayment` → `shipOrder` → `confirmReceived`
  bila alur pembayaran on-chain dipakai penuh.
- Dokumentasikan perilaku refund 2300 gas untuk penerima non-EOA.
