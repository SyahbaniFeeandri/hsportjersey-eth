# 01 — UI WIREFRAMES: WALLET, TX STATUS, PROOF & DASHBOARD

**Issue:** `W2-IHS-02`
**Pemilik:** Ihsanul Farrel
**Reviewer:** Yasmin, Luis
**Status:** Low-fidelity wireframe v1.0
**Tanggal:** 10 November 2026

---

## 1. Prinsip Desain

1. **Status finansial harus selalu terlihat.** Pembeli tahu kapan dana terkunci, kapan escrow dirilis, dan kapan transaksi gagal.
2. **Satu komponen wallet dipakai ulang** di checkout kripto, order detail, dan halaman proof.
3. **Error tidak boleh berupa pesan mentah.** Revert contract diterjemahkan ke bahasa Indonesia.
4. **Mobile-first.** Semua layar harus nyaman di lebar 360 px.
5. **Akses berbasis peran.** Proof page hanya untuk pemilik order atau admin.
6. **Tidak ada data sensitif di UI publik.** Hanya address publik, tx hash, dan status.

---

## 2. Peta Layar

| Layar | File target | Aktor | Sumber data |
|---|---|---|---|
| Wallet widget | `includes/wallet-widget.php`, `assets/js/web3-wallet.js` | Pembeli, admin | MetaMask + RPC |
| Transaction status | `assets/js/tx-status.js` | Pembeli, admin | Receipt/event |
| Blockchain proof page | `order-proof.php` | Pembeli, admin | `orders` + `blockchain_transactions` |
| Verification dashboard | `admin/verification.php` | Admin | `orders`, `blockchain_transactions` |
| Performance dashboard | `admin/performance.php` | Admin, dosen | `v_benchmark_summary` |

---

## 3. Wallet Widget

### 3.1 Anatomi

```text
┌──────────────────────────────────────────────────────────┐
│  🦊  Dompet Blockchain                        [status]   │
│                                                          │
│  Alamat   0x90F8...c9C1                          [copy]  │
│  Saldo    1.000 ETH                                      │
│  Network  Ganache Local · Chain 1337                     │
│                                                          │
│  [ Ganti Akun ]                         [ Putuskan ]     │
└──────────────────────────────────────────────────────────┘
```

### 3.2 State

| State | Tampilan | Aksi utama |
|---|---|---|
| `no-metamask` | Banner: "MetaMask belum terpasang" + link install | Buka metamask.io |
| `disconnected` | Tombol besar **Connect Wallet** | `eth_requestAccounts` |
| `connecting` | Spinner + "Menghubungkan..." | — |
| `wrong-network` | Peringatan kuning: "Jaringan salah" + tombol **Switch ke Ganache** | `wallet_switchEthereumChain` |
| `connected` | Alamat terpotong, saldo, badge network hijau | Ganti akun / putuskan |
| `error` | Pesan error + tombol **Coba lagi** | Retry |

### 3.3 Wireframe state `wrong-network`

```text
┌──────────────────────────────────────────────────────────┐
│  ⚠️  Jaringan tidak sesuai                               │
│                                                          │
│  MetaMask terhubung ke Chain 1.                          │
│  Aplikasi membutuhkan Ganache Local (Chain 1337).        │
│                                                          │
│              [ Switch ke Ganache Local ]                 │
└──────────────────────────────────────────────────────────┘
```

### 3.4 Aturan

1. Widget tidak pernah meminta private key.
2. `chainId` dicek **sebelum** setiap transaksi, bukan hanya saat connect.
3. Perubahan akun MetaMask (`accountsChanged`) memicu refresh saldo dan role.
4. Perubahan network (`chainChanged`) memicu reload halaman.
5. Alamat ditampilkan dalam format `0x90F8...c9C1`.

---

## 4. Transaction Status

### 4.1 Komponen

