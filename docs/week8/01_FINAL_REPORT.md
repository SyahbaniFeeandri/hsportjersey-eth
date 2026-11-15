# 01 — Laporan Final Proyek

**Proyek:** H-Sport Financial dApp — E-Commerce Jersey Berbasis Ethereum Smart Contract & Parallel Computing
**Milestone akhir:** M8 — Finalisasi & Demo
**Issue terkait:** `W8-FEE-13`, `W8-ALL-03`
**Pemilik:** Muhammad Feeandri Syahbani
**Validator:** Yasmin Akmaliyah, Kenjiro B.H.G.
**Tanggal:** 16 November 2026
**Status akhir:** **SELESAI — 112/112 issue, 15/15 requirement, 21/21 test PASS, tag `v1.0.0`**

---

## 1. Ringkasan Eksekutif

H-Sport Financial dApp adalah platform e-commerce jersey olahraga yang
menggabungkan aplikasi web PHP/MySQL dengan smart contract escrow Ethereum.
Pembayaran pembeli dikunci di kontrak, bukti transaksi disimpan permanen
on-chain dan direplikasi ke database relasional, lalu diverifikasi ulang
(`VERIFIED`/`MISMATCH`). Pipeline verifikasi massal dijalankan paralel dengan
hasil yang identik dengan baseline sequential.

Seluruh 8 milestone (112 issue) selesai. Pada M8 proyek dibekukan (code freeze),
diuji ulang (regresi 21/21), direproduksi dari instalasi bersih, didemokan per
peran, dan diberi tag rilis `v1.0.0`.

| Aspek | Target | Hasil akhir |
|---|---|---|
| Requirement fungsional | 15 | **15 Selesai** |
| Issue roadmap | 112 | **112 Selesai** |
| Test wajib | 21 | **21/21 PASS** |
| Benchmark matrix | 16 konfigurasi | **16/16 PASS** (checksum MATCH) |
| UAT business flow | ≥ 12 skenario | **16/16 PASS** |
| Clean install | 1 kali dari nol | **PASS** (5/5 langkah, 7 halaman HTTP) |
| Bug Sev-1/Sev-2 terbuka | 0 | **0** |
| Code freeze + tag | 1 tag rilis | **`v1.0.0`** |

---

## 2. Tujuan dan Cakupan

### 2.1 Tujuan

1. Menyediakan escrow terdesentralisasi: dana pembeli aman sampai barang diterima.
2. Menyimpan bukti pembayaran yang tidak dapat diubah (tx hash, block, wallet).
3. Menjaga konsistensi data DB ↔ blockchain melalui verifikasi otomatis.
4. Mempercepat verifikasi massal dengan pemrosesan paralel tanpa mengubah hasil.
5. Menyediakan dashboard admin, halaman bukti pembeli, dan laporan penjualan.

### 2.2 Cakupan final

| Area | Cakupan |
|---|---|
| Off-chain | Katalog, keranjang, checkout, order, pembayaran manual/QRIS, ulasan, admin panel, laporan |
| On-chain | `HSportStore` V2: 8 state, escrow, operator, cancel/refund, event audit |
| Integrasi | PHP ↔ JSON-RPC, penyimpanan proof, verifikasi DB vs chain, worker paralel |
| Kualitas | 21 test (SC/INT/PAR), benchmark 16 konfigurasi, reproducibility check, UAT |
| Finalisasi | Clean install, seed demo, deployment guide, demo per peran, code freeze `v1.0.0` |

Di luar cakupan: pembayaran mainnet, KYC, pengiriman fisik nyata, dan deployment
cloud multi-node.

---

## 3. Arsitektur Singkat

```text
Customer ──► PHP UI (catalog/cart/checkout) ──► MySQL (orders, proof)
                    │
                    └──► MetaMask ──► Ganache ──► HSportStore (escrow)
                                              │
Admin ◄── dashboard/verification ◄── PHP service ◄── JSON-RPC
                    │
                    └──► worker paralel ──► hasil verifikasi + checksum
```

Komponen kunci:

| Komponen | Lokasi |
|---|---|
| Smart contract | `blockchain/contracts/HSportStore.sol` |
| Kontrak terdeploy | `0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab` |
| Integration service | `api/services/ContractService.php`, `VerificationService.php` |
| Worker paralel | `workers/verify_worker.php`, `benchmark/parallel_runner.php` |
| Dashboard admin | `admin/dashboard.php`, `admin/orders.php`, `admin/performance.php` |
| Bukti pembeli | `blockchain-proof.php` |
| Migrasi & seed | `migration_blockchain.sql`, `migration_parallel.sql`, `migration_reviews.sql`, `seed_demo.sql` |

---

## 4. Hasil per Milestone

