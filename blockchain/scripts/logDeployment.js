const fs = require("fs");
const path = require("path");

/**
 * Mencatat hasil deployment ke blockchain/deployments/.
 * Jalankan setelah `truffle migrate`:
 *   node scripts/logDeployment.js
 */
async function main() {
  const root = path.join(__dirname, "..");
  const artifactPath = path.join(root, "build", "contracts", "HSportStore.json");
  const addressPath = path.join(root, "config", "contract_address.txt");

  if (!fs.existsSync(artifactPath)) {
    throw new Error("Artifact tidak ditemukan. Jalankan `truffle migrate` lebih dulu.");
  }

  const artifact = JSON.parse(fs.readFileSync(artifactPath, "utf8"));
  const networks = artifact.networks || {};
  const networkIds = Object.keys(networks);
  if (networkIds.length === 0) {
    throw new Error("Artifact belum punya data network. Deploy belum berhasil?");
  }

  // Pilih network yang dipakai saat ini: NETWORK_ID env, atau entri terakhir.
  const preferredNetworkId = process.env.NETWORK_ID || "5777";
  const networkId = networks[preferredNetworkId] ? preferredNetworkId : networkIds[networkIds.length - 1];
  const deployment = networks[networkId];
  const address = deployment.address;
  const txHash = deployment.transactionHash;
  const configAddress = fs.existsSync(addressPath)
    ? fs.readFileSync(addressPath, "utf8").trim()
    : "";

  const outDir = path.join(root, "deployments");
  fs.mkdirSync(outDir, { recursive: true });

  const now = new Date();
  const stamp = now.toISOString().replace(/[:.]/g, "-");
  const record = {
    logged_at: now.toISOString(),
    contract: "HSportStore",
    compiler: artifact.compiler ? artifact.compiler.version : "unknown",
    network_id: networkId,
    chain_id: Number(process.env.CHAIN_ID || 1337),
    rpc_url: process.env.RPC_URL || "http://127.0.0.1:7545",
    address,
    transaction_hash: txHash,
    config_address: configAddress,
    config_address_matches: configAddress.toLowerCase() === String(address).toLowerCase(),
  };

  const jsonPath = path.join(outDir, `deployment_${stamp}.json`);
  fs.writeFileSync(jsonPath, JSON.stringify(record, null, 2));

  const logPath = path.join(outDir, "DEPLOYMENTS.md");
  if (!fs.existsSync(logPath)) {
    fs.writeFileSync(
      logPath,
      "# Deployment Log\n\n| Waktu (UTC) | Contract | Network ID | Chain ID | Address | Tx Hash |\n|---|---|---:|---:|---|---|\n"
    );
  }
  fs.appendFileSync(
    logPath,
    `| ${record.logged_at} | ${record.contract} | ${record.network_id} | ${record.chain_id} | \`${record.address}\` | \`${record.transaction_hash}\` |\n`
  );

  console.log("Deployment tercatat:", jsonPath);
  console.log("Address:", address);
  console.log("Network ID:", networkId, "| Chain ID:", record.chain_id);
  if (!record.config_address_matches) {
    console.warn("PERINGATAN: config/contract_address.txt tidak sama dengan address artifact.");
  }
}

main().catch((err) => {
  console.error(err.message);
  process.exit(1);
});
