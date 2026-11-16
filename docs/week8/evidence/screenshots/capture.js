const { chromium } = require('playwright-core');
const fs = require('fs');
const path = require('path');

const BASE = 'http://127.0.0.1:8080';
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const OUT = 'C:\\laragon\\www\\project_jersey\\docs\\week8\\evidence\\screenshots';
const CHAIN_ORDER_ID = process.env.CHAIN_ORDER_ID || '46';

const results = [];

async function contentClip(page) {
  const h = await page.evaluate(() => {
    let bottom = 0;
    document.querySelectorAll('body *').forEach((el) => {
      if (el.closest('footer')) return;
      const r = el.getBoundingClientRect();
      if (r.width > 4 && r.height > 4) {
        bottom = Math.max(bottom, r.bottom + window.scrollY);
      }
    });
    return Math.ceil(bottom);
  });
  return Math.max(h + 24, 900);
}

async function shot(page, file, url) {
  if (url) {
    await page.goto(BASE + url, { waitUntil: 'domcontentloaded', timeout: 30000 });
  }
  await page.waitForTimeout(1800);
  const height = Math.min(await contentClip(page), 6000);
  const target = path.join(OUT, file);
  await page.screenshot({ path: target, fullPage: true, clip: { x: 0, y: 0, width: 1440, height } });
  const size = fs.statSync(target).size;
  const title = (await page.title()).trim();
  results.push({ file, url: url || page.url(), title, height, bytes: size, status: size > 15000 ? 'OK' : 'SUSPECT' });
  console.log(`SHOT ${file.padEnd(36)} ${String(size).padStart(8)} B  h=${height}  ${title}`);
}

async function login(page, email, password) {
  await page.goto(BASE + '/login.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
    page.click('button[type="submit"]'),
  ]);
  await page.waitForTimeout(600);
}

(async () => {
  fs.rmSync(OUT, { recursive: true, force: true });
  fs.mkdirSync(OUT, { recursive: true });

  const browser = await chromium.launch({
    executablePath: CHROME,
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage', '--hide-scrollbars'],
  });

  try {
    // ---------------- CUSTOMER ----------------
    const custCtx = await browser.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1.5 });
    const cust = await custCtx.newPage();
    await login(cust, 'customer@example.com', 'customer123');
    console.log('customer login ->', cust.url());

    await shot(cust, '01_customer_home.png', '/index.php');
    await shot(cust, '02_customer_catalog.png', '/products.php');
    await shot(cust, '03_customer_product_detail.png', '/product-detail.php?id=1');

    await cust.goto(BASE + '/cart.php', { waitUntil: 'domcontentloaded' });
    const addRes = await cust.evaluate(async () => {
      const body = new URLSearchParams({ product_id: '1', quantity: '1', size: 'L', color: '', custom_text: 'DEMO-M8' });
      const r = await fetch('/add-to-cart.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
      return { status: r.status, text: await r.text() };
    });
    console.log('add-to-cart ->', addRes.status, addRes.text.slice(0, 120));

    await shot(cust, '04_customer_cart_checkout.png', '/cart.php');

    await cust.selectOption('select[name="shipping_method"]', 'JNE Reguler');
    await cust.fill('textarea[name="shipping_address"]', 'Jl. Demo Screenshot No. 8, Bandung');
    await cust.check('input[name="payment_method"][value="qris"]', { force: true });
    await Promise.all([
      cust.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 40000 }),
      cust.click('button[name="checkout_qris"]'),
    ]);
    const payUrl = cust.url();
    const orderId = (payUrl.match(/order_id=(\d+)/) || [])[1];
    console.log('checkout ->', payUrl, 'order', orderId);
    await shot(cust, '05_customer_payment_qris.png', null);

    await shot(cust, '06_customer_orders.png', '/orders.php');
    if (orderId) {
      await shot(cust, '07_customer_order_detail.png', `/order-detail.php?id=${orderId}`);
    }

    // ---------------- ADMIN ----------------
    const adminCtx = await browser.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1.5 });
    const admin = await adminCtx.newPage();
    await login(admin, 'admin@hsport.com', 'admin123');
    console.log('admin login ->', admin.url());

    await shot(admin, '08_admin_dashboard.png', '/admin/dashboard.php');
    await shot(admin, '09_admin_orders.png', '/admin/orders.php');

    await admin.goto(BASE + '/admin/orders.php', { waitUntil: 'domcontentloaded' });
    const verifyBtn = admin.locator(`form:has(input[name="order_id"][value="${CHAIN_ORDER_ID}"]) button[name="verify_order"]`).first();
    if (await verifyBtn.count()) {
      await Promise.all([
        admin.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60000 }),
        verifyBtn.click(),
      ]);
      await admin.waitForTimeout(1200);
      const body = await admin.content();
      console.log('verify message present ->', new RegExp(`Verifikasi order #${CHAIN_ORDER_ID}`).test(body));
      await shot(admin, '10_admin_verify_verified.png', null);
    } else {
      console.log('verify button not found for order', CHAIN_ORDER_ID);
    }

    await shot(admin, '11_admin_order_detail.png', `/admin/order-detail.php?id=${CHAIN_ORDER_ID}`);
    await shot(admin, '12_blockchain_proof_onchain.png', `/blockchain-proof.php?order_id=${CHAIN_ORDER_ID}`);
    await shot(admin, '13_admin_performance.png', '/admin/performance.php');
    await shot(admin, '14_admin_reports.png', '/admin/reports.php');
    await shot(admin, '15_admin_products.png', '/admin/products.php');
  } finally {
    await browser.close();
  }

  const meta = {
    captured_at: new Date().toISOString(),
    base_url: BASE,
    contract: '0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab',
    chain_order_id: Number(CHAIN_ORDER_ID),
    customer_checkout_order: Number(results.find((r) => r.file.startsWith('07_')) ? (results.find((r) => r.file.startsWith('07_')).url.match(/id=(\d+)/) || [])[1] : 0),
    viewport: '1440x900 @1.5x, full page (dipotong ke tinggi konten)',
    screenshots: results,
  };
  fs.writeFileSync(path.join(OUT, 'screenshots.json'), JSON.stringify(meta, null, 2));
  console.log('\nTOTAL', results.length, 'screenshots;', results.filter((r) => r.status !== 'OK').length, 'suspect');
})();

