# 03 — UJI REPRODUKSI & KONSISTENSI ABI

**Issue:** `W2-FRL-03`, `W2-FRL-05`
**Pemilik:** Muhammad Farrel
**Reviewer:** Luis, Kenjiro
**Status:** Simulasi clean clone & uji mesin anggota lain **PASS** (`W2-FRL-05` Selesai)
**Tanggal:** 10 November 2026 (diperbarui 11 November 2026)

---

## 1. Tujuan

Membuktikan bahwa repository dapat diambil dari nol (tanpa `node_modules`, tanpa `build/`, tanpa `config/` hasil generate) lalu:

1. `npm ci` berhasil,
2. `npm run compile` berhasil,
3. `npm run migrate` mendeploy kontrak,
4. `npm test` berjalan,
5. ABI yang dihasilkan **identik** dengan repo utama,
6. address yang dihasilkan **tercatat** dan dapat dijelaskan.

---

## 2. Definisi "Clean Clone"

| Termasuk | Tidak termasuk |
|---|---|
| Source dari `git archive HEAD` | `node_modules/` |
| `package.json` + `package-lock.json` | `build/` |
| `contracts/`, `migrations/`, `scripts/`, `test/` | `config/contract_address.txt` hasil deploy |
| `.env.example` | `.env` berisi nilai lokal |

**Catatan:** `config/contract_abi.json` **ikut** ter-commit pada repo ini (dipakai PHP). Clean clone menimpanya saat migrate; itulah yang dibandingkan.

---

## 3. Prosedur Resmi

Dijalankan dari root repo:

```powershell
# 1. Ambil snapshot commit terakhir tanpa artefak
$tmp = "$env:TEMP\hsport_clean_test"
Remove-Item $tmp -Recurse -Force -ErrorAction SilentlyContinue
New-Item -ItemType Directory $tmp | Out-Null
git archive --format=zip --output="$tmp\repo.zip" HEAD
Expand-Archive "$tmp\repo.zip" -DestinationPath "$tmp\repo"
Remove-Item "$tmp\repo.zip"

# 2. Install dependency dari lockfile
cd "$tmp\repo\blockchain"
npm ci

# 3. Compile
npm run compile

# 4. Pastikan Ganache berjalan (chain ID 1337, network ID 5777)
npm run ganache:ping

# 5. Deploy
npm run migrate
node scripts/logDeployment.js

# 6. Test
npm test
```

---

## 4. Hasil Simulasi (10 November 2026)

**Lokasi:** `C:\Users\Feeandri\AppData\Local\Temp\opencode\hsport_clean_test\repo`
**Log mentah:** [`evidence/reproduction_clean_clone.log`](evidence/reproduction_clean_clone.log)

| # | Langkah | Perintah | Hasil | Status |
|---|---|---|---|---|
| 1 | Toolchain | `npx truffle version` | Truffle 5.11.5, Ganache 7.9.1, Solidity 0.8.19, Node 24.20.0, Web3 1.10.0 | ✅ |
| 2 | Install | `npm ci` | Selesai tanpa error (output tidak tersimpan di log karena dijalankan sebelum pencatatan dimulai) | ✅ |
| 3 | Compile | `npm run compile` | `Compiled successfully using solc 0.8.19` | ✅ |
| 4 | Deploy | `npm run migrate` | `HSportStore deployed to: 0x5b1869D9A4C187F2EAa108f3062412ecf0526b24` | ✅ |
| 5 | Log deploy | `node scripts/logDeployment.js` | Address + network ID 5777 + chain ID 1337 tercatat | ✅ |
| 6 | Test | `npm test` | **4 passing, 6 pending** | ✅ |
| 7 | ABI | Bandingkan SHA-256 | Identik dengan repo utama | ✅ |

**Catatan:** peringatan `µWS` muncul di langkah 4 dan 6. Ganache otomatis memakai implementasi Node.js (`Falling back to a NodeJS implementation`). Non-fatal.

---

## 5. Perbandingan Repo Utama vs Clean Clone

| Item | Repo utama | Clean clone | Konsisten? |
|---|---|---|---|
| Commit | `83c4c79` | `83c4c79` (archive) | ✅ |
| Contract | `HSportStore` | `HSportStore` | ✅ |
| Compiler | 0.8.19 | 0.8.19 | ✅ |
| Network ID | 5777 | 5777 | ✅ |
| Chain ID | 1337 | 1337 | ✅ |
| Address | `0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab` | `0x5b1869D9A4C187F2EAa108f3062412ecf0526b24` | ⚠️ berbeda (wajar) |
| Block number | 1 | 2 | ⚠️ berbeda (wajar) |
| ABI SHA-256 | `FC5622FE41D0C2F9BF12BDAD8243BE56BA07074AE227FCAC877700A64A9D6252` | `FC5622FE41D0C2F9BF12BDAD8243BE56BA07074AE227FCAC877700A64A9D6252` | ✅ |

### 5.1 Mengapa address berbeda?

Address kontrak ditentukan oleh `keccak256(rlp(sender, nonce))`. Akun deployer sama (`0x90F8...c9C1`), tetapi **nonce berbeda** karena repo utama melakukan deploy lebih dulu. Perbedaan address adalah konsekuensi normal, bukan bug.

**Yang wajib konsisten adalah ABI**, karena ABI menentukan cara encode/decode transaksi. Address selalu diambil dari konfigurasi hasil deploy, bukan dari kode.

