const HSportStore = artifacts.require("HSportStore");

/**
 * Skeleton SC-01 s.d. SC-06 (akan diaktifkan pada Minggu 3).
 *
 * Kontrak V0 saat ini belum memiliki:
 * - enum 8 state,
 * - externalOrderHash + duplicate guard,
 * - operator management.
 *
 * Karena itu test di bawah sengaja di-skip. Jangan hapus file ini:
 * ini adalah placeholder resmi agar struktur 21 test terlihat sejak awal.
 * Definisi lengkap ada di docs/week1/07_TEST_PLAN.md.
 */
contract("HSportStore — SC-01 s.d. SC-06 (skeleton)", (accounts) => {
  const [owner, buyer] = accounts;

  let store;

  before(async () => {
    store = await HSportStore.new({ from: owner });
  });

  it("SC-01: inisialisasi contract, owner, dan akses owner", async function () {
    this.skip();
  });

  it("SC-02: createOrder valid — escrow, state Created, event", async function () {
    this.skip();
  });

  it("SC-03: createOrder amount tidak valid revert", async function () {
    this.skip();
  });

  it("SC-04: duplicate externalOrderHash revert", async function () {
    this.skip();
  });

  it("SC-05: getOrder ID valid mengembalikan data benar", async function () {
    this.skip();
  });

  it("SC-06: getOrder ID tidak ada revert", async function () {
    this.skip();
  });
});
