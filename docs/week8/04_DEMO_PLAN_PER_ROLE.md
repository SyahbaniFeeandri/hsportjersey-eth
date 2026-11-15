# 04 — Rehearsal & Demo Plan per Peran

**Milestone:** M8 — Finalisasi & Demo
**Issue terkait:** `W8-ALL-04`, `W8-ARB-12`, `W8-AGU-12`, `W8-LUI-14`, `W8-FDR-13`, `W8-KEN-12`, `W8-IHS-09`
**Koordinator:** Feeandri
**Tanggal rehearsal:** 16 November 2026, 09.00–11.00 WIB
**Demo utama:** 16 November 2026, 13.00 WIB

---

## 1. Tujuan Demo

1. Membuktikan sistem berjalan dari **clean setup** (database + chain baru).
2. Menunjukkan alur bisnis lengkap: katalog → checkout → pembayaran → escrow →
   pengiriman → konfirmasi → bukti on-chain.
3. Menjelaskan pembagian peran dan dependency antar komponen.
4. Menunjukkan hasil kualitas: 21/21 test, benchmark paralel, verifikasi DB vs chain.

**Aturan narasi setiap peran:** `input → process → output → error → test → dependency`.

---

## 2. Jadwal Rehearsal

| Waktu | Durasi | Peran | Materi | PIC |
|---|---:|---|---|---|
| 09.00–09.10 | 10' | Persiapan | Nyalakan MySQL, Ganache, migrate, web server, cek login | Farrel |
| 09.10–09.20 | 10' | Contract core | `createOrder`, escrow, event, guard | Arby |
| 09.20–09.32 | 12' | Contract state | `recordPayment`, `shipOrder`, `cancelOrder`, `confirmReceived`, revert | Agustina |
| 09.32–09.44 | 12' | Integrasi | Checkout → chain → DB → proof → verifikasi | Luis |
| 09.44–09.54 | 10' | Parallel | Sequential vs P=1/2/4/8, speedup, checksum | Farelly |
| 09.54–10.04 | 10' | Test & benchmark | 21/21 regression, 16/16 matrix | Kenjiro |
| 10.04–10.14 | 10' | UI & dashboard | Proof page, dashboard admin, performance | Ihsanul |
| 10.14–10.26 | 12' | UAT | Business flow 16 skenario | Yasmin |
| 10.26–10.36 | 10' | Environment | Clean install & deployment guide | Farrel |
| 10.36–10.50 | 14' | Finalisasi | Traceability, weekly log, tag `v1.0.0` | Feeandri |
| 10.50–11.00 | 10' | Gladi bersih | Tanya-jawab & fallback | Semua |

---

## 3. Skenario Demo per Peran

### 3.1 Arby — Core Contract

| Item | Isi |
|---|---|
| **Input** | `externalOrderHash`, amount ETH, buyer address |
| **Process** | `createOrder()` memvalidasi amount > 0, hash unik, lalu mengunci escrow |
| **Output** | Order state `Created`, event `OrderCreated`, saldo escrow naik |
| **Error** | Amount 0 → revert; hash duplikat → revert; non-owner `addOperator` → revert |
| **Test** | SC-01..SC-06 (6/6 PASS) |
| **Dependency** | Ganache + kontrak terdeploy + ABI |
| **Slide** | 3 slide: arsitektur kontrak, state diagram, bukti test |
| **Perintah** | `cd blockchain && npx truffle test test/hsport_store.test.js` |

### 3.2 Agustina — State, Payment, Revert

| Item | Isi |
|---|---|
| **Input** | `orderId`, aksi operator/buyer |
| **Process** | `recordPayment`, `shipOrder`, `cancelOrder`, `confirmReceived` dengan validasi transisi |
| **Output** | Perubahan state sesuai aturan; escrow dirilis/refund tepat sekali |
| **Error** | Double payment, transisi tidak sah, cancel oleh pihak tak berwenang, double release |
| **Test** | SC-07..SC-12 (6/6 PASS) |
| **Dependency** | Kontrak V2 + akun operator |
| **Slide** | Tabel 8 state × aktor + matriks revert |
| **Bukti** | `docs/week7/evidence/sc_regression.log` |

