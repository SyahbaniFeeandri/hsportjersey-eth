const HSportStore = artifacts.require("HSportStore");
const { expectRevert } = require("./helpers/expectRevert");

/**
 * SC-07 s.d. SC-12 — kontrak V2.
 * Definisi lengkap: docs/week1/07_TEST_PLAN.md bagian 7.1.
 *
 * Catatan R-01: confirmReceived kedua pada order Completed revert
 * "Not shipped yet" karena guard status dievaluasi sebelum guard released.
 */
contract("HSportStore — SC-07 s.d. SC-12 (V2)", (accounts) => {
  const [owner, buyer, operator, outsider] = accounts;
  const AMOUNT = web3.utils.toWei("1", "ether");
  const REASON = "Batal sesuai permintaan pembeli";

  let store;

  const makeHash = (orderNumber, account, amount) =>
    web3.utils.soliditySha3(
      { type: "string", value: orderNumber },
      { type: "address", value: account },
      { type: "uint256", value: amount }
    );

  const createOrder = async (orderNumber, account = buyer, amount = AMOUNT) => {
    const hash = makeHash(orderNumber, account, amount);
    await store.createOrder(hash, orderNumber, amount, {
      from: account,
      value: amount,
    });
    return hash;
  };

  const driveToPaid = async (orderId) => {
    await store.recordPayment(orderId, { from: operator });
  };

  const driveToShipped = async (orderId) => {
    await driveToPaid(orderId);
    await store.updateOrderStatus(orderId, 3, { from: operator }); // InProduction
    await store.updateOrderStatus(orderId, 4, { from: operator }); // ReadyToShip
    await store.shipOrder(orderId, { from: operator });
  };

  const driveToCompleted = async (orderId, account = buyer) => {
    await driveToShipped(orderId);
    await store.confirmReceived(orderId, { from: account });
  };

  const findEvent = (receipt, name) => receipt.logs.find((log) => log.event === name);

  beforeEach(async () => {
    store = await HSportStore.new({ from: owner });
    await store.addOperator(operator, { from: owner });
  });

  it("SC-07: access control menolak non-operator, non-buyer, dan non-owner", async () => {
    await createOrder("INV-2026-1001");

    await expectRevert(store.recordPayment(1, { from: outsider }), "Not operator");
    await expectRevert(store.updateOrderStatus(1, 3, { from: outsider }), "Not operator");
    await expectRevert(store.shipOrder(1, { from: outsider }), "Not operator");
    await expectRevert(store.confirmReceived(1, { from: outsider }), "Not buyer");
    await expectRevert(store.addOperator(outsider, { from: outsider }), "Not owner");
    await expectRevert(store.removeOperator(owner, { from: owner }), "Cannot remove owner");

    assert.equal((await store.getOrderStatus(1)).toString(), "0", "status tetap Created");
  });

  it("SC-08: recordPayment valid, event, dan double payment ditolak", async () => {
    await createOrder("INV-2026-1002");

    const receipt = await store.recordPayment(1, { from: operator });
    const event = findEvent(receipt, "PaymentRecorded");

    assert.ok(event, "event PaymentRecorded harus dipancarkan");
    assert.equal(event.args.orderId.toString(), "1", "orderId event harus 1");
    assert.equal(event.args.operator, operator, "operator event harus sesuai");
    assert.equal(event.args.amount.toString(), AMOUNT, "amount event harus sesuai");

    const order = await store.getOrder(1);
    assert.equal(order.status.toString(), "2", "status harus Paid");
    assert.equal(order.paymentRecorded, true, "paymentRecorded harus true");

    await expectRevert(store.recordPayment(1, { from: operator }), "Payment already recorded");

    await createOrder("INV-2026-1003");
    await store.updateOrderStatus(2, 1, { from: operator }); // AwaitingPayment
    await store.recordPayment(2, { from: operator });
    assert.equal(
      (await store.getOrderStatus(2)).toString(),
      "2",
      "AwaitingPayment -> Paid harus berhasil"
    );
  });

  it("SC-09: transisi valid dan shipOrder memancarkan event", async () => {
    await createOrder("INV-2026-1004");
    await driveToPaid(1);

    const before = await store.getOrder(1);

    const toProduction = await store.updateOrderStatus(1, 3, { from: operator });
    const productionEvent = findEvent(toProduction, "OrderStatusUpdated");
    assert.ok(productionEvent, "event OrderStatusUpdated harus dipancarkan");
    assert.equal(productionEvent.args.oldStatus.toString(), "2", "oldStatus harus Paid");
    assert.equal(productionEvent.args.newStatus.toString(), "3", "newStatus harus InProduction");
    assert.equal(productionEvent.args.operator, operator, "operator event harus sesuai");

    await store.updateOrderStatus(1, 4, { from: operator }); // ReadyToShip
    assert.equal((await store.getOrderStatus(1)).toString(), "4", "status harus ReadyToShip");

    const shipped = await store.shipOrder(1, { from: operator });
    const shippedEvent = findEvent(shipped, "OrderShipped");
    assert.ok(shippedEvent, "event OrderShipped harus dipancarkan");
    assert.equal(shippedEvent.args.orderId.toString(), "1", "orderId event harus 1");
    assert.equal(shippedEvent.args.operator, operator, "operator event harus sesuai");

    const after = await store.getOrder(1);
    assert.equal(after.status.toString(), "5", "status akhir harus Shipped");
    assert.ok(
      web3.utils.toBN(after.updatedAt).gte(web3.utils.toBN(before.updatedAt)),
      "updatedAt tidak boleh mundur"
    );
  });

  it("SC-10: transisi tidak sah ditolak dan state tidak berubah", async () => {
    await createOrder("INV-2026-1005");

    await expectRevert(store.shipOrder(1, { from: operator }), "Not ready to ship");
    await expectRevert(
      store.updateOrderStatus(1, 6, { from: operator }),
      "Invalid state transition"
    );
    await expectRevert(
      store.updateOrderStatus(1, 2, { from: operator }),
      "Invalid state transition"
    );
    assert.equal((await store.getOrderStatus(1)).toString(), "0", "status tetap Created");

    await createOrder("INV-2026-1006");
    await driveToCompleted(2);

    await expectRevert(
      store.updateOrderStatus(2, 3, { from: operator }),
      "Order is final"
    );
    assert.equal((await store.getOrderStatus(2)).toString(), "6", "status tetap Completed");
  });

  it("SC-11: cancel valid, refund, unauthorized, terminal, dan batas reason", async () => {
    await createOrder("INV-2026-1007");

    const contractBalanceBefore = await web3.eth.getBalance(store.address);
    const cancelled = await store.cancelOrder(1, REASON, { from: buyer });
    const contractBalanceAfter = await web3.eth.getBalance(store.address);

    const cancelEvent = findEvent(cancelled, "OrderCancelled");
    const refundEvent = findEvent(cancelled, "EscrowRefunded");
    assert.ok(cancelEvent, "event OrderCancelled harus dipancarkan");
    assert.ok(refundEvent, "event EscrowRefunded harus dipancarkan");
    assert.equal(cancelEvent.args.actor, buyer, "aktor cancel harus buyer");
    assert.equal(cancelEvent.args.reason, REASON, "reason harus tersimpan di event");
    assert.equal(refundEvent.args.buyer, buyer, "penerima refund harus buyer");

    const cancelledOrder = await store.getOrder(1);
    const cancelledEscrow = await store.escrows(1);
    assert.equal(cancelledOrder.status.toString(), "7", "status harus Cancelled");
    assert.equal(cancelledEscrow.refunded, true, "escrow.refunded harus true");
    assert.equal(cancelledEscrow.released, false, "escrow.released harus tetap false");
    assert.equal(
      web3.utils
        .toBN(contractBalanceBefore)
        .sub(web3.utils.toBN(contractBalanceAfter))
        .toString(),
      AMOUNT,
      "saldo kontrak harus berkurang sebesar escrow"
    );

    await createOrder("INV-2026-1008");
    await driveToPaid(2);
    await expectRevert(
      store.cancelOrder(2, "Buyer tidak berwenang", { from: buyer }),
      "Not authorized to cancel"
    );

    const operatorCancel = await store.cancelOrder(2, "Dibatalkan operator", { from: operator });
    assert.ok(findEvent(operatorCancel, "OrderCancelled"), "operator boleh cancel state Paid");
    assert.equal((await store.getOrderStatus(2)).toString(), "7", "status order 2 harus Cancelled");
    assert.equal((await store.escrows(2)).refunded, true, "escrow order 2 harus direfund");

    await createOrder("INV-2026-1009");
    await driveToShipped(3);
    await expectRevert(
      store.cancelOrder(3, "Sudah dikirim", { from: operator }),
      "Invalid state transition"
    );

    await createOrder("INV-2026-1010");
    await driveToCompleted(4);
    await expectRevert(
      store.cancelOrder(4, "Sudah selesai", { from: operator }),
      "Order is final"
    );

    await createOrder("INV-2026-1011");
    await store.updateOrderStatus(5, 1, { from: operator }); // AwaitingPayment
    const boundaryReason = "x".repeat(64);
    await store.cancelOrder(5, boundaryReason, { from: buyer });
    assert.equal((await store.getOrderStatus(5)).toString(), "7", "reason 64 byte diterima");

    await createOrder("INV-2026-1012");
    await expectRevert(
      store.cancelOrder(6, "x".repeat(65), { from: buyer }),
      "Reason too long"
    );
    assert.equal((await store.getOrderStatus(6)).toString(), "0", "status tetap Created");

    await expectRevert(
      store.cancelOrder(6, REASON, { from: outsider }),
      "Not authorized to cancel"
    );

    await expectRevert(store.cancelOrder(1, REASON, { from: buyer }), "Order is final");
    await expectRevert(store.cancelOrder(999, REASON, { from: buyer }), "Order not found");
  });

  it("SC-12: confirmReceived merilis escrow sekali dan double release ditolak", async () => {
    await createOrder("INV-2026-1013");
    await driveToShipped(1);

    const ownerBalanceBefore = await web3.eth.getBalance(owner);
    const receipt = await store.confirmReceived(1, { from: buyer });
    const ownerBalanceAfter = await web3.eth.getBalance(owner);

    assert.ok(findEvent(receipt, "OrderCompleted"), "event OrderCompleted harus dipancarkan");
    assert.ok(findEvent(receipt, "EscrowReleased"), "event EscrowReleased harus dipancarkan");
    assert.ok(
      web3.utils
        .toBN(ownerBalanceAfter)
        .sub(web3.utils.toBN(ownerBalanceBefore))
        .eq(web3.utils.toBN(AMOUNT)),
      "saldo owner harus naik sebesar escrow"
    );

    const order = await store.getOrder(1);
    const escrow = await store.escrows(1);
    assert.equal(order.status.toString(), "6", "status harus Completed");
    assert.equal(escrow.released, true, "escrow.released harus true");
    assert.equal(escrow.refunded, false, "escrow.refunded harus tetap false");

    await expectRevert(store.confirmReceived(1, { from: buyer }), "Not shipped yet");

    await createOrder("INV-2026-1014");
    await driveToPaid(2);
    await expectRevert(store.confirmReceived(2, { from: buyer }), "Not shipped yet");

    await createOrder("INV-2026-1015");
    await driveToShipped(3);
    await expectRevert(store.confirmReceived(3, { from: outsider }), "Not buyer");
  });
});
