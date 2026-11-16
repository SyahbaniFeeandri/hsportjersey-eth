# 02 — SPESIFIKASI FINAL KONTRAK `HSportStore` (V1/V2)

**Issue:** `W2-ARB-02`, `W2-AGU-02`, `W2-AGU-03`
**Pemilik:** Arby Putra, Agustina Elsi Bunga
**Reviewer:** Muhammad Farrel, Luis, Kenjiro, Feeandri
**Status:** Spesifikasi final v1.0 — siap implementasi Minggu 3
**Basis:** [`../week1/04_CONTRACT_AND_PARALLEL_DESIGN.md`](../week1/04_CONTRACT_AND_PARALLEL_DESIGN.md), [`../week1/01_ON_OFF_CHAIN_AND_STATE.md`](../week1/01_ON_OFF_CHAIN_AND_STATE.md)
**Tanggal:** 10 November 2026

> **Batas dokumen.** Ini adalah spesifikasi, bukan kode. Implementasi dilakukan Minggu 3 (V1) dan Minggu 4 (V2). Sesuai keputusan **D2**, kontrak existing `HSportStore.sol` **diperluas**, tidak ditulis ulang menjadi kontrak baru. Pembekuan interface final dilakukan di akhir Minggu 3.

---

## 1. Ringkasan Perubahan dari V0

| Elemen | V0 (sekarang) | Target V1/V2 |
|---|---|---|
| Enum | 4 state | 8 state |
| `Order` | 7 field | 9 field (`externalOrderHash`, `paymentRecorded`) |
| `PaymentEscrow` | 4 field | 5 field (`refunded`) |
| Mapping | `orders`, `escrows` | + `orderIdByHash`, `operators` |
| Access control | `onlyOwner`, `onlyBuyer` | + `onlyOperator`, `notFinal` |
| Fungsi | 5 | 13 eksternal |
| Event | 4 | 9 |
| Duplicate guard | Tidak ada | `orderIdByHash` |
| Cancel/refund | Tidak ada | `cancelOrder` + `EscrowRefunded` |

---

## 2. Keputusan Desain yang Dikunci

Lima pertanyaan terbuka dari review Minggu 1 (Q1–Q5) ditutup di sini agar implementasi tidak ambigu.

| # | Pertanyaan | Keputusan final | Alasan |
|---|---|---|---|
| Q1 | Apakah `AwaitingPayment` diimplementasikan? | **Dipertahankan di enum, tanpa fungsi khusus.** State ini hanya dapat dicapai lewat `updateOrderStatus(id, AwaitingPayment)` dari `Created`. Mode kripto tidak memakainya. | Menjaga 8 state sesuai state machine, tanpa menambah fungsi yang tidak dipakai UI. |
| Q2 | Siapa boleh cancel pada `Paid`? | **Operator dan owner.** Pembeli hanya boleh pada `Created` dan `AwaitingPayment`. | Dana sudah masuk escrow; pembatalan butuh otoritas toko. |
| Q3 | Refund: transfer langsung atau pull-pattern? | **Transfer langsung (push)** dengan urutan *checks → effects → interactions*. | Skala demo kecil, tidak perlu mekanisme klaim; urutan state-first mencegah reentrancy. |
| Q4 | Apakah `shipOrder` dipertahankan? | **Ya, sebagai alias tipis** untuk transisi `ReadyToShip → Shipped`. | Menjaga kompatibilitas frontend dan test lama. |
| Q5 | Bagaimana order lama tanpa proof on-chain? | **Ditangani di UI**, bukan di kontrak (kartu "Belum ada bukti on-chain"). | Kontrak tidak menyimpan riwayat order off-chain. |

**Konsekuensi Q1:** fungsi `markAwaitingPayment` dari draft Minggu 1 **tidak diimplementasikan**. Transisi T02 tetap berlaku, dieksekusi melalui `updateOrderStatus`.