### 3.3 Luis — Checkout → Chain → DB → Verification

| Item | Isi |
|---|---|
| **Input** | Checkout customer (produk, alamat, metode bayar) |
| **Process** | Simpan order DB → `createOrder` on-chain → simpan tx/block/wallet → verifikasi |
| **Output** | `blockchain_order_id`, `tx_hash`, `verification_status=VERIFIED` |
| **Error** | Manipulasi nominal → `MISMATCH`; order tanpa chain → `PENDING` |
| **Test** | INT-01..INT-05 (5/5 PASS); REQ-11 retry admin |
| **Dependency** | MySQL + Ganache + service PHP |
| **Slide** | Sequence diagram integrasi + screenshot badge verifikasi |
| **Bukti** | `docs/week8/evidence/demo_http.log`, `req11_admin_verification.log` |

### 3.4 Farelly — Sequential vs Parallel

| Item | Isi |
|---|---|
| **Input** | Dataset D100/D500/D1000/D5000 |
| **Process** | Baseline sequential vs partisi + worker 1/2/4/8 + reduce |
| **Output** | Speedup, efficiency, throughput; checksum identik |
| **Error** | Worker idle → partisi tertinggal (sudah diperbaiki, BUG-07-01) |
| **Test** | PAR-01/PAR-02 (24/24 CHECK); PAR-03/PAR-04 |
| **Dependency** | `migration_parallel.sql`, PHP CLI |
| **Slide** | Grafik speedup + tabel checksum |
| **Bukti** | `docs/week7/evidence/benchmark_matrix.md` |

### 3.5 Kenjiro — Test Result & Benchmark

| Item | Isi |
|---|---|
| **Input** | Seluruh suite test |
| **Process** | `php tests/regression/run_all.php` |
| **Output** | 21/21 PASS; 16/16 konfigurasi benchmark MATCH |
| **Error** | Ganache mati → FAIL eksplisit (tidak silent skip) |
| **Test** | SC 12 + INT 5 + PAR 4 |
| **Dependency** | MySQL, Ganache, kontrak, dataset |
| **Slide** | Ringkasan status 21 test + speedup D5000 P=8 7,318× |
| **Bukti** | `docs/week8/evidence/final_regression.log`, `final_regression_summary.json` |

### 3.6 Ihsanul — Proof Page & Dashboard

| Item | Isi |
|---|---|
| **Input** | Order ber-chain |
| **Process** | Baca DB + `getOrder()` on-chain, render bukti |
| **Output** | Halaman proof: tx hash, block, wallet, state, verifikasi |
| **Error** | Order tanpa chain → pesan "belum ada data on-chain" |
| **Test** | Demo langkah 12; UAT-15 |
| **Dependency** | INT sudah membuat order ber-chain |
| **Slide** | Screenshot dashboard & proof page |
| **Bukti** | `docs/week8/evidence/demo_http.log` |

### 3.7 Yasmin — UAT Business Flow

| Item | Isi |
|---|---|
| **Input** | 16 skenario UAT (login, katalog, cart, checkout, bayar, admin, verifikasi) |
| **Process** | Eksekusi HTTP end-to-end per peran |
| **Output** | 16/16 PASS |
| **Error** | Kredensial salah, DB kosong, chain reset → didokumentasikan |
| **Test** | UAT-01..UAT-16 |
| **Dependency** | Seluruh stack aktif |
| **Slide** | Tabel UAT + ringkasan PASS |
| **Bukti** | `docs/week8/evidence/uat_business_flow.log` |

### 3.8 Farrel — Environment & Deployment

| Item | Isi |
|---|---|
| **Input** | Repo bersih + MySQL kosong |
| **Process** | Import dump, 3 migrasi, seed, npm install, migrate |
| **Output** | 14 tabel/4 view/2 prosedur, 13 produk, kontrak terdeploy |
| **Error** | Port bentrok, DB salah, Ganache reset |
| **Test** | Clean install 5/5 + HTTP smoke 7/7 |
| **Dependency** | Node.js, MySQL, Ganache |
| **Slide** | Checklist deployment + alamat kontrak |
| **Bukti** | `docs/week8/evidence/clean_install*.log` |

