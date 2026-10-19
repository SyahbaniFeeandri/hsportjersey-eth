# DOKUMENTASI PROYEK: H-SPORT FINANCIAL dAPP
**Mata Kuliah:** Financial Distributed and Parallel Computing  
**Semester:** Gasal 2026/2027  
**Durasi Proyek:** 8 Minggu  

---

## 1. Ringkasan & Deskripsi Proyek (Executive Summary)

**H-Sport Financial dApp** adalah platform *e-commerce* terdesentralisasi (decentralized application / dApp) untuk penjualan jersey olahraga yang mengintegrasikan teknologi **Distributed Ledger Technology (Ethereum Blockchain / Smart Contract)** dan **Sistem Pemrosesan Paralel (Parallel Computing)**.

### 1.1 Latar Belakang & Masalah
Pada sistem e-commerce konvensional, transaksi bergantung penuh pada pihak ketiga terpusat dan rawan sengketa:
- **Risiko Penipuan Penjual/Pembeli:** Penjual dapat menerima uang tanpa mengirim barang, atau pembeli membatalkan sepihak.
- **Integritas Data Transaksi:** Bukti pembayaran dapat dimanipulasi di basis data lokal.
- **Skalabilitas Pemrosesan Keuangan:** Saat volume transaksi tinggi, pemrosesan verifikasi bukti transaksi blockchain dan rekonsiliasi keuangan secara sekuensial (single-thread) menimbulkan bottleneck.

### 1.2 Solusi yang Dibangun
1. **Financial Distributed Computing (Smart Contract Escrow):**
   - Transaksi pembayaran menggunakan cryptocurrency (ETH) via dompet Web3 (MetaMask).
   - Dana pembeli ditahan secara aman di dalam smart contract (*escrow mechanism*) dan baru ditransfer ke penjual ketika barang telah diterima dan dikonfirmasi pembeli.
   - Status pesanan dan riwayat transaksi tercatat secara *immutable* (tidak dapat diubah) pada ledger blockchain (Ganache/Ethereum).
2. **On-Chain vs Off-Chain Architecture:**
   - **Off-Chain:** Menyimpan katalog jersey, profil pengguna, keranjang belanja, dan detail gambar di database relasional (MySQL / Backend).
   - **On-Chain:** Mengelola escrow, verifikasi saldo, penerbitan event (`OrderCreated`, `OrderShipped`, `OrderCompleted`, `EscrowReleased`), dan pencatatan state perpindahan dana.
3. **Parallel Computing & High-Throughput Processing:**
   - Memproses verifikasi batch transaksi, bukti kriptografis (*proof verification*), dan agregasi metrik keuangan menggunakan arsitektur antrean multi-worker (*Job/Queue* & *Partition/Reduce*).
   - Mengukur efisiensi sistem melalui perbandingan performa (*speedup, throughput, efficiency*) antara eksekusi sekuensial vs paralel.

---

## 2. Struktur Tim & Pembagian Peran (Role & Responsibilities)

Proyek ini dikerjakan oleh 9 orang dengan pembagian peran yang terdefinisi secara terstruktur dari perancangan hingga pengujian dan demonstrasi akhir:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                            PROJECT COORDINATION                             │
│   • Requirement, Scope & Documentation: Muhammad Feeandri Syahbani         │
│   • Business Process & Acceptance: Yasmin Akmaliyah                        │
└──────────────────────┬──────────────────────────────────────────────────────┘
                       │
         ┌─────────────┴──────────────┐
         ▼                            ▼
┌───────────────────────────┐  ┌──────────────────────────────────────────────┐
│   BLOCKCHAIN & CONTRACT   │  │           INTEGRATION & PARALLEL             │
│ • Infrastructure: Farrel  │  │ • Backend Integration: Luis                  │
│ • Solidity Core: Arby     │  │ • Database & Parallel: Farelly De Rossi      │
│ • State Logic: Agustina   │  │ • UI/UX & Proof Vis: Ihsanul Farrel          │
└─────────────┬─────────────┘  └──────────────────────┬───────────────────────┘
              │                                       │
              └───────────────────┬───────────────────┘
                                  ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         QA, SECURITY & BENCHMARK                            │
│   • Quality, Security & Performance Engineer: Kenjiro B.H.G                 │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

### Detail Deskripsi Peran & Deliverable Wajib