---

## 3. Enum

```solidity
enum OrderStatus {
    Created,          // 0
    AwaitingPayment,  // 1
    Paid,             // 2
    InProduction,     // 3
    ReadyToShip,      // 4
    Shipped,          // 5
    Completed,        // 6
    Cancelled         // 7
}
```

**Aturan pembekuan:** urutan dan nilai numerik **tidak boleh berubah** setelah V1 dideploy. Frontend, backend, dan dataset benchmark memakai angka ini. Menambah state baru = ABI break = wajib versi baru.

---

## 4. Struct & Storage Layout

```solidity
struct Order {
    uint256 id;
    bytes32 externalOrderHash;
    address buyer;
    uint256 totalAmount;      // wei
    OrderStatus status;       // uint8
    bool paymentRecorded;
    uint256 createdAt;
    uint256 updatedAt;
    string orderNumber;
}

struct PaymentEscrow {
    uint256 orderId;
    address buyer;
    uint256 amount;           // wei
    bool released;
    bool refunded;
}
```

### 4.1 Perkiraan layout slot

| Slot | `Order` | `PaymentEscrow` |
|---:|---|---|
| 0 | `id` (32 B) | `orderId` (32 B) |
| 1 | `externalOrderHash` (32 B) | `buyer` (20 B) |
| 2 | `buyer` (20 B) | `amount` (32 B) |
| 3 | `totalAmount` (32 B) | `released` + `refunded` (2 B, dipaket) |
| 4 | `status` + `paymentRecorded` (2 B, dipaket) | — |
| 5 | `createdAt` (32 B) | — |
| 6 | `updatedAt` (32 B) | — |
| 7 | `orderNumber` (dynamic) | — |

**Catatan:** `status` dan `paymentRecorded` sengaja berurutan agar dipaket dalam satu slot (hemat gas). `Order` menempati 8 slot.

### 4.2 Field wajib vs opsional

| Field | Wajib? | Keterangan |
|---|---|---|
| `externalOrderHash` | ✅ | Kunci idempotensi dan jembatan DB ↔ chain |
| `paymentRecorded` | ✅ | Mencegah pencatatan pembayaran ganda |
| `orderNumber` | ✅ | Untuk tampilan, bukan untuk pencarian |
| `createdAt` / `updatedAt` | ✅ | Audit trail immutable |

---

## 5. State Variable & Mapping

```solidity
address public owner;
uint256 public orderCount;

mapping(uint256 => Order) public orders;
mapping(uint256 => PaymentEscrow) public escrows;
mapping(bytes32 => uint256) public orderIdByHash;
mapping(address => bool) public operators;
```

| Nama | Tipe | Fungsi | Catatan keamanan |
|---|---|---|---|
| `owner` | `address` | Pemilik toko, satu-satunya yang mengelola operator | Tidak dapat diubah (tanpa `transferOwnership` di V1) |
| `orderCount` | `uint256` | Penghitung ID; ID mulai 1 | ID 0 = tidak valid |
| `orders` | mapping | Data order | Public getter dipertahankan untuk kompatibilitas |
| `escrows` | mapping | Dana yang dikunci | Hanya diubah oleh `recordPayment`, `confirmReceived`, `cancelOrder` |
| `orderIdByHash` | mapping | Cegah hash ganda | Nilai 0 berarti belum dipakai |
| `operators` | mapping | Daftar akun operator | Owner selalu dianggap berwenang tanpa perlu didaftarkan |

**Keputusan:** `owner` tidak bisa dipindahkan di V1/V2. Jika dibutuhkan, `transferOwnership` masuk backlog pasca-freeze dan merupakan penambahan fungsi (bukan perubahan ABI yang merusak).

---

## 6. Modifier

```solidity
modifier onlyOwner();
modifier onlyOperator();
modifier onlyBuyer(uint256 orderId);
modifier orderExists(uint256 orderId);
modifier notFinal(uint256 orderId);
```

