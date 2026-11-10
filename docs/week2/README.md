# MINGGU 2 — BLOCKCHAIN ENVIRONMENT

**Status:** Berjalan — 15/16 issue selesai, 1 sebagian
**Tanggal:** 10 November 2026
**Lead:** Muhammad Farrel
**Pendukung:** Arby, Agustina, Luis, Kenjiro, Ihsanul, Fadhil

> **Catatan status:** seluruh deliverable Minggu 2 sudah punya artefak. Satu issue, `W2-FRL-05`, baru diuji lewat **simulasi clean clone** di mesin yang sama; uji di komputer anggota lain masih menunggu. Karena itu exit gate "clean clone di mesin anggota lain" belum ditutup 100%.

---

## 1. Daftar Artefak

| # | File | Isi | Issue |
|---|---|---|---|
| 00 | [`00_ENVIRONMENT_BASELINE.md`](00_ENVIRONMENT_BASELINE.md) | Baseline Ganache, akun, RPC, address, ABI hash, aturan snapshot/reset | `W2-FRL-01`, `W2-FRL-04` |
| 01 | [`01_UI_WIREFRAMES.md`](01_UI_WIREFRAMES.md) | Wireframe wallet, tx status, proof page, verification & performance dashboard | `W2-IHS-02` |
| 02 | [`02_CONTRACT_SPEC_FINAL.md`](02_CONTRACT_SPEC_FINAL.md) | Spesifikasi final enum, struct, mapping, modifier, event, fungsi | `W2-ARB-02`, `W2-AGU-02`, `W2-AGU-03` |
| 03 | [`03_REPRODUCTION_TEST.md`](03_REPRODUCTION_TEST.md) | Prosedur + hasil simulasi clean clone, perbandingan ABI/address | `W2-FRL-03`, `W2-FRL-05` |
| — | [`../templates/TEST_REPORT_TEMPLATE.md`](../templates/TEST_REPORT_TEMPLATE.md) | Template laporan test | `W2-KEN-02` |
| — | [`../templates/BUG_REPORT_TEMPLATE.md`](../templates/BUG_REPORT_TEMPLATE.md) | Template bug report | `W2-KEN-02` |
| — | [`../templates/BENCHMARK_SHEET.csv`](../templates/BENCHMARK_SHEET.csv) | Template lembar benchmark | `W2-KEN-02` |
| — | [`evidence/reproduction_clean_clone.log`](evidence/reproduction_clean_clone.log) | Log mentah npm ci → compile → migrate → test | `W2-FRL-05` |

---

## 2. Status per Issue

| PIC | Issue | Status | Bukti |
|---|---|---|---|
| FRL | `W2-FRL-01` Baseline Ganache | ✅ Selesai | `00_ENVIRONMENT_BASELINE.md` |
| FRL | `W2-FRL-02` Panduan MetaMask | ✅ Selesai | `../PANDUAN_SETUP_DAN_OPERASIONAL.md` Langkah 4 |
| FRL | `W2-FRL-03` Truffle config + harness + README | ✅ Selesai | `blockchain/test/`, `blockchain/README.md` |
| FRL | `W2-FRL-04` Deploy script + log + `.env.example` | ✅ Selesai | `blockchain/scripts/logDeployment.js`, `blockchain/deployments/` |
| FRL | `W2-FRL-05` Uji reproduksi mesin lain | ⚠️ Sebagian | Clean clone berhasil; mesin anggota lain belum |
| ARB | `W2-ARB-02` Finalkan struct/enum/mapping/modifier | ✅ Selesai | `02_CONTRACT_SPEC_FINAL.md` |
| ARB | `W2-ARB-03` Skeleton unit test SC-01–SC-06 | ✅ Selesai | `blockchain/test/01_sc01_sc06.skeleton.test.js` |
| AGU | `W2-AGU-02` Transition table + event list | ✅ Selesai | `02_CONTRACT_SPEC_FINAL.md` + `../week1/01` |
| AGU | `W2-AGU-03` Revert rules matrix | ✅ Selesai | `../week1/01_ON_OFF_CHAIN_AND_STATE.md` bagian 5 |
| LUI | `W2-LUI-02` Desain service read/write/receipt/verification | ✅ Selesai | `../week1/05_INTEGRATION_AND_UI_DESIGN.md` bagian B |
| LUI | `W2-LUI-03` Field DB proof + index | ✅ Selesai | `migration_blockchain.sql` (sudah dijalankan) |
| FDR | `W2-FDR-03` Schema queue/verification + index | ✅ Selesai | `migration_parallel.sql` (sudah dijalankan) |
| FEE | `W2-FEE-06` Traceability dengan 21 test | ✅ Selesai | `../week1/03_TRACEABILITY_MATRIX.md` |
| YAS | `W2-YAS-05` Requirement-to-test mapping | ✅ Selesai | `../week1/02` bagian 6 + `../week1/03` |
| IHS | `W2-IHS-02` Wireframe UI | ✅ Selesai | `01_UI_WIREFRAMES.md` |
| KEN | `W2-KEN-02` Template test/bug/benchmark | ✅ Selesai | `../templates/` |

