# Minggu 8 — Finalisasi & Demo

**Milestone:** M8 — Finalisasi & Demo
**Periode:** 14–16 November 2026
**Lead:** semua anggota
**Status milestone:** **Selesai** — 14/14 issue, demo end-to-end PASS, UAT 16/16 PASS, clean install terbukti
**Exit gate:** ✅ demo berjalan dari clean setup · ✅ setiap anggota menjelaskan `input → process → output → error → test → dependency`

---

## 1. Ringkasan

M8 menutup proyek H-Sport Financial dApp: code freeze, tag rilis, reproduksi
instalasi dari nol, demo per peran, UAT business flow, dan dokumentasi final.
Seluruh pekerjaan M1–M7 dikonsolidasikan menjadi satu paket rilis yang dapat
dijalankan ulang.

```text
Dump DB + 3 migrasi + seed_demo.sql
              │
              ▼
     clean install (DB baru) ──► HTTP smoke test (7 halaman) ✅
              │
              ▼
  Ganache deterministik + npm run migrate ──► 0xe78A…a8Ab
              │
              ▼
  INT 5/5 ──► regresi 21/21 ──► benchmark 16/16 (reuse M7)
              │
              ▼
  Demo per peran (13 langkah HTTP) ──► UAT 16/16 ──► tag v1.0.0
```

Hasil utama:

| Metrik | Nilai |
|---|---|
| Issue M8 selesai | **14/14 (100%)** |
| Clean install | **5/5 langkah exit 0**; 14 tabel, 4 view, 2 prosedur |
| Seed data | **2 akun demo + 13 produk**; idempotent (re-run tetap 13 produk) |
| Regression final | **21/21 PASS** (SC 12, INT 5, PAR 4) |
| Integration suite final | **5/5 PASS** |
| Benchmark matrix (M7, dipakai ulang) | **16/16 PASS**, D5000 P=8 **7,318×** |
| UAT business flow | **16/16 PASS** |
| Demo HTTP end-to-end | **13/13 langkah PASS** |
| Bug Sev-1/Sev-2 terbuka | **0** |
| Tag rilis | **`v1.0.0`** |

---

## 2. Status Issue

| PIC | Issue | Status | Bukti |
|---|---|---|---|
| ALL | `W8-ALL-01` Code freeze + tag rilis | ✅ | [`02_RELEASE_NOTES_CODE_FREEZE.md`](02_RELEASE_NOTES_CODE_FREEZE.md); tag `v1.0.0` |
| ALL | `W8-ALL-02` README clean setup + seed data | ✅ | [`README.md`](../../README.md) Langkah 2; [`seed_demo.sql`](../../seed_demo.sql); [`03_DEPLOYMENT_GUIDE.md`](03_DEPLOYMENT_GUIDE.md) |
| ALL | `W8-ALL-03` Final report + traceability + weekly log | ✅ | [`01_FINAL_REPORT.md`](01_FINAL_REPORT.md), [`07_TRACEABILITY_FINAL.md`](07_TRACEABILITY_FINAL.md), [`08_WEEKLY_LOG.md`](08_WEEKLY_LOG.md) |
| ALL | `W8-ALL-04` Rehearsal demo per role | ✅ | [`04_DEMO_PLAN_PER_ROLE.md`](04_DEMO_PLAN_PER_ROLE.md), [`09_DEMO_SLIDES.md`](09_DEMO_SLIDES.md) |
| ALL | `W8-ALL-05` Demo end-to-end | ✅ | [`05_DEMO_EVIDENCE.md`](05_DEMO_EVIDENCE.md); `evidence/demo_http.log` |
| FEE | `W8-FEE-13` Final documentation | ✅ | [`01_FINAL_REPORT.md`](01_FINAL_REPORT.md) |
| YAS | `W8-YAS-12` UAT evidence + demo business flow | ✅ | [`06_UAT_EVIDENCE.md`](06_UAT_EVIDENCE.md); `evidence/uat_business_flow.log` (16/16 PASS) |
| FRL | `W8-FRL-12` Deployment guide + environment | ✅ | [`03_DEPLOYMENT_GUIDE.md`](03_DEPLOYMENT_GUIDE.md); `evidence/clean_install.log`, `evidence/clean_install_http.log` |
| ARB | `W8-ARB-12` Demo core contract | ✅ | [`04_DEMO_PLAN_PER_ROLE.md`](04_DEMO_PLAN_PER_ROLE.md) §3.1; SC-01..SC-06 PASS |
| AGU | `W8-AGU-12` Demo state/payment/revert | ✅ | [`04_DEMO_PLAN_PER_ROLE.md`](04_DEMO_PLAN_PER_ROLE.md) §3.2; SC-07..SC-12 PASS |
| LUI | `W8-LUI-14` Demo checkout → chain → DB → verification | ✅ | [`05_DEMO_EVIDENCE.md`](05_DEMO_EVIDENCE.md) §3; `evidence/req11_admin_verification.log` |
| FDR | `W8-FDR-13` Demo sequential vs parallel | ✅ | [`04_DEMO_PLAN_PER_ROLE.md`](04_DEMO_PLAN_PER_ROLE.md) §3.5; PAR-01/02 PASS |
| KEN | `W8-KEN-12` Demo test result + benchmark | ✅ | [`05_DEMO_EVIDENCE.md`](05_DEMO_EVIDENCE.md) §2; `evidence/final_regression.log` |
| IHS | `W8-IHS-09` Demo proof page + dashboard | ✅ | [`05_DEMO_EVIDENCE.md`](05_DEMO_EVIDENCE.md) §3 langkah 12; `evidence/demo_http.log` |