| Modifier | Aturan | Revert message |
|---|---|---|
| `onlyOwner` | `msg.sender == owner` | `Not owner` |
| `onlyOperator` | `msg.sender == owner \|\| operators[msg.sender]` | `Not operator` |
| `onlyBuyer(orderId)` | `orders[orderId].buyer == msg.sender` | `Not buyer` |
| `orderExists(orderId)` | `orderId > 0 && orderId <= orderCount` | `Order not found` |
| `notFinal(orderId)` | status bukan `Completed`/`Cancelled` | `Order is final` |

**Catatan:** `onlyOperator` mengizinkan owner tanpa registrasi eksplisit. Ini menghindari kebutuhan `addOperator(owner)` saat deploy dan konsisten dengan matriks aktor Minggu 1.

---

## 7. Event (Final)

```solidity
event OrderCreated(uint256 indexed orderId, address indexed buyer, bytes32 indexed externalOrderHash, uint256 amount, string orderNumber);
event PaymentRecorded(uint256 indexed orderId, address indexed operator, uint256 amount);
event OrderStatusUpdated(uint256 indexed orderId, OrderStatus oldStatus, OrderStatus newStatus, address indexed operator);
event OrderShipped(uint256 indexed orderId, address indexed operator);
event OrderCompleted(uint256 indexed orderId, address indexed buyer);
event EscrowReleased(uint256 indexed orderId, address indexed seller, uint256 amount);
event OrderCancelled(uint256 indexed orderId, address indexed actor, string reason);
event EscrowRefunded(uint256 indexed orderId, address indexed buyer, uint256 amount);
event OperatorUpdated(address indexed operator, bool active);
```

### 7.1 Aturan event

1. Semua event yang mengubah status **wajib** dipancarkan setelah state diubah.
2. `OrderStatusUpdated` dipancarkan untuk setiap perubahan status, termasuk yang dilakukan lewat `shipOrder` dan `cancelOrder`.
3. `EscrowReleased` dan `EscrowRefunded` selalu berpasangan dengan event status terkait.
4. `operator` pada event = `msg.sender`, bukan alamat yang diubah.
5. Parameter `reason` pada `OrderCancelled` diisi string alasan dari pemanggil (maks. 64 karakter).

### 7.2 Perubahan signature event vs V0

| Event | V0 | V2 | Dampak |
|---|---|---|---|
| `OrderCreated` | `(orderId, buyer, amount, orderNumber)` | + `externalOrderHash` (indexed) | ABI break, disengaja |
| `OrderShipped` | `(orderId)` | + `operator` | ABI break |
| `OrderCompleted` | `(orderId)` | + `buyer` | ABI break |
| `EscrowReleased` | `(orderId, seller)` | + `amount` | ABI break |

Semua perubahan ini dicatat di arsip ABI V1 sebelum V2 dideploy (`W4-FRL`).

---

## 8. Fungsi Eksternal (Final)

```solidity
// Core
function createOrder(bytes32 externalOrderHash, string calldata orderNumber, uint256 amount)
    external payable returns (uint256 orderId);

// Read
function getOrder(uint256 orderId) external view returns (Order memory);
function getOrderInfo(uint256 orderId) external view returns (Order memory);   // alias V0
function getOrderStatus(uint256 orderId) external view returns (OrderStatus);
function getOrderIdByHash(bytes32 externalOrderHash) external view returns (uint256);
function isOperator(address account) external view returns (bool);

// Payment & state
function recordPayment(uint256 orderId) external onlyOperator orderExists(orderId);
function updateOrderStatus(uint256 orderId, OrderStatus newStatus) external onlyOperator orderExists(orderId) notFinal(orderId);
function shipOrder(uint256 orderId) external onlyOperator orderExists(orderId);
function confirmReceived(uint256 orderId) external onlyBuyer(orderId) orderExists(orderId);
function cancelOrder(uint256 orderId, string calldata reason) external orderExists(orderId) notFinal(orderId);

// Operator management
function addOperator(address account) external onlyOwner;
function removeOperator(address account) external onlyOwner;
```

