# 04 — REVIEW NOTES KONTRAK V1 & SKENARIO M4

**Issue:** `W3-AGU-04`, `W3-YAS-07`
**Pemilik:** Agustina Elsi Bunga, Yasmin Akmaliyah
**Reviewer:** Arby, Kenjiro, Luis, Feeandri
**Status:** Review selesai; 2 temuan perlu ditutup di M4
**Tanggal:** 11 November 2026

---

## 1. Lingkup Review

Review dilakukan terhadap:

1. `blockchain/contracts/HSportStore.sol` (V1).
2. `blockchain/config/contract_abi.json` dan arsip V0/V1.
3. `blockchain/test/01_sc01_sc06.test.js` + evidence test.
4. `docs/week2/02_CONTRACT_SPEC_FINAL.md` sebagai baseline spesifikasi.

Tujuan: memastikan V1 memenuhi requirement kontrak inti dan menyiapkan skenario SC-07 s.d. SC-12 untuk M4.

---

## 2. Checklist Acceptance Criteria

| # | Kriteria | Status | Bukti |
|---|---|---|---|
| AC-01 | Enum 8 state, urutan sesuai spesifikasi | ✅ | SC-02 memeriksa status `Created` = 0; source kontrak |
| AC-02 | `Order` memuat `externalOrderHash` dan `paymentRecorded` | ✅ | SC-02 memeriksa 9 field |
| AC-03 | `PaymentEscrow` memuat `refunded` | ✅ | SC-02 memeriksa `refunded=false` |
| AC-04 | `createOrder(bytes32,string,uint256)` menolak amount 0 / mismatch / hash duplikat | ✅ | SC-02, SC-03, SC-04 |
| AC-05 | Escrow terkunci saat order dibuat | ✅ | SC-02 + saldo kontrak |
| AC-06 | Akses owner/operator/buyer dibatasi modifier | ✅ | SC-01 (owner), SC-06 (buyer pada order tidak ada); SC-07 menyusul |
| AC-07 | Alur `recordPayment → InProduction → ReadyToShip → shipOrder → confirmReceived` tersedia | ✅ implementasi | Belum ada test SC-08 s.d. SC-12 |
| AC-08 | Event untuk setiap perubahan state | ✅ deklarasi | 9 event; 8 dipancarkan di V1 |
| AC-09 | `cancelOrder` + refund | ❌ ditunda | Dijadwalkan V2 (M4) |
| AC-10 | Tidak ada private key/seed di repo | ✅ | Skrip read-only; `.env.example` hanya placeholder |
| AC-11 | Proof transaksi tersimpan di MySQL | ⏳ M5 | INT-03/INT-04 |

**Kesimpulan:** kontrak inti V1 **diterima** untuk M3. AC-09 dan AC-11 berada di luar exit gate M3.

---

## 3. Temuan Review

| # | Severity | Temuan | Dampak | Rekomendasi |
|---|---|---|---|---|
| R-01 | **Medium** | `confirmReceived` memeriksa `status == Shipped` sebelum `!escrow.released`. Panggilan kedua setelah order `Completed` karena itu revert `Not shipped yet`, bukan `Already released`. Pesan `Already released` tidak dapat tercapai lewat alur normal. | Ekspektasi SC-12 di test plan tidak sesuai perilaku nyata; pesan revert R13 di spesifikasi menjadi dead code. | **Resolusi M4:** ekspektasi SC-12 dan test plan disesuaikan menjadi `Not shipped yet`; kontrak tidak diubah. |
| R-02 | **Medium** | `recordPayment`, `updateOrderStatus`, `shipOrder`, `confirmReceived` sudah diimplementasikan tetapi belum punya test otomatis (SC-07 s.d. SC-12 dijadwalkan M4). | Risiko regresi/kesalahan guard tidak terdeteksi sampai M4. | Tulis SC-07 s.d. SC-12 sebagai langkah pertama M4, sebelum `cancelOrder`. Jalankan regresi SC-01 s.d. SC-06. |
| R-03 | Low | `confirmReceived` memakai `transfer` (2300 gas) ke `owner`. | Gagal bila owner kelak berupa kontrak dengan `receive()` berat. | Terima untuk V1 karena owner adalah EOA; dokumentasikan batasan. Bila owner dipindah ke multisig, evaluasi `call` + reentrancy guard. |
| R-04 | Low | `recordPayment` hanya mengubah state; dana sudah masuk escrow saat `createOrder`. | Semantik "pembayaran" mudah disalahpahami: escrow = komitmen bayar, `recordPayment` = konfirmasi operator. | Dokumentasikan di UI/admin: checkout mengunci ETH, admin mengonfirmasi pembayaran off-chain. |
| R-05 | Info | `cancelOrder` belum ada; event `OrderCancelled`/`EscrowRefunded` sudah dideklarasikan. | ABI aditif di V2; tidak mengubah yang ada. | Implementasi V2 sesuai §8 spesifikasi; tambah test SC-11. |
| R-06 | Info | `orders` dan `escrows` adalah mapping publik; auto-getter ikut ABI. | Menambah permukaan baca, bukan tulis. | Tidak ada aksi; berguna untuk verifikasi. |
| R-07 | Info | `orderNumber` dibatasi 64 byte dan disimpan on-chain. | Nomor order bukan data personal; aman. | Pertahankan validasi; frontend memakai nomor < 64 byte. |