---

## 3. Exit Gate Minggu 2

| # | Kriteria | Status | Bukti |
|---|---|---|---|
| 1 | Ganache berjalan dan parameter terkunci | ✅ | chain ID 1337, network ID 5777, RPC 7545 |
| 2 | Chain ID, network ID, akun, address tercatat | ✅ | `00_ENVIRONMENT_BASELINE.md` |
| 3 | `npm ci` / `npm install` berhasil dari clean clone | ✅ | `evidence/reproduction_clean_clone.log` |
| 4 | `npm run compile` berhasil | ✅ | log yang sama |
| 5 | `npm run migrate` berhasil | ✅ | log yang sama; address clean clone `0x5b18...` |
| 6 | ABI konsisten antara repo utama dan clean clone | ✅ | SHA-256 ABI identik |
| 7 | `npm test` berjalan di clean clone | ✅ | 4 passing, 6 pending |
| 8 | Uji di **komputer anggota lain** | ⚠️ | Belum; perlu dijadwalkan |
| 9 | `.env.example` tersedia | ✅ | root repo |
| 10 | Deployment log tersedia | ✅ | `blockchain/deployments/DEPLOYMENTS.md` |

**Kesimpulan:** environment sudah reproducible di mesin yang sama dan ABI/address konsisten. Exit gate tinggal menunggu uji di komputer anggota lain.

---

## 4. Catatan Teknis

1. **Peringatan `µWS` saat menjalankan Ganache/Truffle** bersifat non-fatal. Ganache otomatis memakai implementasi Node.js; performa sedikit lebih rendah tetapi tidak memengaruhi hasil test.
2. **Jangan menjalankan `npx truffle test` langsung** karena migration akan menimpa `config/contract_address.txt` dengan alamat instance test. Gunakan `npm test` (men-set `HSPORT_TEST=1`).
3. **Setiap reset Ganache** menghapus kontrak. Jalankan `npm run migrate` lalu `node scripts/logDeployment.js` lagi.
4. **Private key tidak boleh masuk repo.** Dokumen ini hanya mencatat alamat publik.

---

## 5. Langkah Berikutnya (Minggu 3)

1. Implementasi target interface kontrak: enum 8 state, `externalOrderHash`, operator, `recordPayment`, `updateOrderStatus`, `cancelOrder`.
2. Aktifkan SC-01 s.d. SC-06 dan capai 6/6 PASS.
3. Freeze interface akhir Minggu 3.
4. Uji reproduksi di komputer anggota lain untuk menutup `W2-FRL-05`.
5. Mulai service integrasi backend setelah ABI V2 stabil.

---

## 6. Tautan

- Week 1: [`../week1/README.md`](../week1/README.md)
- Roadmap: [`../ROADMAP_RINCI.md`](../ROADMAP_RINCI.md)
- Gap analysis: [`../GAP_ANALYSIS.md`](../GAP_ANALYSIS.md)
- Setup: [`../PANDUAN_SETUP_DAN_OPERASIONAL.md`](../PANDUAN_SETUP_DAN_OPERASIONAL.md)