**Rekap:** 14 selesai, 0 sebagian, 0 belum → **100%**.

---

## 3. Perubahan Kode M8

| File | Perubahan | Issue |
|---|---|---|
| `admin/orders.php` | Kolom **Verifikasi** (badge status + tanggal) dan tombol retry verifikasi DB vs chain (`POST verify_order`) | REQ-11, `W8-LUI-14` |
| `config/database.php` | Dukungan override `DB_HOST`/`DB_NAME`/`DB_USER`/`DB_PASS` untuk clean install & CI; `session_start()` aman dipanggil berulang | `W8-ALL-02`, `W8-FRL-12` |
| `seed_demo.sql` | Seed idempotent: 2 akun demo + 13 produk | `W8-ALL-02` |
| `README.md` | Langkah migrasi lengkap, seed, override env, dan CLI alternatif | `W8-ALL-02` |
| `.gitignore` | Pengecualian `docs/week8/evidence/*.log` agar bukti ikut ter-commit | `W8-ALL-01` |

Kontrak **tidak berubah** pada M8: `HSportStore` V2 di
`0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab`. Code freeze berlaku sejak
commit rilis `v1.0.0`.

---

## 4. Cara Menjalankan Ulang

```bash
# 1) Database dari nol
mysql -u root -e "CREATE DATABASE h_sport_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root h_sport_db < "h_sport_db (1).sql"
mysql -u root h_sport_db < migration_reviews.sql
mysql -u root h_sport_db < migration_blockchain.sql
mysql -u root h_sport_db < migration_parallel.sql
mysql -u root h_sport_db < seed_demo.sql

# 2) Blockchain
cd blockchain
npm install
npx ganache --server.port 7545 --wallet.deterministic --chain.chainId 1337 --chain.networkId 5777
npm run migrate

# 3) Test & demo
php tests/integration/run_all.php
php tests/regression/run_all.php
php -S 127.0.0.1:8080 -t .
```

Panduan langkah demi langkah: [`03_DEPLOYMENT_GUIDE.md`](03_DEPLOYMENT_GUIDE.md).

---

## 5. Struktur Artefak

| Path | Isi |
|---|---|
| [`01_FINAL_REPORT.md`](01_FINAL_REPORT.md) | Laporan final proyek (ringkasan, hasil, risiko, kesimpulan) |
| [`02_RELEASE_NOTES_CODE_FREEZE.md`](02_RELEASE_NOTES_CODE_FREEZE.md) | Code freeze, isi rilis, tag `v1.0.0` |
| [`03_DEPLOYMENT_GUIDE.md`](03_DEPLOYMENT_GUIDE.md) | Panduan deployment & verifikasi clean install |
| [`04_DEMO_PLAN_PER_ROLE.md`](04_DEMO_PLAN_PER_ROLE.md) | Jadwal rehearsal, slide, narasi per peran |
| [`05_DEMO_EVIDENCE.md`](05_DEMO_EVIDENCE.md) | Bukti demo end-to-end & pemetaan langkah |
| [`06_UAT_EVIDENCE.md`](06_UAT_EVIDENCE.md) | 16 skenario UAT business flow |
| [`07_TRACEABILITY_FINAL.md`](07_TRACEABILITY_FINAL.md) | Status final REQ-01..REQ-15 |
| [`08_WEEKLY_LOG.md`](08_WEEKLY_LOG.md) | Rekap log mingguan 9 anggota × 8 minggu |
| [`09_DEMO_SLIDES.md`](09_DEMO_SLIDES.md) | Deck slide demo (16 slide) |
| [`evidence/`](evidence/) | Log mentah: clean install, regresi, INT, demo, UAT, REQ-11 |

---

## 6. Tautan Cepat

- Laporan final: [`01_FINAL_REPORT.md`](01_FINAL_REPORT.md)
- Rilis & code freeze: [`02_RELEASE_NOTES_CODE_FREEZE.md`](02_RELEASE_NOTES_CODE_FREEZE.md)
- Deployment: [`03_DEPLOYMENT_GUIDE.md`](03_DEPLOYMENT_GUIDE.md)
- Demo: [`04_DEMO_PLAN_PER_ROLE.md`](04_DEMO_PLAN_PER_ROLE.md) · [`05_DEMO_EVIDENCE.md`](05_DEMO_EVIDENCE.md)
- UAT: [`06_UAT_EVIDENCE.md`](06_UAT_EVIDENCE.md)
- Traceability: [`07_TRACEABILITY_FINAL.md`](07_TRACEABILITY_FINAL.md)
- Weekly log: [`08_WEEKLY_LOG.md`](08_WEEKLY_LOG.md)
- Slides demo: [`09_DEMO_SLIDES.md`](09_DEMO_SLIDES.md)
