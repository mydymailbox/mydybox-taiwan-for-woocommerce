=== Taiwan Store Core ===
Contributors: mydymaibox
Tags: woocommerce, taiwan, checkout, invoice, shipping
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.0.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional Taiwan WooCommerce localization: checkout optimization, tax ID lookup, postcode autofill, and a visual rule engine.

== Description ==

Taiwan Store Core is a professional plugin designed specifically for the Taiwan e-commerce market. It provides a seamless localized checkout experience and a powerful business rule engine to help store owners manage complex local requirements effortlessly.

= Core Features =
* **Taiwan Checkout Optimization** — City/District cascading dropdowns and 3+2 digit postcode auto-fill.
* **Tax ID Integration** — Unified Business Number (UBN), Company Name, Mobile Barcode, Citizen Digital Certificate, and Donation Code support.
* **Smart Tax ID Lookup** — Automatically fetches company names from the official GCIS API upon entering a valid 8-digit Tax ID.
* **Visual Rule Engine** — Manage Payment, Shipping, and Cart rules with a modern visual interface. No coding required.
* **Custom Order Numbers** — Professional sequential formats (e.g., Prefix + YYYYMMDD + Sequence).
* **Checkout Countdown** — Create urgency and improve conversion rates with a customizable timer.
* **Mobile Sticky Bar** — Fixed "Buy Now" button for better mobile user experience.
* **Social Login** — One-click login with LINE, Google, and Facebook.
* **CVS Pickup** — Convenience store pickup integration for ECPay and NewebPay logistics.
* **HPOS Ready** — Fully compatible with WooCommerce High-Performance Order Storage.

== Installation ==

1. Upload the `taiwan-store-core` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure settings under Taiwan Store → General Settings.

== Frequently Asked Questions ==

= What are the system requirements? =
Requires PHP 8.1+, WordPress 6.5+, and WooCommerce 8.0+.

= Is it compatible with other checkout plugins? =
It uses the standard WooCommerce Additional Checkout Fields API, making it compatible with most modern themes and plugins. If you encounter issues, please check the Logs tab.

= Do I need the Pro extensions? =
No. The core plugin works standalone. Pro extensions add advanced features such as electronic invoices, member tiers, and marketing automation.

== Changelog ==

= 1.0.7 =
* Fix: Improved sample import button visibility logic in rule management.
* Fix: Shipping rule delete function repaired.
* Added: NewebPay CVS convenience store pickup integration.
* Added: In-plugin user manual.
* Improvement: Full i18n compliance — all strings moved to .po files.
* Improvement: Plugin Check errors resolved (security escaping, sanitization, nonce verification).

= 1.0.6 =
* Added: CVS convenience store pickup map integration (ECPay).
* Improvement: Full admin UI redesign — fixed input widths and tab bar overflow.
* Fix: Invoice field description text display issue resolved.

= 1.0.5 =
* Fix: Optimized mobile checkout flow and resolved iOS compatibility issues.

= 1.0.4 =
* Improvement: Localized SweetAlert2 for WordPress.org compliance.
* Improvement: Upgraded rule management notifications to SweetAlert2.
* Fix: Removed redundant save buttons on log pages.
* Fix: Sanitized all user inputs and improved nonce verification.

= 1.0.3 =
* Added: Social Login module (LINE / Google / Facebook).
* Added: Checkout countdown timer.
* Added: Mobile sticky buy bar.

= 1.0.0 =
* Initial release.

---

== 繁體中文說明 ==

Taiwan Store Core 是專為台灣電商市場設計的專業 WooCommerce 擴充外掛，提供流暢的在地化結帳體驗與強大的視覺化商業規則引擎，協助店主輕鬆管理複雜的在地需求。

= 核心功能 =
* **台灣結帳優化** — 縣市／鄉鎮市區二級聯動下拉選單，支援 3+2 碼郵遞區號自動帶入。
* **發票資訊整合** — 支援公司統編、抬頭、手機條碼、自然人憑證及捐贈碼。
* **智慧統編查詢** — 輸入 8 碼統編自動從官方 API（GCIS）抓取公司名稱。
* **視覺化規則引擎** — 透過現代化視覺界面管理付款、運送及購物車規則，無需撰寫程式碼。
* **自定義訂單編號** — 專業的流水號格式（例如：前綴 + YYYYMMDD + 流水號）。
* **結帳倒數計時** — 透過可自訂的計時器創造急迫感並提升轉換率。
* **行動裝置置底列** — 置底「立即購買」按鈕，提升行動裝置使用者體驗。
* **社群登入** — 支援 LINE、Google 及 Facebook 一鍵登入。
* **超商取貨** — 整合綠界（ECPay）與藍新（NewebPay）超商取貨物流。
* **HPOS 相容** — 完整相容 WooCommerce 高效能訂單儲存。

= 安裝說明 =
1. 將 `taiwan-store-core` 資料夾上傳至 `/wp-content/plugins/` 目錄。
2. 透過 WordPress「外掛」選單啟用。
3. 前往 台灣商店 → 一般設定 進行設定。

= 常見問題 =

**系統需求為何？**
需要 PHP 8.1+、WordPress 6.5+ 及 WooCommerce 8.0+。

**是否相容於其他結帳外掛？**
本外掛使用標準 WooCommerce Additional Checkout Fields API，與大多數現代佈景主題與外掛相容。若遇問題，請查看系統紀錄分頁。

**一定要購買 Pro 擴充外掛嗎？**
不需要。核心外掛可獨立運作。Pro 擴充外掛提供進階功能，例如電子發票、會員分級與行銷自動化。

= 更新日誌 =

**1.0.7**
* 修正：規則管理頁面範例匯入按鈕顯示邏輯優化。
* 修正：運費規則刪除功能修復。
* 新增：藍新超商取貨物流整合。
* 新增：外掛內建使用手冊。
* 優化：完整 i18n 合規 — 所有字串移至 .po 檔。
* 優化：修復 Plugin Check 檢測錯誤（安全輸出、資料清理、Nonce 驗證）。

**1.0.6**
* 新增：ECPay 超商取貨地圖整合。
* 優化：管理後台 UI 全面重設計，修正欄位寬度與分頁列顯示問題。
* 修正：Invoice 欄位描述文字顯示問題。

**1.0.5**
* 修正：優化行動裝置結帳流程，解決 iOS 相容性問題。

**1.0.4**
* 優化：根據 WordPress.org 規範將 SweetAlert2 在地化。
* 優化：將規則管理通知升級為 SweetAlert2。
* 修正：移除日誌頁面冗餘的儲存按鈕。
* 修正：清理所有使用者輸入並強化 Nonce 驗證。

**1.0.3**
* 新增：社群登入模組（LINE / Google / Facebook）。
* 新增：結帳倒數計時器。
* 新增：行動裝置置底購買列。

**1.0.0**
* 首次發布。