> **Perubahan dari draft Minggu 1:** `cancelOrder` mendapat parameter `reason` agar event `OrderCancelled` informatif. Ini disepakati sebelum freeze sehingga tidak dihitung sebagai ABI break.

### 8.1 Perilaku per fungsi

| Fungsi | Aktor | Precondition | Efek | Event |
|---|---|---|---|---|
| `createOrder` | Siapa pun (pembeli) | `amount > 0`; `msg.value == amount`; `orderIdByHash[hash] == 0`; `orderNumber` tidak kosong | `orderCount++`; simpan `Order` status `Created`; simpan escrow `released=false, refunded=false`; set `orderIdByHash` | `OrderCreated` |
| `recordPayment` | Operator/owner | status `Created`/`AwaitingPayment`; `!paymentRecorded`; `escrow.amount > 0` | `paymentRecorded = true`; status `Paid`; `updatedAt` | `PaymentRecorded`, `OrderStatusUpdated` |
| `updateOrderStatus` | Operator/owner | transisi ada di tabel T02–T05 | ubah status; `updatedAt` | `OrderStatusUpdated` |
| `shipOrder` | Operator/owner | status `ReadyToShip` | status `Shipped`; `updatedAt` | `OrderShipped`, `OrderStatusUpdated` |
| `confirmReceived` | Pembeli order | status `Shipped`; `!escrow.released` | `released=true`; status `Completed`; transfer `amount` ke owner | `OrderCompleted`, `EscrowReleased` |
| `cancelOrder` | Lihat §9 | status bukan terminal; aktor berwenang untuk state tersebut | status `Cancelled`; refund bila escrow terisi | `OrderCancelled`, `EscrowRefunded`, `OrderStatusUpdated` |
| `addOperator` | Owner | `account != address(0)`; `!operators[account]` | `operators[account] = true` | `OperatorUpdated` |
| `removeOperator` | Owner | `account != owner`; `operators[account]` | `operators[account] = false` | `OperatorUpdated` |

### 8.2 Read function

| Fungsi | Revert bila | Dipakai oleh |
|---|---|---|
| `getOrder` | `orderId` tidak ada (`Order not found`) | Service verifikasi, proof page |
| `getOrderInfo` | idem (alias) | Frontend lama |
| `getOrderStatus` | `orderId` tidak ada | UI status ringkas |
| `getOrderIdByHash` | tidak revert; mengembalikan 0 | Cek idempotensi sebelum kirim tx |
| `isOperator` | tidak revert | UI menampilkan tombol operator |

**Keputusan:** `getOrder` **revert** untuk ID tidak valid (sesuai SC-06), bukan mengembalikan struct kosong. Ini membuat kesalahan terdeteksi lebih awal.

### 8.3 `externalOrderHash` — definisi kanonik

Hash **dihitung off-chain**, kontrak hanya menyimpan dan memastikan keunikan. Formula kanonik:

```text
externalOrderHash = keccak256(abi.encode(orderNumber, buyerAddress, amountWei))
```

Contoh JavaScript:

```javascript
const hash = web3.utils.soliditySha3(
  { type: "string",  value: orderNumber },
  { type: "address", value: buyer },
  { type: "uint256", value: amountWei }
);
```

Aturan:

1. `orderNumber` memakai format `INV-YYYY-NNNN` tanpa spasi.
2. `buyer` adalah alamat wallet pembeli, bukan alamat email.
3. `amountWei` adalah nilai escrow dalam wei.
4. Service PHP wajib memakai urutan dan tipe yang sama; hasilnya diuji di INT-02 dan INT-05.
5. Perubahan formula = ABI break karena memengaruhi verifikasi.

