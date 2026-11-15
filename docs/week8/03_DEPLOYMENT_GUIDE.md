# 03 — Deployment Guide & Environment

**Milestone:** M8 — Finalisasi & Demo
**Issue terkait:** `W8-FRL-12`, `W8-ALL-02`
**Pemilik:** Muhammad Farrel
**Validator:** Feeandri, Luis
**Tanggal:** 16 November 2026

Panduan ini menghasilkan instalasi bersih H-Sport Financial dApp dari repo
kosong sampai halaman web dapat diakses dan demo siap dijalankan.

---

## 1. Prasyarat

| Komponen | Versi minimum | Catatan |
|---|---|---|
| PHP | 8.1 | Ekstensi `pdo_mysql`, `curl`, `bcmath`, `mbstring` |
| MySQL / MariaDB | 8.0 / 10.4 | Diuji MySQL 8.0.30 (Laragon) |
| Node.js + npm | 18 LTS | Diuji Node 24.20.0 |
| Ganache | CLI 7.x atau GUI | Port 7545 |
| Browser | Chrome/Edge | MetaMask untuk demo crypto |
| Git | 2.x | Clone repo |

Cek cepat:

```bash
php -v
php -m | findstr /i "pdo_mysql curl bcmath mbstring"   # Windows
node -v && npm -v
```

---

## 2. Struktur Environment

```text
project_jersey/
├── config/database.php          # koneksi DB (bisa dioverride env)
├── h_sport_db (1).sql           # dump skema + data dasar
├── migration_reviews.sql        # tabel reviews
├── migration_blockchain.sql     # kolom proof/verifikasi + blockchain_transactions
├── migration_parallel.sql       # tabel job/batch/worker/checkpoint + view benchmark
├── seed_demo.sql                # akun demo + 13 produk (idempotent)
├── blockchain/                  # Truffle project + config kontrak
├── uploads/                     # gambar produk
└── docs/week8/                  # laporan & bukti final
```

### 2.1 Environment variable (opsional)

`config/database.php` memakai nilai default berikut dan dapat dioverride:

| Variable | Default | Keterangan |
|---|---|---|
| `DB_HOST` | `localhost` | Host MySQL |
| `DB_NAME` | `h_sport_db` | Nama database |
| `DB_USER` | `root` | User MySQL |
| `DB_PASS` | *(kosong)* | Password MySQL |

Contoh menjalankan server dengan database terpisah:

```powershell
# PowerShell
$env:DB_NAME="h_sport_clean"
php -S 127.0.0.1:8081 -t C:\laragon\www\project_jersey
```

```cmd
:: cmd.exe
set "DB_NAME=h_sport_clean" && php -S 127.0.0.1:8081 -t C:\laragon\www\project_jersey
```

---

## 3. Langkah Deployment

### Langkah 1 — Ambil source

```bash
git clone <repo-url> project_jersey
cd project_jersey
```

Pastikan folder `uploads/` berisi 13+ gambar produk.

### Langkah 2 — Siapkan database

**Opsi A — CLI (disarankan):**

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS h_sport_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root h_sport_db < "h_sport_db (1).sql"
mysql -u root h_sport_db < migration_reviews.sql
mysql -u root h_sport_db < migration_blockchain.sql
mysql -u root h_sport_db < migration_parallel.sql
mysql -u root h_sport_db < seed_demo.sql
```

**Opsi B — phpMyAdmin:**

1. Buat database `h_sport_db` (utf8mb4_unicode_ci).
2. Import `h_sport_db (1).sql`.
3. Import berturut-turut: `migration_reviews.sql`, `migration_blockchain.sql`,
   `migration_parallel.sql`, `seed_demo.sql`.

Semua migrasi **idempotent** dan `seed_demo.sql` aman dijalankan berulang.

### Langkah 3 — Verifikasi database

```bash
mysql -u root h_sport_db -e "SHOW TABLES;"
mysql -u root h_sport_db -e "SELECT COUNT(*) AS produk FROM products; SELECT id,email,role FROM users WHERE id IN (1,2);"
mysql -u root h_sport_db -e "SHOW COLUMNS FROM orders LIKE 'verification_status';"
```

Ekspektasi:

- Tabel utama + `reviews`, `blockchain_transactions`, `job_batches`,
  `verification_jobs`, `checkpoints`, `worker_logs`.
- View `best_selling_products`, `customer_statistics`, `daily_sales`,
  `v_benchmark_summary`.
- 13 produk; user id 1 = admin, id 2 = customer demo.
- Kolom `verification_status` enum `PENDING/VERIFIED/MISMATCH` ada.

### Langkah 4 — Install & jalankan blockchain

```bash
cd blockchain
npm install
npx ganache --server.port 7545 --wallet.deterministic --chain.chainId 1337 --chain.networkId 5777
```

Di terminal lain:

```bash
cd blockchain
npm run compile
npm run migrate
```

Verifikasi:

```bash
type config\contract_address.txt   # Windows
cat config/contract_address.txt    # bash
```

Ekspektasi: `0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab`.

> Jika memakai Ganache GUI: set RPC `127.0.0.1:7545`, Chain ID `1337`,
> Network ID `5777`, lalu `npm run migrate`. Alamat dapat berbeda bila mnemonic
> Ganache tidak deterministik; file `contract_address.txt` akan diperbarui otomatis.

### Langkah 5 — Konfigurasi MetaMask (untuk demo crypto)

1. Add network: RPC `http://127.0.0.1:7545`, Chain ID `1337`, simbol `ETH`.
2. Import private key akun Ganache (Account 0/1).
3. Pastikan saldo 100 ETH uji.
4. Jika muncul `Nonce too high`: MetaMask → Settings → Advanced → Clear activity tab data.

