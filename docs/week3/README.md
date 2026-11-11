# Minggu 3 — Smart Contract V1

**Milestone:** M3 — Smart Contract V1 (Arby + tim)
**Periode:** 10–11 November 2026
**Branch:** `feature/W3-ARB-04-contract-v1`
**Status:** **Selesai** — 12/12 issue punya artefak; exit gate teknis terpenuhi
**Reviewer sign-off:** ⏳ menunggu review PR

---

## 1. Ringkasan

Minggu 3 memperluas `HSportStore.sol` dari V0 (4 state) ke V1 (8 state) tanpa mengganti nama kontrak. V1 mencakup seluruh alur kripto sampai `confirmReceived`; `cancelOrder` dan refund ditunda ke V2 (Minggu 4). ABI V1 dibekukan dan diarsipkan bersama ABI V0.

| Item | Nilai |
|---|---|
| Compiler | Solidity 0.8.19, optimizer 200 runs |
| Address V1 | `0x59d3631c86BbE35EF041872d502F218A39FBa150` |
| Tx deploy | `0xd796d9d36bb19e921be8d3bffa5c78b1ececfad433c2a1083e20d2e512506228` |
| Block | 12 |
| Gas deploy | 1.560.496 |
| Biaya deploy | 0.004247818713352592 ETH |
| ABI V1 SHA-256 | `9E0C2F61154A362D7388AFB0185676370B6F483F9E37E55FF480AFBF0409AE8D` |
| Test | 6/6 SC PASS + 4 smoke PASS (10 passing) |
| Fungsi / event | 18 fungsi (12 eksplisit + 6 auto-getter) / 9 event |

---

## 2. Status Issue (12/12)

| ID | Issue | Output | Status | Bukti |
|---|---|---|---|---|
| `W3-ARB-04` | Enum 8 state, struct + `externalOrderHash`, mapping, modifier | Kontrak V1 | ✅ Selesai | `blockchain/contracts/HSportStore.sol`; [`00_CONTRACT_V1.md`](00_CONTRACT_V1.md) |
| `W3-ARB-05` | `createOrder`, `getOrder`, `isOperator`, duplicate hash, amount > 0 | Fungsi kontrak | ✅ Selesai | Kontrak + test SC-02..SC-06 |
| `W3-ARB-06` | NatSpec + unit test SC-01 s.d. SC-06 | 6/6 PASS | ✅ Selesai | `blockchain/test/01_sc01_sc06.test.js`; [`02_TEST_REPORT_V1.md`](02_TEST_REPORT_V1.md) |
| `W3-ARB-07` | Export ABI + catat address V1 | ABI/address | ✅ Selesai | `blockchain/config/contract_abi.json`, `config/archive/`, `deployments/` |
| `W3-AGU-04` | Review V1 + skenario SC-07 s.d. SC-12 | Review + test plan V2 | ✅ Selesai | [`04_REVIEW_NOTES.md`](04_REVIEW_NOTES.md) |
| `W3-FRL-06` | Deploy V1 ke Ganache + deployment log | Address V1 | ✅ Selesai | `deployments/deployment_2026-11-11T03-59-13-663Z.json`, `DEPLOYMENTS.md` |
| `W3-KEN-03` | Review & jalankan ulang SC-01 s.d. SC-06, bug report | Test report V1 | ✅ Selesai | [`02_TEST_REPORT_V1.md`](02_TEST_REPORT_V1.md), [`evidence/`](evidence/sc01_sc06_test.log) |
| `W3-FEE-08` | Update on/off-chain matrix + traceability kontrak | Dokumen update | ✅ Selesai | `docs/week1/03_TRACEABILITY_MATRIX.md`, `docs/week1/01_ON_OFF_CHAIN_AND_STATE.md` |
| `W3-YAS-07` | Review acceptance criteria kontrak core | Catatan review | ✅ Selesai | [`04_REVIEW_NOTES.md`](04_REVIEW_NOTES.md) §2 |
| `W3-LUI-04` | Prototipe contract instance read-only | Service read-only | ✅ Selesai | `blockchain/scripts/read_contract.php`, `orderHash.js`; [`03_READONLY_PROTOTYPE.md`](03_READONLY_PROTOTYPE.md) |
| `W3-FDR-04` | Finalkan migration schema paralel | Migration | ✅ Selesai | `migration_parallel.sql`; [`05_PARALLEL_SCHEMA_FINAL.md`](05_PARALLEL_SCHEMA_FINAL.md) |
| `W3-IHS-03` | Mockup proof page + komponen tx status | Mockup | ✅ Selesai | [`mockups/proof-page.html`](mockups/proof-page.html) |

---

## 3. Exit Gate

| Kriteria | Status | Bukti |
|---|---|---|
| Kontrak compile tanpa error | ✅ | `npm run compile` — solc 0.8.19 |
| Kontrak deploy ke Ganache | ✅ | Address `0x59d3…a150`, block 12 |
| SC-01 s.d. SC-06 6/6 PASS | ✅ | `10 passing`; [`evidence/sc01_sc06_test.log`](evidence/sc01_sc06_test.log) |
| ABI V1 diekspor + V0 diarsipkan | ✅ | `config/contract_abi.json`, `config/archive/contract_abi.v{0,1}.json` |
| Interface freeze terdokumentasi | ✅ teknis | [`01_INTERFACE_FREEZE.md`](01_INTERFACE_FREEZE.md) |
| Tidak ada private key/seed di repo | ✅ | Hanya address publik; skrip read-only |
| Sign-off reviewer | ⏳ | Menunggu review PR (Arby, Agustina, Luis, Kenjiro) |

---

## 4. Artefak

### 4.1 Kode dan konfigurasi

