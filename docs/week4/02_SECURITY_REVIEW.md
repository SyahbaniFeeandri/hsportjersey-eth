# 02 — SECURITY REVIEW KONTRAK V2

**Issue:** `W4-KEN-04`, `W4-AGU-07`
**Pemilik:** Kenjiro B.H.G.
**Reviewer:** Agustina, Arby, Luis, Feeandri
**Status:** Selesai — tidak ada temuan Critical/High/Medium
**Tanggal:** 11 November 2026

---

## 1. Lingkup

Review difokuskan pada perubahan V2:

- `cancelOrder(uint256,string)`.
- Refund escrow ke pembeli.
- Otorisasi cancel per state.
- Pemancaran `OrderCancelled` dan `EscrowRefunded`.

Kontrak V1 yang tidak berubah tetap diperiksa sebagai regresi: `createOrder`, `recordPayment`, `updateOrderStatus`, `shipOrder`, `confirmReceived`, `addOperator`, `removeOperator`.

---

## 2. Aset yang Dilindungi

| Aset | Risiko |
|---|---|
| Dana escrow pembeli | Refund ganda, refund ke alamat salah, refund setelah release |
| Status order | Perubahan status tidak sah, cancel order yang sudah final |
| Otorisasi | Buyer/operator/owner bertindak di luar kewenangan |
| Gas/storage | Reason terlalu panjang, loop, penyalahgunaan event |

---

## 3. Analisis Ancaman

| # | Ancaman | Kontrol | Uji | Status |
|---|---|---|---|---|
| T1 | Buyer membatalkan order yang sudah `Paid` | Hanya operator/owner untuk `Paid`/`InProduction`/`ReadyToShip` | SC-11 | ✅ |
| T2 | Outsider membatalkan order `Created` | Harus buyer atau operator/owner | SC-11 | ✅ |
| T3 | Refund ganda | `escrow.refunded` dicek; status menjadi `Cancelled`; panggilan kedua kena `notFinal` | SC-11 | ✅ |
| T4 | Refund setelah escrow dirilis | Status `Completed` terminal; `notFinal` menolak | SC-11 | ✅ |
| T5 | Reentrancy saat refund | Effects (`refunded=true`, status `Cancelled`) sebelum `transfer`; `transfer` 2300 gas | SC-11 | ✅ |
| T6 | Refund ke alamat penyerang | Penerima selalu `escrow.buyer`, bukan parameter pemanggil | SC-11 | ✅ |
| T7 | Reason sangat panjang | Dibatasi 64 byte; revert `Reason too long` | SC-11 | ✅ |
| T8 | Cancel order `Shipped` | Ditolak `Invalid state transition`; sengketa manual | SC-11 | ✅ |
| T9 | Cancel order `Completed`/`Cancelled` | `notFinal` → `Order is final` | SC-11 | ✅ |
| T10 | Non-owner mengelola operator | `onlyOwner` pada `addOperator`/`removeOperator` | SC-01, SC-07 | ✅ |
| T11 | Reentrancy pada release escrow | `released=true` + status `Completed` sebelum transfer | SC-12 | ✅ |

---

## 4. Temuan

| # | Severity | Temuan | Dampak | Rekomendasi |
|---|---|---|---|---|
| S-01 | Low | Refund memakai `transfer` 2300 gas | Gagal bila buyer berupa kontrak dengan `receive()` berat | Terima untuk V2 karena pembeli adalah EOA; dokumentasikan. Evaluasi `call` + guard bila buyer bisa berupa kontrak. |
| S-02 | Low | `reason` dibatasi byte, bukan karakter | String multibyte bisa tampak < 64 karakter tetapi > 64 byte | Sudah sesuai batas teknis; UI sebaiknya memotong berdasarkan byte/UTF-8 |
| S-03 | Info | Urutan event aktual `OrderStatusUpdated → EscrowRefunded → OrderCancelled` | Tidak ada dampak keamanan | Dokumentasikan; test memeriksa isi event, bukan urutan |
| S-04 | Info | `Already released` tidak tercapai lewat alur normal | Pesan revert tidak terpakai | R-01 diselesaikan dengan penyesuaian test; tidak mengubah kontrak |
| S-05 | Info | Owner selalu dianggap operator | Owner dapat membatalkan order sebelum `Shipped` | Sesuai desain; owner adalah admin toko |
| S-06 | Info | `Shipped` tidak dapat dibatalkan | Sengketa harus manual | Sesuai T13 spesifikasi; catat di SOP |

Tidak ada temuan **Critical**, **High**, atau **Medium**.

---

## 5. Pemeriksaan Statis

| Pemeriksaan | Hasil |
|---|---|
| Compiler warning | Tidak ada |
| `selfdestruct` / `delegatecall` | Tidak ada |
| `call` arbitrer | Tidak ada |
| Loop tak terbatas | Tidak ada |
| Akses fungsi sensitif | Semua memakai modifier atau require eksplisit |
| Checks-effects-interactions | Diterapkan pada `confirmReceived` dan `cancelOrder` |
| Penerimaan ETH | Hanya `createOrder` payable; tidak ada `receive`/`fallback` |
| Batas input | `orderNumber` 1–64 byte; `reason` ≤ 64 byte |

---

## 6. Bukti

| Item | Lokasi |
|---|---|
| Test SC-07..SC-12 | `blockchain/test/02_sc07_sc12.test.js` |
| Log test | [`evidence/sc07_sc12_test.log`](evidence/sc07_sc12_test.log) |
| Test report | [`01_TEST_REPORT_V2.md`](01_TEST_REPORT_V2.md) |
| Kontrak | `blockchain/contracts/HSportStore.sol` |
| Spesifikasi revert | [`../week2/02_CONTRACT_SPEC_FINAL.md`](../week2/02_CONTRACT_SPEC_FINAL.md) §12 |

---

## 7. Kesimpulan

Kontrak V2 **layak dilanjutkan** ke integrasi M5. Refund escrow terlindungi dari double refund, unauthorized cancel, dan reentrancy. Dua temuan Low bersifat batasan desain yang sudah didokumentasikan dan tidak menghalangi exit gate M4.

---

## 8. Tautan

- Kontrak V2: [`00_CONTRACT_V2.md`](00_CONTRACT_V2.md)
- Deployment: [`03_DEPLOYMENT_V2.md`](03_DEPLOYMENT_V2.md)
- Review M3: [`../week3/04_REVIEW_NOTES.md`](../week3/04_REVIEW_NOTES.md)