---

## 4. Catatan Keamanan

| Kontrol | Status |
|---|---|
| Checks-effects-interactions pada release escrow | ✅ `released = true` dan status diubah sebelum `transfer` |
| Reentrancy | ✅ Tidak ada `call` eksternal selain `transfer` 2300 gas; state sudah final |
| Akses owner | ✅ `onlyOwner` pada `addOperator`, `removeOperator`; owner tidak bisa dihapus |
| Akses operator | ✅ `onlyOperator` pada `recordPayment`, `updateOrderStatus`, `shipOrder` |
| Akses pembeli | ✅ `onlyBuyer` pada `confirmReceived`; dicek setelah `orderExists` |
| Duplicate order | ✅ `orderIdByHash` + revert |
| Fungsi berbahaya | ✅ Tidak ada `selfdestruct`, `delegatecall`, `call` arbitrer |
| Penerimaan ETH | ✅ Hanya lewat `createOrder` payable; tidak ada `receive`/`fallback` |
| Escrow release | ✅ Hanya ke `owner`, bukan alamat dari pemanggil |
| Finality | ✅ `Completed`/`Cancelled` tidak bisa diubah (`notFinal` pada `updateOrderStatus`) |

---

## 5. Skenario SC-07 s.d. SC-12 untuk M4

Skenario di bawah sudah disesuaikan dengan perilaku V1 dan pesan revert aktual.

### SC-07 — Access control

| Langkah | Ekspektasi |
|---|---|
| Order 1 `Shipped`; `recordPayment(1)` dari outsider | revert `Not operator` |
| `confirmReceived(1)` dari outsider | revert `Not buyer` |
| `addOperator(outsider)` dari outsider | revert `Not owner` |
| `removeOperator(owner)` dari owner | revert `Cannot remove owner` |
| `addOperator(0x0)` dari owner | revert `Invalid operator` |
| `addOperator` dua kali | revert `Operator already active` |
| `removeOperator` operator nonaktif | revert `Operator not active` |

### SC-08 — `recordPayment` valid + double payment

| Langkah | Ekspektasi |
|---|---|
| Order 1 `Created`, escrow terisi; `recordPayment(1)` dari operator | status `Paid`; `paymentRecorded=true`; event `PaymentRecorded` |
| `recordPayment(1)` lagi | revert `Payment already recorded` |
| Order 2 `Created` → `updateOrderStatus(2, AwaitingPayment)` → `recordPayment(2)` | status `Paid`; event tetap konsisten |

### SC-09 — Transisi valid + `shipOrder`

