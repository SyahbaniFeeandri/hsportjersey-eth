TUTORIAL LENGKAP: Implementasi Smart Contract Ethereum pada Website E-Commerce Jersey

H-Sport Jersey - Panduan Setup untuk Tim

DAFTAR ISI

&#x20;1. Pengenalan \& Konsep Dasar

&#x20;2. Install Software yang Dibutuhkan

&#x20;3. Setup Ganache (Blockchain Lokal)

&#x20;4. Setup MetaMask (Dompet Crypto)

&#x20;5. Setup Hardhat (Development Framework)

&#x20;6. Tulis Smart Contract

&#x20;7. Compile \& Deploy Smart Contract

&#x20;8. Update Database MySQL

&#x20;9. Buat Halaman Crypto Payment

10\. Buat PHP API Bridge

11\. Update Checkout Page

12\. Test Keseluruhan

13\. Troubleshooting

BAB 1: PENGENALAN \& KONSEP DASAR

1.1 Apa yang Kita Buat?

Kita akan menambahkan pembayaran cryptocurrency (ETH) ke website e-commerce jersey yang sudah ada. Fitur utama:

\- User bisa bayar pakai ETH (Ethereum) via MetaMask

\- Uang ditahan di Smart Contract (escrow) sampai user konfirmasi barang diterima

\- Semua transaksi tercatat di blockchain (tidak bisa diubah)

1.2 Analogi Sederhana

TANPA Smart Contract:

&#x20; Pembeli ──(bayar)──▶ Seller \[langsung terima uang]

&#x20; Risiko: Seller bisa kabur, tidak kirim barang

DENGAN Smart Contract (Escrow):

&#x20; Pembeli ──(bayar)──▶ Smart Contract \[tahan uang]

&#x20;                                                   │

&#x20; Pembeli: "Barang sudah sampai!" ◀──────────────────┘

&#x20; Smart Contract ──(lepas uang)──▶ Seller

1.3 Software yang Dibutuhkan

Software	Fungsi	Link Download

Node.js	Runtime JavaScript	https://nodejs.org (https://nodejs.org)

Ganache	Blockchain lokal (test)	https://trufflesuite.com/ganache (https://trufflesuite.com/ganache)

MetaMask	Dompet crypto (Chrome extension)	https://metamask.io (https://metamask.io)

Hardhat	Framework smart contract	Install via npm

XAMPP	Server PHP + MySQL	https://www.apachefriends.org (https://www.apachefriends.org)

Google Chrome	Browser untuk MetaMask	https://google.com/chrome (https://google.com/chrome)

1.4 Istilah Dasar

Istilah	Arti Sederhana

Blockchain	Buku besar digital yang tidak bisa diubah

Ethereum	Blockchain yang menjalankan smart contract

Ganache	Blockchain palsu di laptop untuk belajar

MetaMask	Dompet digital di browser Chrome

ETH	Cryptocurrency Ethereum (uang digital)

Wei	Unit terkecil ETH (1 ETH = 10^18 Wei)

Smart Contract	Program yang jalan di blockchain

Solidity	Bahasa untuk nulis smart contract

Hardhat	Alat untuk compile \& deploy contract

Escrow	Uang ditahan sampai kondisi terpenuhi

Gas Fee	Biaya transaksi di blockchain

TX Hash	ID unik setiap transaksi

ABI	Interface supaya JavaScript bisa panggil contract

Chain ID	Identitas jaringan blockchain

BAB 2: INSTALL SOFTWARE

STEP 1: Install Node.js

Apa itu? Software untuk menjalankan JavaScript di komputer.

Cara Install:

1\. Buka browser → https://nodejs.org

2\. Klik tombol "LTS" untuk download

3\. Buka file installer → Next → Next → Install → Finish

Cek berhasil:

1\. Buka Command Prompt (ketik cmd di search Windows)

2\. Ketik:

node --version

3\. Kalau muncul versi (contoh: v18.17.0) = BERHASIL

4\. Ketik juga:

npm --version

5\. Kalau muncul angka = npm juga sudah terinstall

STEP 2: Install XAMPP

Apa itu? Server lokal untuk menjalankan PHP + MySQL.

Cara Install:

1\. Buka browser → https://www.apachefriends.org

2\. Download XAMPP untuk Windows

3\. Buka installer → Next → Next → Install

4\. Centang Apache dan MySQL → Next → Finish

Cara Jalankan:

1\. Buka XAMPP Control Panel

2\. Klik Start di sebelah Apache

3\. Klik Start di sebabah MySQL

4\. Pastai keduanya hijau (running)

Cek berhasil:

\- Buka browser → http://localhost/phpmyadmin

\- Kalau phpMyAdmin terbuka = BERHASIL

BAB 3: SETUP GANACHE (Blockchain Lokal)

