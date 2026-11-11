# H-Sport Blockchain — Truffle + Ganache

Folder ini berisi smart contract escrow, konfigurasi Truffle, script deployment, dan artefak ABI/address untuk H-Sport Financial dApp.

## Stack

| Komponen | Versi / Keterangan |
|---|---|
| Solidity | `^0.8.19` |
| Truffle | `^5.11.5` |
| Web3.js | `^4.16.0` |
| Ganache | GUI atau CLI, RPC `http://127.0.0.1:7545` |
| Chain ID | `1337` |
| Network ID | `5777` (dikunci agar reproducible) |

## Prasyarat

1. Node.js 18.x atau lebih baru (diuji `24.20.0`).
2. Ganache berjalan di port `7545`.
3. Dependency sudah terpasang:

```bash
cd blockchain
npm install
```

## Menjalankan Ganache (CLI)

Gunakan perintah berikut agar chain ID, network ID, dan akun selalu sama:

```bash
cd blockchain
npx ganache --server.port 7545 --chain.chainId 1337 --chain.networkId 5777 --wallet.deterministic --wallet.totalAccounts 10
```

- Menggunakan mnemonic deterministik bawaan Ganache; jangan menulis mnemonic atau private key ke repo.
- Akun #0 = owner/deployer, akun #1 = buyer, akun #2 = operator.
- **Jangan pernah menulis private key ke repo, dokumen, atau screenshot.** Private key hanya untuk import ke MetaMask lokal.

## Perintah Utama

```bash
# Cek koneksi ke Ganache
npm run ganache:ping

# Compile smart contract
npm run compile

# Deploy ke Ganache (reset deployment)
npm run migrate

# Catat deployment ke blockchain/deployments/
node scripts/logDeployment.js

# Menjalankan test (harness + skeleton SC)
npm test
```

## Output Deployment

Setelah `npm run migrate` berhasil, script `migrations/1_deploy_hsportstore.js` akan menulis:

| File | Isi |
|---|---|
| `config/contract_address.txt` | Alamat contract hasil deploy terakhir |
| `config/contract_abi.json` | ABI contract untuk dipakai frontend/PHP service |
| `build/contracts/HSportStore.json` | Artefak lengkap Truffle |
| `deployments/DEPLOYMENTS.md` | Riwayat deploy: waktu, network ID, chain ID, address, tx hash |
| `deployments/deployment_<timestamp>.json` | Detail deployment mesin-readable |

> Setiap kali redeploy, alamat contract bisa berubah. Selalu perbarui `config/contract_address.txt` dan catat alamat baru dengan `node scripts/logDeployment.js`.

## Konfigurasi Network

`truffle-config.js` mendefinisikan dua network yang mengarah ke Ganache:

```js
networks: {
  development: { host: "127.0.0.1", port: 7545, network_id: "*" },
  ganache:     { host: "127.0.0.1", port: 7545, network_id: "*" },
}
```

Jika Ganache memakai port lain, ubah `port` pada kedua network tersebut.

## Smart Contract Saat Ini

`contracts/HSportStore.sol` mengimplementasikan:

- `createOrder(string _orderNumber, uint256 _amount)` — membuat order dan mengunci ETH di escrow.
- `shipOrder(uint256 _orderId)` — hanya owner, status harus `Paid`.
- `confirmReceived(uint256 _orderId)` — hanya buyer, status harus `Shipped`, melepas escrow ke owner.
- `getOrderStatus(uint256 _orderId)` dan `getOrderInfo(uint256 _orderId)`.
- Event: `OrderCreated`, `OrderShipped`, `OrderCompleted`, `EscrowReleased`.
- Modifier: `onlyOwner`, `onlyBuyer`, `orderExists`.

Catatan: kontrak tetap **`HSportStore.sol`** dan akan **di-extend** (bukan rewrite ke `HSportOrder`). Target interface menambahkan `externalOrderHash`, operator management, `cancelOrder`, `recordPayment`, `updateOrderStatus`, dan 8 state. Interface dibekukan akhir Minggu 3. Desain target ada di `../docs/week1/04_CONTRACT_AND_PARALLEL_DESIGN.md`.

## Alur Reproduksi dari Clean Clone

1. Jalankan Ganache dengan chain ID 1337 dan network ID 5777 (lihat bagian Menjalankan Ganache).
2. `cd blockchain && npm install`
3. `npm run ganache:ping` — pastikan RPC merespons.
4. `npm run compile`
5. `npm run migrate`
6. `node scripts/logDeployment.js` — catat address dan tx hash.
7. Pastikan `config/contract_address.txt` dan `config/contract_abi.json` terisi.
8. Jalankan aplikasi web dari Laragon, lalu hubungkan MetaMask ke network Ganache.
9. Import akun Ganache ke MetaMask untuk owner/buyer/operator.

## Troubleshooting

| Gejala | Penyebab umum | Solusi |
|---|---|---|
| `Could not connect to your Ethereum client` | Ganache belum jalan / port salah | Jalankan Ganache, cek port di `truffle-config.js` |
| `nonce too low` / transaksi gagal | State Ganache dan MetaMask tidak sinkron | Reset akun MetaMask, redeploy dengan `npm run migrate` |
| Contract address berubah | Redeploy ke Ganache | Perbarui `config/contract_address.txt`, catat di deployment log |
| ABI tidak cocok | Contract di-redeploy tanpa update ABI | Jalankan ulang migrate; ABI otomatis ditulis |
| MetaMask tidak menemukan network | Chain ID/RPC belum ditambahkan | Tambahkan network manual: RPC `http://127.0.0.1:7545`, chain ID sesuai Ganache |

## Kepemilikan

- **Blockchain Infrastructure & Deployment:** Muhammad Farrel
- **Smart Contract Core:** Arby Putra
- **Payment & State Logic:** Agustina Elsi Bunga
- **QA & Security:** Kenjiro B.H.G