---

## 9. Transition Table Final

| ID | From | To | Fungsi | Aktor | Guard | Event |
|---|---|---|---|---|---|---|
| T01 | — | `Created` | `createOrder` | Pembeli | amount > 0; value == amount; hash unik | `OrderCreated` |
| T02 | `Created` | `AwaitingPayment` | `updateOrderStatus` | Operator/owner | state = `Created` | `OrderStatusUpdated` |
| T03 | `Created` / `AwaitingPayment` | `Paid` | `recordPayment` | Operator/owner | `!paymentRecorded`; escrow terisi | `PaymentRecorded`, `OrderStatusUpdated` |
| T04 | `Paid` | `InProduction` | `updateOrderStatus` | Operator/owner | state = `Paid` | `OrderStatusUpdated` |
| T05 | `InProduction` | `ReadyToShip` | `updateOrderStatus` | Operator/owner | state = `InProduction` | `OrderStatusUpdated` |
| T06 | `ReadyToShip` | `Shipped` | `shipOrder` | Operator/owner | state = `ReadyToShip` | `OrderShipped`, `OrderStatusUpdated` |
| T07 | `Shipped` | `Completed` | `confirmReceived` | Pembeli | `!released` | `OrderCompleted`, `EscrowReleased` |
| T08 | `Created` | `Cancelled` | `cancelOrder` | Pembeli/operator/owner | refund bila escrow terisi | `OrderCancelled`, `OrderStatusUpdated` (+`EscrowRefunded`) |
| T09 | `AwaitingPayment` | `Cancelled` | `cancelOrder` | Pembeli/operator/owner | tidak ada dana | idem |
| T10 | `Paid` | `Cancelled` | `cancelOrder` | Operator/owner | `!released`; refund | `OrderCancelled`, `EscrowRefunded` |
| T11 | `InProduction` | `Cancelled` | `cancelOrder` | Operator/owner | `!released`; refund | idem |
| T12 | `ReadyToShip` | `Cancelled` | `cancelOrder` | Operator/owner | `!released`; refund | idem |
| T13 | `Shipped` | `Cancelled` | — | — | **dilarang**; sengketa manual | — |
| T14 | `Completed` | apa pun | — | — | terminal (`notFinal`) | — |
| T15 | `Cancelled` | apa pun | — | — | terminal (`notFinal`) | — |

### 9.1 Implementasi `_isValidTransition`

```solidity
function _isValidTransition(OrderStatus from, OrderStatus to) internal pure returns (bool) {
    if (from == OrderStatus.Created)         return to == OrderStatus.AwaitingPayment || to == OrderStatus.Paid;
    if (from == OrderStatus.AwaitingPayment) return to == OrderStatus.Paid;
    if (from == OrderStatus.Paid)            return to == OrderStatus.InProduction;
    if (from == OrderStatus.InProduction)    return to == OrderStatus.ReadyToShip;
    if (from == OrderStatus.ReadyToShip)     return to == OrderStatus.Shipped;
    return false; // Shipped, Completed, Cancelled: tidak ada transisi maju
}
```

`cancelOrder` **tidak** memakai `_isValidTransition`; ia memakai aturan otorisasi per state (§10) dan menetapkan status langsung ke `Cancelled`.

---

## 10. Aturan Cancel (Final)

| State | Pembeli | Operator | Owner | Escrow |
|---|---|---|---|---|
| `Created` | ✅ | ✅ | ✅ | Refund jika `amount > 0` |
| `AwaitingPayment` | ✅ | ✅ | ✅ | Tidak ada dana |
| `Paid` | ❌ | ✅ | ✅ | Refund |
| `InProduction` | ❌ | ✅ | ✅ | Refund |
| `ReadyToShip` | ❌ | ✅ | ✅ | Refund |
| `Shipped` | ❌ | ❌ | ❌ | Sengketa manual |
| `Completed` | ❌ | ❌ | ❌ | Selesai |
| `Cancelled` | ❌ | ❌ | ❌ | Terminal |