STEP 3: Install Ganache

Apa itu? Aplikasi yang membuat blockchain pribadi di laptopmu. Kamu dapat 100 ETH test (uang pura-pura) untuk percobaan.

Cara Install:

1\. Buka browser → https://trufflesuite.com/ganache

2\. Klik Download → pilih versi Windows

3\. Buka file .exe → Install → Finish

Cara Setup:

1\. Buka Ganache

2\. Klik tombol "QUICKSTART" (pilih yang Ethereum)

3\. Tunggu sebentar

Yang akan terlihat:

┌──────────────────────────────────────────────────────┐

│  ACCOUNTS                                            │

├──────────────────────────────────────────────────────┤

│ 🔑 0x1234...abcd  │  100.00 ETH  │  TX: 0  │  Idx 0 │

│ 🔑 0x5678...efgh  │  100.00 ETH  │  TX: 0  │  Idx 1 │

│ 🔑 0x9abc...ijkl  │  100.00 ETH  │  TX: 0  │  Idx 2 │

│ ... (10 account total)                               │

├──────────────────────────────────────────────────────┤

│ RPC SERVER: http://127.0.0.1:7545                   │

│ NETWORK ID: 5777                                     │

└──────────────────────────────────────────────────────┘

Catat informasi ini:

\- RPC Server: http://127.0.0.1:7545

\- Chain ID: 1337 (di MetaMask nanti)

\- Setiap account punya 100 ETH

Simpan Workspace (supaya data tidak hilang):

1\. Klik "SAVE" di pojok kanan atas

2\. Ganache akan otomatis simpan workspace

BAB 4: SETUP METAMASK (Dompet Crypto)

STEP 4: Install MetaMask

Apa itu? Extension Chrome yang berfungsi sebagai dompet crypto.

Cara Install:

1\. Buka Google Chrome

2\. Buka https://metamask.io

3\. Klik "Download" → "Install MetaMask" → "Add to Chrome"

4\. Klik "Add extension"

Cara Buat Wallet:

1\. Klik "Create a new wallet"

2\. Centang "I agree" → "Create a new wallet"

3\. Isi password (misal: hsport123) → Create

4\. PALING PENTING: Tulis 12 Secret Recovery Phrase di KERTAS

\- Jangan screenshot

\- Jangan simpan di komputer

\- Kalau hilang, wallet tidak bisa dikembalikan

5\. Klik "Got it" → konfirmasi urutan kata → "Confirm" → "All done"

STEP 5: Connect MetaMask ke Ganache

1\. Di MetaMask, klik ikon jaringan di atas

2\. Klik "Add network" → "Add a network manually"

3\. Isi form:

\- Network Name: Ganache

\- New RPC URL: http://127.0.0.1:7545

\- Chain ID: 1337

\- Currency Symbol: ETH

4\. Klik "Save" → "Switch"

STEP 6: Import Account Ganache

1\. Di Ganache, klik ikon kunci 🔑 di Account 0 (paling atas)

2\. Copy Private Key → klik "Copy to clipboard"

3\. Di MetaMask → klik ikon avatar → "Import account"

4\. Paste Private Key → "Import"

Hasil:

┌─────────────────────────────────────────┐

│  Imported Account 1                     │

│  0xEeB87...805ed                        │

│                                         │

│  Balance: 100 ETH                       │

│  Network: Ganache                       │

└─────────────────────────────────────────┘

BAB 5: SETUP HARDHAT (Development Framework)

STEP 7: Inisialisasi Hardhat

Buka Command Prompt, jalankan perintah satu per satu:

cd C:\\xampp\\htdocs\\project\_jersey

mkdir blockchain

cd blockchain

npm init -y

Install Hardhat:

npm install --save-dev hardhat

npm install --save-dev @nomicfoundation/hardhat-toolbox

npm install --save-dev @nomicfoundation/hardhat-ethers ethers

Inisialisasi project:

npx hardhat --init

Saat ditanya:

\- Ketik . (titik) → Enter

\- Pilih "A minimal Hardhat project" → Enter

\- Tekan Enter untuk semua default

Cek hasil:

dir

Harusnya ada: hardhat.config.ts, node\_modules/, dll.

STEP 8: Config Hardhat

Edit file config:

notepad hardhat.config.ts

Hapus semua isi, paste ini:

import { defineConfig } from "hardhat/config";

import hardhatEthers from "@nomicfoundation/hardhat-ethers";

export default defineConfig({

&#x20; solidity: "0.8.19",

&#x20; plugins: \[hardhatEthers],

&#x20; networks: {

&#x20;   ganache: {

&#x20;     type: "http",

&#x20;     url: "http://127.0.0.1:7545"

&#x20;   }

&#x20; }

});

Save (Ctrl+S) → tutup Notepad.

BAB 6: TULIS SMART CONTRACT

