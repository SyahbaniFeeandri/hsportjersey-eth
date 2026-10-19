const { Web3 } = require("web3");

const RPC_URL = process.env.GANACHE_URL || "http://127.0.0.1:7545";

async function main() {
  const web3 = new Web3(RPC_URL);
  try {
    const chainId = await web3.eth.getChainId();
    const blockNumber = await web3.eth.getBlockNumber();
    const accounts = await web3.eth.getAccounts();

    console.log("Ganache connected:", RPC_URL);
    console.log("Chain ID:", chainId.toString());
    console.log("Block number:", blockNumber.toString());
    console.log("Accounts:", accounts.length);
    if (accounts[0]) {
      console.log("First account:", accounts[0]);
    }
  } catch (err) {
    console.error("Gagal connect ke Ganache di", RPC_URL);
    console.error(err.message);
    console.error("Pastikan Ganache GUI/CLI nyala. Default port 7545; CLI sering 8545 (set GANACHE_URL).");
    process.exit(1);
  }
}

main();
