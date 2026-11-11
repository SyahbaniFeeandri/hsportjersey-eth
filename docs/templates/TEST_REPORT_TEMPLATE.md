# LAPORAN PENGUJIAN — `<MINGGU / MILESTONE>`

> Salin file ini menjadi `docs/reports/test_report_<minggu>_<tanggal>.md`, isi semua bagian, lalu commit. Jangan mengubah template ini.

---

## 1. Identitas

| Field | Isi |
|---|---|
| Judul laporan | |
| Minggu / milestone | |
| Tanggal eksekusi | |
| Penyusun | |
| Reviewer | |
| Commit yang diuji | `<hash>` |
| Branch | |

---

## 2. Lingkup

| Kelompok | Total | Diuji | PASS | FAIL | Pending |
|---|---:|---:|---:|---:|---:|
| Smart Contract (SC) | 12 | | | | |
| Integrasi (INT) | 5 | | | | |
| Parallel (PAR) | 4 | | | | |
| **Total** | **21** | | | | |

Test yang **tidak** termasuk 21 test resmi (mis. smoke test) dicatat terpisah di §6.

---

## 3. Environment

| Komponen | Nilai |
|---|---|
| OS | |
| CPU / RAM | |
| Node.js | |
| npm | |
| Truffle | |
| Ganache | |
| Solidity | |
| PHP | |
| MySQL | |
| Chain ID / Network ID | |
| Contract address | |
| ABI SHA-256 | |

---

## 4. Hasil per Test

| ID | Nama test | Requirement | Hasil | Bukti | Durasi | Catatan |
|---|---|---|---|---|---|---|
| SC-01 | Inisialisasi & akses owner | REQ-02 | PASS / FAIL / PENDING | | | |
| SC-02 | `createOrder` valid | REQ-01, 02, 08 | | | | |
| SC-03 | Amount tidak valid | REQ-02 | | | | |
| SC-04 | Duplicate hash | REQ-03 | | | | |
| SC-05 | `getOrder` valid | REQ-01, 10 | | | | |
| SC-06 | `getOrder` tidak ada | REQ-10 | | | | |
| SC-07 | Access control | REQ-04, 06 | | | | |
| SC-08 | `recordPayment` + double | REQ-04, 08 | | | | |
| SC-09 | Transisi valid & `shipOrder` | REQ-05, 08 | | | | |
| SC-10 | Transisi tidak sah | REQ-05 | | | | |
| SC-11 | `cancelOrder` | REQ-06, 08 | | | | |
| SC-12 | `confirmReceived` & release | REQ-07, 08 | | | | |
| INT-01 | Koneksi RPC | REQ-09 | | | | |
| INT-02 | Checkout + `createOrder` | REQ-01, 09 | | | | |
| INT-03 | Receipt & block number | REQ-09 | | | | |
| INT-04 | Simpan proof ke DB | REQ-09 | | | | |
| INT-05 | Verifikasi DB vs chain | REQ-10, 11 | | | | |
| PAR-01 | Sequential baseline | REQ-13 | | | | |
| PAR-02 | Parallel multi-worker | REQ-13 | | | | |
| PAR-03 | Throughput/speedup/efficiency | REQ-14 | | | | |
| PAR-04 | Ketahanan konkurensi | REQ-14, NFR-01 | | | | |

**Nilai yang diizinkan:** `PASS`, `FAIL`, `PENDING`, `BLOCKED`, `NOT RUN`.

---

## 5. Perintah Eksekusi

```powershell
# Smart contract
cd blockchain
npm test

# Integrasi
php tests/integration/run_all.php

# Parallel / benchmark
php benchmark/run.php --dataset=1000 --workers=4
```

Tempelkan ringkasan output di sini (maks. 30 baris):

```text

```

---

## 6. Test Tambahan (Non-21)

| ID | Nama | Hasil | Catatan |
|---|---|---|---|
| SMOKE-01 | Owner adalah deployer | | |
| SMOKE-02 | `createOrder` valid | | |
| SMOKE-03 | Amount 0 revert | | |
| SMOKE-04 | Value tidak sesuai revert | | |

---

## 7. Defect Ditemukan

| Bug ID | Test | Severity | Ringkasan | Status |
|---|---|---|---|---|
| | | S1–S4 | | Open / Fixed / Verified |

Detail setiap bug memakai [`BUG_REPORT_TEMPLATE.md`](BUG_REPORT_TEMPLATE.md).

---

## 8. Exit Criteria

| Kriteria | Status | Catatan |
|---|---|---|
| Semua test yang dijadwalkan sudah dijalankan | | |
| 21/21 PASS, atau setiap FAIL punya bug ID | | |
| Tidak ada bug S1 terbuka | | |
| Bukti tersimpan dan dapat diakses | | |
| Regression dijalankan setelah fix | | |
| Direview Kenjiro dan Feeandri | | |

---

## 9. Kesimpulan

```text
Tulis 2–4 kalimat: apa yang lulus, apa yang gagal, apa risikonya, dan apa langkah berikutnya.
```

**Keputusan:** `LULUS` / `LULUS DENGAN CATATAN` / `TIDAK LULUS`

---

## 10. Tanda Tangan

| Peran | Nama | Tanggal | Status |
|---|---|---|---|
| Penyusun | | | |
| Reviewer teknis | Kenjiro | | |
| Reviewer milestone | Feeandri | | |