STEP 9: Buat File Smart Contract

mkdir contracts

cd contracts

notepad HSportStore.sol

Klik Yes saat ditanya buat file baru.

Paste kode ini PERSIS (tanpa backtick atau karakter tambahan):

// SPDX-License-Identifier: MIT

pragma solidity ^0.8.19;

contract HSportStore {

&#x20;   enum OrderStatus { Created, Paid, Shipped, Completed }

&#x20;   struct Order {

&#x20;       uint256 id;

&#x20;       address buyer;

&#x20;       uint256 totalAmount;

&#x20;       OrderStatus status;

&#x20;       uint256 createdAt;

&#x20;       uint256 updatedAt;

&#x20;       string orderNumber;

&#x20;   }

&#x20;   struct PaymentEscrow {

&#x20;       uint256 orderId;

&#x20;       address buyer;

&#x20;       uint256 amount;

&#x20;       bool released;

&#x20;   }

&#x20;   address public owner;

&#x20;   uint256 public orderCount;

&#x20;   mapping(uint256 => Order) public orders;

&#x20;   mapping(uint256 => PaymentEscrow) public escrows;

&#x20;   event OrderCreated(uint256 indexed orderId, address indexed buyer, uint256 amount, string orderNumber);

&#x20;   event OrderShipped(uint256 indexed orderId);

&#x20;   event OrderCompleted(uint256 indexed orderId);

&#x20;   event EscrowReleased(uint256 indexed orderId, address seller);

&#x20;   modifier onlyOwner() { require(msg.sender == owner, "Not owner"); \_; }

&#x20;   modifier onlyBuyer(uint256 \_orderId) { require(orders\[\_orderId].buyer == msg.sender, "Not buyer"); \_; }

&#x20;   modifier orderExists(uint256 \_orderId) { require(\_orderId > 0 \&\& \_orderId <= orderCount, "Order not found"); \_; }

&#x20;   constructor() {

&#x20;       owner = msg.sender;

&#x20;   }

&#x20;   function createOrder(string memory \_orderNumber, uint256 \_amount) external payable returns (uint256) {

&#x20;       require(msg.value == \_amount, "Incorrect amount");

&#x20;       require(\_amount > 0, "Amount must be > 0");

&#x20;       orderCount++;

&#x20;       orders\[orderCount] = Order(orderCount, msg.sender, \_amount, OrderStatus.Paid, block.timestamp, block.timestamp, \_orderNumber);

&#x20;       escrows\[orderCount] = PaymentEscrow(orderCount, msg.sender, \_amount, false);

&#x20;       emit OrderCreated(orderCount, msg.sender, \_amount, \_orderNumber);

&#x20;       return orderCount;

&#x20;   }

&#x20;   function shipOrder(uint256 \_orderId) external onlyOwner orderExists(\_orderId) {

&#x20;       require(orders\[\_orderId].status == OrderStatus.Paid, "Not paid yet");

&#x20;       orders\[\_orderId].status = OrderStatus.Shipped;

&#x20;       orders\[\_orderId].updatedAt = block.timestamp;

&#x20;       emit OrderShipped(\_orderId);

&#x20;   }

&#x20;   function confirmReceived(uint256 \_orderId) external onlyBuyer(\_orderId) orderExists(\_orderId) {

&#x20;       require(orders\[\_orderId].status == OrderStatus.Shipped, "Not shipped yet");

&#x20;       require(!escrows\[\_orderId].released, "Already released");

&#x20;       orders\[\_orderId].status = OrderStatus.Completed;

&#x20;       orders\[\_orderId].updatedAt = block.timestamp;

&#x20;       escrows\[\_orderId].released = true;

&#x20;       payable(owner).transfer(escrows\[\_orderId].amount);

&#x20;       emit OrderCompleted(\_orderId);

&#x20;       emit EscrowReleased(\_orderId, owner);

&#x20;   }

&#x20;   function getOrderStatus(uint256 \_orderId) external view returns (OrderStatus) {

&#x20;       return orders\[\_orderId].status;

&#x20;   }

&#x20;   function getOrderInfo(uint256 \_orderId) external view returns (Order memory) {

&#x20;       return orders\[\_orderId];

&#x20;   }

}

Save (Ctrl+S) → tutup Notepad.

BAB 7: COMPILE \& DEPLOY

STEP 10: Compile Smart Contract

Kembali ke folder blockchain:

cd ..

npx hardhat compile --network ganache

Kalau berhasil:

Compiled 1 Solidity file with solc 0.8.19 (evm target: paris)

STEP 11: Buat Deploy Script

mkdir scripts

mkdir config

notepad scripts/deploy.js

Klik Yes, paste ini:

import { network } from "hardhat";

import fs from "fs";

const { ethers } = await network.connect({

&#x20; network: "ganache",

});

