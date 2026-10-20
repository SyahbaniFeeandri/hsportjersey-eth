# PANDUAN LENGKAP SETUP & OPERASIONAL SISTEM H-SPORT FINANCIAL dAPP

Dokumen ini disusun sebagai panduan teknis operasional bagi pengembang (*developer*), penguji (*QA/tester*), dan pengguna (*evaluator*) untuk menginstalasi, mengonfigurasi, dan mengoperasikan sistem **H-Sport Financial dApp** secara menyeluruh (*end-to-end*).

---

## DAFTAR ISI
1. [Prasyarat Perangkat Lunak & Lingkungan](#1-prasyarat-perangkat-lunak--lingkungan)
2. [Langkah 1: Setup Web Server & Basis Data (Laragon/XAMPP)](#langkah-1-setup-web-server--basis-data-laragonxampp)
3. [Langkah 2: Menjalankan Blockchain Lokal (Ganache)](#langkah-2-menjalankan-blockchain-lokal-ganache)
4. [Langkah 3: Kompilasi & Deployment Smart Contract](#langkah-3-kompilasi--deployment-smart-contract)
5. [Langkah 4: Konfigurasi Dompet Kripto (MetaMask)](#langkah-4-konfigurasi-dompet-kripto-metamask)
6. [Langkah 5: Validasi & Akses Sistem Toko & Admin](#langkah-5-validasi--akses-sistem-toko--admin)
7. [Skenario Pengujian Alur Transaksi Escrow (End-to-End Walkthrough)](#skenario-pengujian-alur-transaksi-escrow-end-to-end-walkthrough)
8. [Struktur Basis Data & Penjelasan Tabel](#struktur-basis-data--penjelasan-tabel)
9. [Smart Contract Interface & NatSpec Reference](#smart-contract-interface--natspec-reference)
10. [Panduan Pemecahan Masalah (Troubleshooting Guide)](#panduan-pemecahan-masalah-troubleshooting-guide)

---

## 1. Prasyarat Perangkat Lunak & Lingkungan

Sebelum memulai, pastikan lingkungan kerja komputer lokal memenuhi spesifikasi berikut:

* **Sistem Operasi:** Windows 10/11, macOS, atau Linux Ubuntu 20.04+.
* **Web Server Stack:** Laragon (Sangat direkomendasikan untuk Windows) atau XAMPP.
  - PHP Versi: `8.0` atau `8.2+` (dengan ekstensi `pdo_mysql`, `mbstring`, `json`, `curl` aktif).
  - Basis Data: MySQL 5.7+ atau MariaDB 10.4+.
* **Runtime & Package Manager:**
  - Node.js versi `18.x LTS` atau `20.x LTS`.
  - npm versi `9.x` atau `10.x`.
* **Blockchain Testnet & Framework:**
  - **Truffle Ganache GUI** (unduh di https://trufflesuite.com/ganache/) atau `ganache-cli`.
  - Truffle CLI (`^5.11.5`) — telah terpasang di folder `blockchain/node_modules`.
* **Web3 Client:**
  - Browser Google Chrome, Brave, atau Microsoft Edge.
  - Ekstensi browser **MetaMask** terpasang.

---

## Langkah 1: Setup Web Server & Basis Data (Laragon/XAMPP)

1. Letakkan folder proyek pada direktori web root:
   - Jika menggunakan **Laragon:** `c:\laragon\www\project_jersey`
   - Jika menggunakan **XAMPP:** `c:\xampp\htdocs\project_jersey`
2. Jalankan service **Apache** dan **MySQL** dari kontrol panel Laragon/XAMPP.
3. Buka peramban dan akses **phpMyAdmin** di alamat `http://localhost/phpmyadmin`.
4. Buat basis data baru bernama `h_sport_db` dengan *collation* `utf8mb4_unicode_ci`.
5. Masuk ke database `h_sport_db`, klik menu **Import**, pilih file:
   - `h_sport_db (1).sql` (berisi skema tabel utama dan katalog jersey olahraga).
   - Klik **Go** / **Import**.
6. Masuk kembali ke tab **Import** atau tab **SQL**, jalankan file:
   - `migration_reviews.sql` (berisi tabel ulasan dan rating produk).
7. Periksa file konfigurasi koneksi di `config/database.php`. Pastikan parameter sesuai:
   ```php
   $host = 'localhost';
   $dbname = 'h_sport_db';
   $username = 'root';
   $password = ''; // Default Laragon & XAMPP adalah kosong
   ```

---

## Langkah 2: Menjalankan Blockchain Lokal (Ganache)

1. Buka aplikasi **Ganache**.
2. Klik tombol **QUICKSTART (Ethereum)**.
3. Pastikan konfigurasi server Ganache sebagai berikut:
   - **RPC Server:** `HTTP://127.0.0.1:7545`
   - **Port:** `7545`
   - **Network ID:** `5777` (atau diset otomatis)
4. Ganache akan menyediakan 10 akun uji coba, masing-masing memiliki saldo awal sebesar **100.00 ETH**.
5. **Catatan:** Jangan tutup aplikasi Ganache selama Anda menguji transaksi pembayaran kripto.

---

## Langkah 3: Kompilasi & Deployment Smart Contract

Pada direktori proyek terdapat folder `blockchain/` yang berisi kode Solidity dan skrip otomatis Truffle.

1. Buka terminal (Command Prompt, PowerShell, atau Git Bash).
2. Pindah ke direktori `blockchain`:
   ```powershell
   cd c:\laragon\www\project_jersey\blockchain
   ```
3. Jika folder `node_modules` belum ada, instal dependensi yang dibutuhkan:
   ```powershell
   npm install
   ```
4. Uji koneksi ke Ganache dengan menjalankan skrip pemeriksa:
   ```powershell
   npm run ganache:ping
   ```
   *Output yang diharapkan:*
   ```text
   Ganache connected: http://127.0.0.1:7545
   Chain ID: 1337 (atau 5777)
   Block number: 0
   Accounts: 10
   First account: 0x...
   ```
5. Kompilasi dan sebarkan (*deploy*) kontrak cerdas ke Ganache:
   ```powershell
   npm run compile
   npm run migrate
   ```
   Skrip migrasi `1_deploy_hsportstore.js` akan otomatis:
   - Menyebarkan kontrak `HSportStore.sol`.
   - Mengambil alamat kontrak hasil *deploy* dan menyimpannya ke `blockchain/config/contract_address.txt`.
   - Menyimpan *Application Binary Interface* (ABI) ke `blockchain/config/contract_abi.json`.
6. Pastikan kedua file di `blockchain/config/` telah terisi dengan data yang valid.

---

## Langkah 4: Konfigurasi Dompet Kripto (MetaMask)

1. Buka peramban Google Chrome dan buka ekstensi **MetaMask**.
2. Buat atau buka dompet yang sudah ada.
3. **Menambahkan Jaringan Ganache:**
   - Klik menu pilihan jaringan di sudut kiri atas MetaMask.
   - Klik **Add network** ➔ **Add a network manually**.
   - Masukkan informasi jaringan:
     - **Network name:** `Ganache Local`
     - **New RPC URL:** `http://127.0.0.1:7545`
     - **Chain ID:** `1337` (atau sesuaikan dengan Chain ID Ganache Anda, misal `5777`)
     - **Currency symbol:** `ETH`
   - Klik **Save**, lalu beralih ke jaringan tersebut.
4. **Mengimpor Akun Ganache:**
   - Di aplikasi Ganache, pilih akun nomor 1 (Account 1 atau Account 2 untuk pembeli). Klik ikon kunci 🔑 di sebelah kanan.
   - Salin **Private Key**.
   - Di MetaMask, klik ikon lingkaran akun/avatar di kanan atas ➔ **Add account or hardware wallet** ➔ **Import account**.
   - Tempelkan Private Key yang disalin, lalu klik **Import**.
   - Akun Anda sekarang memiliki saldo **100 ETH**.

---

## Langkah 5: Validasi & Akses Sistem Toko & Admin

Sistem kini siap diakses melalui peramban:

* **Halaman Toko (Customer Portal):**
  `http://localhost/project_jersey/index.php`
* **Halaman Masuk (Login):**
  `http://localhost/project_jersey/login.php`
* **Halaman Dashboard Admin:**
  `http://localhost/project_jersey/admin/dashboard.php`

### Akun Bawaan (Default Credentials):
| Hak Akses (Role) | Email | Kata Sandi (Password) | Keterangan |
|---|---|---|---|
| **Administrator** | `admin@hsport.com` | `admin123` | Akses penuh dashboard admin, kelola status pesanan, input no resi, kelola katalog produk. |
| **Customer** | `customer@example.com` | `password` | Akun demo pelanggan dengan riwayat pesanan. |
| **Pendaftaran Baru** | Bebas via `register.php` | Bebas | Pelanggan baru dapat mendaftar langsung. |

---

## Skenario Pengujian Alur Transaksi Escrow (End-to-End Walkthrough)

Untuk memverifikasi integrasi *off-chain* dan *on-chain*, jalankan skenario pengujian berikut:

### 1. Pembelian Jersey oleh Pelanggan
1. Masuk sebagai pelanggan (`customer@example.com` atau akun baru).
2. Pilih produk jersey di katalog `products.php`.
3. Tentukan ukuran (misal: `L`), warna, dan custom tulisan punggung jika diinginkan.
4. Klik **Tambah ke Keranjang** ➔ Buka **Keranjang** (`cart.php`).
5. Klik **Checkout** (`checkout.php`).
6. Masukkan alamat pengiriman lengkap dan pilih kurir (JNE / J&T).
7. Konfirmasi pemesanan. Pesanan akan tercatat di database dengan status `pending` dan `unpaid`.

### 2. Pembayaran Menggunakan Ethereum (Smart Contract Escrow)
1. Buka halaman pesanan saya di `orders.php`.
2. Klik tombol **Bayar Crypto** berwarna kuning/emas.
3. Anda masuk ke portal Web3 `crypto-payment.php`:
   - Nilai pesanan dikonversi secara otomatis dari IDR ke ETH (rate simulasi: 1 ETH = Rp 10.000.000).
   - Klik tombol **Hubungkan Dompet**. MetaMask akan meminta otorisasi koneksi akun.
   - Klik tombol **Bayar Sekarang (Kirim ETH ke Escrow)**.
   - Jendela popup MetaMask akan terbuka menampilkan detail nilai transfer ETH + gas fee.
   - Klik **Confirm** pada MetaMask.
4. Tunggu beberapa detik hingga transaksi terkonfirmasi pada blockchain:
   - Kontrak `HSportStore` memproses fungsi `createOrder(orderNumber, amount)`.
   - ETH masuk dan terkunci di dalam saldo smart contract (*Escrow*).
   - Backend memanggil `api/blockchain-api.php` secara otomatis untuk mencatat:
     - `payment_type = 'crypto'`
     - `payment_status = 'paid'`
     - `status = 'paid'`
     - `tx_hash` (Transaction Hash)
     - `wallet_address` (Alamat dompet pembeli).

### 3. Pemrosesan & Pengiriman Barang oleh Admin
1. Buka jendela peramban lain / mode samaran, lalu masuk sebagai Admin (`admin@hsport.com`).
2. Masuk ke menu **Pesanan** (`admin/orders.php`) dan pilih pesanan yang baru saja dibayar.
3. Pada halaman detail pesanan (`admin/order-detail.php`), Admin dapat memverifikasi:
   - Bukti *Transaction Hash* (Tx Hash) dan alamat dompet pembeli.
4. Pada bagian form **Update Status, Nomor Resi & Blockchain Receipt**:
   - Ubah Status Pesanan menjadi **Dikirim (Shipped)**.
   - Masukkan Nomor Resi Pengiriman (misal: `JNE-ID-2026102001`).
   - Klik **Simpan Perubahan**.

### 4. Konfirmasi Penerimaan Barang & Pencairan Dana Escrow Otomatis
1. Kembali ke akun Pelanggan di `orders.php`.
2. Status pesanan kini berada pada fase **Sedang Dikirim**.
3. Di bawah diagram pelacakan, muncul tombol hijau:  
   **"Konfirmasi Barang Telah Diterima"**.
4. Pelanggan mengklik tombol tersebut:
   - Peramban memicu panggilan Web3 ke Smart Contract: `contract.methods.confirmReceived(orderId)`.
   - MetaMask terbuka meminta persetujuan eksekusi transaksi dari akun pembeli.
   - Klik **Confirm**.
5. Smart contract menjalankan:
   ```solidity
   escrows[_orderId].released = true;
   payable(owner).transfer(escrows[_orderId].amount);
   ```
   Saldo ETH yang ditahan di smart contract langsung dicairkan dan ditransfer ke dompet pemilik toko (*owner*).
6. Sistem memancarkan efek konfeti selebrasi di layar pengguna, dan status pesanan di database resmi berubah menjadi **Completed (Selesai)**.

---

## Struktur Basis Data & Penjelasan Tabel

Basis data `h_sport_db` terdiri dari entitas-entitas berikut:

1. **`users`**: Menyimpan kredensial pengguna (admin/customer), email, username, dan hash password.
2. **`products`**: Menyimpan katalog jersey, nama, kategori, harga satuan, stok, gambar, dan deskripsi.
3. **`orders`**: Menyimpan rekaman transaksi utama:
   - `order_number`: Kode unik pesanan (misal: `INV-20261020-1234`).
   - `total_amount`: Total nominal pesanan dalam rupiah.
   - `status`: Mesin status (`pending`, `paid`, `production`, `shipped`, `completed`, `cancelled`).
   - `payment_status`: Status pembayaran (`unpaid`, `paid`).
   - `payment_type`: Metode pembayaran (`crypto`, `qris`, `transfer`).
   - `tx_hash`: Hash transaksi bukti pembayaran pada blockchain Ethereum.
   - `wallet_address`: Alamat akun dompet Ethereum pembeli.
   - `block_number`: Nomor blok pada ledger blockchain tempat transaksi tercatat.
4. **`order_items`**: Menyimpan rincian item per pesanan (produk, kuantitas, ukuran, warna, custom teks).
5. **`payments`**: Log pembayaran manual/QRIS dengan bukti transfer gambar.
6. **`reviews`**: Menyimpan rating (1–5) dan testimoni pelanggan untuk produk yang telah selesai dibeli.
7. **`cart`**: Menyimpan keranjang belanja sementara sebelum checkout.
8. **`custom_designs`**: Menyimpan data rancangan jersey kustom hasil visualisasi canvas pengguna.
9. **`activity_logs`**: Menyimpan jejak audit aktivitas sistem dan alamat IP.

---

## Smart Contract Interface & NatSpec Reference

Berkas: `blockchain/contracts/HSportStore.sol`  
Kompiler: Solidity `^0.8.19`

### Fungsi-Fungsi Kunci:
* `createOrder(string memory _orderNumber, uint256 _amount) external payable returns (uint256)`
  - Menerima transfer ETH senilai tagihan (`msg.value == _amount`).
  - Mengunci dana ke struct `PaymentEscrow`.
  - Mengemisi event `OrderCreated`.
* `shipOrder(uint256 _orderId) external onlyOwner orderExists(_orderId)`
  - Hanya dapat dipanggil oleh pemilik kontrak (`owner`).
  - Memvalidasi bahwa pesanan sudah berstatus `Paid`.
  - Mengubah status ke `Shipped` dan mengemisi `OrderShipped`.
* `confirmReceived(uint256 _orderId) external onlyBuyer(_orderId) orderExists(_orderId)`
  - Hanya dapat dipanggil oleh pembeli asli dari pesanan tersebut (`onlyBuyer`).
  - Memverifikasi status pesanan adalah `Shipped` dan dana belum pernah dicairkan.
  - Mentransfer dana escrow ke akun `owner` penjual (`payable(owner).transfer(...)`).
  - Mengubah status ke `Completed` dan mengemisi `OrderCompleted` serta `EscrowReleased`.
* `getOrderStatus(uint256 _orderId) external view returns (OrderStatus)`
  - Mengembalikan status terkini pesanan pada on-chain.
* `getOrderInfo(uint256 _orderId) external view returns (Order memory)`
  - Mengembalikan detail lengkap pesanan on-chain.

---

## Panduan Pemecahan Masalah (Troubleshooting Guide)

| Gejala Kendala | Kemungkinan Penyebab | Langkah Solusi |
|---|---|---|
| **MetaMask: "Nonce too high"** | Ganache pernah direstart tetapi history transaksi di MetaMask belum dibersihkan. | Di MetaMask: Buka **Settings** ➔ **Advanced** ➔ Klik **Clear activity tab data** (atau **Reset account**). |
| **MetaMask: "Internal JSON-RPC error"** | Gas fee tidak cukup, atau kondisi `require()` pada Smart Contract gagal (revert). | Periksa console log browser (F12) untuk melihat pesan revert contract, dan pastikan saldo ETH akun pengirim mencukupi. |
| **"Contract not found" di crypto-payment.php** | Alamat smart contract belum terupdate atau file `contract_address.txt` kosong. | Jalankan `npm run migrate` di direktori `blockchain/` saat Ganache aktif. Periksa isi file `blockchain/config/contract_address.txt`. |
| **Error Database: "Connection failed"** | Layanan MySQL di Laragon/XAMPP belum aktif atau username/password tidak cocok. | Buka kontrol panel Laragon/XAMPP, klik Start pada MySQL. Periksa kredensial di `config/database.php`. |
| **Toko Tidak Bisa Dibuka (404 Not Found)** | Path folder di web server tidak sesuai. | Pastikan folder berada tepat di `c:\laragon\www\project_jersey` dan diakses melalui `http://localhost/project_jersey`. |

---

*Dokumen disusun oleh Tim Pengembang H-Sport Financial dApp.*  
*Hak Cipta © 2026 H-Sport Jersey.*
