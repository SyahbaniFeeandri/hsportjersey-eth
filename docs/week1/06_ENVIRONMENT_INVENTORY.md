# 06 — ENVIRONMENT INVENTORY & BASELINE

**Milestone:** Minggu 1 — Analisis & Scope Freeze
**Issue terkait:** `W1-FRL-01`
**Pemilik:** Muhammad Farrel
**Reviewer:** Luis, Kenjiro
**Status:** Baseline v1.0
**Tanggal audit:** 9 November 2026

---

## 1. Ringkasan

Dokumen ini mencatat environment yang benar-benar ada saat audit. Tujuannya agar benchmark dan demo dapat direproduksi, dan agar gap environment tidak tersembunyi.

**Kesimpulan singkat:**

- Tooling blockchain **Truffle 5.11.5 + Ganache 7.9.1** tersedia.
- Kontrak `HSportStore` sudah pernah dideploy; address dan ABI tersimpan.
- **Ganache tidak berjalan** saat audit (port 7545 tertutup).
- **MySQL tidak berjalan** saat audit (port 3306 tertutup).
- **PHP 8.1.10 tidak ada di PATH**; harus memakai path Laragon.
- **Composer tidak terpasang**, sehingga integrasi sebaiknya memakai JSON-RPC via cURL (ekstensi tersedia).
- **Tidak ada folder `blockchain/test/`**, `.env.example`, atau deployment log.

> **Update 9 November 2026 (setelah audit):** MySQL 8.0.30 sudah dinyalakan dan kedua migration berhasil dijalankan. Ganache 7.9.1 sudah berjalan di `127.0.0.1:7545` dengan **chainId 1337** dan **networkId 5777**. Kontrak `HSportStore` dideploy ulang di `0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab`, dan deployment log sudah dibuat. `.env.example` sudah ditambahkan. Lihat bagian 11.1 untuk bukti.

---

## 2. Workstation Baseline (untuk Benchmark)

| Komponen | Nilai |
|---|---|
| OS | Microsoft Windows 11 Pro 10.0.26200, 64-bit |
| CPU | Intel Core i7-8565U @ 1.80 GHz |
| Core / logical | 4 core / 8 logical |
| RAM | 15,8 GB |
| Disk bebas C: | 11,7 GB |
| Shell | PowerShell |

> **Catatan benchmark:** spesifikasi ini harus dicatat di setiap run. Jika benchmark dijalankan di mesin lain, hasil tidak boleh dicampur tanpa label.

---

## 3. Software Inventory

| Software | Versi | Path / Sumber | Status |
|---|---|---|---|
| PHP CLI | 8.1.10 (ZTS, VS16 x64) | `C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe` | ✅ Ada, ❌ tidak di PATH |
| MySQL client | 8.0.30 | `C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe` | ✅ Ada |
| MySQL server | 8.0.x (Laragon) | localhost:3306 | ❌ Tidak berjalan saat audit |
| Node.js | v24.20.0 | PATH | ✅ |
| npm | 11.19.0 | PATH | ✅ |
| Git | 2.55.0.windows.5 | PATH | ✅ |
| Truffle | 5.11.5 | `blockchain/node_modules/.bin/truffle` | ✅ |
| Ganache | 7.9.1 | bundled Truffle | ✅ Terpasang, ❌ tidak berjalan |
| Solidity compiler | 0.8.19 (solc-js) | Truffle | ✅ |
| Web3.js (Truffle) | 1.10.0 | Truffle internal | ✅ |
| Web3.js (npm) | ^4.16.0 | `blockchain/package.json` | ✅ |
| Web3.js (browser) | 4.2.2 | CDN `cdnjs` di halaman PHP | ⚠️ Beda versi |
| Composer | — | tidak ditemukan | ❌ Tidak ada |
| MetaMask | — | ekstensi browser | ⚠️ Tidak dapat diverifikasi dari CLI |

### 3.1 Ekstensi PHP yang relevan

| Ekstensi | Status | Dipakai untuk |
|---|---|---|
| `curl` | ✅ | JSON-RPC ke Ganache |
| `openssl` | ✅ | Hashing/enkripsi |
| `pdo_mysql` | ✅ | Database |
| `mysqli` | ✅ | Database (legacy) |
| `json` | ✅ | API/JSON-RPC |
| `mbstring` | ✅ | String UTF-8 |

> Karena `curl` + `json` + `openssl` tersedia, integrasi backend **tidak membutuhkan Composer**. Rencana: `BlockchainClient` memakai cURL JSON-RPC langsung. Jika nanti butuh ABI encoding/decoding yang kompleks, evaluasi library PHP di Minggu 5.

---

## 4. Blockchain Inventory

