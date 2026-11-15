# 02 — Release Notes & Code Freeze (`v1.0.0`)

**Milestone:** M8 — Finalisasi & Demo
**Issue terkait:** `W8-ALL-01`
**Pemilik:** seluruh tim (koordinator: Feeandri)
**Tanggal code freeze:** 16 November 2026
**Tag rilis:** **`v1.0.0`**
**Branch rilis:** `feature/W4-AGU-06-contract-v2` (branch kerja terakhir; tag menandai commit final)

---

## 1. Pernyataan Code Freeze

Sejak `v1.0.0`:

1. Tidak ada perubahan pada `blockchain/contracts/HSportStore.sol` dan ABI.
2. Tidak ada perubahan skema database selain migrasi yang sudah ada.
3. Perubahan hanya diperbolehkan untuk **bug Sev-1** dengan retest penuh dan
   penomoran patch (`v1.0.1`).
4. Alamat kontrak tetap `0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab`
   (Ganache deterministik, chainId 1337, networkId 5777).

---

## 2. Isi Rilis

### 2.1 Fitur utama

| Kategori | Fitur |
|---|---|
| Storefront | Katalog, filter kategori, detail produk, kustomisasi, keranjang, checkout |
| Pembayaran | QRIS/manual, crypto escrow via MetaMask, halaman status pembayaran |
| Blockchain | `HSportStore` V2: 8 state, escrow, operator, cancel/refund, 9 event |
| Integrasi | Simpan `tx_hash`, `block_number`, `wallet_address`, `blockchain_order_id`, proof table |
| Verifikasi | `VerificationService` (VERIFIED/MISMATCH) + tombol retry di admin |
| Parallel | Worker 1/2/4/8, partition/reduce, checkpoint, benchmark matrix |
| Admin | Dashboard, manajemen order/produk/pelanggan, laporan, performance dashboard |
| Bukti | Halaman `blockchain-proof.php` untuk pembeli dan admin |

### 2.2 Perubahan M8

| File | Perubahan |
|---|---|
| `admin/orders.php` | Kolom Verifikasi + tombol retry `POST verify_order` (REQ-11) |
| `config/database.php` | Override env `DB_*`; session start idempotent |
| `seed_demo.sql` | Seed idempotent 2 akun + 13 produk |
| `README.md` | Langkah migrasi lengkap, seed, CLI, override env |
| `.gitignore` | Pengecualian log bukti `docs/week8/evidence/*.log` |
| `docs/week8/**` | Laporan final, rilis, deployment, demo, UAT, traceability, weekly log |

### 2.3 Perbaikan bug yang ikut rilis

| ID | Severity | Status |
|---|---|---|
| BUG-07-01 | Sev-2 | Closed — worker idle race |
| BUG-07-02 | Sev-3 | Closed — quoting harness regresi |
| BUG-07-03 | Sev-4 | Closed — kredensial customer dokumen |

Tidak ada bug terbuka Sev-1/Sev-2 pada saat freeze.

---

## 3. Verifikasi Rilis

| Pemeriksaan | Perintah | Hasil |
|---|---|---|
| Sintaks PHP berubah | `php -l admin/orders.php` | No syntax errors |
| Integrasi | `php tests/integration/run_all.php` | **5/5 PASS** |
| Regresi penuh | `php tests/regression/run_all.php` | **21/21 PASS** |
| Clean install | dump + 3 migrasi + seed | **5/5 langkah exit 0** |
| HTTP clean install | 7 halaman utama | **7/7 HTTP 200** |
| UAT | 16 skenario business flow | **16/16 PASS** |
| Demo | 13 langkah customer + admin | **13/13 PASS** |

Bukti: [`evidence/`](evidence/).

---

## 4. Integritas Artefak

SHA-256 berkas kunci:

```text
23A73D09E75661576D3E42C932132C63242B33DCB0966608BDDA5CC86014606F  blockchain/config/contract_abi.json
7D5BCF4BCA48D59BF6A98CC534A4E8E8376C7590FFFA360EBC2A432298B608F9  blockchain/config/contract_address.txt
B947BB7EB907C1A681FD75CC14B392995356536CE9590AC77D5C0E102305D20A  seed_demo.sql
C141E1451962EECEBF6CB79A5538DCFBEFA0FB866D9DBA688C8D695F4B892E55  h_sport_db (1).sql
```

Kontrak: `0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab` (ABI V2).

---

## 5. Instalasi Rilis

Langkah lengkap: [`03_DEPLOYMENT_GUIDE.md`](03_DEPLOYMENT_GUIDE.md).

```bash
git clone <repo> project_jersey
cd project_jersey

mysql -u root -e "CREATE DATABASE h_sport_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root h_sport_db < "h_sport_db (1).sql"
mysql -u root h_sport_db < migration_reviews.sql
mysql -u root h_sport_db < migration_blockchain.sql
mysql -u root h_sport_db < migration_parallel.sql
mysql -u root h_sport_db < seed_demo.sql

cd blockchain && npm install
npx ganache --server.port 7545 --wallet.deterministic --chain.chainId 1337 --chain.networkId 5777
npm run migrate
```

Kredensial demo:

| Peran | Email | Password |
|---|---|---|
| Admin | `admin@hsport.com` | `admin123` |
| Customer | `customer@example.com` | `customer123` |

---

## 6. Kompatibilitas & Prasyarat

| Komponen | Versi diuji |
|---|---|
| PHP | 8.1.10 |
| MySQL | 8.0.30 |
| Node.js | 24.20.0 |
| Truffle | 5.11.5 |
| Solidity | 0.8.19 |
| Ganache CLI | 7.9.1 |
| Browser | Chrome + MetaMask |

---

## 7. Catatan Upgrade

- Dari M7: jalankan `migration_parallel.sql` bila belum; import `seed_demo.sql`
  untuk data demo; tidak ada perubahan kontrak.
- Reset Ganache → wajib `npm run migrate` lalu jalankan INT agar data chain baru
  tersedia sebelum demo.
- Database lama tetap kompatibel; kolom `verification_status` dan `verified_at`
  ditambahkan oleh `migration_blockchain.sql` (idempotent).

---

## 8. Dukungan & Troubleshooting

| Gejala | Solusi |
|---|---|
| `Connection failed` | Pastikan MySQL aktif dan `DB_NAME` benar |
| `Contract not found` | Jalankan `npm run migrate`, cek `contract_address.txt` |
| `Nonce too high` | Reset akun MetaMask (Settings → Advanced → Clear activity) |
| Verifikasi `PENDING` | Order belum punya `blockchain_order_id`/`tx_hash` |
| Verifikasi `MISMATCH` | Bandingkan `total_amount` DB dengan amount on-chain; gunakan tombol retry setelah koreksi |
| Gambar produk rusak | Pastikan folder `uploads/` ikut tersalin |
