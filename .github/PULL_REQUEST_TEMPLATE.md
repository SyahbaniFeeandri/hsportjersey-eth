<!--
Terima kasih sudah mengirim PR.
Isi semua bagian yang relevan. PR tanpa issue/test evidence akan diminta revisi.
-->

## Ringkasan

<!-- Apa yang diubah dan mengapa? 2-5 kalimat. -->

## Issue

- Closes #<!-- nomor issue -->
- Issue ID: `W<mgg>-<PIC>-<nomor>` <!-- contoh: W3-ARB-04 -->

## PIC / Peran

- Nama:
- Role:
- Modul:

## Jenis Perubahan

- [ ] Fitur (`feat`)
- [ ] Perbaikan bug (`fix`)
- [ ] Test (`test`)
- [ ] Dokumentasi (`docs`)
- [ ] Refactor (`refactor`)
- [ ] Performa (`perf`)
- [ ] Konfigurasi/tooling (`chore`)
- [ ] Migration/database
- [ ] Perubahan kontrak (`HSportStore.sol`)
- [ ] Perubahan UI

## Perubahan Utama

<!-- Daftar file/perubahan penting. -->

- 
- 
- 

## Cara Menguji

<!-- Langkah reproduksi singkat. -->

```powershell
# contoh
cd blockchain
npm run ganache:ping
npx truffle test --network ganache
```

## Bukti Test / Validasi

<!-- Wajib: tempel output test, screenshot, tx hash, query DB, atau link log. -->

| Test / Skenario | Hasil | Bukti |
|---|---|---|
|  | PASS / FAIL / N/A |  |
|  |  |  |

**Tx hash / block number (jika ada):**

```text
tx_hash:
block_number:
contract_address:
```

**Query DB (jika ada):**

```sql
-- tempel query dan hasil
```

## Screenshot / Log

<!-- Drag & drop screenshot atau tempel link. Jangan tampilkan private key/seed phrase. -->

## Checklist

- [ ] Branch mengikuti konvensi (`feature/`, `fix/`, `docs/`, `hotfix/`).
- [ ] Commit message menyebut issue ID.
- [ ] Tidak ada secret, private key, `.env`, atau data pribadi.
- [ ] Tidak ada `node_modules`, build artifacts, atau file sementara.
- [ ] Test relevan sudah dijalankan dan buktinya dilampirkan.
- [ ] Tidak menurunkan jumlah test PASS tanpa alasan tertulis.
- [ ] Dokumentasi/traceability diperbarui jika requirement berubah.
- [ ] Tidak ada konflik dengan `develop`.
- [ ] Perubahan kontrak sudah direview Arby/Agustina (jika relevan).
- [ ] Perubahan integrasi sudah direview Luis (jika relevan).
- [ ] Perubahan UI sudah direview Ihsanul/Yasmin (jika relevan).
- [ ] Weekly log sudah diperbarui.

## Risiko & Rollback

<!-- Apa risiko perubahan ini? Bagaimana cara rollback? -->

- Risiko:
- Rollback:

## Catatan Reviewer

<!-- Diisi reviewer. -->

- [ ] Approved
- [ ] Changes requested

Catatan:

<!--
Aturan review:
- Minimal 1 reviewer selain penulis.
- S1/blocker: review ≤ 4 jam.
- Must: review ≤ 1 hari kerja.
- Penulis tidak boleh merge PR sendiri.
-->