**Logika otorisasi:**

```text
jika state == Created atau AwaitingPayment:
    izinkan msg.sender == buyer ATAU onlyOperator
jika state == Paid, InProduction, ReadyToShip:
    izinkan onlyOperator
selain itu:
    revert "Order is final" (lewat modifier notFinal) atau "Invalid state transition"
```

---

## 11. Escrow: Release, Refund, Keamanan

### 11.1 Urutan release (`confirmReceived`)

```text
1. require(status == Shipped)          -> "Not shipped yet"
2. require(!escrow.released)           -> "Already released"
3. escrow.released = true              // effects
4. status = Completed; updatedAt = now // effects
5. payable(owner).transfer(amount)     // interaction
6. emit OrderCompleted, EscrowReleased
```

### 11.2 Urutan refund (`cancelOrder`)

```text
1. validasi aktor & state
2. status = Cancelled; updatedAt = now
3. jika escrow.amount > 0 && !escrow.released && !escrow.refunded:
       escrow.refunded = true
       payable(escrow.buyer).transfer(escrow.amount)
       emit EscrowRefunded
4. emit OrderCancelled, OrderStatusUpdated
```

### 11.3 Aturan keamanan

1. **Checks-effects-interactions**: state diubah sebelum transfer.
2. **Transfer langsung** memakai `transfer` (2300 gas). Aman untuk EOA; tidak mendukung kontrak pembeli dengan `receive()` berat. Ini diterima karena pembeli adalah wallet pengguna.
3. Tidak ada fungsi `selfdestruct`, `delegatecall`, atau `call` arbitrer.
4. `released` dan `refunded` tidak boleh keduanya `true`.
5. Tidak ada `receive()`/`fallback()`: ETH hanya masuk lewat `createOrder`.
6. Escrow hanya dapat dirilis ke `owner`, tidak ke alamat yang dikirim pemanggil.

---

## 12. Revert Matrix Final

Pesan revert **wajib identik** karena diuji otomatis. Jangan mengubah teks setelah V1.

| # | Kondisi | Fungsi | Pesan | Test |
|---|---|---|---|---|
| R01 | `amount == 0` | `createOrder` | `Amount must be > 0` | SC-03 |
| R02 | `msg.value != amount` | `createOrder` | `Incorrect amount` | SC-03 |
| R03 | hash sudah dipakai | `createOrder` | `Duplicate order hash` | SC-04 |
| R04 | `orderNumber` kosong | `createOrder` | `Order number required` | SC-02 |
| R05 | order tidak ada | semua fungsi order | `Order not found` | SC-06 |
| R06 | bukan operator | `recordPayment`, `updateOrderStatus`, `shipOrder`, cancel oleh non-operator | `Not operator` | SC-07 |
| R07 | bukan pembeli | `confirmReceived` | `Not buyer` | SC-07 |
| R08 | bukan owner | `addOperator`, `removeOperator` | `Not owner` | SC-07 |
| R09 | pembayaran ganda | `recordPayment` | `Payment already recorded` | SC-08 |
| R10 | transisi tidak sah | `updateOrderStatus`, `shipOrder` | `Invalid state transition` | SC-10 |
| R11 | `shipOrder` sebelum `ReadyToShip` | `shipOrder` | `Not ready to ship` | SC-10 |
| R12 | `confirmReceived` sebelum `Shipped` | `confirmReceived` | `Not shipped yet` | SC-12 |
| R13 | escrow sudah dirilis | `confirmReceived` | `Already released` | SC-12 |
| R14 | cancel oleh aktor tidak berwenang | `cancelOrder` | `Not authorized to cancel` | SC-11 |
| R15 | cancel order terminal | `cancelOrder` | `Order is final` | SC-11 |
| R16 | operator alamat nol | `addOperator` | `Invalid operator` | SC-07 |
| R17 | owner menghapus diri sendiri | `removeOperator` | `Cannot remove owner` | SC-07 |
| R18 | operator sudah aktif | `addOperator` | `Operator already active` | SC-07 |
| R19 | operator tidak aktif | `removeOperator` | `Operator not active` | SC-07 |
| R20 | fungsi belum diimplementasi di V1 | `recordPayment`, `cancelOrder` (V1 saja) | `Not implemented` | — |
| R21 | `reason` lebih dari 64 byte | `cancelOrder` | `Reason too long` | SC-11 |