| Path | Isi |
|---|---|
| `blockchain/contracts/HSportStore.sol` | Kontrak V1 + NatSpec |
| `blockchain/test/00_baseline_smoke.test.js` | 4 smoke test harness |
| `blockchain/test/01_sc01_sc06.test.js` | 6 test SC-01 s.d. SC-06 |
| `blockchain/config/contract_abi.json` | ABI V1 aktif |
| `blockchain/config/contract_address.txt` | Address V1 |
| `blockchain/config/archive/contract_abi.v0.json` | Arsip ABI V0 |
| `blockchain/config/archive/contract_abi.v1.json` | Arsip ABI V1 |
| `blockchain/deployments/deployment_2026-11-11T03-59-13-663Z.json` | Rekaman deploy |
| `blockchain/deployments/DEPLOYMENTS.md` | Log deploy |
| `blockchain/scripts/read_contract.php` | Prototipe baca kontrak (read-only) |
| `blockchain/scripts/orderHash.js` | Helper hash kanonik |
| `crypto-payment.php` | Frontend checkout disesuaikan ke `createOrder` 3 argumen |

### 4.2 Dokumen

| Dokumen | Isi |
|---|---|
| [`00_CONTRACT_V1.md`](00_CONTRACT_V1.md) | Implementasi, perubahan dari V0, deploy, keputusan |
| [`01_INTERFACE_FREEZE.md`](01_INTERFACE_FREEZE.md) | Signature, event, revert, kebijakan perubahan |
| [`02_TEST_REPORT_V1.md`](02_TEST_REPORT_V1.md) | Hasil SC-01..SC-06 + cakupan M4 |
| [`03_READONLY_PROTOTYPE.md`](03_READONLY_PROTOTYPE.md) | Prototipe PHP, selector, batasan keccak |
| [`04_REVIEW_NOTES.md`](04_REVIEW_NOTES.md) | Acceptance, temuan, skenario SC-07..SC-12 |
| [`05_PARALLEL_SCHEMA_FINAL.md`](05_PARALLEL_SCHEMA_FINAL.md) | Skema paralel final + klaim job |
| [`mockups/proof-page.html`](mockups/proof-page.html) | Mockup halaman bukti + status transaksi |

---

## 5. Cara Mereproduksi

Prasyarat: Node 24, PHP 8.1, MySQL 8.0.30, Ganache 7.9.1.

```bash
# 1. Jalankan Ganache (chainId 1337, networkId 5777, RPC 7545)
cd blockchain
npx ganache --server.port 7545 --chain.chainId 1337 \
  --chain.networkId 5777 --wallet.deterministic --wallet.totalAccounts 10

# 2. Compile
npm run compile

# 3. Test (menyetel HSPORT_TEST=1 agar migration dilewati)
npm test

# 4. Deploy + catat address
npm run migrate

# 5. Baca kontrak (read-only)
php scripts/read_contract.php
php scripts/read_contract.php 1

# 6. Hash kanonik
node scripts/orderHash.js INV-2026-0001 0xFFcf8FDEE72ac11b5c542428B35EEF5769C409f0 1000000000000000000
```

Catatan: Ganache deterministic menghasilkan address kontrak yang sama selama nonce deployer dan bytecode tidak berubah.

---

## 6. Risiko dan Batasan

| # | Risiko | Mitigasi |
|---|---|---|
| 1 | SC-07 s.d. SC-12 belum otomatis | Ditulis sebagai langkah pertama M4; temuan R-02 di [`04_REVIEW_NOTES.md`](04_REVIEW_NOTES.md) |
| 2 | `cancelOrder` belum ada | V2 aditif; signature sudah dibekukan di spesifikasi |
| 3 | `Already released` tidak tercapai karena urutan require | Temuan R-01; putuskan di awal M4 |
| 4 | PHP tidak punya `keccak256` bawaan | Hash dibuat lewat Node/JS; M5 memakai library Keccak teruji |
| 5 | Order V0 tidak dapat diakses setelah redeploy | UI menandai order lama tanpa proof |
| 6 | Sign-off reviewer belum lengkap | PR M3 dengan checklist reviewer |
| 7 | `W2-FRL-05` (clean clone di mesin kedua) belum dilakukan | Dijadwalkan sebelum demo; lihat GAP_ANALYSIS |

---

## 7. Keputusan Minggu 3

| # | Keputusan | Alasan |
|---|---|---|
| 1 | V1 = core + happy path; cancel/refund ke V2 | Exit gate M3 dapat diselesaikan tanpa menunda integrasi |
| 2 | ABI V1 aditif terhadap rencana V2 | V2 hanya menambah `cancelOrder`, tidak mengubah signature |
| 3 | Frontend langsung disesuaikan ke `createOrder` 3 argumen | Mencegah integrasi rusak di M5 |
| 4 | Test memakai `npm test` | Menjamin `HSPORT_TEST=1` dan tidak deploy ulang saat test |
| 5 | Event cancel tetap dideklarasikan | Daftar event final stabil sejak V1 |

---

## 8. Tautan

- Repo: <https://github.com/SyahbaniFeeandri/hsportjersey-eth>
- PR M2: <https://github.com/SyahbaniFeeandri/hsportjersey-eth/pull/5>
- PR M3 (stacked): <https://github.com/SyahbaniFeeandri/hsportjersey-eth/pull/6>
- Spesifikasi kontrak: [`../week2/02_CONTRACT_SPEC_FINAL.md`](../week2/02_CONTRACT_SPEC_FINAL.md)
- Traceability: [`../week1/03_TRACEABILITY_MATRIX.md`](../week1/03_TRACEABILITY_MATRIX.md)
- Roadmap: [`../ROADMAP_RINCI.md`](../ROADMAP_RINCI.md)
