# 00 — BASELINE ENVIRONMENT BLOCKCHAIN (MINGGU 2)

**Issue:** `W2-FRL-01`, `W2-FRL-04`
**Pemilik:** Muhammad Farrel
**Reviewer:** Luis, Kenjiro
**Status:** Baseline v1.0
**Tanggal:** 10 November 2026

---

## 1. Tujuan

Mengunci parameter environment blockchain agar:

1. Setiap anggota mendapatkan chain, akun, dan alamat yang sama.
2. Deploy dan test dapat diulang tanpa hasil yang berbeda.
3. ABI/address yang dipakai frontend dan backend dapat dilacak.
4. Reset Ganache tidak menghilangkan bukti secara diam-diam.

---

## 2. Workstation Baseline

| Komponen | Nilai |
|---|---|
| OS | Windows 11 Pro 10.0.26200, 64-bit |
| CPU | Intel Core i7-8565U @ 1.80 GHz |
| Core / logical | 4 / 8 |
| RAM | 15,8 GB |
| Node.js | v24.20.0 |
| npm | 11.19.0 |
| Truffle | 5.11.5 |
| Ganache | 7.9.1 |
| Solidity | 0.8.19 (solc-js) |
| Web3.js (Truffle) | 1.10.0 |
| Web3.js (browser) | 4.2.2 |
| MySQL | 8.0.30 |
| PHP | 8.1.10 |

> Spesifikasi ini wajib dicatat ulang jika benchmark dijalankan di mesin lain.

---

## 3. Konfigurasi Ganache

### 3.1 Perintah resmi

```powershell
cd C:\laragon\www\project_jersey\blockchain
.\node_modules\.bin\ganache.cmd `
  --server.port 7545 `
  --chain.chainId 1337 `
  --chain.networkId 5777 `
  --wallet.deterministic `
  --wallet.totalAccounts 10 `
  --logging.quiet
```

### 3.2 Parameter yang dikunci

| Parameter | Nilai | Catatan |
|---|---|---|
| RPC URL | `http://127.0.0.1:7545` | Dipakai Truffle, PHP service, MetaMask |
| Chain ID | `1337` | Wajib sama di MetaMask |
| Network ID | `5777` | Dikunci agar tidak berubah tiap start |
| Jumlah akun | 10 | Masing-masing 1000 ETH |
| Mnemonic | deterministik bawaan Ganache | **Tidak boleh ditulis di repo/dokumen** |
| Hardfork | `shanghai` | Bawaan Ganache 7.9.1 |
| Block gas limit | 30.000.000 | Bawaan |

### 3.3 Akun publik

Private key **tidak dicatat**. Hanya alamat publik yang boleh dipakai di dokumen.

| Index | Address | Peran |
|---:|---|---|
| 0 | `0x90F8bf6A479f320ead074411a4B0e7944Ea8c9C1` | Owner / deployer / seller |
| 1 | `0xFFcf8FDEE72ac11b5c542428B35EEF5769C409f0` | Buyer |
| 2 | `0x22d491Bde2303f2f43325b2108D26f1eAbA1e32b` | Operator |
| 3 | `0xE11BA2b4D45Eaed5996Cd0823791E0C93114882d` | Outsider / negative test |
| 4 | `0xd03ea8624C8C5987235048901fB614fDcA89b117` | Cadangan |
| 5 | `0x95cED938F7991cd0dFcb48F0a06a40FA1aF46EBC` | Cadangan |
| 6 | `0x3E5e9111Ae8eB78Fe1CC3bb8915d5D461F3Ef9A9` | Cadangan |
| 7 | `0x28a8746e75304c0780E011BEd21C72cD78cd535E` | Cadangan |
| 8 | `0xACa94ef8bD5ffEE41947b4585a84BdA5a3d3DA6E` | Cadangan |
| 9 | `0x1dF62f291b2E969fB0849d99D9Ce41e2F137006e` | Cadangan |

> Akun #0–#3 dipakai tetap di seluruh test agar hasil dapat dibandingkan.

---

## 4. Konfigurasi Truffle

`blockchain/truffle-config.js`:

```js
networks: {
  development: { host: "127.0.0.1", port: 7545, network_id: "*" },
  ganache:     { host: "127.0.0.1", port: 7545, network_id: "*" },
}
```

`network_id: "*"` menerima network ID apa pun. **Network ID aktual tetap 5777** dan dicatat di deployment log.

---

## 5. Deployment Baseline

| Item | Repo utama | Clean clone |
|---|---|---|
| Contract | `HSportStore` | `HSportStore` |
| Address | `0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab` | `0x5b1869D9A4C187F2EAa108f3062412ecf0526b24` |
| Block | 1 | 2 |
| Tx hash | `0xd6afaa67400673212a28b865432f7b45d6acb232b9ca1de375095d69894d4bb0` | `0x13d5b895b2fefb2ff0442acb6d877f2bd95a1edf8998b7bb48916b9b61ee2841` |
| ABI SHA-256 | `FC5622FE41D0C2F9BF12BDAD8243BE56BA07074AE227FCAC877700A64A9D6252` | sama |