> R20 tidak dipakai pada V1 aktual (happy path diimplementasikan). Baris dipertahankan sebagai jejak keputusan; tidak ada pesan ini di kontrak.

---

## 13. Gas & Batasan

| Operasi | Perkiraan | Catatan |
|---|---|---|
| Deploy | **1.560.496 gas (aktual V1)** | Naik dari V0 karena fungsi dan event baru; tercatat di deployment log |
| `createOrder` | ~150.000 gas | Termasuk 2 penulisan struct + 2 mapping |
| `recordPayment` | ~50.000 gas | 2 field berubah |
| `updateOrderStatus` | ~35.000 gas | 1 field + event |
| `confirmReceived` | ~60.000 gas | + transfer ETH |
| `cancelOrder` + refund | ~70.000 gas | + transfer ETH |

Angka ini **perkiraan desain**, bukan hasil pengukuran. Gas aktual dicatat Minggu 4 dari `truffle test` dan deployment receipt.

**Batasan yang disepakati:**

1. Maksimum 1 order per transaksi.
2. Tidak ada batch operation di kontrak; batch ada di layer aplikasi.
3. `orderNumber` maksimum 64 byte.
4. `reason` maksimum 64 byte.
5. Tidak ada upgradeability (proxy) — kontrak immutable.

---

## 14. Strategi Versi ABI

| Versi | Isi | Deploy | ABI file |
|---|---|---|---|
| V0 | Kontrak 4 state saat ini | Sudah (10 Nov 2026) | `config/archive/contract_abi.v0.json` |
| V1 | ABI **lengkap untuk alur inti** (18 entri: 12 fungsi eksplisit + 6 auto-getter) dan 9 event; happy path `createOrder` → `recordPayment` → `updateOrderStatus` → `shipOrder` → `confirmReceived` sudah berfungsi; `cancelOrder`/refund belum ada | Sudah (11 Nov 2026) | `config/archive/contract_abi.v1.json` |
| V2 | Tambah `cancelOrder` + refund dan pemancaran `OrderCancelled`/`EscrowRefunded` | Sudah (11 Nov 2026) — `0x2f3efA6bbDC5fAf4dC1a600765c7B7829e47bE10` | `config/archive/contract_abi.v2.json` |

**Deviasi dari rencana awal:** dokumen ini semula merencanakan V1 berisi stub `Not implemented` untuk `recordPayment` dan `cancelOrder`. Implementasi aktual (11 November 2026) memilih **happy path penuh** dan hanya menunda cancel/refund ke V2. Konsekuensi:

1. Pesan revert `Not implemented` (R20) tidak dipakai dan dihapus dari kontrak.
2. ABI V1 sudah memuat signature `cancelOrder`? **Tidak** — fungsi itu baru ditambahkan di V2. Signature-nya tetap dibekukan di §8 agar penambahan V2 aditif.
3. Interface freeze V1 mencakup 12 fungsi eksplisit yang benar-benar ada dan 9 event; V2 hanya menambah, tidak mengubah.

**Mengapa interface tetap bermakna beku?** Frontend dan backend M5 dapat dikembangkan terhadap 12 fungsi V1 tanpa risiko signature berubah. Penambahan `cancelOrder` di V2 bersifat aditif dan sudah diketahui sejak M3.