### 1. Muhammad Feeandri Syahbani
* **Peran (Role):** Requirement, Scope & Documentation Lead
* **Periode Keterlibatan:** Minggu 1 – 8
* **Tanggung Jawab:**
  - Bertindak sebagai pengarah ruang lingkup (scope) proyek serta koordinator dokumentasi teknis menyeluruh.
  - Memastikan seluruh kebutuhan bisnis dan arsitektur terdokumentasi dan konsisten dari awal hingga akhir.
  - Memetakan pembagian data mana yang dikelola secara *on-chain* vs *off-chain*.
* **Deliverable Wajib:**
  1. **Requirement Addendum:** Dokumen spesifikasi kebutuhan fungsional & non-fungsional sistem keuangan.
  2. **Scope Statement:** Batasan jelas fitur yang masuk (in-scope) dan di luar (out-of-scope) proyek.
  3. **On/Off-Chain Matrix:** Matriks pemetaan penyimpanan data & logika antara database relasional dan smart contract.
  4. **Traceability Matrix:** Matriks pelacakan kebutuhan mulai dari requirement, kode implementasi, hingga test case.
  5. **Final Documentation:** Laporan akhir komprehensif proyek H-Sport Financial dApp.

---

### 2. Yasmin Akmaliyah
* **Peran (Role):** Business Process & Acceptance Analyst
* **Periode Keterlibatan:** Minggu 1 – 8
* **Tanggung Jawab:**
  - Merancang dan memvalidasi alur proses bisnis pembayaran terdistribusi e-commerce.
  - Menentukan kriteria penerimaan (*acceptance criteria*) dari setiap modul sistem.
  - Merancang skenario pengujian penerimaan pengguna (*User Acceptance Testing - UAT*) dan memastikan alur UX bebas kendala (*error flow handling*).
* **Deliverable Wajib:**
  1. **Business Process Map:** Diagram alur proses bisnis pemesanan, escrow payment, pengiriman, hingga penyelesaian pesanan.
  2. **Acceptance Criteria:** Kriteria keberhasilan sistem yang terukur untuk setiap fitur.
  3. **UAT Scenario:** Skenario uji operasional pengguna akhir (pembeli, penjual, admin).
  4. **Requirement-to-Test Mapping:** Pemetaan skenario uji terhadap dokumen kebutuhan.
  5. **Review Error/UX Flow:** Analisis penanganan error pada interaksi Web3 (misal: user reject tx di MetaMask, gas fee tidak cukup, revert contract).

---

### 3. Arby Putra
* **Peran (Role):** Solidity Developer I - Order Core
* **Periode Keterlibatan:** Minggu 3 – 4 (Dukungan aktif s.d. Minggu 7)
* **Tanggung Jawab:**
  - Merancang arsitektur inti dari Smart Contract (`HSportOrder.sol` / `HSportStore.sol`).
  - Mengembangkan struktur data utama (`struct`, `enum`, `mapping`) untuk pesanan dan pembeli.
  - Mengimplementasikan kendali akses dasar (`access control`, `modifier`).
  - Menulis pengujian unit contract untuk modul order core (SC-01 s.d. SC-06).
* **Deliverable Wajib:**
  1. **HSportOrder.sol Core:** Kode smart contract modul pembuatan dan pembacaan order.
  2. **Data Structure:** Definisi `Order`, `OrderStatus`, relasi mapping id pemesanan ke alamat wallet pembeli.
  3. **Access Control Dasar:** Modifier proteksi fungsi (`onlyOwner`, `onlyBuyer`, `orderExists`).
  4. **Unit Test SC-01 s.d. SC-06:** Test script otomatis menggunakan Hardhat/Truffle untuk validasi fungsi dasar order.
  5. **Function Documentation:** Komentar & spesifikasi teknis NatSpec untuk setiap fungsi smart contract order core.

---

### 4. Agustina Elsi Bunga
* **Peran (Role):** Solidity Developer II - Payment & State Logic
* **Periode Keterlibatan:** Minggu 3 – 4 (Dukungan aktif s.d. Minggu 7)
* **Tanggung Jawab:**
  - Mengembangkan logika finansial escrow, penerimaan pembayaran cryptocurrency, dan pelepasan dana otomatis.
  - Mengatur mesin status pemesanan (*state machine*): `Created` -> `Paid` -> `Shipped` -> `Completed` / `Cancelled`.
  - Mengimplementasikan *event logging* blockchain dan aturan validasi keamanan finansial.
  - Menulis pengujian unit contract lanjutan (SC-07 s.d. SC-12).