**Catatan:** address berbeda karena nonce akun deployer berbeda (repo utama deploy pertama, clean clone deploy kedua). Yang wajib konsisten adalah **ABI**, bukan address. Setiap address harus tercatat di deployment log.

---

## 6. Snapshot & Reset

### 6.1 Snapshot logis (default)

Karena mnemonic deterministik, akun selalu sama. Yang hilang saat reset hanyalah state chain (kontrak, transaksi, block).

**Prosedur reset resmi:**

```powershell
# 1. Hentikan Ganache, lalu jalankan ulang dengan perintah di bagian 3.1
# 2. Deploy ulang
cd blockchain
npm run migrate
node scripts/logDeployment.js

# 3. Verifikasi
npm run ganache:ping
npm test
```

### 6.2 Persistensi opsional

Jika ingin state bertahan setelah Ganache dimatikan:

```powershell
.\node_modules\.bin\ganache.cmd `
  --server.port 7545 `
  --chain.chainId 1337 `
  --chain.networkId 5777 `
  --wallet.deterministic `
  --wallet.totalAccounts 10 `
  --database.dbPath .ganache-db
```

Folder `.ganache-db/` **tidak boleh di-commit** (tambahkan ke `.gitignore` jika dipakai).

### 6.3 Snapshot EVM (opsional, untuk demo)

Ganache mendukung `evm_snapshot` dan `evm_revert` melalui JSON-RPC. Contoh:

```powershell
# Simpan snapshot sebelum demo
curl -X POST http://127.0.0.1:7545 `
  -H "Content-Type: application/json" `
  -d '{"jsonrpc":"2.0","method":"evm_snapshot","params":[],"id":1}'

# Kembalikan ke snapshot
curl -X POST http://127.0.0.1:7545 `
  -H "Content-Type: application/json" `
  -d '{"jsonrpc":"2.0","method":"evm_revert","params":["0x1"],"id":2}'
```

Gunakan hanya saat rehearsal demo, bukan saat benchmark.

---

## 7. Verifikasi Cepat

| Perintah | Output yang diharapkan |
|---|---|
| `npm run ganache:ping` | chain ID 1337, 10 akun, block number |
| `npx truffle version` | Truffle 5.11.5, Ganache 7.9.1, Solidity 0.8.19 |
| `npm run compile` | `Compiled successfully` |
| `npm run migrate` | `HSportStore deployed to: 0x...` |
| `node scripts/logDeployment.js` | address + network ID + tx hash tercatat |
| `npm test` | 4 passing, 6 pending |
| `mysql -u root h_sport_db -e "SHOW TABLES LIKE 'job_batches';"` | tabel paralel ada |

---

## 8. Konfigurasi MetaMask

| Field | Nilai |
|---|---|
| Network name | `Ganache Local` |
| New RPC URL | `http://127.0.0.1:7545` |
| Chain ID | `1337` |
| Currency symbol | `ETH` |
| Block explorer | kosong |

Import akun Ganache #0 (owner), #1 (buyer), #2 (operator) sesuai kebutuhan test. Jangan pernah membagikan private key.

---

## 9. Troubleshooting

| Gejala | Penyebab | Solusi |
|---|---|---|
| `Could not connect to your Ethereum client` | Ganache mati / port salah | Jalankan Ganache, cek port 7545 |
| `nonce too low` | State MetaMask tidak sinkron setelah reset | Reset akun MetaMask, redeploy |
| Address berubah | Deploy ulang / nonce berubah | Catat dengan `logDeployment.js`, update `.env` |
| ABI tidak cocok | Kontrak berubah tanpa update ABI | `npm run migrate` menulis ABI baru |
| Peringatan `µWS` | Binary native tidak cocok dengan Node 24 | Non-fatal; Ganache fallback ke Node.js |
| `config/contract_address.txt` berubah setelah test | `npx truffle test` menjalankan migration | Gunakan `npm test` (`HSPORT_TEST=1`) |

---

## 10. Bukti

| Item | Lokasi |
|---|---|
| Deployment log | `blockchain/deployments/DEPLOYMENTS.md` |
| Deployment JSON | `blockchain/deployments/deployment_*.json` |
| Log clean clone | `docs/week2/evidence/reproduction_clean_clone.log` |
| Konfigurasi Truffle | `blockchain/truffle-config.js` |
| Environment template | `.env.example` |

---

## 11. Tautan

- Reproduksi: [`03_REPRODUCTION_TEST.md`](03_REPRODUCTION_TEST.md)
- Setup guide: [`../PANDUAN_SETUP_DAN_OPERASIONAL.md`](../PANDUAN_SETUP_DAN_OPERASIONAL.md)
- Blockchain README: [`../../blockchain/README.md`](../../blockchain/README.md)
- Inventory Minggu 1: [`../week1/06_ENVIRONMENT_INVENTORY.md`](../week1/06_ENVIRONMENT_INVENTORY.md)
