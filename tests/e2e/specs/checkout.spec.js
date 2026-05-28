// @ts-check
const { test, expect } = require('@playwright/test');

const SHOP_URL     = '/shop';
const CHECKOUT_URL = '/checkout';
const CART_URL     = '/cart';

/**
 * Add the first product on the shop page to cart and go to checkout.
 */
async function addToCartAndCheckout(page) {
	await page.goto(SHOP_URL);
	await page.locator('.add_to_cart_button').first().click();
	await page.waitForURL(/cart|checkout/);
	await page.goto(CHECKOUT_URL);
	await expect(page.locator('form.checkout, form.wc-block-checkout__form')).toBeVisible({ timeout: 10_000 });
}

// ── 1. 進度條顯示 ────────────────────────────────────────────────────────────
test('結帳頁顯示多步驟進度條', async ({ page }) => {
	await addToCartAndCheckout(page);
	const steps = page.locator('.ts-checkout-steps .ts-step');
	await expect(steps).toHaveCount(4);
	await expect(steps.nth(1)).toHaveClass(/active/);
	await expect(steps.nth(0)).toHaveClass(/done/);
});

// ── 2. 縣市／區聯動 ──────────────────────────────────────────────────────────
test('選擇縣市後鄉鎮市區下拉更新', async ({ page }) => {
	await addToCartAndCheckout(page);

	const stateSelect = page.locator('#billing_state');
	await stateSelect.selectOption('TPE'); // 台北市

	// 區域下拉應出現台北市的行政區
	const citySelect = page.locator('#billing_city');
	await expect(citySelect).toBeVisible();
	const options = await citySelect.locator('option').allTextContents();
	expect(options).toContain('中正區');
});

// ── 3. 郵遞區號自動帶入 ───────────────────────────────────────────────────────
test('選擇鄉鎮市區後郵遞區號自動填入', async ({ page }) => {
	await addToCartAndCheckout(page);

	await page.locator('#billing_state').selectOption('TPE');
	await page.waitForTimeout(300);
	await page.locator('#billing_city').selectOption('中正區');
	await page.waitForTimeout(300);

	const postcode = await page.locator('#billing_postcode').inputValue();
	expect(postcode).toBe('100');
});

// ── 4. 地址欄位拆分顯示 ────────────────────────────────────────────────────────
test('地址欄位顯示路名和巷弄兩個欄位', async ({ page }) => {
	await addToCartAndCheckout(page);

	await expect(page.locator('#billing_address_1')).toBeVisible();
	await expect(page.locator('#billing_address_2')).toBeVisible();

	// 確認 placeholder 符合台灣習慣
	const addr1Placeholder = await page.locator('#billing_address_1').getAttribute('placeholder');
	expect(addr1Placeholder).toContain('中山北路');
});

// ── 5. 地址預覽即時更新 ────────────────────────────────────────────────────────
test('填寫地址後即時預覽顯示完整地址', async ({ page }) => {
	await addToCartAndCheckout(page);

	await page.locator('#billing_state').selectOption('TPE');
	await page.waitForTimeout(300);
	await page.locator('#billing_city').selectOption('中正區');
	await page.locator('#billing_address_1').fill('中山北路一段');
	await page.locator('#billing_address_2').fill('10號5樓');
	await page.waitForTimeout(200);

	const preview = page.locator('#ts-address-preview-billing');
	await expect(preview).toBeVisible();
	const text = await preview.textContent();
	expect(text).toContain('中山北路一段');
	expect(text).toContain('10號5樓');
});

// ── 6. 發票類型切換 ────────────────────────────────────────────────────────────
test('選擇公司發票後顯示統編欄位', async ({ page }) => {
	await addToCartAndCheckout(page);

	const invoiceSelect = page.locator('#billing_taiwan_store_core_invoice_type');
	if (!(await invoiceSelect.isVisible())) {
		test.skip();
		return;
	}

	await invoiceSelect.selectOption('company');
	await expect(page.locator('#billing_taiwan_store_core_company_tax_id')).toBeVisible();
	await expect(page.locator('#billing_taiwan_store_core_company_title')).toBeVisible();
});

test('選擇個人發票後隱藏統編欄位', async ({ page }) => {
	await addToCartAndCheckout(page);

	const invoiceSelect = page.locator('#billing_taiwan_store_core_invoice_type');
	if (!(await invoiceSelect.isVisible())) {
		test.skip();
		return;
	}

	await invoiceSelect.selectOption('company');
	await invoiceSelect.selectOption('personal');
	await expect(page.locator('#billing_taiwan_store_core_company_tax_id')).toBeHidden();
});

// ── 7. 手機號碼格式驗證 ────────────────────────────────────────────────────────
test('填寫非台灣手機格式時顯示驗證錯誤', async ({ page }) => {
	await addToCartAndCheckout(page);

	// 填入無效電話並嘗試送出
	await page.locator('#billing_phone').fill('12345');
	await page.locator('#billing_email').fill('test@example.com');
	await page.locator('#place_order, .wc-block-components-checkout-place-order-button').click();

	await expect(
		page.locator('.woocommerce-error, .wc-block-components-validation-error')
	).toBeVisible({ timeout: 5_000 });
});

// ── 8. 社群登入按鈕顯示 ────────────────────────────────────────────────────────
test('登入頁面顯示社群登入按鈕', async ({ page }) => {
	await page.goto('/my-account');
	// 至少一個社群登入按鈕（LINE / Google / Facebook）
	const socialBtns = page.locator('.taiwan-store-core-social-btn');
	// 若未啟用任何社群登入則跳過
	const count = await socialBtns.count();
	if (count === 0) {
		test.skip();
		return;
	}
	await expect(socialBtns.first()).toBeVisible();
});
