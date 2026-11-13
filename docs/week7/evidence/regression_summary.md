# Regression 21 Test — Ringkasan

**Dibuat:** 2026-11-13 03:46:25
**Hasil:** 21/21 PASS

| # | Test | Requirement | Status | Detail |
|---:|---|---|---|---|
| 1 | `SC-01` inisialisasi contract, owner, dan akses owner (133ms) | REQ kontrak | PASS | inisialisasi contract, owner, dan akses owner (133ms) |
| 2 | `SC-02` createOrder valid — escrow, state Created, event (88ms) | REQ kontrak | PASS | createOrder valid — escrow, state Created, event (88ms) |
| 3 | `SC-03` createOrder amount tidak valid revert | REQ kontrak | PASS | createOrder amount tidak valid revert |
| 4 | `SC-04` duplicate externalOrderHash revert (78ms) | REQ kontrak | PASS | duplicate externalOrderHash revert (78ms) |
| 5 | `SC-05` getOrder ID valid mengembalikan data benar (92ms) | REQ kontrak | PASS | getOrder ID valid mengembalikan data benar (92ms) |
| 6 | `SC-06` getOrder ID tidak ada revert | REQ kontrak | PASS | getOrder ID tidak ada revert |
| 7 | `SC-07` access control menolak non-operator, non-buyer, dan non-owner (115ms) | REQ kontrak | PASS | access control menolak non-operator, non-buyer, dan non-owner (115ms) |
| 8 | `SC-08` recordPayment valid, event, dan double payment ditolak (246ms) | REQ kontrak | PASS | recordPayment valid, event, dan double payment ditolak (246ms) |
| 9 | `SC-09` transisi valid dan shipOrder memancarkan event (208ms) | REQ kontrak | PASS | transisi valid dan shipOrder memancarkan event (208ms) |
| 10 | `SC-10` transisi tidak sah ditolak dan state tidak berubah (342ms) | REQ kontrak | PASS | transisi tidak sah ditolak dan state tidak berubah (342ms) |
| 11 | `SC-11` cancel valid, refund, unauthorized, terminal, dan batas reason (963ms) | REQ kontrak | PASS | cancel valid, refund, unauthorized, terminal, dan batas reason (963ms) |
| 12 | `SC-12` confirmReceived merilis escrow sekali dan double release ditolak (556ms) | REQ kontrak | PASS | confirmReceived merilis escrow sekali dan double release ditolak (556ms) |
| 13 | `INT-01` Koneksi RPC Backend ke Ganache Node | REQ integrasi | PASS | Koneksi RPC Backend ke Ganache Node |
| 14 | `INT-02` Sinkronisasi Order DB Lokal dengan Smart Contract | REQ integrasi | PASS | Sinkronisasi Order DB Lokal dengan Smart Contract |
| 15 | `INT-03` Pengambilan Transaction Hash, Receipt & Status Blockchain | REQ integrasi | PASS | Pengambilan Transaction Hash, Receipt & Status Blockchain |
| 16 | `INT-04` Penyimpanan Bukti Kriptografis (Tx Hash, Block, Wallet) ke DB | REQ integrasi | PASS | Penyimpanan Bukti Kriptografis (Tx Hash, Block, Wallet) ke DB |
| 17 | `INT-05` Verifikasi Konsistensi Data Status DB vs Smart Contract (VERIFIED / MISMATCH) | REQ integrasi | PASS | Verifikasi Konsistensi Data Status DB vs Smart Contract (VERIFIED / MISMATCH) |
| 18 | `PAR-01` Sequential baseline: waktu & agregasi | REQ-13 | PASS | checks=4 checksum=61cfe1d0d9769ea1 |
| 19 | `PAR-02` Parallel partition/reduce = sequential | REQ-13 | PASS | checks=18 runs=4 |
| 20 | `PAR-03` Throughput, speedup, efficiency | REQ-14 | PASS | checks=5 rows=16 |
| 21 | `PAR-04` Ketahanan konkurensi & integritas agregasi | REQ-14, NFR-01 | PASS | checks=4 |
