// @ts-check
const { test, expect } = require('@playwright/test');

const ADMIN_USER = process.env.WP_ADMIN_USER || 'admin';
const ADMIN_PASS = process.env.WP_ADMIN_PASS || 'password';

async function loginAdmin(page) {
	await page.goto('/wp-login.php');
	await page.locator('#user_login').fill(ADMIN_USER);
	await page.locator('#user_pass').fill(ADMIN_PASS);
	await page.locator('#wp-submit').click();
	await page.waitForURL(/wp-admin/);
}

// ── 1. 規則編輯器載入 ─────────────────────────────────────────────────────────
test('付款規則編輯器正常載入', async ({ page }) => {
	await loginAdmin(page);
	await page.goto('/wp-admin/admin.php?page=taiwan-store-core&tab=rules&type=payment');

	await expect(page.locator('#wc-tw-rules-app')).toBeVisible({ timeout: 10_000 });
	// 等待 JS 渲染完成（loading 消失）
	await expect(page.locator('.rules-loading')).toBeHidden({ timeout: 8_000 });
	await expect(page.locator('.taiwan-store-core-page-header, .taiwan-store-core-empty-state')).toBeVisible();
});

test('運費規則編輯器正常載入', async ({ page }) => {
	await loginAdmin(page);
	await page.goto('/wp-admin/admin.php?page=taiwan-store-core&tab=rules&type=shipping');
	await expect(page.locator('.rules-loading')).toBeHidden({ timeout: 8_000 });
	await expect(page.locator('.taiwan-store-core-page-header, .taiwan-store-core-empty-state')).toBeVisible();
});

// ── 2. 新增規則流程 ────────────────────────────────────────────────────────────
test('可以新增一條付款規則', async ({ page }) => {
	await loginAdmin(page);
	await page.goto('/wp-admin/admin.php?page=taiwan-store-core&tab=rules&type=payment');
	await expect(page.locator('.rules-loading')).toBeHidden({ timeout: 8_000 });

	// 點擊新增規則
	await page.locator('#taiwan-store-core-add-btn').click();
	await expect(page.locator('.taiwan-store-core-modal, [class*="modal"]')).toBeVisible({ timeout: 3_000 });
});

// ── 3. 電子發票設定頁 ─────────────────────────────────────────────────────────
test('電子發票設定頁正常顯示', async ({ page }) => {
	await loginAdmin(page);
	await page.goto('/wp-admin/admin.php?page=taiwan-store-core&tab=invoice');
	await expect(page.locator('h2').filter({ hasText: 'ECPay 電子發票設定' })).toBeVisible();
	await expect(page.locator('[name="ts_invoice_enabled"]')).toBeVisible();
});

// ── 4. 結帳設定儲存 ────────────────────────────────────────────────────────────
test('結帳設定頁可以儲存', async ({ page }) => {
	await loginAdmin(page);
	await page.goto('/wp-admin/admin.php?page=taiwan-store-core&tab=checkout');
	await page.locator('input[type="submit"]').click();
	await expect(
		page.locator('.updated, .notice-success').filter({ hasText: /設定已儲存|Settings saved/ })
	).toBeVisible({ timeout: 5_000 });
});