| Item | Nilai | Catatan |
|---|---|---|
| RPC host | `127.0.0.1` | `truffle-config.js` |
| RPC port | `7545` | development & ganache |
| Network ID | `*` | Belum dikunci |
| Chain ID | **Belum terverifikasi** | Ganache tidak berjalan saat audit |
| Contract | `HSportStore.sol` | 4 state, escrow dasar |
| Contract address | `0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab` | `blockchain/config/contract_address.txt` (deploy 9 Nov 2026) |
| ABI | `blockchain/config/contract_abi.json` (6.208 byte) | Sesuai kontrak 4 state |
| Build artifacts | `blockchain/build/contracts/` | Di-ignore Git |
| Migration | `blockchain/migrations/1_deploy_hsportstore.js` | Deploy + simpan address/ABI |
| Check script | `blockchain/scripts/checkGanache.js` | Ping Ganache |
| Test folder | — | ❌ Belum ada |
| Deployment log | — | ❌ Belum ada |
| `.env.example` | — | ❌ Belum ada |

### 4.1 Perintah verifikasi

```powershell
# Ganache harus berjalan lebih dulu
cd blockchain
npm run ganache:ping
npm run compile
npm run migrate

# Cek versi
npx truffle version
```

### 4.2 Address yang tercatat

```text
0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab
```

> Address ini adalah hasil deploy deterministik pada 9 November 2026 (Ganache `chainId 1337`, `networkId 5777`). Karena Ganache lokal bisa reset, address dapat berubah. Deployment log ada di `blockchain/deployments/DEPLOYMENTS.md`.

---

## 5. Database Inventory

| Item | Nilai | Status |
|---|---|---|
| Database | `h_sport_db` | Diasumsikan ada |
| Host | `localhost:3306` | ✅ Berjalan (8.0.30) |
| User | `root` (lokal) | — |
| Dump awal | `h_sport_db (1).sql` | ⚠️ Belum memuat kolom proof |
| Migration blockchain | `migration_blockchain.sql` | ✅ Sudah dijalankan |
| Migration paralel | `migration_parallel.sql` | ✅ Sudah dijalankan |
| Tabel proof | `blockchain_transactions` | ✅ Ada |
| Tabel paralel | `job_batches`, `verification_jobs`, `checkpoints`, `worker_logs` | ✅ Ada + view `v_benchmark_summary` |

### 5.1 Aksi yang harus dilakukan

1. ~~Nyalakan MySQL Laragon.~~ **Selesai.**
2. ~~Jalankan `migration_blockchain.sql`.~~ **Selesai.**
3. ~~Jalankan `migration_parallel.sql`.~~ **Selesai.**
4. ~~Verifikasi dengan query di bagian bawah masing-masing file.~~ **Selesai.**
5. Catat hasil di deployment log. ✅

---

## 6. Aplikasi Web Inventory

| Area | File | Status |
|---|---|---|
| Katalog | `index.php`, `products.php`, `product-detail.php` | ✅ |
| Cart/checkout | `cart.php`, `checkout.php` | ✅ |
| Pembayaran QRIS | `generate-qr.php`, `verify-payment.php`, `upload-payment-proof.php` | ✅ |
| Pembayaran kripto | `crypto-payment.php` | ✅ client-side |
| Order pembeli | `orders.php`, `order-detail.php` | ✅ |
| Admin | `admin/*.php` | ✅ |
| API blockchain | `api/blockchain-api.php` | ⚠️ hanya `save_crypto_order` |
| Config | `config/database.php`, `config/functions.php` | ✅ |
| Blockchain config | `blockchain/config/contract_address.txt`, `contract_abi.json` | ✅ |

---

## 7. Status Layanan Saat Audit

| Layanan | Port | Status | Dampak |
|---|---:|---|---|
| Ganache | 7545 | ✅ Berjalan (chainId 1337, networkId 5777) | Deploy/test/integrasi live bisa dilakukan |
| MySQL | 3306 | ✅ Berjalan (8.0.30) | Migration sudah dijalankan |
| PHP built-in server | 8000 | ❌ Tidak berjalan | Web belum diuji live (Laragon Apache dapat dipakai) |
| Node/npm | — | ✅ | Bisa compile dan test |
| Truffle | — | ✅ | `truffle test` berjalan; smoke 4 PASS |

---

## 8. Gap Environment

| # | Gap | Risiko | Aksi | Owner | Target |
|---|---|---|---|---|---|
| E01 | PHP tidak di PATH | Perintah `php` gagal | Tambahkan ke PATH atau pakai path absolut | Farrel | M2 |
| E02 | Ganache tidak berjalan | Tidak bisa test/deploy | Jalankan Ganache GUI/CLI sebelum kerja | Farrel | M2 |
| E03 | MySQL tidak berjalan | Migration gagal | ✅ MySQL 8.0.30 aktif; kedua migration sudah dijalankan | Farrel | M1–M2 |
| E04 | Chain ID belum dicatat | MetaMask salah network | ✅ Tercatat: chain ID 1337, network ID 5777 | Farrel | M2 |
| E05 | Tidak ada `test/` | 0/21 test | ✅ Harness ada; baseline smoke 4 PASS; SC-01..SC-06 masih pending | Arby | M2–M3 |
| E06 | Tidak ada `.env.example` | Setup tidak reproducible | ✅ `.env.example` sudah ditambahkan | Farrel | M2 |
| E07 | Tidak ada deployment log | Address/ABI tidak terlacak | ✅ `blockchain/deployments/DEPLOYMENTS.md` | Farrel | M2 |
| E08 | Web3 browser 4.2.2 vs npm 4.16.0 | API bisa berbeda | Kunci versi atau update CDN | Ihsanul/Luis | M2 |
| E09 | Composer tidak ada | Library PHP tidak bisa dipasang | Pakai cURL JSON-RPC | Luis | M5 |
| E10 | Contract address bisa berubah saat reset | Proof lama invalid | Snapshot + deployment log + dataset | Farrel | M2 |
| E11 | Tidak ada CI | Regresi tidak terdeteksi | Test manual + log; CI opsional | Kenjiro | M3 |

