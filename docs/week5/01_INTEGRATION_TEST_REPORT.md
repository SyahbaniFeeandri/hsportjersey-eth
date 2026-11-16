# 01 — LAPORAN TEST INTEGRASI INT-01 s.d. INT-05

**Issue:** `W5-LUI-11`, `W5-KEN-05`
**Pemilik:** Luis (Integration Engineer)
**Validator:** Kenjiro B.H.G. (QA & Security)
**Status:** ✅ 5/5 PASS
**Tanggal eksekusi:** 12 November 2026 02:41 UTC
**Lingkungan:** Windows 11, PHP 8.1.10, MySQL 8.0.30, Ganache CLI (chain ID 1337)

---

## 1. Tujuan

Membuktikan alur end-to-end:

```
checkout DB → createOrder on-chain → receipt/event → persist proof → verifikasi DB vs chain
```

Test dijalankan oleh `tests/integration/run_all.php` dan bersifat idempoten:
seluruh order uji lama dihapus, lalu setiap eksekusi memakai `order_number` unik.

---

## 2. Hasil

| Test | Nama | Hasil | Detail |
|---|---|---|---|
| INT-01 | Koneksi RPC backend ke Ganache | ✅ PASS | RPC `http://127.0.0.1:7545`, chain ID 1337, block #3, bytecode kontrak `0xe78A…a8Ab` terverifikasi |
| INT-02 | Sinkronisasi order DB ↔ on-chain | ✅ PASS | DB order #34 `INV-INT-0241375239` → on-chain order #2, buyer `0xffcf…09f0`, status `Created` |
| INT-03 | Tx hash, receipt, status | ✅ PASS | Tx `0x2639d90018…3b91`, status `CONFIRMED`, block #4, gas 281.317 |
| INT-04 | Persist proof | ✅ PASS | Tersimpan di `orders` (tx, block, wallet, contract, chain id) dan 1 baris `blockchain_transactions` |
| INT-05 | Verifikasi DB vs chain | ✅ PASS | Cocok → VERIFIED; manipulasi Rp 10.500.000 → MISMATCH (1 diff); restore → VERIFIED |

**Hasil akhir: 5 / 5 PASS (100%).**

Bukti:

- [`evidence/int01_int05_test.log`](evidence/int01_int05_test.log)
- [`evidence/int01_int05_test.json`](evidence/int01_int05_test.json)

---

## 3. Detail Skenario

### INT-01 — Koneksi RPC
`OrderIntegrationService::healthCheck()` memanggil `eth_chainId`, `eth_blockNumber`,
dan `eth_getCode`. PASS bila chain ID = 1337 dan bytecode kontrak tidak kosong.

### INT-02 — Checkout → chain
1. Insert order kripto ke MySQL (`status=pending`, `payment_status=unpaid`).
2. Hitung `external_order_hash = sha256(order_number|wallet|amount)`.
3. Kirim `createOrder(bytes32,string,uint256)` dari akun pembeli Ganache
   dengan `msg.value = amountWei` melalui `ContractService::createOrder()`.
4. Baca event `OrderCreated` dari receipt untuk mendapatkan order ID on-chain.
5. Simpan relasi `blockchain_order_id`, `external_order_hash`, `wallet_address`,
   dan `contract_address` ke DB.

### INT-03 — Receipt
`ReceiptService::fetchAndParseReceipt()` memetakan receipt JSON-RPC menjadi
status `CONFIRMED`/`FAILED`, block number, gas used, dan daftar event terparse.

### INT-04 — Bukti
`ProofRepository::persistProof()` menulis ke `orders`:
`tx_hash`, `block_number`, `wallet_address`, `contract_address`,
`blockchain_order_id`, `external_order_hash`, `payment_status=paid`,
`verification_status=PENDING`, dan mencatat baris `blockchain_transactions`.

### INT-05 — Verifikasi
- Data cocok → `VERIFIED` tanpa diff.
- `total_amount` dinaikkan Rp 500.000 → `MISMATCH` dengan diff `total_amount`.
- Nilai dikembalikan → `VERIFIED`.

---

## 4. Perbaikan yang Dilakukan

| Masalah | Penyebab | Perbaikan |
|---|---|---|
| INT-05 gagal `Duplicate entry 'INV-TEST-001'` | Sisa order uji lama + `order_number` dipaksa mengikuti chain | Isolasi data uji (hapus `INV-INT-%` dan `INV-TEST-001`), order uji memakai nomor unik yang sama di DB dan chain |
| `createOrder` revert (gas 90.000) | Ganache memberi gas default terlalu rendah untuk transaksi ber-`value` | `BlockchainClient::sendTransaction()` mengirim `gas` eksplisit (500.000) |
| Backend belum punya write path | `ContractService` hanya membaca | Tambah `BlockchainClient::sendTransaction()` dan `ContractService::createOrder()` |

---

## 5. Catatan Independen (Kenjiro)

- Test tidak memakai mock: seluruh pembacaan dan penulisan menyentuh Ganache nyata.
- Assertion tidak hanya mengecek “tidak error”, tetapi juga kecocokan field
  (`buyer`, `external_order_hash`, `block`, `chain order id`, status verifikasi).
- Skenario negatif (manipulasi nominal) wajib menghasilkan `MISMATCH`.
- Test dapat dijalankan ulang tanpa intervensi manual; data uji dibersihkan
  otomatis pada awal eksekusi.
- Risiko sisa: test memakai akun Ganache yang unlocked; di testnet/produksi
  penandatanganan harus melalui wallet pengguna atau signer terpisah.
