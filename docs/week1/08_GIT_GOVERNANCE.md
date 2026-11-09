# 08 — GIT GOVERNANCE & WEEKLY LOG

**Milestone:** Minggu 1 — Analisis & Scope Freeze
**Issue terkait:** `W1-FEE-05`
**Pemilik:** Muhammad Feeandri Syahbani
**Reviewer:** Kenjiro B.H.G., Farrel
**Status:** Aktif mulai 9 November 2026
**Tanggal:** 9 November 2026

---

## 1. Tujuan

1. Memastikan setiap pekerjaan dapat ditelusuri ke issue, commit, PR, test, dan weekly log.
2. Mencegah konflik branch dan perubahan tak sengaja.
3. Menjaga kualitas review dan bukti kontribusi individu.
4. Menyediakan aturan rilis dan code freeze.

---

## 2. Branch Strategy

| Branch | Fungsi | Siapa yang merge | Protection |
|---|---|---|---|
| `main` | Kondisi stabil/demo | Lead setelah review | Wajib PR + 1 approval |
| `develop` | Integrasi harian | Lead/owner modul | Wajib PR + 1 approval |
| `feature/<issue-id>-<slug>` | Fitur baru | Developer | — |
| `fix/<issue-id>-<slug>` | Perbaikan bug | Developer | — |
| `docs/<issue-id>-<slug>` | Dokumen | Developer | — |
| `hotfix/<issue-id>-<slug>` | Perbaikan darurat saat code freeze | Lead | PR ke `main` + `develop` |

### 2.1 Contoh nama branch

```text
feature/W3-ARB-04-contract-8-state
feature/W4-AGU-06-record-payment
fix/W5-LUI-09-verification-mismatch
docs/W1-FEE-04-traceability
hotfix/W7-KEN-09-escrow-double-release
```

### 2.2 Aturan branch

1. Branch dibuat dari `develop` (kecuali hotfix dari `main`).
2. Satu branch = satu issue utama. Pekerjaan tambahan kecil boleh menyertai jika masih satu modul.
3. Rebase/merge `develop` sebelum PR jika branch tertinggal.
4. Hapus branch setelah merge.
5. Jangan commit langsung ke `main` atau `develop`.

---

## 3. Commit Convention

Format:

```text
<type>(<scope>): <subject> [<issue-id>]
```

| Type | Penggunaan |
|---|---|
| `feat` | Fitur baru |
| `fix` | Perbaikan bug |
| `docs` | Dokumentasi |
| `test` | Test baru/perbaikan test |
| `refactor` | Refactor tanpa perubahan perilaku |
| `perf` | Peningkatan performa |
| `chore` | Konfigurasi/tooling |
| `style` | Formatting |
| `revert` | Membatalkan commit |

Contoh:

```text
feat(contract): add externalOrderHash and duplicate guard [W3-ARB-04]
test(contract): add SC-04 duplicate hash test [W3-ARB-06]
fix(integration): parse receipt block number correctly [W5-LUI-07]
docs(week1): finalize scope and decisions [W1-FEE-02]
perf(worker): add partition checkpoint [W6-FDR-09]
```

Aturan:

1. Subject maksimal 72 karakter, imperatif, tanpa titik di akhir.
2. Satu commit = satu perubahan logis.
3. Commit harus menyebut issue ID di akhir.
4. Jangan commit file rahasia, `node_modules`, build artifacts, atau database dump berisi data nyata.
5. Commit message berbahasa Indonesia atau Inggris diperbolehkan, konsisten dalam satu PR.

---

## 4. Issue & Label

### 4.1 Format issue

```text
[<issue-id>] <judul singkat>
```

Contoh: `[W3-ARB-04] Implement enum 8 state dan externalOrderHash`

### 4.2 Label wajib

