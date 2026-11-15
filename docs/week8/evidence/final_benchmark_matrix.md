# Benchmark Matrix — Sequential vs Parallel

**Dibuat:** 2026-11-13 03:35:12
**Latensi simulasi:** 5000 us/order (Mode A)

| Dataset | P | Baseline (ms) | Wall (ms) | Speedup | Efficiency | Throughput (order/s) | Checksum | Gate |
|---|---:|---:|---:|---:|---:|---:|---|---|
| D100 | 1 | 529 | 629 | 0,841 | 84,1% | 158,98 | `6ae585e5f5bd…` | MATCH |
| D100 | 2 | 529 | 385 | 1,374 | 68,7% | 259,74 | `6ae585e5f5bd…` | MATCH |
| D100 | 4 | 529 | 297 | 1,781 | 44,5% | 336,70 | `6ae585e5f5bd…` | MATCH |
| D100 | 8 | 529 | 305 | 1,734 | 21,7% | 327,87 | `6ae585e5f5bd…` | MATCH |
| D500 | 1 | 2647 | 2726 | 0,971 | 97,1% | 183,42 | `61cfe1d0d976…` | MATCH |
| D500 | 2 | 2647 | 1432 | 1,849 | 92,4% | 349,16 | `61cfe1d0d976…` | MATCH |
| D500 | 4 | 2647 | 817 | 3,240 | 81,0% | 612,00 | `61cfe1d0d976…` | MATCH |
| D500 | 8 | 2647 | 567 | 4,668 | 58,4% | 881,83 | `61cfe1d0d976…` | MATCH |
| D1000 | 1 | 5271 | 5373 | 0,981 | 98,1% | 186,12 | `1258b0c3790f…` | MATCH |
| D1000 | 2 | 5271 | 2758 | 1,911 | 95,6% | 362,58 | `1258b0c3790f…` | MATCH |
| D1000 | 4 | 5271 | 1493 | 3,531 | 88,3% | 669,79 | `1258b0c3790f…` | MATCH |
| D1000 | 8 | 5271 | 917 | 5,748 | 71,9% | 1.090,51 | `1258b0c3790f…` | MATCH |
| D5000 | 1 | 26387 | 26569 | 0,993 | 99,3% | 188,19 | `972e6a4d1fe2…` | MATCH |
| D5000 | 2 | 26387 | 13379 | 1,972 | 98,6% | 373,72 | `972e6a4d1fe2…` | MATCH |
| D5000 | 4 | 26387 | 6798 | 3,882 | 97,0% | 735,51 | `972e6a4d1fe2…` | MATCH |
| D5000 | 8 | 26387 | 3606 | 7,318 | 91,5% | 1.386,58 | `972e6a4d1fe2…` | MATCH |

Checksum lengkap tersedia di `benchmark_matrix.json`. Seluruh baris di atas memakai dataset deterministik yang sama dengan baseline sequential.
