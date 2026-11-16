# 04 — DUKUNGAN RPC/NETWORK DAN TROUBLESHOOTING

**Issue:** `W5-FRL-09`
**Pemilik:** Muhammad Farrel
**Status:** ✅ Selesai
**Tanggal:** 12 November 2026

---

## 1. Konfigurasi Acuan

| Parameter | Nilai |
|---|---|
| RPC URL | `http://127.0.0.1:7545` (override: `RPC_URL`) |
| Chain ID | 1337 (`0x539`) |
| Network ID | 5777 |
| Kontrak | `blockchain/config/contract_address.txt` |
| ABI | `blockchain/config/contract_abi.json` |
| Akun | Ganache deterministik; akun #0 = owner/operator, #1 = pembeli uji |
| Kurs | 1 ETH = 10.000.000 IDR (1 IDR = 1e11 wei) |

---

## 2. Gejala & Solusi

### 2.1 `Koneksi RPC gagal`
**Gejala:** API `health` mengembalikan `rpc_ok=false`, halaman bukti menampilkan
“state on-chain tidak dapat dibaca”.

**Cek:**
```bash
curl -s -X POST http://127.0.0.1:7545 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"eth_chainId","params":[]}'
```
**Solusi:** jalankan Ganache (`npx ganache --port 7545 --chain.chainId 1337 --chain.networkId 5777 --wallet.deterministic`).

### 2.2 `Chain ID` bukan 1337
MetaMask mungkin terhubung ke jaringan lain. UI sudah meminta switch otomatis;
bila gagal, tambahkan jaringan manual dengan RPC di atas dan chain ID `1337`.

### 2.3 `contract_deployed=false`
Bytecode kosong berarti address di config tidak ada di chain saat ini — biasanya
karena Ganache di-reset.

**Solusi:**
```bash
cd blockchain
npx truffle migrate --network ganache --reset
node scripts/logDeployment.js
```
Migration menulis ulang `config/contract_address.txt` dan ABI.

### 2.4 Transaksi `createOrder` revert / gas terlalu rendah
Ganache dapat memberi estimasi gas default yang terlalu kecil untuk transaksi
ber-`value`. Backend kini mengirim `gas` eksplisit (500.000). Untuk transaksi
lain, gunakan estimasi + margin 20%.

### 2.5 `Duplicate entry` pada `orders.order_number`
Order uji lama tertinggal. Test integrasi sekarang menghapus `INV-INT-%` dan
`INV-TEST-001` sebelum eksekusi. Untuk data nyata, pastikan `order_number`
dibuat unik oleh checkout.

### 2.6 `bcmath` / `curl` tidak aktif
`ContractService` membutuhkan `bcmath`, sedangkan RPC membutuhkan `curl`.
Cek dengan `php -m`. Pada Laragon keduanya aktif secara default.

### 2.7 MySQL tidak bisa dihubungi
Jalankan MySQL Laragon atau:
```powershell
Start-Process "C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqld.exe" `
  -ArgumentList "--defaults-file=C:\laragon\bin\mysql\mysql-8.0.30-winx64\my.ini"
```

### 2.8 Nonce / transaksi tertahan
Ganache deterministik mereset nonce setiap restart. Kirim ulang transaksi baru
setelah node siap; jangan memakai tx hash dari sesi chain sebelumnya.

---

## 3. Checklist Sebelum Demo

1. `php api/blockchain-api.php` action `health` → `rpc_ok=true`, `chain_id=1337`,
   `contract_deployed=true`.
2. `php tests/integration/run_all.php` → 5/5 PASS.
3. `php workers/verify_worker.php --dry-run` → konfigurasi queue terbaca.
4. MetaMask memakai akun Ganache dan jaringan 1337.
5. `blockchain/config/contract_address.txt` sama dengan address hasil deploy
   terakhir (`blockchain/deployments/DEPLOYMENTS.md`).

---

## 4. Log Dukungan

| Tanggal | Masalah | Tindakan | Status |
|---|---|---|---|
| 12 Nov 2026 | Ganache/MySQL mati saat mulai M5 | Menyalakan mysqld + Ganache deterministik | Selesai |
| 12 Nov 2026 | Kontrak historis tidak ada setelah reset | `truffle migrate --reset` + `logDeployment.js` | Selesai |
| 12 Nov 2026 | `createOrder` revert gas 90k | Kirim gas eksplisit 500k | Selesai |
| 12 Nov 2026 | INT-05 duplikat `INV-TEST-001` | Isolasi data uji + nomor unik | Selesai |
