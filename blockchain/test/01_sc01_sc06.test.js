const HSportStore = artifacts.require("HSportStore");
const { expectRevert } = require("./helpers/expectRevert");

/**
 * SC-01 s.d. SC-06 — kontrak V1.
 * Definisi lengkap: docs/week1/07_TEST_PLAN.md bagian 7.1.
 */
contract("HSportStore — SC-01 s.d. SC-06 (V1)", (accounts) => {
  const [owner, buyer, operator, outsider] = accounts;
  const AMOUNT = web3.utils.toWei("1", "ether");
  const ORDER_NUMBER = "INV-2026-0001";

  let store;
  let hash;

  const makeHash = (orderNumber, account, amount) =>
    web3.utils.soliditySha3(
      { type: "string", value: orderNumber },
      { type: "address", value: account },
      { type: "uint256", value: amount }
    );

  const createOrder = (overrides = {}) => {
    const orderNumber = overrides.orderNumber || ORDER_NUMBER;
    const buyerAccount = overrides.buyer || buyer;
    const amount = overrides.amount || AMOUNT;
    const value = overrides.value !== undefined ? overrides.value : amount;
    const orderHash = overrides.hash || makeHash(orderNumber, buyerAccount, amount);

    return store.createOrder(orderHash, orderNumber, amount, {
      from: buyerAccount,
      value,
    });
  };

  beforeEach(async () => {
    store = await HSportStore.new({ from: owner });
    hash = makeHash(ORDER_NUMBER, buyer, AMOUNT);
  });

  it("SC-01: inisialisasi contract, owner, dan akses owner", async () => {
    assert.equal(await store.owner(), owner, "owner harus deployer");
    assert.equal((await store.orderCount()).toString(), "0", "orderCount awal 0");
    assert.equal(await store.isOperator(owner), true, "owner selalu operator");
    assert.equal(await store.isOperator(operator), false, "operator belum terdaftar");

    await expectRevert(store.addOperator(operator, { from: outsider }), "Not owner");

    const receipt = await store.addOperator(operator, { from: owner });
    assert.equal(await store.isOperator(operator), true, "operator harus aktif");

    const event = receipt.logs.find((log) => log.event === "OperatorUpdated");
    assert.ok(event, "event OperatorUpdated harus dipancarkan");
    assert.equal(event.args.operator, operator, "alamat operator sesuai");
    assert.equal(event.args.active, true, "status event harus aktif");

    await expectRevert(
      store.addOperator(operator, { from: owner }),
      "Operator already active"
    );
    await expectRevert(
      store.addOperator("0x0000000000000000000000000000000000000000", { from: owner }),
      "Invalid operator"
    );
    await expectRevert(
      store.removeOperator(owner, { from: owner }),
      "Cannot remove owner"
    );

    await store.removeOperator(operator, { from: owner });
    assert.equal(await store.isOperator(operator), false, "operator harus nonaktif");
    await expectRevert(
      store.removeOperator(operator, { from: owner }),
      "Operator not active"
    );
  });

  it("SC-02: createOrder valid — escrow, state Created, event", async () => {
    const receipt = await createOrder();

    assert.equal((await store.orderCount()).toString(), "1", "orderCount harus 1");
    assert.equal((await store.getOrderIdByHash(hash)).toString(), "1", "hash harus terdaftar");

    const order = await store.getOrder(1);
    assert.equal(order.id.toString(), "1", "id harus 1");
    assert.equal(order.externalOrderHash, hash, "externalOrderHash harus sama");
    assert.equal(order.buyer, buyer, "buyer harus sama");
    assert.equal(order.totalAmount.toString(), AMOUNT, "totalAmount harus sama");
    assert.equal(order.status.toString(), "0", "status harus Created");
    assert.equal(order.paymentRecorded, false, "paymentRecorded harus false");
    assert.equal(order.orderNumber, ORDER_NUMBER, "orderNumber harus sama");
    assert.ok(order.createdAt.toString() !== "0", "createdAt harus terisi");
    assert.equal(
      order.updatedAt.toString(),
      order.createdAt.toString(),
      "updatedAt harus sama dengan createdAt saat dibuat"
    );

    const escrow = await store.escrows(1);
    assert.equal(escrow.orderId.toString(), "1", "escrow orderId harus 1");
    assert.equal(escrow.buyer, buyer, "escrow buyer harus sama");
    assert.equal(escrow.amount.toString(), AMOUNT, "escrow amount harus sama");
    assert.equal(escrow.released, false, "escrow belum dirilis");
    assert.equal(escrow.refunded, false, "escrow belum direfund");

    const event = receipt.logs.find((log) => log.event === "OrderCreated");
    assert.ok(event, "event OrderCreated harus dipancarkan");
    assert.equal(event.args.orderId.toString(), "1", "event orderId harus 1");
    assert.equal(event.args.buyer, buyer, "event buyer harus sama");
    assert.equal(event.args.externalOrderHash, hash, "event hash harus sama");
    assert.equal(event.args.amount.toString(), AMOUNT, "event amount harus sama");
    assert.equal(event.args.orderNumber, ORDER_NUMBER, "event orderNumber harus sama");

    await expectRevert(
      store.createOrder(makeHash("", buyer, AMOUNT), "", AMOUNT, {
        from: buyer,
        value: AMOUNT,
      }),
      "Order number required"
    );
  });

  it("SC-03: createOrder amount tidak valid revert", async () => {
    await expectRevert(
      store.createOrder(makeHash("INV-2026-0002", buyer, 0), "INV-2026-0002", 0, {
        from: buyer,
        value: 0,
      }),
      "Amount must be > 0"
    );

    const half = web3.utils.toWei("0.5", "ether");
    await expectRevert(
      store.createOrder(makeHash("INV-2026-0003", buyer, AMOUNT), "INV-2026-0003", AMOUNT, {
        from: buyer,
        value: half,
      }),
      "Incorrect amount"
    );

    assert.equal((await store.orderCount()).toString(), "0", "orderCount tidak boleh bertambah");
  });

  it("SC-04: duplicate externalOrderHash revert", async () => {
    await createOrder();
    assert.equal((await store.orderCount()).toString(), "1", "order pertama tercatat");

    await expectRevert(
      store.createOrder(hash, "INV-2026-0001-DUP", AMOUNT, {
        from: buyer,
        value: AMOUNT,
      }),
      "Duplicate order hash"
    );

    assert.equal((await store.orderCount()).toString(), "1", "orderCount tetap 1");
    assert.equal((await store.getOrderIdByHash(hash)).toString(), "1", "hash tetap ke order 1");
  });

  it("SC-05: getOrder ID valid mengembalikan data benar", async () => {
    await createOrder();

    const order = await store.getOrder(1);
    const alias = await store.getOrderInfo(1);
    const status = await store.getOrderStatus(1);

    assert.equal(alias.id.toString(), order.id.toString(), "alias id harus sama");
    assert.equal(alias.externalOrderHash, order.externalOrderHash, "alias hash harus sama");
    assert.equal(alias.orderNumber, order.orderNumber, "alias orderNumber harus sama");
    assert.equal(status.toString(), "0", "getOrderStatus harus Created");

    const unknown = await store.getOrderIdByHash(makeHash("INV-2026-9999", buyer, AMOUNT));
    assert.equal(unknown.toString(), "0", "hash tidak dikenal harus 0");
  });

  it("SC-06: getOrder ID tidak ada revert", async () => {
    await expectRevert(store.getOrder(0), "Order not found");
    await expectRevert(store.getOrder(999), "Order not found");
    await expectRevert(store.getOrderInfo(999), "Order not found");
    await expectRevert(store.getOrderStatus(999), "Order not found");
    await expectRevert(store.confirmReceived(999, { from: buyer }), "Order not found");
  });
});