| Minggu | Fokus | Issue | Hasil kunci |
|---|---|---:|---|
| M1 | Analisis & scope freeze | 17/17 | Requirement, test plan 21, arsitektur, governance |
| M2 | Environment blockchain | 16/16 | Ganache, Truffle, deploy script, reproduksi 2 mesin |
| M3 | Smart contract V1 | 12/12 | SC-01..SC-06 PASS, kontrak V1 terdeploy |
| M4 | Smart contract V2 | 12/12 | 8 state, cancel/refund, SC-07..SC-12 PASS |
| M5 | Integrasi PHP–blockchain | 15/15 | INT-01..05 PASS, proof page, verification service |
| M6 | Parallel processing | 13/13 | PAR-01/02 24/24 CHECK, worker 1/2/4/8 |
| M7 | Benchmark & regression | 13/13 | 16/16 matrix, 21/21 regresi, reproducibility PASS |
| M8 | Finalisasi & demo | 14/14 | Clean install, UAT 16/16, demo, tag `v1.0.0` |

Total: **112/112 issue selesai**.

---

## 5. Hasil Pengujian Final

### 5.1 Regression 21 test

| Suite | Jumlah | PASS | Bukti |
|---|---:|---:|---|
| Smart contract (SC-01..SC-12) | 12 | 12 | `docs/week7/evidence/sc_regression.log` |
| Integrasi (INT-01..INT-05) | 5 | 5 | `docs/week8/evidence/final_int_suite.log` |
| Parallel (PAR-01..PAR-04) | 4 | 4 | `docs/week7/evidence/par01_par02_regression.log`, `par03_par04_regression.log` |
| **Total** | **21** | **21** | `docs/week8/evidence/final_regression.log` + `final_regression_summary.json` |

Eksekusi final: `php tests/regression/run_all.php` → **21/21 PASS**, durasi 47,19 detik.

### 5.2 Benchmark (M7, dipakai ulang pada demo)

| Dataset | Baseline | P=1 | P=2 | P=4 | P=8 |
|---|---:|---:|---:|---:|---:|
| D100 | 529 ms | 0,841× | 1,374× | 1,781× | 1,734× |
| D500 | 2.647 ms | 0,971× | 1,849× | 3,240× | 4,668× |
| D1000 | 5.271 ms | 0,981× | 1,911× | 3,531× | 5,748× |
| D5000 | 26.387 ms | 0,993× | 1,972× | 3,882× | **7,318×** |

- Throughput D5000 P=8: **1.386,58 order/detik**; efficiency **91,5%**.
- Seluruh 16 konfigurasi menghasilkan checksum identik dengan baseline (`MATCH`).

### 5.3 Clean install & UAT

| Uji | Hasil | Bukti |
|---|---|---|
| Import dump + 3 migrasi + seed | 5/5 langkah exit 0 | `evidence/clean_install.log` |
| Objek DB | 14 tabel, 4 view, 2 prosedur | `evidence/clean_install.log` |
| Seed idempotent | 13 produk & 3 user tetap setelah re-run | `evidence/clean_install.log` |
| HTTP smoke dari DB bersih | 7/7 halaman HTTP 200 | `evidence/clean_install_http.log` |
| UAT business flow | **16/16 PASS** | `evidence/uat_business_flow.log` |
| Demo end-to-end | **13/13 langkah PASS** | `evidence/demo_http.log` |

### 5.4 Verifikasi admin (REQ-11)

Alur diuji melalui UI admin pada order ber-chain:

```text
Data cocok        → VERIFIED
Total dimanipulasi → MISMATCH (1 perbedaan)
Data direstore    → VERIFIED
```

Bukti: `evidence/req11_admin_verification.log`.

---

## 6. Requirement Final

| REQ | Ringkasan | Status | Bukti utama |
|---|---|---|---|
| REQ-01 | Checkout DB + on-chain terhubung | ✅ Selesai | INT-02 |
| REQ-02 | Validasi amount & lock escrow | ✅ Selesai | SC-02, SC-03 |
| REQ-03 | Tolak hash duplikat | ✅ Selesai | SC-04 |
| REQ-04 | Operator & double payment | ✅ Selesai | SC-07, SC-08 |
| REQ-05 | 8 state & transisi valid | ✅ Selesai | SC-09, SC-10 |
| REQ-06 | Cancel/refund berwenang | ✅ Selesai | SC-11 |
| REQ-07 | Release escrow tepat sekali | ✅ Selesai | SC-12 |
| REQ-08 | Event audit lengkap | ✅ Selesai | SC-02/08/09/11/12 |
| REQ-09 | Proof transaksi lengkap | ✅ Selesai | INT-01/03/04 |
| REQ-10 | Verifikasi DB vs chain | ✅ Selesai | INT-05 |
| REQ-11 | Admin lihat status + retry verifikasi | ✅ Selesai | `admin/orders.php`, `req11_admin_verification.log` |
| REQ-12 | Halaman proof pembeli | ✅ Selesai | `blockchain-proof.php`, demo langkah 12 |
| REQ-13 | Parallel partition/reduce = sequential | ✅ Selesai | PAR-01, PAR-02 |
| REQ-14 | Throughput/speedup/efficiency | ✅ Selesai | PAR-03, PAR-04 |
| REQ-15 | Reproducible + kontribusi tertelusur | ✅ Selesai | clean install, weekly log, tag `v1.0.0` |

