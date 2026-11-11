# 03 — PROTOTIPE READ-ONLY KONTRAK V1 (PHP)

**Issue:** `W3-LUI-04`
**Pemilik:** Luis
**Reviewer:** Farrel, Arby, Kenjiro
**Status:** Berfungsi terhadap kontrak V1 terdeploy
**Tanggal:** 11 November 2026

---

## 1. Tujuan

Membuktikan bahwa backend PHP dapat membaca kontrak `HSportStore` V1 langsung lewat JSON-RPC **tanpa private key, tanpa library Web3, dan tanpa menulis state**. Prototipe ini adalah fondasi service integrasi penuh di Minggu 5.

| Aspek | Keputusan |
|---|---|
| Bahasa | PHP 8.1 native |
| Transport | `curl` ke JSON-RPC Ganache |
| Metode | `eth_chainId`, `eth_blockNumber`, `eth_getCode`, `eth_call` |
| Encoding | Selector fungsi di-hardcode, parameter di-ABI-encode manual (32 byte) |
| Kunci privat | Tidak dipakai sama sekali |
| Write | Tidak ada (`eth_sendRawTransaction` tidak diimplementasi) |

---

## 2. File

| File | Fungsi |
|---|---|
| `blockchain/scripts/read_contract.php` | Membaca owner, orderCount, chain info, dan status order |
| `blockchain/scripts/orderHash.js` | Menghitung hash kanonik order (helper off-chain) |
| `blockchain/config/contract_address.txt` | Address kontrak aktif (V1) |
| `blockchain/config/contract_abi.json` | ABI V1 (referensi selector) |

---

## 3. Selector

Selector dihitung sekali dari ABI, lalu di-hardcode agar skrip tidak perlu parser ABI.

| Fungsi | Selector | Dipakai |
|---|---|---|
| `owner()` | `0x8da5cb5b` | ✅ |
| `orderCount()` | `0x2453ffa8` | ✅ |
| `getOrderStatus(uint256)` | `0x45fa8aae` | ✅ |
| `getOrder(uint256)` | `0xd09ef241` | Dihitung, belum didekode |

Regenerasi selector (dari `blockchain/`):

```bash
node -e "const {Web3}=require('web3');const w=new Web3();['owner()','orderCount()','getOrderStatus(uint256)','getOrder(uint256)'].forEach(s=>console.log(s,w.eth.abi.encodeFunctionSignature(s)))"
```

---

## 4. Cara Pakai

```bash
cd blockchain

# Info kontrak + jumlah order
php scripts/read_contract.php

# Tambah status order ID 1
php scripts/read_contract.php 1
```

`RPC_URL` dapat dioverride lewat environment; default `http://127.0.0.1:7545`.

---

## 5. Contoh Output (kontrak V1)

```text
RPC URL        : http://127.0.0.1:7545
Chain ID       : 1337
Block number   : 13
Contract       : 0x59d3631c86BbE35EF041872d502F218A39FBa150
Bytecode size  : 6874 bytes
Owner          : 0x90f8bf6a479f320ead074411a4b0e7944ea8c9c1
Order count    : 1
Order #1       : 0 (Created)
```

Angka `Bytecode size` cocok dengan ukuran deployed V1 (6.874 byte), sehingga address di config terbukti berisi kontrak yang benar.

---

## 6. Order Demo

Untuk memverifikasi jalur baca status, satu order disemai ke kontrak deploy:

| Field | Nilai |
|---|---|
| `orderNumber` | `INV-2026-DEMO-01` |
| Buyer | `0xFFcf8FDEE72ac11b5c542428B35EEF5769C409f0` (accounts[1]) |
| Amount | 1 ETH |
| Hash | `0x43c3bc063b647f0f60508c60bd35159c305ffed717a1d359d7f41c60ff7b0863` |
| On-chain ID | 1 |
| Status | `Created` (0) |

Order demo tidak memengaruhi test otomatis karena test memakai instance kontrak baru.

---

## 7. Hash Kanonik

Formula yang dibekukan:

```text
keccak256(abi.encode(orderNumber, buyer, amountWei))
```

Vektor uji:

```text
orderNumber = "INV-2026-0001"
buyer       = 0xFFcf8FDEE72ac11b5c542428B35EEF5769C409f0
amountWei   = 1000000000000000000
hash        = 0x10e43d6eede25e203a02c2e865329e767e3d0956b9fc3812e8708c3c57a7ff86
```

Verifikasi:

```bash
cd blockchain
node scripts/orderHash.js INV-2026-0001 0xFFcf8FDEE72ac11b5c542428B35EEF5769C409f0 1000000000000000000
```

Nilai ini identik dengan yang dihitung `crypto-payment.php` di browser dan dipakai test SC.

---

## 8. Keterbatasan (Penting untuk M5)

1. **PHP tidak punya `keccak256` bawaan.** `hash('sha3-256', ...)` adalah SHA3 NIST, **bukan** Keccak Ethereum; hasilnya berbeda. Karena itu hashing di prototipe dilakukan lewat Node (`orderHash.js`) dan di frontend lewat `web3.utils.soliditySha3`. Untuk service M5, pilih salah satu:
   - library pure-PHP Keccak yang sudah teruji, atau
   - sidecar Node kecil yang dijalankan sebagai service internal.
   Jangan memakai `sha3-256` PHP untuk hash order.
2. **Hanya tipe statis.** Decoder manual baru menangani `address`, `uint256`, `bool`. `getOrder` mengembalikan struct + string dinamis, sehingga belum didekode; status dibaca lewat `getOrderStatus` yang mengembalikan `uint8`.
3. **Tanpa retry/timeout lanjutan.** `CURLOPT_TIMEOUT` 10 detik; kegagalan RPC langsung keluar dengan kode 1.
4. **Tanpa verifikasi ABI.** Selector di-hardcode; bila interface berubah, selector harus diregenerasi. Ini alasan interface freeze penting.
5. **Ganache saja.** Belum diuji ke node testnet; chain ID dan address masih lokal.

---

## 9. Bukti Tidak Ada Operasi Tulis

| Pemeriksaan | Hasil |
|---|---|
| Metode RPC yang dipanggil | Hanya `eth_chainId`, `eth_blockNumber`, `eth_getCode`, `eth_call` |
| Private key / seed di skrip | Tidak ada |
| `eth_sendTransaction` / `eth_sendRawTransaction` | Tidak dipanggil |
| Perubahan state setelah `read_contract.php` | Tidak ada; block number tetap kecuali ada transaksi lain |

---

## 10. Rencana M5

1. Bungkus menjadi kelas service (`BlockchainReader`) dengan config `.env`.
2. Tambah decoder ABI penuh dari `contract_abi.json` (struct, string, array).
3. Tambah endpoint health + verifikasi order (INT-01, INT-05).
4. Simpan `external_order_hash`, `tx_hash`, `block_number`, `verification_status` ke MySQL saat checkout dikonfirmasi.
5. Tambah test integrasi INT-01 s.d. INT-05.

---

## 11. Tautan

- Implementasi kontrak: [`00_CONTRACT_V1.md`](00_CONTRACT_V1.md)
- Interface freeze: [`01_INTERFACE_FREEZE.md`](01_INTERFACE_FREEZE.md)
- Desain integrasi: [`../week1/05_INTEGRATION_AND_UI_DESIGN.md`](../week1/05_INTEGRATION_AND_UI_DESIGN.md)
- Skrip: [`../../blockchain/scripts/read_contract.php`](../../blockchain/scripts/read_contract.php)
