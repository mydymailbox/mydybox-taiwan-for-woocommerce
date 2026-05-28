// @ts-check
const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
	testDir: './specs',
	timeout: 30_000,
	retries: 1,
	reporter: [['html', { open: 'never' }], ['list']],

	use: {
		baseURL: process.env.WP_BASE_URL || 'http://woo.local',
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
		locale: 'zh-TW',
	},

	projects: [
		{ name: 'chromium', use: { ...devices['Desktop Chrome'] } },
		{ name: 'mobile',   use: { ...devices['iPhone 14'] } },
	],
});