### 5.2 Aturan address

1. Setiap address **wajib** dicatat di `blockchain/deployments/DEPLOYMENTS.md`.
2. `.env` lokal memakai address hasil deploy mesin masing-masing.
3. Screenshot/log yang dikumpulkan wajib menyertakan address yang dipakai.
4. Jangan menyalin address dari mesin lain tanpa mencatatnya.

---

## 6. Verifikasi ABI

```powershell
$a = Get-FileHash "blockchain\config\contract_abi.json" -Algorithm SHA256
$b = Get-FileHash "$env:TEMP\hsport_clean_test\repo\blockchain\config\contract_abi.json" -Algorithm SHA256
"MAIN=$($a.Hash)"
"CLEAN=$($b.Hash)"
"MATCH=$($a.Hash -eq $b.Hash)"
```

Hasil:

```text
MAIN=FC5622FE41D0C2F9BF12BDAD8243BE56BA07074AE227FCAC877700A64A9D6252
CLEAN=FC5622FE41D0C2F9BF12BDAD8243BE56BA07074AE227FCAC877700A64A9D6252
MATCH=True
```

---

## 7. Hasil Uji Mesin/Lingkungan Anggota Lain (11 November 2026)

Pengujian reproduksi penuh dijalankan di workstation lingkungan terpisah (Linux / Debian Bookworm via WSL2) oleh Luis untuk memvalidasi clean clone antar-lingkungan OS dan toolchain berbeda.

- **Tester / Runner:** Luis (didampingi Farrel & Kenjiro)
- **Lingkungan OS:** Linux (Debian GNU/Linux 12 / bookworm) x86_64
- **Node.js / npm / git:** v20.20.2 / 10.8.2 / 2.39.5
- **Log mentah:** [`evidence/reproduction_second_env.log`](evidence/reproduction_second_env.log)

| # | Langkah | Perintah | Hasil | Status |
|---|---|---|---|---|
| 1 | Clone | `git clone ... /work/repo` | Commit `94431a0` diambil dari nol tanpa dependensi | ✅ PASS |
| 2 | Install | `npm ci` | 1.262 package terpasang, audited 1.292 package | ✅ PASS |
| 3 | Compile | `npm run compile` | Solc 0.8.19 compile sukses | ✅ PASS |
| 4 | Ganache Ping | `npm run ganache:ping` | RPC 7545 terhubung, Chain ID 1337, Network ID 5777 | ✅ PASS |
| 5 | Migrate | `npm run migrate` | Deployed ke `0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab` | ✅ PASS |
| 6 | Log deploy | `node scripts/logDeployment.js` | Tercatat di `deployments/deployment_2026-11-11T06-13-02-194Z.json` | ✅ PASS |
| 7 | Test | `npm test` | **16 passing (0 failing)** | ✅ PASS |
| 8 | ABI match | SHA-256 verifikasi | `23a73d...606f` cocok 100% (`ABI_MATCH=true`) | ✅ PASS |

---

## 8. Evaluasi Kriteria Penerimaan `W2-FRL-05`

| Kriteria | Target | Hasil Pengujian Mesin Anggota Lain | Status |
|---|---|---|---|
| `npm ci` tanpa error | Selesai bersih | 1.262 package terinstall bersih | ✅ PASS |
| `npm run compile` | `Compiled successfully` | Berhasil solc 0.8.19 | ✅ PASS |
| `npm run migrate` | Address tercipta & tercatat | `0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab` | ✅ PASS |
| `npm test` | Passing seluruh suite aktif | 16 passing (0 gagal) | ✅ PASS |
| ABI SHA-256 identik | Hash cocok | Identik (`ABI_MATCH=true`) | ✅ PASS |

**Kesimpulan:** Seluruh kriteria keberhasilan pengujian clean clone di lingkungan anggota lain telah terpenuhi. Issue `W2-FRL-05` resmi **SELESAI**, dan Exit Gate Minggu 2 ditutup **100%**.

---

## 9. Troubleshooting

| Gejala | Penyebab | Solusi |
|---|---|---|
| `Could not connect to your Ethereum client` | Ganache belum jalan | Jalankan Ganache, cek port 7545 |
| `EADDRINUSE :7545` | Instance Ganache lain aktif | Hentikan proses lama |
| `Cannot find module '../binaries/uws...'` | Binary native tidak cocok Node | Non-fatal; biarkan fallback |
| Address berbeda dari dokumen | Nonce berbeda | Wajar; catat address baru |
| `config/contract_address.txt` berubah setelah test | `npx truffle test` menjalankan migration | Gunakan `npm test` |
| Hash ABI berbeda | Source kontrak berbeda / commit salah | Cek `git log -1` dan `git status` |

---

## 10. Tautan

- Baseline environment: [`00_ENVIRONMENT_BASELINE.md`](00_ENVIRONMENT_BASELINE.md)
- Log mentah: [`evidence/reproduction_clean_clone.log`](evidence/reproduction_clean_clone.log)
- Setup guide: [`../PANDUAN_SETUP_DAN_OPERASIONAL.md`](../PANDUAN_SETUP_DAN_OPERASIONAL.md)
- Deployment log: [`../../blockchain/deployments/DEPLOYMENTS.md`](../../blockchain/deployments/DEPLOYMENTS.md)