* **Deliverable Wajib:**
  1. **Business Logic Smart Contract:** Logika pembayaran dan penguncian saldo escrow di dalam contract.
  2. **State Transition Rules:** Validasi transisi status yang ketat (mencegah manipulasi status jika belum bayar / belum dikirim).
  3. **Event Implementation:** Emisi event blockchain (`OrderCreated`, `OrderShipped`, `OrderCompleted`, `EscrowReleased`).
  4. **Operator Management:** Pengelolaan role khusus (operator/seller/admin) untuk memperbarui status pengiriman.
  5. **Unit Test SC-07 s.d. SC-12:** Test script Hardhat/Truffle untuk skenario pembayaran, refund, security revert, dan release escrow.

---

### 5. Muhammad Farrel
* **Peran (Role):** Blockchain Infrastructure & Deployment Engineer
* **Periode Keterlibatan:** Minggu 2 – 8
* **Tanggung Jawab:**
  - Mempersiapkan dan mengonfigurasi infrastruktur blockchain lokal (Ganache testnet, Hardhat node).
  - Menyediakan konfigurasi RPC, akun uji coba, faucet ETH lokal, dan integrasi MetaMask.
  - Menyusun skrip deployment otomatis smart contract dan mengelola arsip ABI serta alamat contract address antar-environment.
* **Deliverable Wajib:**
  1. **Ganache / MetaMask Setup:** Panduan dan automasi environment node blockchain lokal dengan multi-account test.
  2. **Hardhat Config:** Berkas konfigurasi network, compiler solidity, optimizer, dan gas reporter.
  3. **Deploy Script:** Skrip migrasi/deployment smart contract yang *reproducible* ke jaringan uji.
  4. **ABI / Address History:** Pencatatan versi contract artifacts (ABI) dan histori alamat contract saat dideploy ulang.

---

### 6. Luis
* **Peran (Role):** Laravel-Blockchain Integration Engineer
* **Periode Keterlibatan:** Minggu 5 – 7 (Demo Minggu 8)
* **Tanggung Jawab:**
  - Membangun jembatan komunikasi (*bridge*) antara aplikasi backend (Laravel/PHP) dengan Ethereum node.
  - Menyediakan *service* untuk membaca transaksi (*read*) dan menulis bukti ke blockchain (*write*).
  - Membaca transaction receipt dan event listener untuk verifikasi status on-chain.
  - Menyimpan bukti kriptografis transaksi (*tx_hash*, block number, wallet address) ke database.
* **Deliverable Wajib:**
  1. **README Reproducibility:** Panduan langkah demi langkah menjalankan service integrasi secara konsisten.
  2. **Blockchain Service (Read-Write):** Service class / controller untuk RPC call (Web3.php / ethers / axios API).
  3. **Receipt / Event Reader:** Modul pembaca dan parser receipt transaksi serta log event dari blockchain.
  4. **DB Proof Persistence:** Mekanisme penyimpanan bukti hash transaksi dan status pembayaran ke tabel database.
  5. **Verification Service:** Service validasi konsistensi antara data order off-chain dengan state on-chain.
  6. **INT-01 s.d. INT-05 Evidence:** Bukti eksekusi dan dokumentasi hasil uji integrasi.

---

### 7. Ihsanul Farrel
* **Peran (Role):** dApp UI/UX & Proof Visualization
* **Periode Keterlibatan:** Minggu 5 – 8
* **Tanggung Jawab:**
  - Merancang dan membangun antarmuka pengguna Web3 modern dan responsif.
  - Mengintegrasikan koneksi dompet (Connect Wallet MetaMask) ke antarmuka toko.
  - Menyajikan visualisasi bukti blockchain (*transaction hash, block explorer viewer, escrow state*).
  - Membuat dashboard verifikasi data dan dashboard metrik performa komputasi paralel.
* **Deliverable Wajib:**
  1. **Wallet UI:** Komponen antarmuka koneksi dompet Web3 (status connected, alamat terpotong, saldo ETH, switch network).
  2. **Transaction Status UI:** Indikator visual real-time saat transaksi pending, success, atau failed di blockchain.
  3. **Blockchain Proof Page:** Halaman verifikasi publik yang menampilkan bukti transaksi on-chain untuk setiap order.
  4. **Verification Dashboard:** Antarmuka admin untuk memvalidasi keabsahan data pesanan dengan blockchain.
  5. **Performance Dashboard:** Tampilan visual grafik perbandingan kecepatan pemrosesan sekuensial vs paralel.

---