async function main() {

&#x20;   console.log("Deploying HSportStore...");

&#x20;   const HSportStore = await ethers.getContractFactory("HSportStore");

&#x20;   const store = await HSportStore.deploy();

&#x20;   await store.waitForDeployment();

&#x20;   const address = await store.getAddress();

&#x20;   console.log("HSportStore deployed to:", address);

&#x20;   fs.writeFileSync("config/contract\_address.txt", address);

&#x20;   const artifact = JSON.parse(

&#x20;       fs.readFileSync("artifacts/contracts/HSportStore.sol/HSportStore.json", "utf8")

&#x20;   );

&#x20;   fs.writeFileSync("config/contract\_abi.json", JSON.stringify(artifact.abi, null, 2));

&#x20;   console.log("Contract address and ABI saved to config/");

}

main().catch((error) => {

&#x20;   console.error(error);

&#x20;   process.exitCode = 1;

});

Save → tutup Notepad.

STEP 12: Deploy ke Ganache

npx hardhat run scripts/deploy.js --network ganache

Kalau berhasil:

Deploying HSportStore...

HSportStore deployed to: 0x5FbDB2315678afecb367f032d93F642f64180aa3

Contract address and ABI saved to config/

Catat alamat contract (0x5FbD...) → ini alamat smart contract kamu.

Cek di Ganache:

\- Buka Ganache → tab "TRANSACTIONS"

\- Harusnya ada 1 transaksi baru (deploy)

BAB 8: UPDATE DATABASE MySQL

STEP 13: Tambah Kolom Blockchain

Buka phpMyAdmin → http://localhost/phpmyadmin

1\. Klik database h\_sport\_db di panel kiri

2\. Klik tab "SQL"

3\. Paste query ini:

USE h\_sport\_db;

ALTER TABLE orders

&#x20;   ADD COLUMN payment\_type ENUM('fiat', 'crypto') DEFAULT 'fiat' AFTER payment\_status,

&#x20;   ADD COLUMN tx\_hash VARCHAR(100) NULL AFTER payment\_type,

&#x20;   ADD COLUMN wallet\_address VARCHAR(50) NULL AFTER tx\_hash,

&#x20;   ADD COLUMN blockchain\_order\_id INT UNSIGNED NULL AFTER wallet\_address;

4\. Klik "Go"

Kalau berhasil: "MySQL returned an empty result set (i.e. zero rows)"

BAB 9: BUAT HALAMAN CRYPTO PAYMENT

STEP 14: Buat File crypto-payment.php

Buka File Explorer → C:\\xampp\\htdocs\\project\_jersey\\

1\. Klik kanan → New Text Document → rename crypto-payment.php

2\. Buka dengan Notepad

3\. Paste kode ini (WAJIB PERSIS, tidak boleh ada yang diubah):

<?php

require\_once 'config/database.php';

require\_once 'config/functions.php';

if(!isLoggedIn()) {

&#x20;   redirect('login.php');

}

$order\_id = isset($\_GET\['order\_id']) ? (int)$\_GET\['order\_id'] : 0;

$stmt = $pdo->prepare("SELECT \* FROM orders WHERE id = ? AND user\_id = ?");

$stmt->execute(\[$order\_id, $\_SESSION\['user\_id']]);

$order = $stmt->fetch();

if(!$order) {

&#x20;   redirect('cart.php');

}

$contract\_address = file\_get\_contents('blockchain/config/contract\_address.txt');

$contract\_abi = file\_get\_contents('blockchain/config/contract\_abi.json');

?>

<!DOCTYPE html>

<html lang="id">

<head>

&#x20;   <meta charset="UTF-8">

&#x20;   <meta name="viewport" content="width=device-width, initial-scale=1.0">

&#x20;   <title>Pembayaran Crypto - H-Sport Jersey</title>

&#x20;   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

&#x20;   <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

&#x20;   <style>

&#x20;       body {

&#x20;           background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);

&#x20;           min-height: 100vh;

&#x20;           color: white;

&#x20;       }

&#x20;       .crypto-card {

&#x20;           background: rgba(255,255,255,0.05);

&#x20;           border: 1px solid rgba(255,255,255,0.1);

&#x20;           border-radius: 20px;

&#x20;           padding: 30px;

&#x20;           backdrop-filter: blur(10px);

&#x20;       }

&#x20;       .wallet-info {

&#x20;           background: rgba(0,200,83,0.1);

&#x20;           border: 1px solid rgba(0,200,83,0.3);

&#x20;           border-radius: 15px;

&#x20;           padding: 20px;

&#x20;       }

&#x20;       .btn-pay {

&#x20;           background: linear-gradient(135deg, #00c853 0%, #00897b 100%);

&#x20;           border: none;

&#x20;           padding: 15px;

