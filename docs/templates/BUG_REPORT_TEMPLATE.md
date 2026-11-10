# BUG REPORT — `<BUG-ID>`

> Salin file ini menjadi `docs/reports/bugs/BUG-<minggu>-<nomor>.md`, isi, lalu tautkan dari laporan test. Jangan mengubah template ini.

---

## 1. Identitas

| Field | Isi |
|---|---|
| Bug ID | `BUG-<minggu>-<nomor>` |
| Judul | |
| Ditemukan oleh | |
| Tanggal | |
| Test case terkait | SC-xx / INT-xx / PAR-xx / manual |
| Commit saat ditemukan | `<hash>` |
| Environment | OS, Node, Truffle, Ganache, PHP, MySQL |
| Severity | S1 / S2 / S3 / S4 |
| Prioritas | P1 / P2 / P3 |
| Status | Open / In Progress / Fixed / Verified / Closed / Won't Fix |
| Owner fix | |

### 1.1 Panduan severity

| Severity | Definisi | Contoh | SLA |
|---|---|---|---|
| **S1 Critical** | Kehilangan dana, bypass akses, data loss | Escrow release ganda; non-owner menambah operator | Blokir milestone; fix < 24 jam |
| **S2 High** | Alur utama gagal | `createOrder` gagal; proof tidak tersimpan | Fix sebelum milestone |
| **S3 Medium** | Sebagian fitur salah | Badge status salah; retry tidak muncul | Fix minggu yang sama |
| **S4 Low** | Kosmetik | Typo, alignment | Backlog |

---

## 2. Ringkasan

```text
Satu paragraf: apa yang salah, di mana, dan dampaknya bagi pengguna/sistem.
```

---

## 3. Precondition

| Item | Nilai |
|---|---|
| Contract address | |
| Order ID | |
| Akun yang dipakai | |
| State awal | |
| Data DB terkait | |

---

## 4. Langkah Reproduksi

1.
2.
3.

**Tingkat reproduksi:** `Selalu` / `Sering` / `Kadang` / `Sekali`

---

## 5. Expected vs Actual

| | Isi |
|---|---|
| **Expected** | |
| **Actual** | |
| **Pesan error** | |
| **Tx hash** | |

```text
Tempel output/log (maks. 30 baris). Jangan sertakan private key, mnemonic, atau data pribadi.
```

---

## 6. Dampak

| Aspek | Dampak |
|---|---|
| Finansial | |
| Keamanan | |
| Data | |
| Pengguna | |
| Milestone | |

---

## 7. Root Cause

```text
Diisi setelah investigasi. Sebutkan file/fungsi/baris dan alasan teknis.
```

| Item | Isi |
|---|---|
| File | |
| Fungsi / baris | |
| Penyebab | |
| Mengapa lolos dari test | |

---

## 8. Perbaikan

| Item | Isi |
|---|---|
| Pendekatan fix | |
| Commit fix | `<hash>` |
| File berubah | |
| Test baru/ubah | |
| Risiko regresi | |

---

## 9. Retest

| Tanggal | Oleh | Environment | Hasil | Bukti |
|---|---|---|---|---|
| | | | PASS / FAIL | |

**Regression dijalankan:** SC-01–SC-12 / INT / PAR — sebutkan yang relevan.

---

## 10. Verifikasi Penutupan

- [ ] Fix sudah di commit dan di-push.
- [ ] Test yang gagal sebelumnya sekarang PASS.
- [ ] Tidak ada test lain yang rusak.
- [ ] Bukti retest dilampirkan.
- [ ] Direview minimal satu anggota lain.
- [ ] Status diubah menjadi `Closed`.
