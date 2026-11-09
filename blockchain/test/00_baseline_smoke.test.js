const HSportStore = artifacts.require("HSportStore");
const { expectRevert } = require("./helpers/expectRevert");

/**
 * Baseline smoke test untuk kontrak V0 (4 state).
 *
 * Ini BUKAN bagian dari 21 test wajib. Tujuannya hanya memastikan
 * harness Truffle berjalan dan perilaku dasar kontrak saat ini terverifikasi.
 * Test SC-01 s.d. SC-12 yang sebenarnya akan ditulis setelah kontrak
 * di-extend ke interface target (Minggu 3-4).
 */
contract("HSportStore — baseline smoke (V0, bukan 21 test)", (accounts) => {
  const [owner, buyer] = accounts;
  const AMOUNT = web3.utils.toWei("1", "ether");

  let store;

  beforeEach(async () => {
    store = await HSportStore.new({ from: owner });
  });

  it("SMOKE-01: owner adalah deployer", async () => {
    assert.equal(await store.owner(), owner, "owner harus deployer");
    assert.equal((await store.orderCount()).toString(), "0", "orderCount awal 0");
  });

  it("SMOKE-02: createOrder valid mengunci escrow dan memancarkan event", async () => {
    const receipt = await store.createOrder("ORDER-SMOKE-001", AMOUNT, {
      from: buyer,
      value: AMOUNT,
    });

    assert.equal((await store.orderCount()).toString(), "1", "orderCount harus 1");

    const order = await store.getOrderInfo(1);
    assert.equal(order.buyer, buyer, "buyer harus sesuai");
    assert.equal(order.totalAmount.toString(), AMOUNT, "amount harus sesuai");
    assert.equal(order.orderNumber, "ORDER-SMOKE-001", "orderNumber harus sesuai");

    const escrow = await store.escrows(1);
    assert.equal(escrow.amount.toString(), AMOUNT, "escrow amount harus sesuai");
    assert.equal(escrow.released, false, "escrow belum dirilis");

    const event = receipt.logs.find((log) => log.event === "OrderCreated");
    assert.ok(event, "event OrderCreated harus dipancarkan");
    assert.equal(event.args.orderId.toString(), "1", "event orderId harus 1");
  });

  it("SMOKE-03: createOrder amount 0 revert", async () => {
    await expectRevert(
      store.createOrder("ORDER-SMOKE-002", 0, { from: buyer, value: 0 }),
      "Amount must be > 0"
    );
    assert.equal((await store.orderCount()).toString(), "0", "orderCount tidak bertambah");
  });

  it("SMOKE-04: createOrder dengan msg.value tidak sesuai revert", async () => {
    const half = web3.utils.toWei("0.5", "ether");
    await expectRevert(
      store.createOrder("ORDER-SMOKE-003", AMOUNT, { from: buyer, value: half }),
      "Incorrect amount"
    );
  });
});