| Kategori | Label | Contoh |
|---|---|---|
| Minggu | `week:1` … `week:8` | `week:3` |
| Peran | `role:fee`, `role:yas`, `role:arb`, `role:agu`, `role:frl`, `role:lui`, `role:ihs`, `role:fdr`, `role:ken` | `role:arb` |
| Tipe | `type:feat`, `type:fix`, `type:docs`, `type:test`, `type:chore` | `type:feat` |
| Prioritas | `priority:must`, `priority:should`, `priority:could` | `priority:must` |
| Status | `status:todo`, `status:in-progress`, `status:review`, `status:blocked`, `status:done` | `status:review` |
| Severity | `sev:S1`, `sev:S2`, `sev:S3`, `sev:S4` | `sev:S1` |
| Area | `area:contract`, `area:integration`, `area:parallel`, `area:ui`, `area:docs`, `area:qa` | `area:contract` |

### 4.3 Isi issue minimum

1. Latar belakang / tujuan.
2. Output yang diharapkan.
3. Definition of Done.
4. Dependensi.
5. Bukti yang harus dilampirkan.
6. Estimasi dan deadline.

---

## 5. Pull Request

### 5.1 Aturan

1. Setiap PR wajib menautkan issue (`Closes #<nomor>` atau `Refs #<nomor>`).
2. PR harus menjelaskan **apa**, **mengapa**, dan **cara menguji**.
3. Wajib melampirkan bukti: output test, screenshot, tx hash, atau query DB.
4. Minimal **1 reviewer** selain penulis.
5. Penulis tidak boleh merge PR-nya sendiri.
6. PR yang mengubah kontrak wajib direview Arby/Agustina + Kenjiro.
7. PR yang mengubah integrasi wajib direview Luis + Kenjiro.
8. PR yang mengubah UI wajib direview Ihsanul + Yasmin.
9. PR yang mengubah dokumen requirement/traceability wajib direview Feeandri.
10. PR tidak boleh menurunkan jumlah test PASS tanpa alasan tertulis.

### 5.2 Ukuran PR

| Ukuran | Rekomendasi |
|---|---|
| Ideal | ≤ 400 baris perubahan |
| Maksimum | 800 baris (kecuali migration/generated file) |
| File generated | Dipisah atau dijelaskan |

### 5.3 Review SLA

| Prioritas | Waktu review |
|---|---|
| S1 / blocker | ≤ 4 jam |
| Must | ≤ 1 hari kerja |
| Should/Could | ≤ 2 hari kerja |

### 5.4 Checklist review

- [ ] Issue tertaut dan sesuai scope.
- [ ] Tidak ada secret/private key.
- [ ] Kode mengikuti pola repo.
- [ ] Test/bukti ada dan relevan.
- [ ] Tidak ada regresi.
- [ ] Dokumentasi/traceability diperbarui jika perlu.
- [ ] Error handling memadai.
- [ ] Tidak ada `var_dump`, `console.log` debug, atau file sementara.
- [ ] Nama branch/commit sesuai konvensi.
- [ ] Tidak ada konflik dengan `develop`.

Template PR: [`.github/PULL_REQUEST_TEMPLATE.md`](../../.github/PULL_REQUEST_TEMPLATE.md).

---

## 6. Weekly Progress Log

### 6.1 Jadwal

| Hari | Aktivitas |
|---|---|
| Jumat pagi | Setiap mahasiswa mengisi log mingguan |
| Jumat siang | Lead modul review log + bukti |
| Jumat sore | Update `Progress Tracker.docx` dan `GAP_ANALYSIS.md` |
| Jumat malam | Pengumuman status milestone dan blocker |

### 6.2 Isi minimum

1. Target minggu ini.
2. Pekerjaan selesai.
3. Commit/PR/issue.
4. Test/hasil.
5. Kendala.
6. Keputusan teknis.
7. Cross-role contribution.
8. Rencana minggu berikut.
9. Status log (`Draft`/`Final`).

### 6.3 Aturan

1. Satu log per mahasiswa per minggu.
2. Tidak boleh kosong dua minggu berturut-turut.
3. Wajib memuat minimal satu link commit/PR/issue dan satu bukti test.
4. `Final` hanya jika direview minimal satu anggota lain.
5. Log kosong tidak dihitung dalam penilaian individu.

