# 03 — ERROR HANDLING, UX, DAN UAT INTEGRASI

**Issue:** `W5-LUI-10`, `W5-IHS-05`, `W5-YAS-09`
**Pemilik:** Luis, Ihsanul, Yasmin
**Status:** ✅ Selesai (implementasi + skenario UAT)
**Tanggal:** 12 November 2026

---

## 1. Matriks Error Backend

| Kondisi | Deteksi | Respons API | Perilaku UI |
|---|---|---|---|
| RPC mati / tidak bisa dihubungi | `curl_exec` gagal di `BlockchainClient` | HTTP 503 `RPC_UNAVAILABLE` + hint | Halaman bukti tetap tampil dari DB; modal menampilkan pesan RPC |
| Chain ID salah | `healthCheck` / pembacaan state | Health `chain_id` ≠ 1337 | UI meminta pindah ke Ganache 1337 |
| Kontrak tidak ada bytecode | `eth_getCode` kosong | Health `contract_deployed=false` | Status wallet “kontrak belum siap” |
| Order tidak ada di DB | `ProofRepository` | HTTP 404 `ORDER_NOT_FOUND` | Pesan “pesanan tidak ditemukan” |
| Order belum punya ID chain | `VerificationService` | `PENDING` + `NO_BLOCKCHAIN_ID` | Bukti on-chain disembunyikan |
| Tx belum mined | `getReceipt` null | HTTP 202 `TX_NOT_FOUND` | Minta pengguna menunggu konfirmasi |
| Tx gagal (revert) | Receipt `status=0` | HTTP 400 `TX_FAILED` | Tampilkan tx hash + saran ulangi |
| Revert saat cancel | Validasi kontrak | `NOT_CANCELLED_ON_CHAIN` | Pesan state on-chain terkini |
| Tx hash duplikat | `getOrderByTxHash` | HTTP 409 `DUPLICATE_TX` | Arahkan ke pesanan pemilik tx |
| Alasan cancel > 64 byte | Validasi UI + API | HTTP 400 `REASON_TOO_LONG` | Counter byte merah, tombol tidak dikirim |
| Aksi tidak dikenal | `switch` default | HTTP 400 `INVALID_ACTION` | — |

Kode backend: `api/blockchain-api.php` (catch `BlockchainException` → 503),
`api/services/BlockchainClient.php`, `api/services/OrderIntegrationService.php`.

---

## 2. Status Transaksi di UI (W5-IHS-05)

| Tahap | Tampilan |
|---|---|
| Wallet belum terhubung | Tombol connect + badge status |
| Jaringan salah | Kotak peringatan + tombol switch ke `0x539` |
| Menunggu konfirmasi MetaMask | Spinner “Menunggu MetaMask…” |
| Transaksi dikirim | Spinner “Menyimpan bukti…” + tx hash |
| Sukses | Alert sukses, tx hash, jumlah refund (cancel), reload otomatis |
| Ditolak pengguna | “Transaksi dibatalkan di MetaMask” |
| Gagal / revert | Alert error berisi alasan; state dibaca ulang |

Implementasi: `crypto-payment.php` (pembayaran), `order-detail.php` (cancel),
`blockchain-proof.php` (verifikasi bukti).

---

## 3. Prinsip UX

1. **Jangan menakut-nakuti tanpa jalan keluar** — setiap error menyebut langkah
   berikutnya (pindah jaringan, tunggu konfirmasi, hubungi operator).
2. **State on-chain adalah sumber kebenaran** untuk tombol cancel, bukan status DB.
3. **Bukti selalu bisa dilihat** meski RPC sedang mati (data DB tetap dirender).
4. **Konfirmasi eksplisit** sebelum aksi permanen (cancel) + peringatan modal.
5. **Tidak ada double-submit**: tombol dinonaktifkan selama transaksi.

---

## 4. Skenario UAT Integrasi

Prasyarat: Ganache 1337, kontrak aktif, MySQL berisi order kripto, MetaMask
terpasang.

| UAT | Skenario | Ekspektasi |
|---|---|---|
| UAT-INT-01 | Buka halaman bukti order yang sudah dibayar | Tx hash, block, wallet, contract tampil; status verifikasi terlihat |
| UAT-INT-02 | Buka order milik pengguna lain (ubah `order_id`) | Dialihkan ke daftar pesanan dengan pesan error |
| UAT-INT-03 | Matikan Ganache lalu buka halaman bukti | Data DB tetap tampil + peringatan state on-chain tidak terbaca |
| UAT-INT-04 | Nyalakan Ganache, klik **Verifikasi Ulang** | Status berubah `VERIFIED`; reload menampilkan badge hijau |
| UAT-INT-05 | Manipulasi nominal di DB lalu verifikasi | Badge `MISMATCH` + daftar perbedaan |
| UAT-INT-06 | Bayar dengan jaringan MetaMask salah | UI meminta switch ke chain 1337 |
| UAT-INT-07 | Tolak transaksi di MetaMask | Pesan “dibatalkan di MetaMask”, tidak ada perubahan status |
| UAT-INT-08 | Cancel dengan alasan > 64 byte | Ditolak sebelum transaksi, counter merah |

Status eksekusi: skenario siap; eksekusi browser penuh menunggu sesi login
interaktif. Komponen backend-nya sudah dibuktikan lewat INT-01..05 (5/5 PASS).