&#x20;           font-size: 18px;

&#x20;           font-weight: bold;

&#x20;           border-radius: 15px;

&#x20;           transition: all 0.3s;

&#x20;       }

&#x20;       .btn-pay:hover {

&#x20;           transform: translateY(-3px);

&#x20;           box-shadow: 0 10px 30px rgba(0,200,83,0.4);

&#x20;           color: white;

&#x20;       }

&#x20;       .btn-pay:disabled {

&#x20;           background: #555;

&#x20;           cursor: not-allowed;

&#x20;           transform: none;

&#x20;           box-shadow: none;

&#x20;       }

&#x20;       .order-summary {

&#x20;           background: rgba(255,255,255,0.03);

&#x20;           border-radius: 15px;

&#x20;           padding: 20px;

&#x20;       }

&#x20;       .status-box {

&#x20;           border-radius: 10px;

&#x20;           padding: 15px;

&#x20;           margin-top: 15px;

&#x20;           display: none;

&#x20;       }

&#x20;   </style>

</head>

<body>

&#x20;   <?php include 'includes/navbar.php'; ?>

&#x20;   <div class="container mt-5">

&#x20;       <div class="row justify-content-center">

&#x20;           <div class="col-md-8">

&#x20;               <div class="crypto-card">

&#x20;                   <h2 class="text-center mb-4">

&#x20;                       <i class="fab fa-ethereum me-2"></i>

&#x20;                       Pembayaran Crypto (ETH)

&#x20;                   </h2>

&#x20;                   <div class="order-summary mb-4">

&#x20;                       <h5><i class="fas fa-receipt me-2"></i>Ringkasan Pesanan</h5>

&#x20;                       <hr style="border-color: rgba(255,255,255,0.1);">

&#x20;                       <p><strong>No. Pesanan:</strong> <?php echo $order\['order\_number']; ?></p>

&#x20;                       <p><strong>Total:</strong> <span class="text-success fw-bold">Rp <?php echo number\_format($order\['total\_amount'], 0, ',', '.'); ?></span></p>

&#x20;                       <p><strong>Alamat Pengiriman:</strong> <?php echo nl2br($order\['shipping\_address']); ?></p>

&#x20;                   </div>

&#x20;                   <div class="wallet-info mb-4">

&#x20;                       <h5><i class="fas fa-wallet me-2"></i>Wallet</h5>

&#x20;                       <div id="walletStatus">

&#x20;                           <p class="text-muted">Klik tombol di bawah untuk connect MetaMask</p>

&#x20;                       </div>

&#x20;                   </div>

&#x20;                   <div class="order-summary mb-4">

&#x20;                       <h5><i class="fas fa-file-contract me-2"></i>Smart Contract</h5>

&#x20;                       <p><strong>Contract Address:</strong><br>

&#x20;                           <code id="contractAddr"><?php echo trim($contract\_address); ?></code>

&#x20;                       </p>

&#x20;                       <p><strong>Network:</strong> Ganache (Local Testnet)</p>

&#x20;                   </div>

&#x20;                   <div id="statusBox" class="status-box"></div>

&#x20;                   <button id="connectBtn" class="btn btn-primary w-100 mb-3" onclick="connectWallet()">

&#x20;                       <i class="fab fa-metamask me-2"></i>Connect MetaMask

&#x20;                   </button>

&#x20;                   <button id="payBtn" class="btn btn-pay w-100 mb-3" onclick="payWithETH()" disabled>

&#x20;                       <i class="fab fa-ethereum me-2"></i>Bayar dengan ETH

&#x20;                   </button>

&#x20;                   <a href="orders.php" class="btn btn-outline-light w-100">

&#x20;                       <i class="fas fa-arrow-left me-2"></i>Kembali ke Pesanan

&#x20;                   </a>

&#x20;               </div>

&#x20;           </div>

&#x20;       </div>

&#x20;   </div>

&#x20;   <script src="https://cdnjs.cloudflare.com/ajax/libs/web3/4.2.2/web3.min.js"></script>

&#x20;   <script>

&#x20;       let web3;

&#x20;       let contract;

&#x20;       let userAccount;

&#x20;       let orderId = <?php echo $order\_id; ?>;

&#x20;       let orderNumber = '<?php echo $order\['order\_number']; ?>';

&#x20;       let totalAmount = <?php echo $order\['total\_amount']; ?>;

&#x20;       let contractAddress = '<?php echo trim($contract\_address); ?>';

&#x20;       let contractABI = <?php echo $contract\_abi; ?>;

&#x20;       function showStatus(message, type) {

&#x20;           const box = document.getElementById('statusBox');

&#x20;           box.style.display = 'block';

&#x20;           box.className = 'status-box alert alert-' + type;

&#x20;           box.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + ' me-2"></i>' + message;

&#x20;       }