---

## 7. Definition of Done per Issue

Sebuah issue hanya boleh ditutup jika:

- [ ] Kode/dokumen ada di branch dan sudah di-merge.
- [ ] Test/UAT PASS atau FAIL terdokumentasi dengan alasan.
- [ ] Bukti dilampirkan (log/screenshot/tx hash/query).
- [ ] Direview minimal satu anggota lain.
- [ ] Traceability diperbarui jika requirement berubah.
- [ ] Weekly log mencantumkan issue tersebut.
- [ ] Tidak ada TODO kritis yang menggantung.

---

## 8. Release & Code Freeze

| Tahap | Aturan |
|---|---|
| Freeze fitur | Akhir Minggu 7; hanya bug fix |
| Code freeze | Awal Minggu 8; hanya hotfix dengan persetujuan lead |
| Tag rilis | `v0.1.0-w1`, `v0.2.0-w2`, …, `v1.0.0-final` |
| Artefak rilis | Source, ABI, address, migration, README, laporan, bukti test |
| Demo | Dari tag `v1.0.0-final` |

### 8.1 Checklist rilis

- [ ] Semua test 21/21 PASS atau FAIL terdokumentasi.
- [ ] README clean setup teruji.
- [ ] Migration berjalan dari dump bersih.
- [ ] ABI/address final tercatat.
- [ ] Tidak ada secret di repo.
- [ ] Tag dibuat dan di-push.
- [ ] Bukti demo tersimpan.

---

## 9. Keamanan & File yang Tidak Boleh Di-commit

| File | Alasan |
|---|---|
| `.env`, `.env.local` | Berisi kredensial/private key |
| `node_modules/` | Dependency, bukan source |
| `blockchain/build/`, `artifacts/`, `cache/` | Generated |
| Database dump dengan data nyata | Privasi |
| Private key, seed phrase, screenshot MetaMask | Keamanan |
| File upload pengguna | Privasi dan ukuran |

Jika secret tidak sengaja ter-commit:

1. Rotasi/anggap secret bocor.
2. Hapus dari history (mis. `git filter-repo`).
3. Catat insiden di weekly log.
4. Review ulang akses.

---

## 10. Konflik & Eskalasi

| Situasi | Tindakan |
|---|---|
| Konflik branch | Rebase ke `develop`, selesaikan manual, jalankan test |
| Perbedaan desain | Diskusi di issue, keputusan dicatat di dokumen |
| Deadline terancam | Eskalasi ke Feeandri/Yasmin, catat di weekly log |
| Blocker dependency | Tandai `status:blocked`, cari workaround |
| Bug S1 | Hentikan milestone, fix, retest, catat |

---

## 11. Contoh Alur Kerja

```powershell
# 1. Sinkronisasi
git checkout develop
git pull origin develop

# 2. Buat branch
git checkout -b feature/W3-ARB-04-contract-8-state

# 3. Kerjakan + commit
git add blockchain/contracts/HSportStore.sol blockchain/test/
git commit -m "feat(contract): implement 8-state enum and duplicate guard [W3-ARB-04]"

# 4. Push
git push -u origin feature/W3-ARB-04-contract-8-state

# 5. Buka PR ke develop, isi template, lampirkan bukti test

# 6. Setelah review dan merge, hapus branch
git branch -d feature/W3-ARB-04-contract-8-state
```

---

## 12. Tautan

- PR template: [`../../.github/PULL_REQUEST_TEMPLATE.md`](../../.github/PULL_REQUEST_TEMPLATE.md)
- Roadmap: [`../ROADMAP_RINCI.md`](../ROADMAP_RINCI.md)
- Gap analysis: [`../GAP_ANALYSIS.md`](../GAP_ANALYSIS.md)
- Tracker: [`../Progress Tracker.docx`](../Progress%20Tracker.docx)