**Aturan pembekuan:**

| Perubahan | Butuh versi baru? |
|---|---|
| Menambah/mengubah fungsi eksternal | ✅ Ya |
| Mengubah urutan/nilai enum | ✅ Ya |
| Menambah/menghapus field struct | ✅ Ya |
| Mengubah signature event | ✅ Ya |
| Mengubah pesan revert | ✅ Ya (kontrak test) |
| Menambah fungsi internal/helper | ❌ Tidak |
| Mengubah modifier internal | ❌ Tidak |
| Menambah NatSpec | ❌ Tidak |
| Optimasi gas tanpa ubah perilaku | ❌ Tidak |

---

## 15. Pemetaan ke 21 Test

| Test | Cakupan dalam spesifikasi | Status target |
|---|---|---|
| SC-01 | `owner`, `addOperator`, `onlyOwner` | M3 |
| SC-02 | `createOrder` valid, event, escrow, hash | M3 |
| SC-03 | R01, R02 | M3 |
| SC-04 | R03, `orderIdByHash` | M3 |
| SC-05 | `getOrder` field lengkap | M3 |
| SC-06 | R05 | M3 |
| SC-07 | R06, R07, R08, R16, R17, R18, R19 | M4 |
| SC-08 | T03, R09 | M4 |
| SC-09 | T04, T05, T06 | M4 |
| SC-10 | R10, R11 | M4 |
| SC-11 | T08–T15, R14, R15 | M4 |
| SC-12 | T07, R12, R13, §11.1 | M4 |
| INT-02/INT-05 | §8.3 formula hash | M5 |
| INT-04 | §7 event | M5 |

---

## 16. Checklist Pembekuan Interface (Akhir Minggu 3)

- [ ] Enum 8 state final dan tidak berubah urutan.
- [ ] Struct `Order` dan `PaymentEscrow` final.
- [ ] 13 signature fungsi eksternal final.
- [ ] 9 event final.
- [ ] 20 pesan revert final dan unik.
- [ ] `contract_abi.v1.json` diarsipkan sebelum V2.
- [ ] SC-01 s.d. SC-06 PASS.
- [ ] Persetujuan Arby, Agustina, Luis, Kenjiro.

> Sampai checklist ini selesai, status dokumen adalah **final untuk implementasi**, bukan **interface frozen**.

---

## 17. Risiko Spesifikasi

| # | Risiko | Dampak | Mitigasi |
|---|---|---|---|
| S1 | `transfer` gagal untuk pembeli berupa kontrak | Refund macet | Batasi pembeli ke EOA; dokumentasikan |
| S2 | Hash dihitung berbeda antara PHP dan JS | INT-05 gagal | Formula kanonik + test vector di §8.3 |
| S3 | V1 stub `Not implemented` terbawa ke produksi | Fitur tidak jalan | Checklist V2 + regression 12 SC |
| S4 | Frontend masih memakai ABI V0 | Transaksi gagal | Arsip ABI + update M4 (`W4-FRL-08`) |
| S5 | Order lama tanpa proof | UI bingung | Kartu "Belum ada bukti on-chain" (Q5) |

---

## 18. Tautan

- State machine & matriks revert: [`../week1/01_ON_OFF_CHAIN_AND_STATE.md`](../week1/01_ON_OFF_CHAIN_AND_STATE.md)
- Analisis gap kontrak: [`../week1/04_CONTRACT_AND_PARALLEL_DESIGN.md`](../week1/04_CONTRACT_AND_PARALLEL_DESIGN.md)
- Test plan 21 case: [`../week1/07_TEST_PLAN.md`](../week1/07_TEST_PLAN.md)
- Baseline environment: [`00_ENVIRONMENT_BASELINE.md`](00_ENVIRONMENT_BASELINE.md)
- Wireframe UI: [`01_UI_WIREFRAMES.md`](01_UI_WIREFRAMES.md)