```text
┌──────────────────────────────────────────────────────────┐
│  Status Transaksi                                        │
│                                                          │
│  ⏳  Menunggu konfirmasi block                           │
│                                                          │
│  Tx Hash   0x13d5...2841                         [copy]  │
│  Network   Ganache Local · Chain 1337                     │
│                                                          │
│  [ Lihat di Explorer ]              [ Tutup ]            │
└──────────────────────────────────────────────────────────┘
```

### 4.2 Tujuh state

| State | Ikon | Pesan | Tombol |
|---|---|---|---|
| `idle` | — | — | — |
| `awaiting-approval` | 🦊 | "Konfirmasi transaksi di MetaMask" | — |
| `pending` | ⏳ | "Menunggu konfirmasi block" | Lihat tx |
| `confirmed` | ✅ | "Transaksi berhasil" | Lihat proof |
| `failed` | ❌ | "Transaksi gagal" | Coba lagi |
| `reverted` | ⚠️ | Alasan revert yang dipetakan | Tutup |
| `timeout` | ⏱️ | "Belum terkonfirmasi setelah 60 detik" | Cek ulang |

### 4.3 Mapping revert ke bahasa Indonesia

| Revert contract | Pesan UI |
|---|---|
| `Not owner` | "Hanya pemilik toko yang boleh melakukan aksi ini." |
| `Not operator` | "Akun ini bukan operator." |
| `Not buyer` | "Hanya pembeli order ini yang boleh konfirmasi." |
| `Amount must be > 0` | "Jumlah pembayaran harus lebih dari 0." |
| `Incorrect amount` | "Nilai ETH tidak sama dengan total order." |
| `Duplicate order hash` | "Order ini sudah pernah dicatat." |
| `Invalid state transition` | "Status order tidak mengizinkan aksi ini." |
| `Payment already recorded` | "Pembayaran sudah pernah dicatat." |
| `Already released` | "Escrow sudah dirilis." |
| `Order not found` | "Order tidak ditemukan di blockchain." |
| `User rejected` (4001) | "Transaksi dibatalkan di MetaMask." |

### 4.4 Aturan

1. Tombol submit **disabled** setelah diklik untuk mencegah double submit.
2. `pending` melakukan polling receipt setiap 3 detik, maksimal 60 detik.
3. `confirmed` hanya muncul jika `receipt.status == 1`.
4. `reverted` menampilkan alasan yang sudah dipetakan, bukan stack trace.
5. Semua state dapat diuji tanpa menunggu jaringan nyata (mock receipt).

---

## 5. Blockchain Proof Page

### 5.1 Wireframe desktop

```text
┌────────────────────────────────────────────────────────────────────┐
│  H-Sport · Bukti Blockchain                        [Kembali]       │
├────────────────────────────────────────────────────────────────────┤
│  Order #INV-2026-0042                                              │
│  Tanggal 10 Nov 2026 · Total Rp 450.000 · Status  Shipped          │
│  Pembeli 0x90F8...c9C1                                             │
├────────────────────────────────────────────────────────────────────┤
│  Status Verifikasi         [ ✅ VERIFIED ]                         │
│  Terakhir dicek            10 Nov 2026 09:15                       │
├────────────────────────────────────────────────────────────────────┤
│  BUKTI TRANSAKSI                                                   │
│  Tx Hash          0x13d5...2841                            [copy]  │
│  Block Number     2                                                │
│  Contract         0xe78A...a8Ab                            [copy]  │
│  Wallet           0x90F8...c9C1                            [copy]  │
│  On-chain Order   7                                                │
│  External Hash    0xabc1...9f02                            [copy]  │
├────────────────────────────────────────────────────────────────────┤
│  TIMELINE                                                          │
│  ● OrderCreated        block 2   10 Nov 08:55                      │
│  ● PaymentRecorded     block 3   10 Nov 09:01                      │
│  ● OrderStatusUpdated  block 4   10 Nov 09:05                      │
│  ● OrderShipped        block 5   10 Nov 09:10                      │
│  ○ OrderCompleted      menunggu konfirmasi pembeli                 │
├────────────────────────────────────────────────────────────────────┤
│  [ Buka Tx di Explorer ]                    [ Verifikasi Ulang ]   │
└────────────────────────────────────────────────────────────────────┘
```

