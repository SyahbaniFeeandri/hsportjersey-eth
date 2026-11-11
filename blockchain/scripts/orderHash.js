#!/usr/bin/env node
/**
 * Helper hash kanonik order (W3-LUI-04).
 *
 * Formula: keccak256(abi.encode(orderNumber, buyer, amountWei))
 * Sama dengan yang dipakai test SC dan frontend crypto-payment.php.
 *
 * Pemakaian:
 *   node scripts/orderHash.js INV-2026-0001 0xFFcf...09f0 1000000000000000000
 */

const { Web3 } = require("web3");

const web3 = new Web3();

function orderHash(orderNumber, buyer, amountWei) {
  if (!orderNumber) throw new Error("orderNumber wajib diisi");
  if (!/^0x[0-9a-fA-F]{40}$/.test(buyer)) throw new Error("buyer bukan address valid");
  if (!/^[0-9]+$/.test(String(amountWei))) throw new Error("amountWei harus bilangan bulat wei");

  return web3.utils.soliditySha3(
    { type: "string", value: orderNumber },
    { type: "address", value: buyer },
    { type: "uint256", value: String(amountWei) }
  );
}

module.exports = { orderHash };

if (require.main === module) {
  const [orderNumber, buyer, amountWei] = process.argv.slice(2);
  if (!orderNumber || !buyer || !amountWei) {
    console.error("Pemakaian: node scripts/orderHash.js <orderNumber> <buyer> <amountWei>");
    process.exit(1);
  }
  console.log(orderHash(orderNumber, buyer, amountWei));
}