&#x20;       async function connectWallet() {

&#x20;           if (typeof window.ethereum === 'undefined') {

&#x20;               showStatus('MetaMask tidak terinstall! Install dari https://metamask.io', 'danger');

&#x20;               return;

&#x20;           }

&#x20;           try {

&#x20;               const accounts = await window.ethereum.request({ method: 'eth\_requestAccounts' });

&#x20;               userAccount = accounts\[0];

&#x20;               web3 = new Web3(window.ethereum);

&#x20;               const chainId = await window.ethereum.request({ method: 'eth\_chainId' });

&#x20;               if (chainId !== '0x539') {

&#x20;                   showStatus('Harus di network Ganache! Chain ID: 1337', 'warning');

&#x20;                   return;

&#x20;               }

&#x20;               const balance = await web3.eth.getBalance(userAccount);

&#x20;               const balanceETH = web3.utils.fromWei(balance, 'ether');

&#x20;               document.getElementById('walletStatus').innerHTML = '<p class="mb-1"><strong>Address:</strong> <code>' + userAccount + '</code></p><p class="mb-0"><strong>Saldo:</strong> ' + parseFloat(balanceETH).toFixed(4) + ' ETH</p>';

&#x20;               contract = new web3.eth.Contract(contractABI, contractAddress);

&#x20;               document.getElementById('connectBtn').innerHTML = '<i class="fas fa-check me-2"></i>Connected';

&#x20;               document.getElementById('connectBtn').className = 'btn btn-success w-100 mb-3';

&#x20;               document.getElementById('payBtn').disabled = false;

&#x20;               showStatus('Wallet connected! Siap untuk pembayaran.', 'success');

&#x20;           } catch (error) {

&#x20;               showStatus('Gagal connect: ' + error.message, 'danger');

&#x20;           }

&#x20;       }

&#x20;       async function payWithETH() {

&#x20;           if (!userAccount) {

&#x20;               showStatus('Connect MetaMask terlebih dahulu!', 'warning');

&#x20;               return;

&#x20;           }

&#x20;           const amountETH = totalAmount / 10000000;

&#x20;           const amountWei = web3.utils.toWei(amountETH.toFixed(18), 'ether');

&#x20;           document.getElementById('payBtn').disabled = true;

&#x20;           document.getElementById('payBtn').innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';

&#x20;           try {

&#x20;               const encodedData = web3.eth.abi.encodeFunctionCall({

&#x20;                   name: 'createOrder',

&#x20;                   type: 'function',

&#x20;                   inputs: \[

&#x20;                       { type: 'string', name: '\_orderNumber' },

&#x20;                       { type: 'uint256', name: '\_amount' }

&#x20;                   ]

&#x20;               }, \[orderNumber, amountWei]);

&#x20;               const tx = await web3.eth.sendTransaction({

&#x20;                   from: userAccount,

&#x20;                   to: contractAddress,

&#x20;                   value: amountWei,

&#x20;                   data: encodedData,

&#x20;                   gas: 300000

&#x20;               });

&#x20;               showStatus('Transaksi berhasil! TX Hash: ' + tx.transactionHash, 'success');

&#x20;               const response = await fetch('api/blockchain-api.php', {

&#x20;                   method: 'POST',

&#x20;                   headers: { 'Content-Type': 'application/json' },

&#x20;                   body: JSON.stringify({

&#x20;                       action: 'save\_crypto\_order',

&#x20;                       order\_id: orderId,

&#x20;                       tx\_hash: tx.transactionHash,

&#x20;                       wallet\_address: userAccount,

&#x20;                       amount: totalAmount

&#x20;                   })

&#x20;               });

&#x20;               const result = await response.json();

&#x20;               if (result.success) {

&#x20;                   showStatus('Pembayaran berhasil! Pesanan sedang diproses.', 'success');

&#x20;                   setTimeout(() => { window.location.href = 'orders.php?payment\_success=1'; }, 2000);

&#x20;               } else {

&#x20;                   showStatus('Pembayaran di blockchain berhasil, tapi gagal simpan ke server.', 'warning');

&#x20;               }

&#x20;           } catch (error) {

&#x20;               showStatus('Transaksi gagal: ' + error.message, 'danger');

&#x20;               document.getElementById('payBtn').disabled = false;

&#x20;               document.getElementById('payBtn').innerHTML = '<i class="fab fa-ethereum me-2"></i>Bayar dengan ETH';

&#x20;           }

&#x20;       }

&#x20;   </script>

</body>

</html>

4\. Save (Ctrl+S) → tutup Notepad

BAB 10: BUAT PHP API BRIDGE

STEP 15: Buat File API

1\. Masuk ke folder C:\\xampp\\htdocs\\project\_jersey\\

2\. Klik kanan → New Folder → rename api

3\. Buka folder api