| Langkah | Ekspektasi |
|---|---|
| Order 1 `Created` → `updateOrderStatus(1, AwaitingPayment)` | status `AwaitingPayment` |
| `recordPayment(1)` | status `Paid` |
| `updateOrderStatus(1, InProduction)` | status `InProduction`; event `OrderStatusUpdated` old/new benar |
| `updateOrderStatus(1, ReadyToShip)` | status `ReadyToShip` |
| `shipOrder(1)` | status `Shipped`; event `OrderShipped` |
| Cek `updatedAt` setiap langkah | nilainya berubah/monoton |

### SC-10 — Transisi tidak sah

| Langkah | Ekspektasi |
|---|---|
| Order 1 `Created`; `shipOrder(1)` | revert `Not ready to ship` |
| `updateOrderStatus(1, Completed)` | revert `Invalid state transition` |
| `updateOrderStatus(1, Paid)` | revert `Invalid state transition` (target tidak diizinkan) |
| Order 2 `Completed`; `updateOrderStatus(2, InProduction)` | revert `Order is final` |

### SC-11 — `cancelOrder` (V2)

| Langkah | Ekspektasi |
|---|---|
| V1: panggil `cancelOrder` | Tidak ada di ABI V1; test ditulis setelah V2 |
| Buyer cancel order `Created` | `Cancelled`; refund penuh; event `OrderCancelled` + `EscrowRefunded` |
| Buyer cancel order `Paid` | revert `Not authorized to cancel` |
| Operator cancel order `Paid` | `Cancelled` + refund |
| Operator cancel order `Completed` | revert `Order is final` |
| Panggilan cancel kedua | revert `Order is final` |

### SC-12 — `confirmReceived`, release escrow, double release

| Langkah | Ekspektasi |
|---|---|
| Order 1 `Shipped`; catat saldo owner; `confirmReceived(1)` dari buyer | status `Completed`; `released=true`; saldo owner + amount; event `OrderCompleted` + `EscrowReleased` |
| `confirmReceived(1)` kedua | revert **`Not shipped yet`** (lihat R-01) |
| `confirmReceived` pada order `Paid`/`ReadyToShip` | revert `Not shipped yet` |
| `confirmReceived` dari non-buyer | revert `Not buyer` |

---

## 6. Keputusan yang Dikonfirmasi

| # | Keputusan | Alasan |
|---|---|---|
| 1 | `cancelOrder` ditunda ke V2 | Menjaga exit gate M3 tetap kecil dan dapat diselesaikan |
| 2 | `updateOrderStatus` hanya menerima 3 target status | Mencegah bypass event `PaymentRecorded`/`OrderShipped` |
| 3 | Hash dihitung off-chain dan tidak dihitung ulang on-chain | Menghemat gas; integritas dijaga lewat keunikan hash + data bisnis di DB |
| 4 | Escrow dirilis ke owner, bukan alamat dari pemanggil | Mengurangi permukaan penyalahgunaan |
| 5 | Event cancel tetap dideklarasikan di V1 | Daftar event final stabil; V2 hanya mulai memancarkan |

---

## 7. Action Items

| # | Aksi | Owner | Target |
|---|---|---|---|
| A-01 | Putuskan R-01: sesuaikan ekspektasi SC-12 atau tukar urutan require di V2 | Agustina + Kenjiro | Awal M4 |
| A-02 | Tulis SC-07 s.d. SC-12 dan jalankan regresi SC-01 s.d. SC-06 | Agustina + Kenjiro | M4 |
| A-03 | Implementasi `cancelOrder` + refund + event | Agustina | M4 |
| A-04 | Dokumentasikan semantik escrow vs `recordPayment` di UI admin | Luis + Ihsanul | M5 |
| A-05 | Simpan proof transaksi ke MySQL | Luis | M5 |
| A-06 | Tanda tangan review PR M3 | Semua reviewer | PR M3 |

---

## 8. Tautan

- Test report: [`02_TEST_REPORT_V1.md`](02_TEST_REPORT_V1.md)
- Interface freeze: [`01_INTERFACE_FREEZE.md`](01_INTERFACE_FREEZE.md)
- Spesifikasi final: [`../week2/02_CONTRACT_SPEC_FINAL.md`](../week2/02_CONTRACT_SPEC_FINAL.md)
- Test plan: [`../week1/07_TEST_PLAN.md`](../week1/07_TEST_PLAN.md)