### 8. Farelly De Rossi
* **Peran (Role):** Database & Parallel Processing Engineer
* **Periode Keterlibatan:** Minggu 5 – 7 (Tuning Minggu 8)
* **Tanggung Jawab:**
  - Merancang skema database pendukung pemrosesan transaksi paralel dan log audit.
  - Mengembangkan sistem antrean tugas (*Job / Queue*) dengan arsitektur multi-worker paralel.
  - Menerapkan strategi partisi data (*partition/reduce*) untuk agregasi metrik keuangan.
  - Membuat baseline pemrosesan sekuensial untuk dibandingkan dengan pemrosesan paralel.
* **Deliverable Wajib:**
  1. **Migration / Schema:** Skema tabel database untuk order, log verifikasi paralel, queue job, dan checkpoint.
  2. **Job / Queue Architecture:** Konfigurasi queue worker untuk memproses verifikasi transaksi secara asinkron.
  3. **Worker Architecture:** Desain konkurensi worker (multi-process / multi-threading worker).
  4. **Partition / Reduce:** Algoritma pemecahan workload transaksi ke beberapa partisi dan penggabungan hasil.
  5. **Sequential + Parallel Implementation:** Kode implementasi pemrosesan versi sekuensial vs paralel.
  6. **PAR-01 / PAR-02 Evidence:** Bukti eksekusi pengujian pemrosesan paralel dan pembuktian non-blocking.

---

### 9. Kenjiro B.H.G
* **Peran (Role):** QA, Security & Performance Engineer
* **Periode Keterlibatan:** Minggu 3 – 8
* **Tanggung Jawab:**
  - Bertanggung jawab atas pengujian mutu menyeluruh (Quality Assurance) perangkat lunak.
  - Melakukan audit keamanan smart contract (*security/revert test*, reentrancy check, modifier enforcement).
  - Menguji integrasi end-to-end antara Web3, backend, dan database.
  - Mengukur performa komputasi paralel (*benchmark, speedup, throughput, efficiency*).
  - Mengelola *bug report* dan mengumpulkan bukti pelaksanaan UAT.
* **Deliverable Wajib:**
  1. **Smart Contract Test Report:** Laporan hasil eksekusi unit test SC-01 s.d. SC-12 beserta coverage.
  2. **Integration Test Report:** Laporan hasil pengujian integrasi INT-01 s.d. INT-05.
  3. **Security / Revert Test:** Bukti pengujian keamanan terhadap akses terlarang dan kondisi ekstrim transaksi.
  4. **Parallel Benchmark Report:** Laporan benchmark komputasi dengan metrik speedup, throughput, dan efisiensi grafis.
  5. **Bug Report & UAT Evidence:** Catatan pelacakan isu bug serta bukti dokumentasi pengujian penerimaan pengguna.

---

## 3. Rencana Milestone 8 Minggu

| Minggu | Milestone | Lead | Pendukung Utama | Deliverable Kunci |
|---|---|---|---|---|
| **1** | **Analisis & Scope Freeze** | Feeandri + Yasmin | Farelly, Arby, Agustina | Requirement addendum, scope statement, on/off-chain matrix, state diagram, parallel workload design, Git repository setup. |
| **2** | **Blockchain Environment** | Muhammad Farrel | Arby, Agustina, Luis | Ganache node setup, MetaMask network config, Hardhat framework config, RPC endpoints, initial test deployment, contract address mapping. |
| **3** | **Smart Contract V1** | Arby Putra | Agustina, Farrel, Kenjiro | Core data structures (`enum`, `struct`, `mapping`, `modifier`), fungsi `createOrder`/`getOrder`, ABI export, unit test awal (SC-01 s.d SC-06). |
| **4** | **Smart Contract V2** | Agustina Elsi Bunga | Arby, Farrel, Kenjiro | Business logic payment escrow, transisi status order, refund/cancel, operator authorization, event emission, security revert test (SC-07 s.d SC-12). |
| **5** | **Laravel-Blockchain Integration** | Luis | Farrel, Ihsanul, Yasmin | Integrasi READ+WRITE ke RPC node, wallet address binding, transaction hash & receipt verification, persistensi bukti transaksi ke DB (INT-01 s.d INT-05). |
| **6** | **Parallel Processing** | Farelly De Rossi | Sahrul, Ihsanul, Kenjiro | Arsitektur Job/Queue, implementasi pemrosesan sekuensial baseline, arsitektur multi-worker paralel, algoritma verifikasi partition/reduce (PAR-01 s.d PAR-04). |
| **7** | **Benchmark & Integration Test** | Kenjiro B.H.G | Farelly, Ihsanul, Feeandri | Pengujian performa beban tinggi, analisis metrik speedup/efficiency/throughput, visualisasi grafik performa, pelaporan bug, verifikasi UAT lengkap. |
| **8** | **Finalisasi & Demo** | Semua Anggota | Semua Anggota | Reproducible repository, dokumentasi README & final report, rehearsal presentasi, dan demonstrasi sistem end-to-end terintegrasi. |

