# Minggu 4 — Smart Contract V2: Cancel, Refund & Security

**Milestone:** M4 — Smart Contract V2
**Periode:** 11 November 2026
**Branch:** `feature/W4-AGU-06-contract-v2`
**Kontrak aktif:** `0x2f3efA6bbDC5fAf4dC1a600765c7B7829e47bE10` (Ganache, chain ID 1337)
**Status milestone:** **Selesai untuk scope kontrak** — 12/12 SC PASS, security review selesai, ABI V2 terpublikasi

---

## 1. Ringkasan

M4 memperluas `HSportStore` V1 menjadi V2 dengan menambahkan `cancelOrder(uint256,string)` dan refund escrow. Perubahan bersifat **aditif**: enum, struct, signature V1, dan event tidak berubah. Enam test baru (SC-07 s.d. SC-12) melengkapi enam test V1 sehingga seluruh 12 test smart contract PASS.

| Item | Hasil |
|---|---|
| Test SC | **12/12 PASS** (6 regresi + 6 baru) |
| Test tambahan | 4 smoke PASS |
| Total eksekusi | 16 passing, 0 gagal |
| Security review | Tidak ada Critical/High/Medium |
| ABI | 29 entri (19 fungsi + 9 event + constructor) |
| Bytecode | 8.107 creation / 8.057 deployed |
| Address V2 | `0x2f3efA6bbDC5fAf4dC1a600765c7B7829e47bE10` |
| Tag kontrak V1 | `contract-v1` @ `ee0ef03` |

---

## 2. Status Issue Minggu 4

| Issue | Ringkasan | Status | Bukti |
|---|---|---|---|
| `W4-AGU-06` | `cancelOrder`, refund, operator management | ✅ Selesai | [`00_CONTRACT_V2.md`](00_CONTRACT_V2.md) |
| `W4-AGU-07` | Event lengkap + revert rules + security check | ✅ Selesai | [`02_SECURITY_REVIEW.md`](02_SECURITY_REVIEW.md) |
| `W4-AGU-08` | Unit test SC-07 s.d. SC-12 | ✅ Selesai | [`01_TEST_REPORT_V2.md`](01_TEST_REPORT_V2.md) |
| `W4-ARB-08` | Review V2 + regresi SC-01 s.d. SC-06 | ✅ Selesai | [`01_TEST_REPORT_V2.md`](01_TEST_REPORT_V2.md) §2 |
| `W4-FRL-07` | Deploy V2 + update ABI/address | ✅ Selesai | [`03_DEPLOYMENT_V2.md`](03_DEPLOYMENT_V2.md) |
| `W4-FRL-08` | Kompatibilitas frontend dengan V2 | ✅ Selesai | [`04_FRONTEND_COMPAT.md`](04_FRONTEND_COMPAT.md) |
| `W4-KEN-04` | Security/revert test independen | ✅ Selesai | [`02_SECURITY_REVIEW.md`](02_SECURITY_REVIEW.md) |
| `W4-LUI-05` | Sesuaikan service dengan ABI V2 | ✅ Selesai | [`03_DEPLOYMENT_V2.md`](03_DEPLOYMENT_V2.md) §2 |
| `W4-IHS-04` | Wallet connect + tx status UI | ⚠️ Sebagian | [`04_FRONTEND_COMPAT.md`](04_FRONTEND_COMPAT.md) §4 |
| `W4-FDR-05` | Sequential baseline CLI data mock | ⬜ Belum | Di luar scope M4 (mulai M6) |
| `W4-FEE-09` | Traceability + dokumentasi kontrak | ✅ Selesai | Dokumen ini + traceability |
| `W4-YAS-08` | UAT payment/status/cancel | ✅ Selesai (skenario) | [`05_UAT_CANCEL.md`](05_UAT_CANCEL.md) |

**Rekap:** 10 selesai, 1 sebagian, 1 belum → **~88%** (issue sebagian dihitung 0,5).

> `W4-IHS-04` hanya mencakup wallet connect dan modal transaksi yang sudah ada di `crypto-payment.php`; tombol cancel belum dibuat. `W4-FDR-05` sengaja ditunda karena baseline parallel baru relevan di M6.

---

## 3. Exit Gate M4

| Kriteria | Target | Hasil | Status |
|---|---|---|---|
| Kontrak V2 compile | Berhasil | Solc 0.8.19, 0 warning | ✅ |
| Kontrak V2 deploy | Address baru | `0x2f3e…bE10`, block 225 | ✅ |
| SC-07 s.d. SC-12 | 6/6 PASS | 6/6 PASS | ✅ |
| Regresi SC-01 s.d. SC-06 | 6/6 PASS | 6/6 PASS | ✅ |
| Security review | Selesai | Tanpa Critical/High/Medium | ✅ |
| ABI V2 | Dipublikasikan | `contract_abi.json` + arsip V2 | ✅ |
| Address history | Diperbarui | `DEPLOYMENTS.md` + config | ✅ |
| Dokumentasi | Lengkap | Dokumen 00–05 + README | ✅ |
| Frontend kompatibel | Tidak breaking | Config-driven, signature sama | ✅ |
| UAT browser cancel | Berjalan | Menunggu UI cancel (M5) | ⚠️ |