### 5.2 Wireframe mobile

```text
┌──────────────────────────────┐
│  ← Bukti Blockchain          │
├──────────────────────────────┤
│  #INV-2026-0042              │
│  Rp 450.000 · Shipped        │
│  [ ✅ VERIFIED ]             │
├──────────────────────────────┤
│  Tx Hash                     │
│  0x13d5...2841      [copy]   │
│  Block 2 · Order 7           │
├──────────────────────────────┤
│  Timeline                    │
│  ● Created     08:55         │
│  ● Paid        09:01         │
│  ● Shipped     09:10         │
│  ○ Completed   menunggu      │
├──────────────────────────────┤
│  [ Verifikasi Ulang ]        │
└──────────────────────────────┘
```

### 5.3 Kondisi khusus

| Kondisi | Tampilan |
|---|---|
| Order belum on-chain | Card abu-abu: "Belum ada bukti on-chain" + tombol Bayar |
| Tx masih pending | Badge kuning `PENDING` + tombol Cek Ulang |
| Verifikasi cocok | Badge hijau `VERIFIED` |
| Verifikasi beda | Badge merah `MISMATCH` + daftar field yang berbeda |
| RPC mati | Banner merah "Blockchain tidak tersedia" |
| Order milik orang lain | HTTP 403 + pesan akses ditolak |

### 5.4 Aturan akses

1. Pembeli hanya dapat membuka order miliknya.
2. Admin dapat membuka semua order.
3. Halaman tidak menampilkan nama, email, alamat, atau file desain.
4. Semua field proof diambil dari database, bukan dari query string.

---

## 6. Verification Dashboard (Admin)

### 6.1 Wireframe

```text
┌──────────────────────────────────────────────────────────────────────────────┐
│  Admin · Verifikasi Blockchain                                               │
├──────────────────────────────────────────────────────────────────────────────┤
│  Ringkasan:  Total 120   ✅ Verified 104   ⚠️ Mismatch 12   ⏳ Pending 4      │
├──────────────────────────────────────────────────────────────────────────────┤
│  Filter: [Semua ▼] [VERIFIED] [MISMATCH] [PENDING]        [ Verifikasi Batch ]│
├──────────────┬──────────┬────────────┬──────────┬──────────┬────────┬─────────┤
│ Order        │ Pembeli  │ Total      │ DB       │ Chain    │ Status │ Aksi    │
├──────────────┼──────────┼────────────┼──────────┼──────────┼────────┼─────────┤
│ INV-2026-001 │ 0x90...  │ Rp 450.000 │ paid     │ Paid     │ ✅     │ [Detail]│
│ INV-2026-002 │ 0xFF...  │ Rp 320.000 │ paid     │ Paid     │ ⚠️     │ [Detail]│
│ INV-2026-003 │ 0x22...  │ Rp 275.000 │ pending  │ —        │ ⏳     │ [Detail]│
└──────────────┴──────────┴────────────┴──────────┴──────────┴────────┴─────────┘
```

### 6.2 Detail mismatch

```text
┌──────────────────────────────────────────────────────────┐
│  ⚠️ MISMATCH — INV-2026-002                              │
├──────────────────────────────────────────────────────────┤
│  Field          Database        Blockchain               │
│  total_amount   Rp 320.000      Rp 350.000               │
│  status         paid            Paid                     │
│  wallet         0xFFcf...09f0   0xFFcf...09f0            │
├──────────────────────────────────────────────────────────┤
│  [ Verifikasi Ulang ]                    [ Tutup ]       │
└──────────────────────────────────────────────────────────┘
```

### 6.3 Aturan

1. Tombol **Verifikasi Batch** membuat satu baris `job_batches`.
2. Filter tidak menghapus data; hanya menyaring tampilan.
3. `MISMATCH` wajib menampilkan field yang berbeda.
4. Aksi verifikasi hanya untuk admin yang login.
5. Status `PENDING` tidak dianggap gagal.

