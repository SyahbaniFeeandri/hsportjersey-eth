# 02 — TEST REPORT KONTRAK V1 (SC-01 s.d. SC-06)

**Issue:** `W3-KEN-03`, `W3-ARB-06`
**Pemilik:** Kenjiro B.H.G. (validator), Arby Putra (dev)
**Reviewer:** Agustina, Farrel, Feeandri
**Status:** **6/6 SC PASS** + 4 smoke PASS
**Tanggal eksekusi:** 11 November 2026
**Kontrak:** `HSportStore` V1 — `0x59d3631c86BbE35EF041872d502F218A39FBa150`

---

## 1. Ringkasan Eksekusi

| Item | Nilai |
|---|---|
| Perintah | `npm test` (menyetel `HSPORT_TEST=1`, network `ganache`) |
| Lokasi | `blockchain/` |
| Compiler | Solidity 0.8.19+commit.7dd6d404 |
| Network | Ganache 7.9.1, chain ID 1337, network ID 5777, RPC `127.0.0.1:7545` |
| Node.js | v24.20.0 |
| Hasil | **10 passing (2s)** — 6 test SC + 4 smoke |
| Kegagalan | 0 |
| Evidence | [`evidence/sc01_sc06_test.log`](evidence/sc01_sc06_test.log) |

> `npm test` dipakai, bukan `npx truffle test`, karena script menetapkan `HSPORT_TEST=1` agar migration tidak dijalankan ulang saat test.

---

## 2. Hasil per Test Case

### 2.1 Smart Contract (wajib M3)

| Test | Nama | Requirement | Hasil | Assertion utama |
|---|---|---|---|---|
| **SC-01** | Inisialisasi contract, owner, akses owner | REQ-02, NFR-02 | ✅ PASS | `owner` = deployer; `orderCount` awal 0; owner selalu `isOperator`; non-owner `addOperator` revert `Not owner`; `OperatorUpdated` aktif/nonaktif; revert `Operator already active`, `Invalid operator`, `Cannot remove owner`, `Operator not active` |
| **SC-02** | `createOrder` valid: escrow, state `Created`, event | REQ-01, REQ-02, REQ-08 | ✅ PASS | `orderCount` = 1; hash terdaftar; 9 field `Order` benar; status 0 (`Created`); escrow terisi, `released=false`, `refunded=false`; event `OrderCreated` lengkap; `orderNumber` kosong revert `Order number required` |
| **SC-03** | `createOrder` amount tidak valid revert | REQ-02 | ✅ PASS | `amount=0` revert `Amount must be > 0`; `msg.value` 0,5 ETH vs amount 1 ETH revert `Incorrect amount`; `orderCount` tidak bertambah |
| **SC-04** | Duplicate `externalOrderHash` revert | REQ-03 | ✅ PASS | Order pertama sukses; order kedua dengan hash sama revert `Duplicate order hash`; `orderCount` tetap 1; mapping hash tetap ke order 1 |
| **SC-05** | `getOrder` ID valid mengembalikan data benar | REQ-01, REQ-10 | ✅ PASS | `getOrder`, alias `getOrderInfo`, dan `getOrderStatus` konsisten; hash tak dikenal mengembalikan 0 |
| **SC-06** | `getOrder` ID tidak ada revert | REQ-10 | ✅ PASS | ID 0 dan 999 revert `Order not found` pada `getOrder`, `getOrderInfo`, `getOrderStatus`, `confirmReceived` |

### 2.2 Baseline smoke (bukan bagian 21 test)

| Test | Hasil | Catatan |
|---|---|---|
| SMOKE-01 | ✅ PASS | Owner = deployer, `orderCount` 0 |
| SMOKE-02 | ✅ PASS | `createOrder` mengunci escrow dan memancarkan event |
| SMOKE-03 | ✅ PASS | Amount 0 revert |
| SMOKE-04 | ✅ PASS | `msg.value` tidak sesuai revert |

Smoke test dipertahankan sebagai pemeriksaan cepat harness Truffle → Ganache; bukan pengganti SC.

---

## 3. Cakupan yang Belum Diuji di M3

SC-07 s.d. SC-12 **belum** dijalankan pada milestone ini sesuai pembagian roadmap (M4). Fungsi yang diuji di sana sudah ada di V1 (`recordPayment`, `updateOrderStatus`, `shipOrder`, `confirmReceived`), tetapi belum punya bukti test otomatis. Ini risiko yang tercatat di [`04_REVIEW_NOTES.md`](04_REVIEW_NOTES.md) dan harus ditutup sebelum V2 di-freeze.

| Test | Rencana | Status |
|---|---|---|
| SC-07 | Access control non-operator/non-buyer/non-owner | M4 |
| SC-08 | `recordPayment` + double payment | M4 |
| SC-09 | Transisi valid + `shipOrder` | M4 |
| SC-10 | Transisi tidak sah | M4 |
| SC-11 | `cancelOrder` — belum ada di V1 | M4 |
| SC-12 | `confirmReceived` + release escrow | M4 |

---

## 4. Catatan Lingkungan

1. Log memuat peringatan `µWS is not compatible with your Node.js build` dari dependensi Ganache. Truffle otomatis beralih ke implementasi Node.js; hasil test tidak terpengaruh. Peringatan ini bukan kegagalan test.
2. Test memakai instance kontrak baru pada `beforeEach`, sehingga tidak menyentuh order demo di kontrak deploy (`INV-2026-DEMO-01`).
3. Test dijalankan ulang dari branch `feature/W3-ARB-04-contract-v1` setelah kontrak dan test difinalkan; log yang dilampirkan adalah hasil eksekusi terakhir.

---

## 5. Kesimpulan

- Exit gate M3 untuk test **terpenuhi**: SC-01 s.d. SC-06 6/6 PASS, 0 gagal.
- Regresi V0 tidak berlaku karena kontrak di-redeploy; ABI V0 diarsipkan untuk jejak.
- Rekomendasi: jalankan ulang seluruh 21 test setelah SC-07 s.d. SC-12 ditulis di M4.

---

## 6. Sign-off

| Peran | Nama | Status |
|---|---|---|
| Test owner / validator | Kenjiro B.H.G. | ⏳ menunggu review PR |
| Dev kontrak | Arby Putra | ⏳ menunggu review PR |
| Review perilaku | Agustina Elsi Bunga | ⏳ menunggu review PR |
| Deployment | Farrelly De Rossi | ⏳ menunggu review PR |

---

## 7. Tautan

- Implementasi & deploy: [`00_CONTRACT_V1.md`](00_CONTRACT_V1.md)
- Interface freeze: [`01_INTERFACE_FREEZE.md`](01_INTERFACE_FREEZE.md)
- Test plan asal: [`../week1/07_TEST_PLAN.md`](../week1/07_TEST_PLAN.md) §7.1
- Evidence: [`evidence/sc01_sc06_test.log`](evidence/sc01_sc06_test.log)