Exit gate **kontrak** terpenuhi seluruhnya. Sisa pekerjaan UI/UAT browser dicatat sebagai carry-over M5, bukan penghalang M4.

---

## 4. Artefak

| Artefak | Lokasi |
|---|---|
| Kontrak V2 | `blockchain/contracts/HSportStore.sol` |
| Test SC-07..SC-12 | `blockchain/test/02_sc07_sc12.test.js` |
| Log test (16 passing) | [`evidence/sc07_sc12_test.log`](evidence/sc07_sc12_test.log) |
| ABI aktif V2 | `blockchain/config/contract_abi.json` |
| Arsip ABI V2 | `blockchain/config/archive/contract_abi.v2.json` |
| Address aktif | `blockchain/config/contract_address.txt` |
| Deployment record | `blockchain/deployments/deployment_2026-11-11T05-26-49-751Z.json` |
| Riwayat deploy | `blockchain/deployments/DEPLOYMENTS.md` |
| Read-only PHP | `blockchain/scripts/read_contract.php` |

Dokumen:

1. [`00_CONTRACT_V2.md`](00_CONTRACT_V2.md) — delta V1→V2, aturan cancel, ABI.
2. [`01_TEST_REPORT_V2.md`](01_TEST_REPORT_V2.md) — hasil 12/12 SC.
3. [`02_SECURITY_REVIEW.md`](02_SECURITY_REVIEW.md) — ancaman, kontrol, temuan.
4. [`03_DEPLOYMENT_V2.md`](03_DEPLOYMENT_V2.md) — fakta deploy dan verifikasi address.
5. [`04_FRONTEND_COMPAT.md`](04_FRONTEND_COMPAT.md) — kompatibilitas dan pekerjaan UI.
6. [`05_UAT_CANCEL.md`](05_UAT_CANCEL.md) — skenario UAT cancel/refund.

---

## 5. Cara Mereproduksi

Prasyarat: Node.js, Ganache berjalan di `127.0.0.1:7545` (chain ID 1337, network ID 5777).

```bash
cd blockchain
npm install
npm run compile
npm test          # menyetel HSPORT_TEST=1 dan network ganache
npm run migrate   # deploy V2 ke Ganache
node scripts/logDeployment.js
```

Verifikasi baca kontrak:

```bash
php scripts/read_contract.php
```

Output yang diharapkan menampilkan address V2 dan `Bytecode size : 8057 bytes`.

> Jangan commit private key atau seed phrase. Deploy memakai akun deterministik Ganache yang hanya ada di mesin lokal.

---

## 6. Keputusan Penting

| # | Keputusan | Alasan |
|---|---|---|
| 1 | Extend kontrak, bukan rewrite | Menjaga kompatibilitas V1 dan riwayat |
| 2 | `cancelOrder` hanya aditif | Tidak mengubah ABI V1 yang sudah frozen |
| 3 | `Shipped` tidak dapat dibatalkan | Sengketa dikelola manual; sesuai spesifikasi T13 |
| 4 | Reason maksimal 64 byte | Mencegah penyalahgunaan gas/storage |
| 5 | Kontrak tidak diubah untuk R-01 | Cukup sesuaikan ekspektasi test; perilaku tetap |
| 6 | ABI V2 diarsipkan | Memungkinkan audit dan rollback referensi |

---

## 7. Carry-over ke M5

1. UI tombol cancel + validasi state + batas 64 byte.
2. Listener event `OrderCancelled`/`EscrowRefunded` di halaman order.
3. UAT browser UAT-09 s.d. UAT-12 dan screenshot.
4. Simpan proof refund ke DB dan verifikasi DB vs chain (INT-04, INT-05).
5. Tandai order V1 historis pada tampilan.

---

## 8. Tautan

- Roadmap: [`../ROADMAP_RINCI.md`](../ROADMAP_RINCI.md)
- GAP analysis: [`../GAP_ANALYSIS.md`](../GAP_ANALYSIS.md)
- Traceability: [`../week1/03_TRACEABILITY_MATRIX.md`](../week1/03_TRACEABILITY_MATRIX.md)
- Test plan: [`../week1/07_TEST_PLAN.md`](../week1/07_TEST_PLAN.md)
- Spesifikasi kontrak: [`../week2/02_CONTRACT_SPEC_FINAL.md`](../week2/02_CONTRACT_SPEC_FINAL.md)
- Minggu 3 (V1): [`../week3/README.md`](../week3/README.md)
- Review M3: [`../week3/04_REVIEW_NOTES.md`](../week3/04_REVIEW_NOTES.md)
