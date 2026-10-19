const HSportStore = artifacts.require("HSportStore");
const fs = require("fs");
const path = require("path");

module.exports = async function (deployer) {
  await deployer.deploy(HSportStore);
  const instance = await HSportStore.deployed();

  const configDir = path.join(__dirname, "..", "config");
  if (!fs.existsSync(configDir)) {
    fs.mkdirSync(configDir, { recursive: true });
  }

  fs.writeFileSync(path.join(configDir, "contract_address.txt"), instance.address);

  const artifactPath = path.join(__dirname, "..", "build", "contracts", "HSportStore.json");
  const artifact = JSON.parse(fs.readFileSync(artifactPath, "utf8"));
  fs.writeFileSync(
    path.join(configDir, "contract_abi.json"),
    JSON.stringify(artifact.abi, null, 2)
  );

  console.log("HSportStore deployed to:", instance.address);
  console.log("Address and ABI saved to config/");
};
