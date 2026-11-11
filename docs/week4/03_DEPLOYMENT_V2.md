# 03 — DEPLOYMENT V2

**Issue:** `W4-FRL-07`
**Pemilik:** Farrelly De Rossi
**Reviewer:** Arby, Kenjiro
**Status:** Berhasil
**Tanggal:** 11 November 2026

---

## 1. Fakta Deploy

| Item | Nilai |
|---|---|
| Contract | `HSportStore` |
| Compiler | `0.8.19+commit.7dd6d404.Emscripten.clang` |
| Network | Ganache 7.9.1 |
| Chain ID | 1337 |
| Network ID | 5777 |
| RPC | `http://127.0.0.1:7545` |
| Address | `0x2f3efA6bbDC5fAf4dC1a600765c7B7829e47bE10` |
| Transaction | `0x5b18d347ba2561276890faa3bdcdd204a502721fd6e83d1895d1f13e2aad90ef` |
| Block | 225 |
| Gas used | 1.816.066 |
| Biaya | 0.004540165014528528 ETH |
| Bytecode creation | 8.107 byte |
| Bytecode deployed | 8.057 byte |
| ABI SHA-256 | `23A73D09E75661576D3E42C932132C63242B33DCB0966608BDDA5CC86014606F` |

Rekaman lengkap:

- `blockchain/deployments/deployment_2026-11-11T05-26-49-751Z.json`
- `blockchain/deployments/DEPLOYMENTS.md`

---

## 2. Verifikasi Address

`blockchain/config/contract_address.txt`:

```text
0x2f3efA6bbDC5fAf4dC1a600765c7B7829e47bE10
```

Verifikasi baca langsung:

```text
RPC URL        : http://127.0.0.1:7545
Chain ID       : 1337
Block number   : 225
Contract       : 0x2f3efA6bbDC5fAf4dC1a600765c7B7829e47bE10
Bytecode size  : 8057 bytes
Owner          : 0x90f8bf6a479f320ead074411a4b0e7944ea8c9c1
Order count    : 0
```

Ukuran bytecode 8.057 byte cocok dengan artefak V2, sehingga address di config berisi kontrak yang benar.

---

## 3. ABI

| Item | Lokasi |
|---|---|
| ABI aktif | `blockchain/config/contract_abi.json` |
| Arsip V2 | `blockchain/config/archive/contract_abi.v2.json` |
| Arsip V1 | `blockchain/config/archive/contract_abi.v1.json` |
| Arsip V0 | `blockchain/config/archive/contract_abi.v0.json` |

ABI V2 berisi 29 entri: 19 fungsi + 9 event + constructor. Penambahan dari V1 hanya `cancelOrder(uint256,string)`.

---

## 4. Riwayat Address

| Versi | Address | Status |
|---|---|---|
| V0 | `0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab` | Historis |
| V1 | `0x59d3631c86BbE35EF041872d502F218A39FBa150` | Historis, tag `contract-v1` |
| V2 | `0x2f3efA6bbDC5fAf4dC1a600765c7B7829e47bE10` | **Aktif** |

Order on-chain V1 tetap ada di chain, tetapi frontend kini menunjuk V2. Order V1 tidak dapat dibaca lewat address aktif; UI harus menandainya sebagai order lama tanpa proof jika ada.

---

## 5. Catatan Reproducibility

- Deploy V2 dilakukan pada Ganache yang sama setelah V1, sehingga nonce deployer berbeda dan address V2 berbeda dari V1.
- Pada Ganache fresh dengan urutan deploy yang sama (V1 lalu V2), address akan deterministik.
- Untuk lingkungan baru, cukup deploy V2; V1 tidak diperlukan kecuali untuk demo riwayat.
- Jangan commit private key; deploy memakai akun deterministik Ganache yang hanya ada di mesin lokal.

---

## 6. Checklist

- [x] Compile berhasil
- [x] Deploy berhasil
- [x] Address tersimpan di config
- [x] ABI aktif diperbarui
- [x] ABI V2 diarsipkan
- [x] Deployment log dicatat
- [x] Bytecode terverifikasi lewat `eth_getCode`
- [x] Read-only prototype membaca kontrak V2
- [ ] Frontend diuji end-to-end dengan V2 (M5)

---

## 7. Tautan

- Kontrak V2: [`00_CONTRACT_V2.md`](00_CONTRACT_V2.md)
- Test report: [`01_TEST_REPORT_V2.md`](01_TEST_REPORT_V2.md)
- Deployment log: [`../../blockchain/deployments/DEPLOYMENTS.md`](../../blockchain/deployments/DEPLOYMENTS.md)
- Read-only prototype: [`../week3/03_READONLY_PROTOTYPE.md`](../week3/03_READONLY_PROTOTYPE.md)