---

## 9. Environment Variables Template (tanpa secret)

Disarankan dibuat `.env.example`:

```dotenv
# Database
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=h_sport_db
DB_USER=root
DB_PASS=

# Blockchain
RPC_URL=http://127.0.0.1:7545
CHAIN_ID=1337
CONTRACT_ADDRESS=0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab
CONTRACT_ABI_PATH=blockchain/config/contract_abi.json

# Operator (HANYA untuk Ganache lokal; jangan commit .env)
OPERATOR_ADDRESS=
OPERATOR_PRIVATE_KEY=

# Worker
WORKER_COUNT=4
JOB_TIMEOUT_SECONDS=300
BENCHMARK_SEED=HSPORT-2026
```

> **Aturan:** `.env` sudah masuk `.gitignore`. Private key tidak boleh ditulis di dokumen, screenshot, atau commit.

---

## 10. Checklist Reproducibility (M2)

- [ ] PHP path terdokumentasi dan bisa dipanggil.
- [x] MySQL berjalan dan database `h_sport_db` ada.
- [x] `migration_blockchain.sql` dijalankan tanpa error.
- [x] `migration_parallel.sql` dijalankan tanpa error.
- [x] Ganache berjalan di port 7545.
- [x] Chain ID dan network ID dicatat.
- [x] `npm install` berhasil di `blockchain/`.
- [x] `npm run compile` berhasil.
- [x] `npm run migrate` berhasil dan address/ABI diperbarui.
- [x] `npm run ganache:ping` berhasil.
- [ ] Anggota lain dapat mengulang langkah dari README ≤ 30 menit.

---

## 11. Bukti Audit

| Perintah | Hasil |
|---|---|
| `npx truffle version` | Truffle v5.11.5, Ganache v7.9.1, Solidity 0.8.19, Node v24.20.0, Web3.js 1.10.0 |
| `node -v` | v24.20.0 |
| `npm -v` | 11.19.0 |
| `git --version` | 2.55.0.windows.5 |
| `php -v` (path absolut) | PHP 8.1.10 (cli), ZTS, VS16 x64 |
| `Test-NetConnection 127.0.0.1:7545` | `False` |
| `mysql --version` | 8.0.30 |
| MySQL connect | Error 2003: tidak dapat terhubung |
| `composer` | tidak ditemukan |
| `php -m` | `curl`, `openssl`, `pdo_mysql`, `mysqli`, `json`, `mbstring` tersedia |

---

## 11.1 Verifikasi Ulang Setelah Audit (9 November 2026)

| Perintah / Aksi | Hasil | Status |
|---|---|---|
| `mysqld --defaults-file=...` | MySQL 8.0.30 aktif di `127.0.0.1:3306` | ✅ |
| `mysql -u root h_sport_db -e "source migration_blockchain.sql"` | Kolom proof + `blockchain_transactions` terbuat | ✅ |
| `mysql -u root h_sport_db -e "source migration_parallel.sql"` | 4 tabel + view `v_benchmark_summary` terbuat | ✅ |
| `npm run ganache:ping` | Chain ID 1337, 10 akun, RPC 7545 | ✅ |
| `npx truffle migrate --network ganache --reset` | Deploy sukses, address `0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab` | ✅ |
| `node scripts/logDeployment.js` | `blockchain/deployments/DEPLOYMENTS.md` + JSON log | ✅ |
| `.env.example` | Template DB, RPC, chain ID, operator, worker | ✅ |
| Test harness `blockchain/test/` | Ada: baseline smoke 4 PASS, skeleton SC-01..SC-06 6 pending | ✅ |
| 21 test wajib dijalankan | 0/21 PASS | ❌ |

**Catatan network:** Ganache dijalankan dengan `--chain.chainId 1337 --chain.networkId 5777 --wallet.deterministic` agar alamat dan network ID stabil setiap reset. Perintah lengkap ada di `blockchain/README.md`.

---

## 12. Tautan

- Setup guide: [`../PANDUAN_SETUP_DAN_OPERASIONAL.md`](../PANDUAN_SETUP_DAN_OPERASIONAL.md)
- Blockchain README: [`../../blockchain/README.md`](../../blockchain/README.md)
- Migration blockchain: [`../../migration_blockchain.sql`](../../migration_blockchain.sql)
- Migration paralel: [`../../migration_parallel.sql`](../../migration_parallel.sql)
- Desain integrasi: [`05_INTEGRATION_AND_UI_DESIGN.md`](05_INTEGRATION_AND_UI_DESIGN.md)
