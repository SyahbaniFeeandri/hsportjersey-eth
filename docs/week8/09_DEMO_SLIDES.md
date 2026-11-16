# 09 — Demo Slides (Markdown Deck)

**Milestone:** M8 — Finalisasi & Demo
**Issue terkait:** `W8-ALL-04`
**Format:** 16:9, dapat dipindahkan ke PowerPoint/Google Slides atau dirender
dengan Marp (`marp docs/week8/09_DEMO_SLIDES.md`).
**Durasi total:** ± 40 menit (9 peran × 3–4 menit + pembukaan/penutup).

---

## Slide 1 — H-Sport Financial dApp

**E-Commerce Jersey Berbasis Ethereum Smart Contract & Parallel Computing**

- Rilis final: `v1.0.0`
- 112/112 issue · 15/15 requirement · 21/21 test PASS
- UAT 16/16 · Demo 13/13 · Benchmark 16/16

---

## Slide 2 — Agenda Demo

1. Arsitektur & alur escrow
2. Core contract (Arby)
3. State, payment, revert (Agustina)
4. Integrasi checkout → chain → DB → verifikasi (Luis)
5. Sequential vs parallel (Farelly)
6. Hasil test & benchmark (Kenjiro)
7. Proof page & dashboard (Ihsanul)
8. UAT business flow (Yasmin)
9. Environment & clean install (Farrel)
10. Finalisasi & traceability (Feeandri)

---

## Slide 3 — Arsitektur

```text
Customer → PHP UI → MySQL
              │
              └→ MetaMask → Ganache → HSportStore (escrow)
                                  │
Admin ← dashboard/verifikasi ← PHP service ← JSON-RPC
```

- Off-chain: katalog, cart, order, profil, ulasan
- On-chain: escrow, state, event, refund
- Jembatan: `ContractService`, `VerificationService`, worker paralel

---

## Slide 4 — Alur Escrow (8 State)

```text
Created → AwaitingPayment → Paid → InProduction
        → ReadyToShip → Shipped → Completed
                         └→ Cancelled (refund)
```

- Dana dikunci saat `createOrder`
- Release hanya saat `confirmReceived`
- Cancel/refund oleh aktor berwenang
- Semua transisi memancarkan event

---

## Slide 5 — Arby: Core Contract

| Item | Isi |
|---|---|
| Input | `externalOrderHash`, amount, buyer |
| Process | Validasi amount, hash unik, lock escrow |
| Output | State `Created`, event `OrderCreated` |
| Error | Amount 0, hash duplikat, non-owner |
| Test | SC-01..SC-06 — **6/6 PASS** |
| Dependency | Ganache + kontrak `0xe78A…a8Ab` |

---

## Slide 6 — Agustina: State, Payment, Revert

| Item | Isi |
|---|---|
| Input | `orderId`, aksi operator/buyer |
| Process | `recordPayment`, `shipOrder`, `cancelOrder`, `confirmReceived` |
| Output | Perubahan state + release/refund sekali |
| Error | Double payment, transisi tidak sah, unauthorized, double release |
| Test | SC-07..SC-12 — **6/6 PASS** |
| Dependency | Kontrak V2 + akun operator |

---

## Slide 7 — Luis: Integrasi End-to-End

| Item | Isi |
|---|---|
| Input | Checkout customer |
| Process | Order DB → `createOrder` → simpan proof → verifikasi |
| Output | `blockchain_order_id`, `tx_hash`, `VERIFIED` |
| Error | Manipulasi nominal → `MISMATCH`; tanpa chain → `PENDING` |
| Test | INT-01..INT-05 — **5/5 PASS** |
| Dependency | MySQL + Ganache + service PHP |

---

## Slide 8 — Luis: Retry Verifikasi Admin (REQ-11)

```text
Data cocok        → VERIFIED
Total +Rp 10.000  → MISMATCH (1 perbedaan)
Restore           → VERIFIED
```

- Kolom **Verifikasi** + badge status di `admin/orders.php`
- Tombol retry memanggil `VerificationService::verifyOrder($id, true)`
- Bukti: `docs/week8/evidence/req11_admin_verification.log`