---

## 7. Performance Dashboard (Admin/Dosen)

### 7.1 Wireframe

```text
┌──────────────────────────────────────────────────────────────────────────────┐
│  Admin · Performa Verifikasi Paralel                                         │
├──────────────────────────────────────────────────────────────────────────────┤
│  Filter: Dataset [D1000 ▼]  Mode [ALGORITHM ▼]  Baseline [Auto ▼]            │
├──────────────────────────────────────────────────────────────────────────────┤
│  Execution Time (ms)                                                         │
│  1.000 ┤ ██████████████████████████████  sequential                           │
│    750 ┤ ██████████████████████                                          2w  │
│    500 ┤ ███████████████                                                 4w  │
│    250 ┤ ████████                                                        8w  │
│        └──────────────────────────────────────────────────────                │
├──────────────────────────────────────────────────────────────────────────────┤
│  Worker │ Duration │ Speedup │ Efficiency │ Throughput │ Correctness         │
│    1    │ 1.000 ms │ 1,00x   │ 100%       │ 1.000/s    │ MATCH               │
│    2    │   560 ms │ 1,79x   │ 89%        │ 1.786/s    │ MATCH               │
│    4    │   330 ms │ 3,03x   │ 76%        │ 3.030/s    │ MATCH               │
│    8    │   250 ms │ 4,00x   │ 50%        │ 4.000/s    │ MATCH               │
└──────────────────────────────────────────────────────────────────────────────┘
```

### 7.2 Aturan

1. Sumber data hanya `v_benchmark_summary`.
2. `Correctness` wajib `MATCH`; jika `DIFFERENT`, grafik diberi peringatan merah.
3. Grafik speedup menampilkan garis ideal `y = p`.
4. Angka dibulatkan konsisten (2 desimal).
5. Halaman menampilkan nama mesin, versi PHP/MySQL, dan dataset hash.

---

## 8. Komponen & Token

| Token | Nilai | Catatan |
|---|---|---|
| Warna sukses | `#16A34A` | `VERIFIED`, `confirmed` |
| Warna peringatan | `#F59E0B` | `PENDING`, `MISMATCH` ringan |
| Warna bahaya | `#DC2626` | `FAILED`, `MISMATCH`, RPC mati |
| Warna netral | `#6B7280` | Off-chain, data sekunder |
| Radius kartu | `12px` | Konsisten dengan UI existing |
| Font | Sistem existing | Tidak menambah font baru |
| Lebar konten | `max-width: 1120px` | Desktop |
| Breakpoint | `768px`, `480px` | Tablet, mobile |

---

## 9. Acceptance Criteria UI

- [ ] Wallet widget mendeteksi MetaMask, chain, akun, dan saldo.
- [ ] Wallet widget menolak transaksi jika network salah.
- [ ] Tx status membedakan 7 state.
- [ ] Revert contract tampil dalam bahasa Indonesia.
- [ ] Proof page hanya bisa diakses pemilik order atau admin.
- [ ] Proof page menampilkan 6 field proof + timeline event.
- [ ] Verification dashboard menampilkan `VERIFIED`/`MISMATCH`/`PENDING`.
- [ ] Mismatch menampilkan detail field.
- [ ] Performance dashboard menampilkan speedup, efficiency, throughput, correctness.
- [ ] Semua layar nyaman di lebar 360 px.

---

## 10. Tautan

- Desain integrasi & API: [`../week1/05_INTEGRATION_AND_UI_DESIGN.md`](../week1/05_INTEGRATION_AND_UI_DESIGN.md)
- State machine: [`../week1/01_ON_OFF_CHAIN_AND_STATE.md`](../week1/01_ON_OFF_CHAIN_AND_STATE.md)
- Test plan: [`../week1/07_TEST_PLAN.md`](../week1/07_TEST_PLAN.md)
- Baseline environment: [`00_ENVIRONMENT_BASELINE.md`](00_ENVIRONMENT_BASELINE.md)
