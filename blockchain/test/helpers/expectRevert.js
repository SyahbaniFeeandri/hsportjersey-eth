/**
 * Helper untuk memastikan sebuah transaksi revert dengan alasan tertentu.
 * Dipakai oleh test SC dan smoke test.
 */
async function expectRevert(promise, reason) {
  try {
    await promise;
  } catch (err) {
    if (reason && !err.message.includes(reason)) {
      throw new Error(`Expected revert "${reason}", got: ${err.message}`);
    }
    return;
  }
  throw new Error(`Expected revert "${reason || "any reason"}" but transaction succeeded`);
}

module.exports = { expectRevert };