### 3.9 Feeandri — Finalisasi & Traceability

| Item | Isi |
|---|---|
| **Input** | Seluruh artefak M1–M8 |
| **Process** | Konsolidasi laporan, traceability, weekly log, code freeze |
| **Output** | 112/112 issue, 15/15 requirement, tag `v1.0.0` |
| **Error** | Dokumen tidak sinkron → audit ulang GAP_ANALYSIS |
| **Test** | Verifikasi tag & checklist rilis |
| **Dependency** | Semua milestone selesai |
| **Slide** | Rekap milestone + peta requirement |
| **Bukti** | `docs/week8/01_FINAL_REPORT.md`, `07_TRACEABILITY_FINAL.md` |

---

## 4. Urutan Demo End-to-End (13 Langkah)

| # | Langkah | Peran | Bukti |
|---:|---|---|---|
| 1 | Login customer | Yasmin | HTTP 200 |
| 2 | Buka katalog & filter | Yasmin | Produk tampil |
| 3 | Tambah ke keranjang | Yasmin | `success=true` |
| 4 | Buka keranjang | Yasmin | Item & total |
| 5 | Checkout (JNE/QRIS) | Yasmin | Order baru |
| 6 | Halaman pembayaran | Yasmin | QR tampil |
| 7 | Riwayat & detail order | Yasmin | Order tampil |
| 8 | Login admin | Feeandri | Dashboard |
| 9 | Dashboard admin | Ihsanul | Ringkasan |
| 10 | Manajemen order + kolom verifikasi | Luis | Order tampil |
| 11 | Retry verifikasi DB vs chain | Luis | `VERIFIED` |
| 12 | Halaman bukti blockchain | Ihsanul | Tx/block/state |
| 13 | Laporan penjualan | Feeandri | Halaman laporan |

Bukti lengkap: [`05_DEMO_EVIDENCE.md`](05_DEMO_EVIDENCE.md).

---

## 5. Pembagian Slide (maks. 3 slide/orang)

| Slide | Isi wajib |
|---|---|
| 1 | Peran & tanggung jawab + dependency |
| 2 | Diagram/screenshot input → process → output |
| 3 | Bukti test/UAT + error handling |

Template narasi:

> "Saya [nama], peran [role]. Input saya [X]. Prosesnya [Y]. Outputnya [Z].
> Jika terjadi [error], sistem [respons]. Ini dibuktikan oleh [test/log].
> Komponen ini bergantung pada [dependency]."

---

## 6. Fallback Demo

| Risiko saat demo | Fallback |
|---|---|
| Ganache mati / chain reset | Tampilkan log INT 5/5 + bukti `demo_http.log`; jalankan `npm run migrate` |
| MetaMask gagal | Gunakan order hasil INT yang sudah ber-chain, bukan transaksi baru |
| Internet lambat (CDN) | UI tetap berfungsi; screenshot tersedia |
| MySQL mati | Tampilkan `clean_install.log` + `final_regression.log` |
| Pertanyaan angka benchmark | Buka `docs/week7/evidence/benchmark_matrix.md` |

---

## 7. Checklist Sebelum Demo

- [ ] Database terisi (13 produk) dan akun demo bisa login.
- [ ] Ganache deterministik aktif; kontrak `0xe78A…a8Ab` terdeploy.
- [ ] INT sudah dijalankan → ada order ber-chain (`VERIFIED`).
- [ ] Web server aktif di port demo.
- [ ] MetaMask di jaringan Ganache dengan saldo.
- [ ] Tab browser disiapkan: katalog, cart, admin orders, proof page, dashboard.
- [ ] Log bukti terbuka di editor untuk ditampilkan.
- [ ] Jadwal & pembagian slide dibagikan ke tim.
