# 02 — Test Report Final (21 Test Case)

**Milestone:** M7 — Benchmark & Integration Test
**Issue terkait:** `W7-KEN-09`, `W7-KEN-11`
**Pemilik:** Kenjiro B.H.G.
**Validator:** Feeandri, Yasmin
**Tanggal eksekusi:** 13 November 2026, 03:37 WIB
**Status akhir:** **21/21 PASS (100%)**

---

## 1. Lingkungan Eksekusi

| Komponen | Nilai |
|---|---|
| OS | Windows (PHP_OS_FAMILY = Windows) |
| PHP | 8.1.10 (Win32, VS16 x64) |
| MySQL | 8.0.30, database `h_sport_db` |
| Node.js / Truffle | Node 24.20.0, Truffle 5.11.5, solc 0.8.19 |
| Ganache | CLI 7.9.1, `127.0.0.1:7545`, chainId 1337, networkId 5777, deterministic |
| Kontrak | `HSportStore` V2 di `0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab` |
| Perintah | `php tests/regression/run_all.php` |
| Durasi total | 23,08 detik |

Ganache dijalankan deterministik agar alamat kontrak dan akun stabil antar-run:

```bash
cd blockchain
npx ganache --server.port 7545 --wallet.deterministic --chain.chainId 1337 --chain.networkId 5777
npm run migrate
```

---

## 2. Hasil per Test Case

**Sumber:** [`evidence/regression_summary.json`](evidence/regression_summary.json)
· [`evidence/regression_summary.md`](evidence/regression_summary.md)

### 2.1 Smart Contract — 12/12 PASS

Suite Truffle: 4 smoke (bukan bagian 21 test) + 12 SC. Output `16 passing`.

| # | Test | Nama | Status | Bukti |
|---:|---|---|---|---|
| 1 | SC-01 | Inisialisasi contract, owner, akses owner | PASS | `evidence/sc_regression.log` |
| 2 | SC-02 | `createOrder` valid — escrow, state `Created`, event | PASS | `evidence/sc_regression.log` |
| 3 | SC-03 | `createOrder` amount tidak valid revert | PASS | `evidence/sc_regression.log` |
| 4 | SC-04 | Duplicate `externalOrderHash` revert | PASS | `evidence/sc_regression.log` |
| 5 | SC-05 | `getOrder` ID valid mengembalikan data benar | PASS | `evidence/sc_regression.log` |
| 6 | SC-06 | `getOrder` ID tidak ada revert | PASS | `evidence/sc_regression.log` |
| 7 | SC-07 | Access control menolak non-operator/buyer/owner | PASS | `evidence/sc_regression.log` |
| 8 | SC-08 | `recordPayment` valid, event, double payment ditolak | PASS | `evidence/sc_regression.log` |
| 9 | SC-09 | Transisi valid + `shipOrder` event | PASS | `evidence/sc_regression.log` |
| 10 | SC-10 | Transisi tidak sah ditolak, state tidak berubah | PASS | `evidence/sc_regression.log` |
| 11 | SC-11 | Cancel valid, refund, unauthorized, terminal, reason | PASS | `evidence/sc_regression.log` |
| 12 | SC-12 | `confirmReceived` release sekali + double release ditolak | PASS | `evidence/sc_regression.log` |

### 2.2 Integrasi — 5/5 PASS

| # | Test | Nama | Status | Detail |
|---:|---|---|---|---|
| 13 | INT-01 | Koneksi RPC backend ke Ganache | PASS | Chain ID 1337, bytecode kontrak terverifikasi |
| 14 | INT-02 | Sinkronisasi order DB lokal dengan smart contract | PASS | Order DB #35 → on-chain order #1, tx `0x61a9d441…` |
| 15 | INT-03 | Tx hash, receipt, dan status blockchain | PASS | CONFIRMED, block #2, gas 298.405 |
| 16 | INT-04 | Simpan proof (tx, block, wallet) ke DB | PASS | `orders` + `blockchain_transactions` konsisten |
| 17 | INT-05 | Verifikasi DB vs chain `VERIFIED`/`MISMATCH` | PASS | Cocok → VERIFIED; manipulasi Rp 10.500.000 → MISMATCH; restore → VERIFIED |

Bukti: [`evidence/int_regression.log`](evidence/int_regression.log)

### 2.3 Parallel & Benchmark — 4/4 PASS