Detail pemetaan: [`07_TRACEABILITY_FINAL.md`](07_TRACEABILITY_FINAL.md).

---

## 7. Risiko, Keterbatasan, dan Mitigasi

| # | Risiko / keterbatasan | Dampak | Mitigasi |
|---|---|---|---|
| 1 | Clean install diuji pada satu mesin (bukan komputer fisik kedua) | Generalisasi lingkungan terbatas | Seluruh langkah SQL/CLI terdokumentasi; M2 sudah membuktikan clean clone di lingkungan Debian WSL2 |
| 2 | Ganache reset menghapus state kontrak | Order lama tidak lagi punya pasangan on-chain | Selalu jalankan `npm run migrate` lalu INT sebelum demo; alamat kontrak deterministik |
| 3 | MetaMask & pembayaran crypto bersifat manual | Demo bergantung interaksi browser | Skenario demo menyiapkan akun Ganache & jaringan lebih dulu; fallback bukti INT otomatis |
| 4 | Kurs ETH→IDR tetap 10.000.000 | Verifikasi amount tidak real-time | Didokumentasikan sebagai asumsi demo; dapat diganti di `VerificationService` |
| 5 | Data demo bergantung pada `uploads/` | Gambar produk hilang bila folder tidak ikut | Clean install memverifikasi 13/13 file gambar ada |
| 6 | Tidak ada CI/CD | Regresi dijalankan manual | Satu perintah `php tests/regression/run_all.php`; hasil JSON tersimpan |

---

## 8. Kontribusi Tim

| Anggota | Peran | Kontribusi utama M8 |
|---|---|---|
| Muhammad Feeandri Syahbani | Requirement & Documentation Lead | Laporan final, traceability, weekly log, code freeze |
| Yasmin Akmaliyah | QA / UAT Lead | UAT business flow 16/16, verifikasi requirement |
| Arby Putra | Smart Contract Engineer | Demo core contract, verifikasi SC-01..SC-06 |
| Agustina Elsi Bunga | Smart Contract Engineer | Demo state/payment/revert, SC-07..SC-12 |
| Muhammad Farrel | Blockchain Environment Engineer | Deployment guide, reproduksi environment |
| Luis | Integration Engineer | Demo checkout→chain→DB→verification, REQ-11 retry |
| Ihsanul Farrel | UI/UX & Dashboard | Demo proof page & dashboard |
| Farelly De Rossi | Parallel Processing Engineer | Demo sequential vs parallel |
| Kenjiro B.H.G. | Test & Benchmark Lead | Regresi 21/21, benchmark 16/16 |

Rekap log mingguan: [`08_WEEKLY_LOG.md`](08_WEEKLY_LOG.md).

---

## 9. Kesimpulan

1. Seluruh requirement **Must** terpenuhi dan memiliki bukti test/UAT.
2. Sistem dapat dibangun dari nol hanya dengan dump, migrasi, dan seed yang tersedia di repo.
3. Konsistensi DB ↔ blockchain terbukti, termasuk deteksi manipulasi (`MISMATCH`).
4. Pemrosesan paralel memberikan speedup hingga **7,318×** tanpa mengubah hasil.
5. Proyek siap didemokan dan diserahkan sebagai rilis **`v1.0.0`**.

---

## 10. Referensi

- [`README.md`](README.md) — indeks M8
- [`02_RELEASE_NOTES_CODE_FREEZE.md`](02_RELEASE_NOTES_CODE_FREEZE.md) — isi rilis
- [`03_DEPLOYMENT_GUIDE.md`](03_DEPLOYMENT_GUIDE.md) — panduan deployment
- [`05_DEMO_EVIDENCE.md`](05_DEMO_EVIDENCE.md) — bukti demo
- [`06_UAT_EVIDENCE.md`](06_UAT_EVIDENCE.md) — bukti UAT
- [`07_TRACEABILITY_FINAL.md`](07_TRACEABILITY_FINAL.md) — traceability final
- [`../week7/02_TEST_REPORT_FINAL.md`](../week7/02_TEST_REPORT_FINAL.md) — laporan test M7
- [`../week7/01_BENCHMARK_REPORT.md`](../week7/01_BENCHMARK_REPORT.md) — laporan benchmark
- [`../GAP_ANALYSIS.md`](../GAP_ANALYSIS.md) — status 112 issue