---

## Slide 9 — Farelly: Sequential vs Parallel

| Dataset | Baseline | P=1 | P=2 | P=4 | P=8 |
|---|---:|---:|---:|---:|---:|
| D100 | 529 ms | 0,841× | 1,374× | 1,781× | 1,734× |
| D500 | 2.647 ms | 0,971× | 1,849× | 3,240× | 4,668× |
| D1000 | 5.271 ms | 0,981× | 1,911× | 3,531× | 5,748× |
| D5000 | 26.387 ms | 0,993× | 1,972× | 3,882× | **7,318×** |

- Checksum 16/16 identik dengan sequential
- Throughput D5000 P=8: **1.386,58 order/detik** (efficiency 91,5%)

---

## Slide 10 — Kenjiro: Hasil Test Final

| Suite | Jumlah | PASS |
|---|---:|---:|
| Smart contract | 12 | 12 |
| Integrasi | 5 | 5 |
| Parallel | 4 | 4 |
| **Total** | **21** | **21** |

- Perintah: `php tests/regression/run_all.php`
- Durasi: 47,19 detik
- Tidak ada bug Sev-1/Sev-2 terbuka
- Bukti: `docs/week8/evidence/final_regression.log`

---

## Slide 11 — Ihsanul: Proof Page & Dashboard

- `blockchain-proof.php`
  - tx hash, block, wallet, order ID, state on-chain
  - status verifikasi DB vs chain
- Dashboard admin
  - total produk/order/customer
  - manajemen order + kolom verifikasi
  - performance dashboard (matrix & regresi)

---

## Slide 12 — Yasmin: UAT Business Flow

| Kelompok | Skenario | Hasil |
|---|---:|---:|
| Customer | UAT-01..UAT-09 | PASS |
| Admin | UAT-10..UAT-16 | PASS |
| **Total** | **16** | **16/16 PASS** |

Alur: login → katalog → cart → checkout → bayar → order → status → verifikasi → proof → laporan.

---

## Slide 13 — Farrel: Clean Install

| Langkah | Hasil |
|---|---|
| Dump + 3 migrasi + seed | 5/5 exit 0 |
| Objek DB | 14 tabel, 4 view, 2 prosedur |
| Seed idempotent | 13 produk tetap |
| HTTP smoke | 7/7 HTTP 200 |

- Database uji: `h_sport_clean`
- Bukti: `docs/week8/evidence/clean_install*.log`
- Keterbatasan: satu mesin (clean clone lingkungan kedua sudah PASS di M2)

---

## Slide 14 — Feeandri: Finalisasi

| Aspek | Hasil |
|---|---|
| Issue roadmap | 112/112 |
| Requirement | 15/15 |
| Regression | 21/21 |
| UAT | 16/16 |
| Demo | 13/13 |
| Tag rilis | `v1.0.0` |

- Traceability: `docs/week8/07_TRACEABILITY_FINAL.md`
- Weekly log: `docs/week8/08_WEEKLY_LOG.md` (72/72)
- Code freeze: `docs/week8/02_RELEASE_NOTES_CODE_FREEZE.md`

---

## Slide 15 — Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Ganache reset | `npm run migrate` + INT ulang |
| MetaMask nonce | Clear activity |
| Clean install 1 mesin | Langkah SQL/CLI terdokumentasi + bukti M2 |
| Kurs ETH tetap | Asumsi demo terdokumentasi |
| CDN offline | UI inti tetap berfungsi |

---

## Slide 16 — Penutup

**H-Sport Financial dApp `v1.0.0`**

- Escrow aman & transparan
- Bukti transaksi permanen
- Verifikasi DB ↔ chain
- Paralel hingga 7,318×
- Siap didemokan dan diserahkan

Terima kasih — sesi tanya jawab.

---

## Catatan Presenter

1. Buka tab: katalog, cart, admin orders, proof page, dashboard, log bukti.
2. Jalankan INT sebelum sesi agar ada order ber-chain baru.
3. Jika Ganache bermasalah, gunakan bukti log (`demo_http.log`, `final_int_suite.log`).
4. Setiap peran menyebut: input → process → output → error → test → dependency.
