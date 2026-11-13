# Acceptance Criteria — Hasil Agregasi Paralel

**Issue:** `W6-YAS-10`
**Pemilik:** Yasmin
**Referensi:** `docs/ROADMAP_RINCI.md` M6, `docs/week1/02_BUSINESS_PROCESS_AND_UAT.md`
**Tanggal review:** 13 November 2026
**Status:** ✅ Seluruh AC terpenuhi

Dokumen ini menetapkan kriteria penerimaan untuk hasil agregasi verifikasi
paralel, agar angka yang tampil di dashboard dapat dipercaya untuk
pengambilan keputusan.

---

## 1. Prinsip

1. **Sama dengan sequential.** Untuk dataset dan aturan verifikasi yang sama,
   hasil paralel harus identik dengan baseline sequential, bukan hanya mirip.
2. **Dapat diaudit.** Setiap angka agregat harus dapat ditelusuri ke
   checkpoint dan hasil per order.
3. **Jujur tentang status.** Order yang belum punya relasi on-chain dicatat
   sebagai `SKIPPED`, bukan dihitung sebagai error atau mismatch.
4. **Gagal terlihat.** Batch yang tidak lengkap atau checksum-nya berbeda
   harus berstatus `FAILED`, bukan `DONE`.

---

## 2. Acceptance criteria

### AC-01 — Kelengkapan order

| Aspek | Kriteria | Bukti | Status |
|---|---|---|---|
| Jumlah | `processed_orders == total_orders` dataset | PAR-02 P=1/2/4/8: 500/500 | ✅ |
| Cakupan | Partisi menutup 1..N tanpa gap/overlap | Rentang `1..63 … 439..500` (P=8) | ✅ |
| Duplikasi | Tidak ada ID diproses dua kali | `duplicate_ids = []` semua run | ✅ |

### AC-02 — Konsistensi status verifikasi

| Aspek | Kriteria | Bukti | Status |
|---|---|---|---|
| Komposisi | V/M/E sama dengan dataset D500 | 350/100/50 | ✅ |
| SKIPPED terpisah | `SKIPPED` tidak masuk `ERROR`/`MISMATCH` | Batch #39: MISMATCH=1, SKIPPED=20, ERROR=0 | ✅ |
| Checksum | Checksum paralel = sequential | `61cfe1d0…` untuk P=1/2/4/8 | ✅ |

### AC-03 — Agregasi finansial

| Metrik | Kriteria | Nilai D500 | Status |
|---|---|---|---|
| `revenue_all` | Sama dengan jumlah `amount_idr` seluruh order | Rp 612.423.000 | ✅ |
| `revenue_verified` | Hanya VERIFIED **dan** paid | Dihitung dari status per order | ✅ |
| `paid_count` / `unpaid_count` | Sesuai `payment_status` dataset | 428 / 72 | ✅ |
| `aov` | `revenue_all / processed_orders` | Rp 1.224.846 | ✅ |
| Pembulatan | Rupiah bulat; tidak ada selisih pembulatan | Selisih 0 | ✅ |

### AC-04 — Gate kebenaran

| Aspek | Kriteria | Status |
|---|---|---|
| Gate batch | `DONE` hanya bila lengkap, tanpa duplikat, checksum MATCH | ✅ |
| Gate default | Batch tanpa baseline → `NO-BASELINE` (bukan MATCH palsu) | ✅ |
| Checksum beda | Batch ditandai `FAILED` + `checksum_match=false` | ✅ (diuji lewat logika reducer) |

### AC-05 — Konsistensi internal

| Aspek | Kriteria | Status |
|---|---|---|
| Jumlah checkpoint | Jumlah metrik checkpoint = hasil hitung ulang payload | ✅ `metrics_consistent=yes` |
| Revenue | Jumlah revenue checkpoint = total payload | ✅ |
| Idempotensi | Reduce ulang tidak mengubah checksum/checkpoint | ✅ RACE-02 |

### AC-06 — Kinerja

| Aspek | Kriteria | Status |
|---|---|---|
| Speedup | P>1 menghasilkan speedup > 1 pada dataset memadai | ✅ D1000: 1,96 / 3,64 / 6,10 |
| Efisiensi | Terdokumentasi, termasuk penurunan wajar di P=8 | ✅ 97,8% → 76,2% |
| Throughput | Dihitung dari wall-clock, bukan jumlah worker | ✅ 1.108,65 order/s (P=8) |

### AC-07 — Keterbacaan untuk pengguna

| Aspek | Kriteria | Status |
|---|---|---|
| Dashboard | Menampilkan status, V/M/E, revenue, AOV, speedup, gate | ✅ `admin/performance.php` |
| Label gate | `MATCH` / `DIFFERENT` / `NO-BASELINE` jelas | ✅ |
| Baseline | Speedup dihitung terhadap baseline terbaru per dataset | ✅ view `v_benchmark_summary` |

---

## 3. Skenario UAT terkait

| Skenario | Fokus | Hasil |
|---|---|---|
| UAT-15 | Sequential vs parallel menghasilkan hasil identik | ✅ Lulus lewat PAR-01/PAR-02 |
| UAT-16 | Angka performa (waktu, speedup, efficiency) masuk akal | ✅ D1000 + dashboard |
| UAT-13 | Admin melihat status verifikasi | ✅ Kolom V/M/E + gate |

Catatan: UAT-16 secara formal dijadwalkan M7, tetapi data pendukungnya sudah
tersedia dari M6.

---

## 4. Contoh penerimaan

Batch D500 P=4 dinyatakan **diterima** karena:

```text
status            = DONE
processed_orders  = 500 / 500
verified/mismatch = 350 / 100 (error 50)
revenue_all       = Rp 612.423.000
checksum          = 61cfe1d0…  (MATCH dengan baseline)
duplicate_ids     = []
metrics_consistent= yes
jobs_done         = 4 / 4, distinct_workers = 4, max_attempts = 1
```

Bila salah satu baris di atas tidak terpenuhi, batch **tidak** boleh
ditampilkan sebagai hasil yang sah.

---

## 5. Keputusan

| Pertanyaan | Keputusan |
|---|---|
| Apakah hasil agregasi paralel dapat dipakai sebagai angka resmi? | **Ya**, bila gate `MATCH` dan `complete=true` |
| Apakah batch `NO-BASELINE` boleh dipakai untuk klaim speedup? | Tidak untuk klaim correctness; boleh untuk smoke integrasi |
| Apakah `SKIPPED` boleh dianggap kegagalan? | Tidak; ini order tanpa relasi on-chain |
| Apakah dashboard boleh menampilkan batch `FAILED`? | Boleh, sebagai riwayat, dengan badge merah |

**Kesimpulan:** seluruh acceptance criteria M6 terpenuhi. Hasil agregasi
paralel diterima sebagai ekuivalen baseline sequential.
