# 05 — UAT CANCEL & REFUND

**Issue:** `W4-YAS-08`, `W4-AGU-06`
**Pemilik:** Yasmin Akmaliyah
**Reviewer:** Agustina, Kenjiro, Feeandri
**Status:** Skenario siap; eksekusi browser menunggu UI cancel (M5)
**Tanggal:** 11 November 2026

---

## 1. Tujuan

Memetakan skenario UAT pembatalan order dan refund ke implementasi `cancelOrder` V2, serta mencatat apa yang **sudah** dapat dibuktikan sekarang dan apa yang masih menunggu UI.

| Field | Nilai |
|---|---|
| Kontrak | `HSportStore` V2 — `0x2f3efA6bbDC5fAf4dC1a600765c7B7829e47bE10` |
| Fungsi | `cancelOrder(uint256 _orderId, string _reason)` |
| Batas reason | 64 byte |
| Bukti otomatis | [`01_TEST_REPORT_V2.md`](01_TEST_REPORT_V2.md) — SC-11 PASS |

---

## 2. Mapping UAT → Test

| UAT | Skenario | Requirement | Test | Status |
|---|---|---|---|---|
| **UAT-09** | Pembeli cancel order `Created`, escrow terisi | REQ-06, REQ-08 | SC-11 | ✅ Terbukti via test kontrak |
| **UAT-10** | Pihak luar cancel order `Paid` | REQ-06 | SC-11 | ✅ Terbukti via test kontrak |
| **UAT-11** | Konfirmasi terima & release escrow | REQ-07, REQ-08 | SC-12 | ✅ Terbukti via test kontrak |
| **UAT-12** | Release ganda ditolak | REQ-07 | SC-12 | ✅ Terbukti; pesan aktual `Not shipped yet` |

> **Catatan R-01:** UAT-12 semula mengharapkan `Already released`/`Order is final`. Perilaku aktual adalah `Not shipped yet` karena guard status dievaluasi lebih dulu. Ekspektasi UAT sudah disesuaikan; kontrak tidak diubah. Lihat [`../week3/04_REVIEW_NOTES.md`](../week3/04_REVIEW_NOTES.md).

---

## 3. Skenario UAT-09 — Pembeli Cancel `Created`

| Langkah | Aksi | Hasil yang Diharapkan |
|---:|---|---|
| 1 | Checkout order 1 ETH sebagai pembeli | Status `Created`, escrow 1 ETH di kontrak |
| 2 | Buka order, pilih **Batalkan Order** | Form alasan tampil |
| 3 | Isi alasan ≤ 64 byte, konfirmasi | Transaksi `cancelOrder` terkirim |
| 4 | Tunggu konfirmasi | Status `Cancelled`; saldo kontrak turun 1 ETH; saldo pembeli naik (dikurangi gas) |
| 5 | Buka detail order | Event `OrderCancelled` + `EscrowRefunded` dan tx hash tampil |
| 6 | Coba batalkan lagi | Ditolak `Order is final` |

**Status saat ini:** langkah 1–6 terbukti di level kontrak lewat SC-11, tetapi langkah 2–3 dan 5 **belum dapat dilakukan di browser** karena tombol cancel belum ada.

---

## 4. Skenario UAT-10 — Pihak Luar Cancel `Paid`

| Langkah | Aksi | Hasil yang Diharapkan |
|---:|---|---|
| 1 | Operator menandai order `Paid` | Status `Paid` |
| 2 | Pembeli mencoba cancel | Revert `Not authorized to cancel` |
| 3 | Akun luar mencoba cancel | Revert `Not authorized to cancel` |
| 4 | Operator cancel dengan alasan | Berhasil; status `Cancelled`; escrow direfund |

**Status saat ini:** terbukti di level kontrak (SC-11). Uji browser menunggu UI dan integrasi wallet per peran.

---

## 5. Skenario UAT-11 — Konfirmasi Terima & Release

| Langkah | Aksi | Hasil yang Diharapkan |
|---:|---|---|
| 1 | Operator mengirim order (`Shipped`) | Status `Shipped` |
| 2 | Pembeli klik **Konfirmasi Terima** | Transaksi `confirmReceived` |
| 3 | Tunggu konfirmasi | Status `Completed`; escrow 1 ETH pindah ke owner |
| 4 | Coba konfirmasi lagi | Revert `Not shipped yet` |

**Status saat ini:** terbukti di level kontrak (SC-12). Alur `confirmReceived` di UI belum menjadi bagian M4.

---

## 6. Matriks Otorisasi Cancel

| State order | Pembeli | Operator / Owner | Hasil |
|---|---|---|---|
| `Created` | ✅ | ✅ | Refund bila escrow > 0 |
| `AwaitingPayment` | ✅ | ✅ | Refund bila escrow > 0 |
| `Paid` | ❌ | ✅ | Refund |
| `InProduction` | ❌ | ✅ | Refund |
| `ReadyToShip` | ❌ | ✅ | Refund |
| `Shipped` | ❌ | ❌ | `Invalid state transition` |
| `Completed` | ❌ | ❌ | `Order is final` |
| `Cancelled` | ❌ | ❌ | `Order is final` |

---

## 7. Batasan Eksekusi Sekarang

1. **UI cancel belum ada**, sehingga UAT browser belum dapat dijalankan end-to-end.
2. Peran di MetaMask masih memakai akun Ganache; belum ada pemisahan akun pembeli/operator yang ramah pengguna.
3. Refund memakai `transfer` 2300 gas; pembeli EOA aman, kontrak dengan `receive()` berat tidak diuji.
4. Waktu konfirmasi bergantung pada Ganache lokal; tidak ada uji testnet.
5. Bukti visual (screenshot) belum diambil karena menunggu UI.

---

## 8. Cara Mereproduksi Bukti Kontrak

Dari folder `blockchain/`:

```bash
npm test
```

Test SC-11 mencakup cancel valid, refund, unauthorized, state terminal, dan batas reason. Log tersimpan di [`evidence/sc07_sc12_test.log`](evidence/sc07_sc12_test.log).

Untuk eksplorasi manual dengan Truffle Console:

```js
const s = await HSportStore.deployed();
const accounts = await web3.eth.getAccounts();
await s.createOrder(hash, "INV-UAT-09", web3.utils.toWei("1","ether"), { from: accounts[1], value: web3.utils.toWei("1","ether") });
await s.cancelOrder(1, "UAT-09 batal oleh pembeli", { from: accounts[1] });
await s.getOrderStatus(1); // 7 = Cancelled
```

Jangan menuliskan private key atau seed phrase di dokumen, screenshot, atau commit.

---

## 9. Rencana Penutupan

| Item | Target |
|---|---|
| UI tombol cancel + validasi state | M5 (`W5-IHS-...`) |
| UAT browser UAT-09/10/11/12 | M5 setelah UI tersedia |
| Screenshot bukti | M5 |
| Integrasi simpan tx refund ke DB | M5 (INT-04/INT-05) |

---

## 10. Tautan

- Kontrak V2: [`00_CONTRACT_V2.md`](00_CONTRACT_V2.md)
- Test report: [`01_TEST_REPORT_V2.md`](01_TEST_REPORT_V2.md)
- Kompatibilitas frontend: [`04_FRONTEND_COMPAT.md`](04_FRONTEND_COMPAT.md)
- UAT lengkap: [`../week1/02_BUSINESS_PROCESS_AND_UAT.md`](../week1/02_BUSINESS_PROCESS_AND_UAT.md) §4
- Review M3: [`../week3/04_REVIEW_NOTES.md`](../week3/04_REVIEW_NOTES.md)