| # | Test | Nama | Status | Detail |
|---:|---|---|---|---|
| 18 | PAR-01 | Sequential baseline: waktu & agregasi | PASS | 4 check; checksum `61cfe1d0…` (D500) |
| 19 | PAR-02 | Parallel partition/reduce = sequential | PASS | 18 check; P=1/2/4/8 checksum MATCH |
| 20 | PAR-03 | Throughput, speedup, efficiency | PASS | 5 check; 16 baris matrix valid |
| 21 | PAR-04 | Ketahanan konkurensi & integritas agregasi | PASS | 4 check; race, repeat P=8, checkpoint, lease |

Bukti:

- PAR-01/PAR-02: [`evidence/par01_par02_regression.log`](evidence/par01_par02_regression.log)
  (suite M6: 24/24 CHECK PASS)
- PAR-03/PAR-04: [`evidence/par03_par04_regression.log`](evidence/par03_par04_regression.log)
  (9/9 CHECK PASS) dan [`evidence/par03_par04_test.json`](evidence/par03_par04_test.json)

---

## 3. Ringkasan per Level

| Level | Jumlah | PASS | FAIL | Bukti utama |
|---|---:|---:|---:|---|
| Smart contract | 12 | 12 | 0 | `sc_regression.log` |
| Integrasi | 5 | 5 | 0 | `int_regression.log` |
| Parallel & benchmark | 4 | 4 | 0 | `par01_par02_regression.log`, `par03_par04_regression.log` |
| **Total** | **21** | **21** | **0** | `regression_summary.json` |

---

## 4. Cakupan Requirement

| Requirement | Test terkait | Status |
|---|---|---|
| REQ-01..REQ-03 | SC-02, SC-03, SC-04, INT-02 | PASS |
| REQ-04..REQ-08 | SC-07..SC-12 | PASS |
| REQ-09..REQ-11 | INT-01..INT-05 | PASS |
| REQ-12 | UAT-14 (manual) + HTTP smoke | PASS |
| REQ-13 | PAR-01, PAR-02 | PASS |
| REQ-14 | PAR-03, PAR-04 | PASS |
| REQ-15 | UAT-17 + reproducibility check | PASS (lihat catatan) |

Detail pemetaan: [`06_UAT_AND_REQUIREMENT_TO_TEST.md`](06_UAT_AND_REQUIREMENT_TO_TEST.md).

---

## 5. Bug Selama M7

| ID | Severity | Ringkasan | Status |
|---|---|---|---|
| BUG-07-01 | Sev-2 | Worker idle race → partisi tertinggal, checksum DIFFERENT | **Closed**, retest PASS |
| BUG-07-02 | Sev-3 | Regression runner salah mengutip `npm test` → SC 0/12 | **Closed**, retest PASS |
| BUG-07-03 | Sev-4 | Kredensial customer di README/panduan salah | **Closed**, login HTTP PASS |

Tidak ada bug kontrak baru. Detail root cause & retest:
[`03_BUG_REPORT_AND_RETEST.md`](03_BUG_REPORT_AND_RETEST.md).

---

## 6. Kriteria Lulus

| Kriteria | Target | Hasil |
|---|---|---|
| Seluruh test punya status | 21/21 | ✅ 21/21 PASS |
| Tidak ada bug Sev-1 terbuka | 0 | ✅ 0 |
| Bug Sev-2 tertutup dengan retest | Semua | ✅ BUG-07-01 closed |
| Regression dapat dijalankan ulang | 1 perintah | ✅ `php tests/regression/run_all.php` |
| Checksum paralel = sequential | Semua konfigurasi | ✅ 16/16 MATCH |

---

## 7. Cara Menjalankan Ulang

```bash
# Prasyarat: MySQL aktif, Ganache deterministik + kontrak terdeploy
php tests/regression/run_all.php

# Opsi parsial
php tests/regression/run_all.php --skip-sc
php tests/regression/run_all.php --skip-int
```

Output yang dihasilkan:

- `docs/week7/evidence/sc_regression.log`
- `docs/week7/evidence/int_regression.log`
- `docs/week7/evidence/par01_par02_regression.log`
- `docs/week7/evidence/par03_par04_regression.log`
- `docs/week7/evidence/regression_summary.json` + `.md`

Catatan: suite SC/INT memerlukan Ganache hidup. Bila Ganache mati, test
dilaporkan **FAIL** dengan detail eksplisit (bukan dilewati diam-diam), sesuai
prinsip "seluruh test punya status".