---

## 4. Matriks 21 Test Case Wajib Proyek

Sistem diwajibkan lolos 21 skenario pengujian terstandarisasi yang dibagi ke dalam 3 kelompok utama:

### Kelompok A: Smart Contract Unit & Security Tests (12 Test Cases)
* **SC-01:** Verifikasi inisialisasi contract dan penentuan owner address.
* **SC-02:** Pembuatan pesanan baru (`createOrder`) dengan nilai transfer ETH yang valid.
* **SC-03:** Validasi penolakan pembuatan pesanan jika nilai ETH yang dikirim 0 atau tidak sesuai tagihan.
* **SC-04:** Pembacaan data pesanan (`getOrderInfo`) dengan id pesanan yang valid.
* **SC-05:** Validasi penolakan pemanggilan query terhadap ID pesanan yang tidak terdaftar (`orderExists`).
* **SC-06:** Pengecekan modifier proteksi hak akses dasar (`onlyBuyer`, `onlyOwner`).
* **SC-07:** Pengujian pembaruan status pengiriman oleh penjual/operator (`shipOrder`).
* **SC-08:** Validasi larangan pengiriman barang jika status order belum lunas (`Paid`).
* **SC-09:** Konfirmasi penerimaan barang oleh pembeli (`confirmReceived`) dan verifikasi pelepasan dana escrow ke penjual.
* **SC-10:** Validasi larangan pelepasan escrow ganda (`Already released`).
* **SC-11:** Verifikasi emisi Event lengkap (`OrderCreated`, `OrderShipped`, `OrderCompleted`, `EscrowReleased`).
* **SC-12:** Uji ketahanan keamanan terhadap eksekusi transaksi dari pihak luar tanpa izin (Unauthorized Access Revert).

### Kelompok B: Integration Tests Backend & Blockchain (5 Test Cases)
* **INT-01:** Pengujian koneksi RPC dari service backend ke node blockchain lokal.
* **INT-02:** Sinkronisasi pembuatan order di database lokal dengan eksekusi pembuatan order di smart contract.
* **INT-03:** Pengambilan transaction hash dan receipt secara asinkron dari blockchain.
* **INT-04:** Penyimpanan bukti kriptografis (tx hash, block number, wallet address) ke database secara persisten.
* **INT-05:** Verifikasi konsistensi data antara status pesanan di database dengan state di smart contract.

### Kelompok C: Parallel Computing & Benchmark Tests (4 Test Cases)
* **PAR-01:** Eksekusi verifikasi transaksi batch secara sekuensial untuk mencatat baseline waktu eksekusi.
* **PAR-02:** Eksekusi verifikasi transaksi batch menggunakan multi-worker paralel dengan workload seimbang (*partition/reduce*).
* **PAR-03:** Pengukuran throughput (transaksi per detik / TPS) dan percepatan (*Speedup = T_sequential / T_parallel*).
* **PAR-04:** Pengujian ketahanan konkurensi (mencegah *race condition*, duplikasi eksekusi, dan integritas data agregasi).

---

## 5. Ringkasan Teknis On-Chain vs Off-Chain

| Komponen | On-Chain (Ethereum Smart Contract) | Off-Chain (Laravel/PHP & MySQL Database) |
|---|---|---|
| **Penyimpanan Data** | Order ID, Wallet Buyer, Total Amount (Wei), Status Order, Timestamp, Payment Escrow State. | Katalog produk, foto jersey, detail spesifikasi, alamat pengiriman fisik, data kontak, cart, audit log. |
| **Logika Bisnis** | Kunci dana pembeli, validasi nilai pembayaran, transfer dana ke penjual saat konfirmasi selesai, emisi event. | Checkout belanja, manajemen inventaris stok, antrean verifikasi paralel, tampilan UI/UX, dashboard reporting. |
| **Keamanan & Verifikasi** | Kriptografi kunci privat, tanda tangan digital MetaMask, data transaksi tidak dapat diubah (immutable). | Enkripsi database, autentikasi session user, CSRF token, hashing password. |