4\. Klik kanan → New Text Document → rename blockchain-api.php

5\. Buka dengan Notepad, paste ini:

<?php

$root = dirname(\_\_DIR\_\_);

require\_once $root . '/config/database.php';

require\_once $root . '/config/functions.php';

header('Content-Type: application/json');

$input = json\_decode(file\_get\_contents('php://input'), true);

$action = $input\['action'] ?? '';

if ($action === 'save\_crypto\_order') {

&#x20;   $order\_id = (int)($input\['order\_id'] ?? 0);

&#x20;   $tx\_hash = $input\['tx\_hash'] ?? '';

&#x20;   $wallet\_address = $input\['wallet\_address'] ?? '';

&#x20;   if (!$order\_id || !$tx\_hash || !$wallet\_address) {

&#x20;       echo json\_encode(\['success' => false, 'message' => 'Data tidak lengkap']);

&#x20;       exit();

&#x20;   }

&#x20;   $stmt = $pdo->prepare("UPDATE orders SET payment\_type='crypto', payment\_status='paid', tx\_hash=?, wallet\_address=?, status='paid', updated\_at=NOW() WHERE id=?");

&#x20;   $stmt->execute(\[$tx\_hash, $wallet\_address, $order\_id]);

&#x20;   echo json\_encode(\['success' => true, 'message' => 'Pembayaran tersimpan']);

} else {

&#x20;   echo json\_encode(\['success' => false, 'message' => 'Action tidak valid']);

}

6\. Save → tutup Notepad

Test file API:

Buka browser → http://localhost/project\_jersey/api/blockchain-api.php

Harusnya muncul:

{"success":false,"message":"Action tidak valid"}

Kalau ada error merah/putih, berarti ada masalah di kode PHP.

BAB 11: UPDATE CHECKOUT PAGE

STEP 16: Update cart.php

Buka C:\\xampp\\htdocs\\project\_jersey\\cart.php dengan Notepad.

Perubahan yang perlu dilakukan:

1\. Ganti baris checkout (baris 10):

Dari:

if($\_SERVER\['REQUEST\_METHOD'] === 'POST' \&\& isset($\_POST\['checkout'])) {

Jadi:

if($\_SERVER\['REQUEST\_METHOD'] === 'POST' \&\& (isset($\_POST\['checkout\_qris']) || isset($\_POST\['checkout\_crypto']))) {

2\. Ganti redirect (baris 66):

Dari:

redirect('generate-qr.php?order\_id=' . $order\_id);

Jadi:

if(isset($\_POST\['checkout\_crypto'])) {

&#x20;   redirect('crypto-payment.php?order\_id=' . $order\_id);

} else {

&#x20;   redirect('generate-qr.php?order\_id=' . $order\_id);

}

3\. Tambah CSS (setelah style .btn-checkout:hover):

.crypto-logo {

&#x20;   width: 50px;

&#x20;   height: 50px;

&#x20;   background: linear-gradient(135deg, #627eea 0%, #3b5998 100%);

&#x20;   border-radius: 10px;

&#x20;   display: flex;

&#x20;   align-items: center;

&#x20;   justify-content: center;

&#x20;   margin-right: 15px;

}

.crypto-logo i {

&#x20;   font-size: 28px;

&#x20;   color: white;

}

.btn-crypto {

&#x20;   background: linear-gradient(135deg, #627eea 0%, #3b5998 100%);

&#x20;   border: none;

&#x20;   padding: 12px;

&#x20;   font-weight: bold;

&#x20;   color: white;

}

.btn-crypto:hover {

&#x20;   transform: translateY(-2px);

&#x20;   box-shadow: 0 5px 15px rgba(98, 126, 234, 0.4);

&#x20;   color: white;

}

4\. Tambah opsi Crypto (setelah div QRIS di payment method):

<div class="payment-method" id="cryptoMethod" onclick="selectPayment('crypto')">

&#x20;   <div class="d-flex align-items-center">

&#x20;       <div class="crypto-logo">

&#x20;           <i class="fab fa-ethereum"></i>

&#x20;       </div>

&#x20;       <div class="flex-grow-1">

&#x20;           <h6 class="mb-0">Crypto (ETH)</h6>

&#x20;           <small class="text-muted">Bayar pakai Ethereum via MetaMask</small>

&#x20;       </div>

&#x20;       <div class="form-check">

&#x20;           <input type="radio" name="payment\_method" value="crypto">

&#x20;       </div>

&#x20;   </div>

</div>

5\. Ganti tombol checkout:

Dari:

<button type="submit" class="btn btn-checkout w-100 text-white">

&#x20;   <i class="fas fa-qrcode"></i> Bayar dengan QRIS

</button>

Jadi:

<button type="submit" name="checkout\_qris" class="btn btn-checkout w-100 text-white mb-2">

&#x20;   <i class="fas fa-qrcode"></i> Bayar dengan QRIS

</button>

<button type="button" class="btn btn-crypto w-100" onclick="checkoutCrypto()">

&#x20;   <i class="fab fa-ethereum"></i> Bayar dengan Crypto (ETH)

</button>

6\. Update JavaScript (ganti function selectPayment + tambah checkoutCrypto):

function selectPayment(method) {

&#x20;   document.querySelectorAll('.payment-method').forEach(el => {

&#x20;       el.classList.remove('selected');

&#x20;   });

&#x20;   document.getElementById(method + 'Method').classList.add('selected');

&#x20;   document.querySelector('input\[name="payment\_method"]\[value="' + method + '"]').checked = true;

}

function checkoutCrypto() {

&#x20;   const shippingAddress = document.querySelector('textarea\[name="shipping\_address"]').value;

&#x20;   if (!shippingAddress.trim()) {

&#x20;       alert('Mohon isi alamat pengiriman!');

&#x20;       return;

&#x20;   }

&#x20;   const form = document.getElementById('checkoutForm');

&#x20;   const input = document.createElement('input');

&#x20;   input.type = 'hidden';

&#x20;   input.name = 'checkout\_crypto';

&#x20;   input.value = '1';

&#x20;   form.appendChild(input);

&#x20;   form.submit();

}

7\. Save → tutup Notepad

BAB 12: TEST KESELURUHAN

STEP 17: Test Crypto Payment

Sebelum test, pastikan:

\- ✅ XAMPP Running (Apache + MySQL)

\- ✅ Ganache Running

\- ✅ MetaMask connected ke Ganache (ada saldo ETH)

\- ✅ Website project\_jersey bisa diakses

Alur Test:

1\. Buka browser → http://localhost/project\_jersey

2\. Login ke website

3\. Pilih jersey → Add to Cart

4\. Buka Keranjang → isi alamat pengiriman

5\. Pilih "Crypto (ETH)" di metode pembayaran

6\. Klik "Bayar dengan Crypto (ETH)"

7\. Di halaman crypto-payment.php:

\- Klik "Connect MetaMask"

\- MetaMask popup → "Connect"

\- Klik "Bayar dengan ETH"

\- MetaMask popup → "Confirm"

8\. Tunggu transaksi selesai

9\. Redirect ke orders.php dengan pesan "Pembayaran Berhasil"

Verifikasi:

Cek	Di Mana

Transaksi blockchain	Ganache → TRANSACTIONS

Saldo berkurang	MetaMask

Order status	orders.php

Payment type	phpMyAdmin → orders

TX Hash	phpMyAdmin → orders

BAB 13: TROUBLESHOOTING

Error Umum dan Solusi

Error	Penyebab	Solusi

"Web3 is not defined"	CDN Web3.js tidak load	Ganti URL CDN ke https://cdnjs.cloudflare.com/ajax/libs/web3/4.2.2/web3.min.js

"Harus di network Ganache"	MetaMask di network salah	Switch ke network Ganache di MetaMask

"Unexpected token '<'"	PHP return HTML error	Cek api/blockchain-api.php, hapus session\_start()

"Invalid params input"	Web3.js v4 encoding beda	Pakai web3.eth.abi.encodeFunctionCall() manual

"Cannot read properties of undefined"	ethers tidak terload	Cek hardhat.config.ts ada plugins: \[hardhatEthers]

Contract address salah	Deploy ulang tanpa update	Buka config/contract\_address.txt, copy address baru

MetaMask tidak detect	Ganache belum jalan	Pastikan Ganache running di port 7545

Saldo 0 ETH	Belum import account	Import private key dari Ganache ke MetaMask

Checklist Sebelum Test

\- Node.js terinstall (node --version)

\- Ganache running

\- MetaMask connected ke Ganache

\- MetaMask ada saldo ETH

\- Hardhat project sudah di-init

\- Smart Contract sudah di-compile

\- Smart Contract sudah di-deploy

\- Contract address tersimpan di config/contract\_address.txt

\- Database MySQL sudah ditambah kolom blockchain

\- File crypto-payment.php sudah dibuat

\- File api/blockchain-api.php sudah dibuat

\- File cart.php sudah diupdate

Struktur File Final

project\_jersey/

├── blockchain/

│   ├── contracts/

│   │   └── HSportStore.sol

│   ├── scripts/

│   │   └── deploy.js

│   ├── config/

│   │   ├── contract\_address.txt

│   │   └── contract\_abi.json

│   ├── artifacts/

│   ├── hardhat.config.ts

│   └── package.json

├── api/

│   └── blockchain-api.php

├── crypto-payment.php

├── cart.php (updated)

├── config/

│   ├── database.php

│   └── functions.php

└── (file PHP lainnya...)