### Langkah 6 — Jalankan web server

```bash
php -S 127.0.0.1:8080 -t .
```

Atau letakkan folder di `C:\laragon\www\` dan akses
`http://localhost/project_jersey`.

### Langkah 7 — Login & smoke test

| Peran | URL | Email | Password |
|---|---|---|---|
| Admin | `/admin/dashboard.php` | `admin@hsport.com` | `admin123` |
| Customer | `/index.php` | `customer@example.com` | `customer123` |

Halaman yang harus terbuka: `index.php`, `products.php`, `product-detail.php?id=1`,
`cart.php`, `checkout.php`, `admin/dashboard.php`, `admin/orders.php`,
`blockchain-proof.php`.

---

## 4. Verifikasi Instalasi Bersih (bukti M8)

Pengujian dilakukan pada database baru `h_sport_clean` (bukan database kerja):

| Langkah | Hasil | Bukti |
|---|---|---|
| Import dump | exit 0 | `evidence/clean_install.log` |
| `migration_reviews.sql` | exit 0 | idem |
| `migration_blockchain.sql` | exit 0 | idem |
| `migration_parallel.sql` | exit 0 | idem |
| `seed_demo.sql` | exit 0 | idem |
| Objek database | 14 tabel, 4 view, 2 prosedur | idem |
| Seed dijalankan ulang | tetap 13 produk (idempotent) | idem |
| Gambar produk | 13/13 ditemukan | idem |
| HTTP smoke (DB bersih) | 7/7 HTTP 200 | `evidence/clean_install_http.log` |

Catatan keterbatasan: pengujian dilakukan pada satu mesin (database terpisah),
bukan komputer fisik kedua. Clean clone di lingkungan kedua sudah dibuktikan
pada M2 (`docs/week2/evidence/reproduction_second_env.log`).

---

## 5. Menjalankan Test & Demo

```bash
# Integrasi (butuh Ganache + kontrak terdeploy)
php tests/integration/run_all.php

# Regresi penuh 21 test
php tests/regression/run_all.php

# Benchmark matrix (opsional, ~105 detik)
php benchmark/run_matrix.php --sizes=100,500,1000,5000 --workers=1,2,4,8 --latency-us=5000
```

Skenario demo per peran: [`04_DEMO_PLAN_PER_ROLE.md`](04_DEMO_PLAN_PER_ROLE.md).
Bukti demo & UAT: [`05_DEMO_EVIDENCE.md`](05_DEMO_EVIDENCE.md),
[`06_UAT_EVIDENCE.md`](06_UAT_EVIDENCE.md).

---

## 6. Reset & Re-deploy

```bash
# Reset data demo (katalog & akun tetap)
mysql -u root h_sport_db < seed_demo.sql

# Reset chain (hapus semua order on-chain)
#   hentikan Ganache -> jalankan ulang -> migrate
cd blockchain
npx ganache --server.port 7545 --wallet.deterministic --chain.chainId 1337 --chain.networkId 5777
npm run migrate

# Setelah reset chain, jalankan INT untuk membuat order ber-chain baru
php tests/integration/run_all.php
```

---

## 7. Troubleshooting

| Gejala | Penyebab | Solusi |
|---|---|---|
| `Connection failed: ... Unknown database` | DB belum dibuat / `DB_NAME` salah | Buat database, cek env |
| `Access denied for user 'root'` | Password MySQL berbeda | Set `DB_PASS` |
| Halaman putih | Ekstensi PHP kurang | Aktifkan `pdo_mysql`, `curl`, `bcmath` |
| `Contract not found` / ABI kosong | Belum migrate / file config hilang | `npm run migrate`; cek `blockchain/config/` |
| `Nonce too high` | State MetaMask tidak sinkron dengan Ganache baru | Clear activity MetaMask |
| Verifikasi `PENDING` | Order belum punya data on-chain | Selesaikan pembayaran crypto / jalankan INT |
| Verifikasi `MISMATCH` | Nominal DB ≠ on-chain | Koreksi data lalu klik tombol verifikasi ulang |
| Gambar produk 404 | `uploads/` tidak tersalin | Salin folder `uploads/` |
| Seed error duplicate | Menjalankan SQL lama non-idempotent | Gunakan `seed_demo.sql` versi rilis |

---

## 8. Checklist Go-Live Demo

- [ ] MySQL aktif, database terisi (13 produk).
- [ ] Ganache deterministik aktif, kontrak terdeploy di alamat rilis.
- [ ] `contract_address.txt` & `contract_abi.json` sesuai.
- [ ] Web server aktif dan login admin/customer berhasil.
- [ ] INT 5/5 PASS (order ber-chain tersedia).
- [ ] Regresi 21/21 PASS.
- [ ] Halaman bukti blockchain menampilkan data order.
- [ ] Browser demo sudah login MetaMask ke jaringan Ganache.
- [ ] Bukti demo & UAT tersimpan di `docs/week8/evidence/`.
