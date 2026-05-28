<?php
namespace Taiwan_Store_Core\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Taiwan Store 使用手冊
 * 在後台 taiwan-store-core?tab=manual 渲染完整文字手冊。
 */
class Manual {

	public function render(): void {
		$sections = $this->get_sections();
		?>
		<div class="ts-manual-wrap">

		<div class="ts-manual-hero">
			<div class="ts-manual-hero-inner">
				<h1>📖 Taiwan Store 使用手冊</h1>
				<p>詳細說明所有功能的設定方式與操作流程。點擊各章節標題展開內容。</p>
			</div>
		</div>

		<div class="ts-manual-toc">
			<strong>快速導覽</strong>
			<div class="ts-manual-toc-links">
				<?php foreach ( $sections as $i => $s ) : ?>
					<a href="#ts-manual-<?php echo esc_attr( $s['id'] ); ?>" class="ts-manual-toc-link">
						<span class="ts-manual-toc-num"><?php echo absint( $i + 1 ); ?></span>
						<?php echo esc_html( $s['title'] ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>

		<?php foreach ( $sections as $s ) :
			$is_pro      = ! empty( $s['badge'] );
			$plugin_path = $s['plugin'] ?? '';
			$installed   = ! $is_pro || ! $plugin_path || $this->is_plugin_active( $plugin_path );
			$install_url = admin_url( 'plugin-install.php' );
		?>
		<div class="ts-manual-section <?php echo $is_pro && ! $installed ? 'ts-manual-section--inactive' : ''; ?>" id="ts-manual-<?php echo esc_attr( $s['id'] ); ?>">
			<div class="ts-manual-section-header">
				<span class="ts-manual-section-icon"><?php echo esc_html( $s['icon'] ); ?></span>
				<div>
					<h2><?php echo esc_html( $s['title'] ); ?></h2>
					<p class="ts-manual-section-desc"><?php echo esc_html( $s['desc'] ); ?></p>
				</div>
				<div style="margin-left:auto;flex-shrink:0;display:flex;align-items:center;gap:8px;">
					<?php if ( $is_pro ) : ?>
						<span class="ts-pro-badge">PRO</span>
					<?php endif; ?>
					<?php if ( $is_pro && ! $installed ) : ?>
						<span class="ts-manual-not-installed">未安裝</span>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( $is_pro && ! $installed ) : ?>
			<div class="ts-manual-install-banner">
				<span>📦 此功能需要安裝 <strong><?php echo esc_html( $s['title'] ); ?></strong> 擴充外掛才能使用。</span>
				<a href="<?php echo esc_url( $install_url ); ?>" class="ts-manual-install-btn">前往安裝</a>
			</div>
			<?php endif; ?>
			<div class="ts-manual-articles <?php echo $is_pro && ! $installed ? 'ts-manual-articles--dim' : ''; ?>">
				<?php foreach ( $s['articles'] as $article ) : ?>
				<details class="ts-manual-article">
					<summary class="ts-manual-article-title">
						<span class="ts-manual-arrow">▶</span>
						<?php echo esc_html( $article['title'] ); ?>
					</summary>
					<div class="ts-manual-article-body">
						<?php echo wp_kses_post( $article['content'] ); ?>
					</div>
				</details>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endforeach; ?>

		</div>

		<style>
		.ts-manual-wrap { max-width: 900px; }

		.ts-manual-hero {
			background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
			border-radius: 12px;
			padding: 32px 36px;
			margin-bottom: 24px;
			color: #fff;
		}
		.ts-manual-hero h1 { margin: 0 0 8px; font-size: 22px; color: #fff; }
		.ts-manual-hero p  { margin: 0; color: #94a3b8; font-size: 14px; }

		.ts-manual-toc {
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 10px;
			padding: 16px 20px;
			margin-bottom: 28px;
		}
		.ts-manual-toc > strong { font-size: 12px; text-transform: uppercase; letter-spacing: .06em; color: #64748b; display: block; margin-bottom: 12px; }
		.ts-manual-toc-links { display: flex; flex-wrap: wrap; gap: 8px; }
		.ts-manual-toc-link {
			display: inline-flex; align-items: center; gap: 6px;
			padding: 5px 12px; border-radius: 20px;
			background: #fff; border: 1px solid #e2e8f0;
			font-size: 13px; color: #374151; text-decoration: none;
			transition: border-color .15s, background .15s;
		}
		.ts-manual-toc-link:hover { border-color: #3b82f6; background: #eff6ff; color: #1d4ed8; }
		.ts-manual-toc-num {
			width: 18px; height: 18px; border-radius: 50%;
			background: #e2e8f0; color: #64748b;
			font-size: 10px; font-weight: 700;
			display: flex; align-items: center; justify-content: center;
		}

		.ts-manual-section {
			background: #fff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			margin-bottom: 20px;
			overflow: hidden;
		}
		.ts-manual-section-header {
			display: flex; align-items: flex-start; gap: 14px;
			padding: 20px 24px;
			border-bottom: 1px solid #f1f5f9;
			background: #f8fafc;
		}
		.ts-manual-section-icon { font-size: 28px; line-height: 1; flex-shrink: 0; }
		.ts-manual-section-header h2 { margin: 0 0 4px; font-size: 16px; color: #1e293b; }
		.ts-manual-section-desc { margin: 0; font-size: 13px; color: #64748b; }

		.ts-manual-articles { padding: 8px 0; }

		.ts-manual-article { border-bottom: 1px solid #f1f5f9; }
		.ts-manual-article:last-child { border-bottom: none; }

		.ts-manual-article-title {
			display: flex; align-items: center; gap: 10px;
			padding: 14px 24px;
			cursor: pointer;
			font-size: 14px; font-weight: 600; color: #374151;
			list-style: none;
			user-select: none;
			transition: background .1s;
		}
		.ts-manual-article-title:hover { background: #f8fafc; }
		.ts-manual-article-title::-webkit-details-marker { display: none; }

		.ts-manual-arrow { font-size: 10px; color: #94a3b8; transition: transform .2s; flex-shrink: 0; }
		details[open] .ts-manual-arrow { transform: rotate(90deg); }

		.ts-manual-article-body {
			padding: 4px 24px 20px 44px;
			font-size: 13px; color: #374151; line-height: 1.7;
		}
		.ts-manual-article-body h4 {
			margin: 16px 0 6px; font-size: 13px; color: #1e293b;
			border-left: 3px solid #3b82f6; padding-left: 8px;
		}
		.ts-manual-article-body p  { margin: 0 0 10px; }
		.ts-manual-article-body ul { margin: 6px 0 10px 16px; padding: 0; }
		.ts-manual-article-body li { margin-bottom: 4px; }
		.ts-manual-article-body code {
			background: #f1f5f9; padding: 2px 6px; border-radius: 4px;
			font-size: 12px; color: #0f172a;
		}
		.ts-manual-article-body .ts-manual-step {
			display: flex; gap: 12px; align-items: flex-start;
			background: #f8fafc; border-radius: 8px;
			padding: 10px 14px; margin-bottom: 8px;
		}
		.ts-manual-step-num {
			width: 24px; height: 24px; border-radius: 50%;
			background: #3b82f6; color: #fff;
			font-size: 12px; font-weight: 700;
			display: flex; align-items: center; justify-content: center;
			flex-shrink: 0; margin-top: 1px;
		}
		.ts-manual-article-body .ts-tip {
			background: #fefce8; border: 1px solid #fde68a;
			border-radius: 8px; padding: 10px 14px;
			margin: 10px 0; font-size: 12px; color: #78350f;
		}
		.ts-manual-article-body .ts-warn {
			background: #fef2f2; border: 1px solid #fecaca;
			border-radius: 8px; padding: 10px 14px;
			margin: 10px 0; font-size: 12px; color: #991b1b;
		}
		.ts-manual-article-body table {
			width: 100%; border-collapse: collapse; margin: 10px 0;
			font-size: 12px;
		}
		.ts-manual-article-body th {
			background: #f1f5f9; text-align: left;
			padding: 7px 10px; font-weight: 600; color: #374151;
			border: 1px solid #e2e8f0;
		}
		.ts-manual-article-body td {
			padding: 7px 10px; border: 1px solid #e2e8f0;
			vertical-align: top;
		}
		.ts-manual-article-body tr:nth-child(even) td { background: #f8fafc; }

		.ts-manual-section--inactive { opacity: .85; }
		.ts-manual-section--inactive .ts-manual-section-header { background: #f9fafb; }

		.ts-manual-not-installed {
			font-size: 11px; font-weight: 600;
			padding: 2px 8px; border-radius: 10px;
			background: #f1f5f9; color: #64748b;
			border: 1px solid #cbd5e1;
		}

		.ts-manual-install-banner {
			display: flex; align-items: center; justify-content: space-between; gap: 12px;
			background: #fffbeb; border-bottom: 1px solid #fde68a;
			padding: 10px 20px; font-size: 13px; color: #78350f;
		}
		.ts-manual-install-btn {
			flex-shrink: 0;
			background: #f59e0b; color: #1a1a1a;
			font-size: 12px; font-weight: 700;
			padding: 5px 14px; border-radius: 6px;
			text-decoration: none;
			transition: background .15s;
		}
		.ts-manual-install-btn:hover { background: #d97706; color: #fff; }

		.ts-manual-articles--dim .ts-manual-article-title { color: #94a3b8; }
		</style>

		<script>
		// Smooth scroll for TOC links
		document.querySelectorAll('.ts-manual-toc-link').forEach(function(link) {
			link.addEventListener('click', function(e) {
				e.preventDefault();
				var target = document.querySelector(this.getAttribute('href'));
				if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
			});
		});
		</script>
		<?php
	}

	// ── Content ──────────────────────────────────────────────────────────────

	private function get_sections(): array {
		return [
			$this->section_core(),
			$this->section_rules(),
			$this->section_checkout(),
			$this->section_cvs(),
			$this->section_social(),
			$this->section_marketing(),
			$this->section_member(),
			$this->section_invoice(),
			$this->section_notifier(),
			$this->section_groupbuy(),
		];
	}

	private function is_plugin_active( string $plugin_path ): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active( $plugin_path );
	}

	// ── 主程式 ────────────────────────────────────────────────────────────────

	private function section_core(): array {
		return [
			'id'       => 'core',
			'icon'     => '🏪',
			'title'    => '台灣商店：核心助手',
			'desc'     => '所有功能的基礎主程式，提供一般設定、系統日誌與環境檢測。',
			'articles' => [
				[
					'title'   => '一般設定 — 自訂訂單編號',
					'content' => $this->wrap( '
						<p>讓訂單號碼更符合台灣電商習慣，例如 <code>ORD-20240101-0001</code>。</p>
						<h4>啟用步驟</h4>
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>前往 <strong>台灣商店 → 一般設定</strong></div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>找到「自訂訂單編號」卡片，開啟切換開關</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">3</div><div>設定前綴（如 <code>ORD-</code>）、位數、是否附加隨機碼</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">4</div><div>點擊「儲存一般設定」</div></div>
						<h4>欄位說明</h4>
						<table>
							<tr><th>欄位</th><th>說明</th><th>範例</th></tr>
							<tr><td>前綴</td><td>訂單號前面的固定文字</td><td>ORD-</td></tr>
							<tr><td>位數</td><td>流水號的最小位數</td><td>4 → 0001</td></tr>
							<tr><td>隨機碼</td><td>末尾附加隨機字元，避免被猜測</td><td>開/關</td></tr>
						</table>
						<div class="ts-tip">💡 每日重置：流水號會在每天零時自動歸零重新計算。</div>
					' ),
				],
				[
					'title'   => '結帳公告橫幅',
					'content' => $this->wrap( '
						<p>在結帳頁頂端顯示一行公告文字，適合促銷活動、物流延誤說明等。</p>
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>一般設定 → 結帳公告 → 開啟切換開關</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>填入公告文字</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">3</div><div>儲存後立即在前台結帳頁生效</div></div>
						<div class="ts-tip">💡 公告文字支援純文字，不支援 HTML 標籤。</div>
					' ),
				],
				[
					'title'   => '系統紀錄與 Debug 模式',
					'content' => $this->wrap( '
						<p>系統紀錄 tab 提供今日操作概覽，協助排查問題。</p>
						<h4>Debug 模式</h4>
						<p>開啟後，所有 API 請求（ECPay、GCIS、LINE）的詳細資料會寫入 WooCommerce 日誌。</p>
						<ul>
							<li>日誌位置：WooCommerce → 系統狀態 → 日誌</li>
							<li>來源標籤：<code>taiwan-store-core</code></li>
						</ul>
						<div class="ts-warn">⚠ 正式環境請關閉 Debug 模式，避免日誌過大影響效能。</div>
					' ),
				],
			],
		];
	}

	// ── 規則引擎 ──────────────────────────────────────────────────────────────

	private function section_rules(): array {
		return [
			'id'       => 'rules',
			'icon'     => '⚙️',
			'title'    => '規則引擎',
			'desc'     => '條件式控制付款方式、運費、購物車行為，無需寫程式。',
			'articles' => [
				[
					'title'   => '規則引擎概覽',
					'content' => $this->wrap( '
						<p>規則引擎讓你用「條件 → 動作」的邏輯自動控制結帳行為。例如：</p>
						<ul>
							<li>訂單金額 &lt; NT$500 → 隱藏「免運費」選項</li>
							<li>買家選擇「超商取貨」→ 隱藏「信用卡分期」付款</li>
							<li>商品含有「冷藏品」分類 → 禁止「超商取貨」</li>
						</ul>
						<h4>三個規則 Tab</h4>
						<table>
							<tr><th>Tab</th><th>控制對象</th></tr>
							<tr><td>付款規則</td><td>付款方式（隱藏、封鎖結帳）</td></tr>
							<tr><td>運費規則</td><td>運費方式（隱藏、封鎖結帳）</td></tr>
							<tr><td>購物車規則</td><td>整體購物車（封鎖、提示訊息）</td></tr>
						</table>
					' ),
				],
				[
					'title'   => '新增規則步驟',
					'content' => $this->wrap( '
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>前往對應的規則 Tab（付款 / 運費 / 購物車）</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>點擊右上角「＋ 新增規則」按鈕</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">3</div><div>在「條件」區塊選擇觸發條件</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">4</div><div>在「動作」區塊設定觸發後的行為</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">5</div><div>點擊「儲存規則」</div></div>
						<div class="ts-tip">💡 多個條件之間是「且」的關係，所有條件同時成立才觸發動作。</div>
					' ),
				],
				[
					'title'   => '可用條件一覽',
					'content' => $this->wrap( '
						<table>
							<tr><th>條件</th><th>說明</th></tr>
							<tr><td>訂單金額</td><td>購物車小計 ≥ 或 ≤ 指定金額</td></tr>
							<tr><td>商品數量</td><td>購物車中的總商品數量</td></tr>
							<tr><td>商品</td><td>購物車含有特定商品 ID</td></tr>
							<tr><td>商品分類</td><td>購物車含有特定商品分類</td></tr>
							<tr><td>付款方式</td><td>買家選擇了特定付款方式</td></tr>
							<tr><td>運費方式</td><td>買家選擇了特定運費方式</td></tr>
							<tr><td>收件縣市 / 鄉鎮</td><td>收件地址的縣市或鄉鎮</td></tr>
							<tr><td>地址不一致</td><td>帳單地址與收件地址不同</td></tr>
							<tr><td>購買頻率</td><td>首次購買 或 回購客戶</td></tr>
						</table>
					' ),
				],
				[
					'title'   => '可用動作一覽',
					'content' => $this->wrap( '
						<table>
							<tr><th>動作</th><th>說明</th></tr>
							<tr><td>隱藏付款方式</td><td>從結帳頁移除指定的付款選項</td></tr>
							<tr><td>隱藏運費方式</td><td>從結帳頁移除指定的運費選項</td></tr>
							<tr><td>封鎖結帳</td><td>阻止下單，並顯示自訂錯誤訊息</td></tr>
						</table>
						<div class="ts-tip">💡 安裝「行銷助手 Pro」後，動作清單會增加折扣、贈品、免運等行銷功能。</div>
					' ),
				],
				[
					'title'   => '匯入範例規則',
					'content' => $this->wrap( '
						<p>每個規則 Tab 右上角有「匯入範例」按鈕，提供常用情境的預設規則，可直接使用或修改。</p>
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>點擊「匯入範例」</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>勾選要匯入的範例</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">3</div><div>點擊「匯入選取項目」</div></div>
						<div class="ts-tip">💡 匯入的範例規則預設為「停用」狀態，啟用前請先確認設定符合需求。</div>
					' ),
				],
			],
		];
	}

	// ── 結帳設定 ──────────────────────────────────────────────────────────────

	private function section_checkout(): array {
		return [
			'id'       => 'checkout',
			'icon'     => '🧾',
			'title'    => '結帳欄位與發票設定',
			'desc'     => '台灣結帳欄位、電子發票資訊收集、統編查詢與地址聯動。',
			'articles' => [
				[
					'title'   => '電子發票欄位',
					'content' => $this->wrap( '
						<p>結帳頁自動新增發票開立所需欄位，買家可選擇發票類型：</p>
						<table>
							<tr><th>發票類型</th><th>需填欄位</th></tr>
							<tr><td>個人發票（雲端）</td><td>無需額外填寫</td></tr>
							<tr><td>手機條碼載具</td><td>載具號碼（/XXX...格式）</td></tr>
							<tr><td>自然人憑證</td><td>憑證號碼（2字母+14數字）</td></tr>
							<tr><td>捐贈發票</td><td>愛心碼（3-7位數）</td></tr>
							<tr><td>公司三聯式</td><td>統一編號 + 公司抬頭</td></tr>
						</table>
					' ),
				],
				[
					'title'   => '統一編號自動查詢（GCIS API）',
					'content' => $this->wrap( '
						<p>買家輸入統一編號後，自動從財政部查詢公司名稱並填入「公司抬頭」欄位。</p>
						<h4>啟用步驟</h4>
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>結帳設定 → 啟用「統編自動查詢」</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>GCIS API UUID 使用預設值即可（公開 API）</div></div>
						<div class="ts-tip">💡 查詢結果會快取 24 小時，避免重複呼叫 API。</div>
					' ),
				],
				[
					'title'   => '電話驗證與郵遞區號自動填入',
					'content' => $this->wrap( '
						<p><strong>電話驗證</strong>：確保買家填入有效的台灣手機或市話格式。</p>
						<p><strong>郵遞區號自動填入</strong>：買家選擇縣市 + 鄉鎮後，自動填入對應郵遞區號，減少填寫錯誤。</p>
						<p>兩項功能皆在「結帳設定」卡片中各自開關切換。</p>
					' ),
				],
				[
					'title'   => '結帳倒數計時器',
					'content' => $this->wrap( '
						<p>在結帳頁顯示倒數計時，製造緊迫感，減少買家猶豫離開。</p>
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>結帳設定 → 啟用「結帳倒數」</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>設定倒數分鐘數（建議 10-30 分鐘）</div></div>
						<div class="ts-tip">💡 倒數結束後購物車不會自動清空，僅顯示提示訊息。</div>
					' ),
				],
				[
					'title'   => '棄單回收（Email + LINE）',
					'content' => $this->wrap( '
						<p>買家進入結帳頁但未完成付款，系統自動發送提醒訊息。</p>
						<h4>設定項目</h4>
						<table>
							<tr><th>欄位</th><th>說明</th></tr>
							<tr><td>啟用</td><td>開啟棄單追蹤</td></tr>
							<tr><td>延遲時間</td><td>幾小時後發送提醒（建議 1-2 小時）</td></tr>
							<tr><td>Email 主旨</td><td>提醒 Email 的標題</td></tr>
							<tr><td>Email 內容</td><td>提醒內容，支援 {cart_url} 變數</td></tr>
							<tr><td>LINE 通知</td><td>同時透過 LINE 發送（需搭配通知助手 Pro）</td></tr>
						</table>
					' ),
				],
			],
		];
	}

	// ── 超商取貨 ──────────────────────────────────────────────────────────────

	private function section_cvs(): array {
		return [
			'id'       => 'cvs',
			'icon'     => '🏪',
			'title'    => '超商取貨',
			'desc'     => '整合 ECPay 與藍新物流，提供 7-11、全家、萊爾富、OK 超商選店地圖。',
			'articles' => [
				[
					'title'   => '啟用 ECPay 超商取貨',
					'content' => $this->wrap( '
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>結帳設定 → 「超商取貨（ECPay 物流）」→ 啟用</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>測試模式預設開啟，正式上線前填入 ECPay 物流 MerchantID / HashKey / HashIV</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">3</div><div>前往 <strong>WooCommerce → 運費 → 運送區域</strong></div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">4</div><div>新增或選擇運送區域 → 點擊「新增運費方式」→ 選擇「超商取貨」</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">5</div><div>設定運費金額、免運門檻、超商類型</div></div>
						<div class="ts-tip">💡 測試帳號：MerchantID <code>3002607</code>，HashKey <code>pwFHCqoQZGmho4w6</code>，HashIV <code>EkRm7iFT261dpevs</code></div>
					' ),
				],
				[
					'title'   => '啟用藍新超商取貨',
					'content' => $this->wrap( '
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>結帳設定 → 「超商取貨（藍新物流）」→ 啟用</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>填入藍新 MerchantID / HashKey / HashIV</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">3</div><div>WooCommerce → 運費 → 運送區域 → 新增「超商取貨（藍新）」</div></div>
						<h4>支援超商</h4>
						<table>
							<tr><th>超商</th><th>ECPay 代碼</th><th>藍新代碼</th></tr>
							<tr><td>7-ELEVEN</td><td>UNIMART / UNIMARTC2C</td><td>SEVEN</td></tr>
							<tr><td>全家</td><td>FAMI / FAMIC2C</td><td>FAMILY</td></tr>
							<tr><td>萊爾富</td><td>HILIFE</td><td>HILIFE</td></tr>
							<tr><td>OK 超商</td><td>OKMART</td><td>OK</td></tr>
						</table>
					' ),
				],
				[
					'title'   => '買家選店流程',
					'content' => $this->wrap( '
						<p>買家在結帳頁選擇超商取貨方式後，流程如下：</p>
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>頁面出現「🗺️ 選擇門市」按鈕</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>點擊後開啟地圖彈出視窗</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">3</div><div>搜尋並點選門市</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">4</div><div>視窗自動關閉，結帳頁顯示已選門市名稱與地址</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">5</div><div>完成結帳後，訂單確認信與後台訂單詳情均顯示取貨門市資訊</div></div>
						<div class="ts-warn">⚠ 未選擇門市無法送出訂單，系統會出現提示要求選店。</div>
					' ),
				],
			],
		];
	}

	// ── 社群登入 ──────────────────────────────────────────────────────────────

	private function section_social(): array {
		return [
			'id'       => 'social',
			'icon'     => '🔗',
			'title'    => '社群登入',
			'desc'     => '讓買家用 LINE、Google、Facebook 帳號快速登入或註冊。',
			'articles' => [
				[
					'title'   => 'LINE 登入設定',
					'content' => $this->wrap( '
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>前往 <a href="https://developers.line.biz/" target="_blank">LINE Developers</a> 建立 Login Channel</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>複製 Channel ID 與 Channel Secret</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">3</div><div>社群登入設定 → LINE → 貼上憑證 → 啟用</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">4</div><div>將設定頁顯示的「Callback URL」填入 LINE Developers 後台</div></div>
						<div class="ts-tip">💡 需要 HTTPS，本機開發環境請使用 ngrok 或 LocalWP 的 HTTPS 功能。</div>
					' ),
				],
				[
					'title'   => 'Google 登入設定',
					'content' => $this->wrap( '
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>前往 Google Cloud Console → 憑證 → 建立 OAuth 2.0 用戶端</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>複製 Client ID 與 Client Secret</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">3</div><div>社群登入設定 → Google → 貼上憑證 → 啟用</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">4</div><div>將「重新導向 URI」填入 Google Console 的「已授權重新導向 URI」</div></div>
					' ),
				],
				[
					'title'   => 'Facebook 登入設定',
					'content' => $this->wrap( '
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>前往 Meta for Developers → 新增應用程式</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>新增 Facebook 登入產品，複製 App ID 與 App Secret</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">3</div><div>社群登入設定 → Facebook → 貼上憑證 → 啟用</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">4</div><div>將「重新導向 URI」填入 Facebook 應用程式的「有效 OAuth 重新導向 URI」</div></div>
					' ),
				],
			],
		];
	}

	// ── 行銷助手 ──────────────────────────────────────────────────────────────

	private function section_marketing(): array {
		return [
			'id'     => 'marketing',
			'plugin' => 'taiwan-store-marketing/taiwan-store-marketing.php',
			'icon'   => '📣',
			'title' => '行銷助手 Pro',
			'desc'  => '折扣、贈品、買一送一、加價購、進度條、橫幅等行銷工具。',
			'badge' => true,
			'articles' => [
				[
					'title'   => '行銷規則概覽',
					'content' => $this->wrap( '
						<p>行銷助手整合進規則引擎，在「行銷規則」tab 設定。每條規則由「條件」+ 「行銷動作」組成。</p>
						<h4>可用行銷動作</h4>
						<table>
							<tr><th>動作</th><th>說明</th></tr>
							<tr><td>折扣</td><td>固定金額或百分比折扣</td></tr>
							<tr><td>免運費</td><td>條件成立時免除運費</td></tr>
							<tr><td>贈送商品</td><td>自動加入免費商品至購物車</td></tr>
							<tr><td>購物車進度條</td><td>顯示距離優惠門檻的進度</td></tr>
							<tr><td>組合折扣</td><td>購買指定商品組合享折扣</td></tr>
							<tr><td>加價購</td><td>以優惠價購買附加商品</td></tr>
							<tr><td>閃購倒數</td><td>限時優惠倒數計時器</td></tr>
							<tr><td>買一送一</td><td>BOGO 折扣設定</td></tr>
							<tr><td>推廣橫幅</td><td>全站頂端橫幅公告</td></tr>
						</table>
					' ),
				],
				[
					'title'   => '設定折扣規則',
					'content' => $this->wrap( '
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>行銷助手 Pro → 新增規則</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>設定條件（如：訂單金額 ≥ NT$1,000）</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">3</div><div>動作選擇「折扣」→ 設定折扣名稱、類型（固定/百分比）、金額</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">4</div><div>儲存並啟用規則</div></div>
						<div class="ts-tip">💡 折扣名稱會顯示在購物車明細中，建議填入促銷活動名稱。</div>
					' ),
				],
				[
					'title'   => '設定購物車進度條',
					'content' => $this->wrap( '
						<p>在購物車頁顯示距離免運或優惠的金額進度條，有效提升客單價。</p>
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>新增規則 → 動作選「購物車進度條」</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>設定目標金額（例如 NT$2,000 免運）</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">3</div><div>設定提示文字（例如：再買 {gap} 元免運費！）</div></div>
						<div class="ts-tip">💡 可搭配「免運費」動作使用，達標後自動免運。</div>
					' ),
				],
			],
		];
	}

	// ── 會員分級 ──────────────────────────────────────────────────────────────

	private function section_member(): array {
		return [
			'id'     => 'member',
			'plugin' => 'taiwan-store-member/taiwan-store-member.php',
			'icon'   => '👑',
			'title' => '會員分級 Pro',
			'desc'  => '消費累積等級、點數兌換、VIP 折扣，提升回購率。',
			'badge' => true,
			'articles' => [
				[
					'title'   => '等級制度設定',
					'content' => $this->wrap( '
						<p>預設提供 4 個等級，可自由調整名稱、門檻、折扣、圖示與顏色。</p>
						<table>
							<tr><th>等級</th><th>預設門檻</th><th>折扣</th></tr>
							<tr><td>🌱 一般會員</td><td>NT$0</td><td>0%</td></tr>
							<tr><td>🥈 銀卡會員</td><td>NT$3,000</td><td>5%</td></tr>
							<tr><td>🥇 金卡會員</td><td>NT$10,000</td><td>10%</td></tr>
							<tr><td>💎 VIP 白金</td><td>NT$30,000</td><td>15%</td></tr>
						</table>
						<h4>自訂等級</h4>
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>會員分級 Pro → 等級設定區塊</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>修改等級名稱、最低累積消費、折扣百分比</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">3</div><div>上傳圖示圖片或選擇備用 Emoji</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">4</div><div>填入福利說明（顯示在前台進度卡與升等 Email）</div></div>
					' ),
				],
				[
					'title'   => '點數設定',
					'content' => $this->wrap( '
						<table>
							<tr><th>設定項目</th><th>說明</th><th>預設值</th></tr>
							<tr><td>點數累積</td><td>每消費 NT$1 累積幾點</td><td>1 點</td></tr>
							<tr><td>折抵比率</td><td>幾點折抵 NT$ 幾元</td><td>100點 = NT$10</td></tr>
							<tr><td>折抵上限</td><td>單筆最多折抵訂單金額的百分比</td><td>50%</td></tr>
						</table>
						<div class="ts-tip">💡 折抵上限設為 0 可停用點數折抵功能，只保留點數累積。</div>
					' ),
				],
				[
					'title'   => '買家點數兌換流程',
					'content' => $this->wrap( '
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>買家在結帳頁看到「可用點數」欄位</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>輸入要折抵的點數或點擊「全部折抵」</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">3</div><div>訂單金額自動扣除折抵金額</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">4</div><div>訂單完成後自動累積本次消費點數</div></div>
						<p>買家可在「我的帳戶 → 點數紀錄」查看所有交易明細。</p>
					' ),
				],
			],
		];
	}

	// ── 電子發票 ──────────────────────────────────────────────────────────────

	private function section_invoice(): array {
		return [
			'id'     => 'invoice',
			'plugin' => 'wc-tw-invoice-pro/wc-tw-invoice-pro.php',
			'icon'   => '🧾',
			'title' => '電子發票 Pro',
			'desc'  => '整合 ECPay 電子發票 API，自動開立、作廢、對獎通知與報表匯出。',
			'badge' => true,
			'articles' => [
				[
					'title'   => '初始設定',
					'content' => $this->wrap( '
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>電子發票 Pro → 選擇供應商（目前支援 ECPay）</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>測試模式預設開啟，測試帳號已內建無需填寫</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">3</div><div>選擇「開立時機」：</div></div>
						<table>
							<tr><th>選項</th><th>說明</th></tr>
							<tr><td>付款後立即開立</td><td>訂單進入 Processing 狀態時開立</td></tr>
							<tr><td>訂單完成後開立</td><td>訂單進入 Completed 狀態時開立</td></tr>
							<tr><td>手動觸發</td><td>不自動開立，由店家人工操作</td></tr>
						</table>
						<div class="ts-tip">💡 建議搭配「延遲開立天數」設定，等鑑賞期過後再開立，方便退貨處理。</div>
					' ),
				],
				[
					'title'   => '手動開立與作廢',
					'content' => $this->wrap( '
						<h4>手動開立</h4>
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>WooCommerce → 訂單 → 開啟訂單詳情</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>右側找到「電子發票」區塊</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">3</div><div>點擊「手動開立電子發票」</div></div>
						<h4>作廢發票</h4>
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>同一區塊點擊「作廢」按鈕</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>確認作廢動作後送出</div></div>
						<div class="ts-warn">⚠ 發票作廢後無法復原，請確認原因後再操作。</div>
					' ),
				],
				[
					'title'   => '批次開立',
					'content' => $this->wrap( '
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>訂單列表 → 勾選多筆訂單</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>「批次動作」下拉選單選「批次開立電子發票」</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">3</div><div>點擊「套用」</div></div>
						<div class="ts-tip">💡 已開立的訂單會自動跳過，不會重複開立。</div>
					' ),
				],
				[
					'title'   => '對獎通知',
					'content' => $this->wrap( '
						<p>系統每 12 小時自動比對財政部公告的中獎號碼，中獎時自動寄送 Email 通知買家。</p>
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>電子發票 Pro 設定 → 開啟「中獎自動 Email」</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>系統自動執行，無需額外操作</div></div>
						<p>也可在 Dashboard 點擊「立即對獎」手動觸發。</p>
					' ),
				],
				[
					'title'   => '報表匯出',
					'content' => $this->wrap( '
						<p>匯出 CSV 格式的月份發票報表，符合會計對帳需求。</p>
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>電子發票 Pro Dashboard → 點擊「匯出 CSV」</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>選擇月份後下載</div></div>
						<p>CSV 內容包含：發票日期、號碼、買受人統編、金額、稅額、中獎狀態。</p>
						<div class="ts-tip">💡 CSV 使用 UTF-8 BOM 編碼，可直接用 Excel 開啟不亂碼。</div>
					' ),
				],
			],
		];
	}

	// ── 通知助手 ──────────────────────────────────────────────────────────────

	private function section_notifier(): array {
		return [
			'id'     => 'notifier',
			'plugin' => 'taiwan-store-notifier/taiwan-store-notifier.php',
			'icon'   => '🔔',
			'title' => '通知助手 Pro',
			'desc'  => 'LINE 訊息與 SMS 訂單通知、物流追蹤、超商到貨提醒。',
			'badge' => true,
			'articles' => [
				[
					'title'   => '設定 LINE Messaging API',
					'content' => $this->wrap( '
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>前往 LINE Developers → 建立 Messaging API Channel</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>複製 Channel Access Token</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">3</div><div>通知助手 Pro 設定 → LINE → 貼上 Token</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">4</div><div>填入「管理員 LINE ID」接收訂單通知</div></div>
						<div class="ts-tip">💡 可使用「傳送測試訊息」功能驗證設定是否正確。</div>
					' ),
				],
				[
					'title'   => '設定 SMS（三竹簡訊）',
					'content' => $this->wrap( '
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>向三竹簡訊申請帳號</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>通知助手 Pro 設定 → SMS → 填入帳號密碼</div></div>
						<div class="ts-tip">💡 SMS 主要用於通知客戶，不適合用於行銷（台灣簡訊法規）。</div>
					' ),
				],
				[
					'title'   => '通知類型與訊息範本',
					'content' => $this->wrap( '
						<table>
							<tr><th>通知類型</th><th>觸發時機</th><th>收件對象</th></tr>
							<tr><td>新訂單</td><td>買家下單後</td><td>管理員</td></tr>
							<tr><td>已出貨</td><td>訂單標記出貨</td><td>買家</td></tr>
							<tr><td>超商到貨</td><td>超商取貨包裹到店</td><td>買家</td></tr>
						</table>
						<h4>可用範本變數</h4>
						<table>
							<tr><th>變數</th><th>說明</th></tr>
							<tr><td><code>{order_id}</code></td><td>訂單編號</td></tr>
							<tr><td><code>{order_total}</code></td><td>訂單金額</td></tr>
							<tr><td><code>{store_name}</code></td><td>超商門市名稱</td></tr>
							<tr><td><code>{tracking_number}</code></td><td>物流追蹤號碼</td></tr>
						</table>
					' ),
				],
				[
					'title'   => '物流追蹤設定',
					'content' => $this->wrap( '
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>開啟訂單詳情 → 找到「物流追蹤」區塊</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>選擇物流商、填入追蹤號碼</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">3</div><div>系統每小時自動同步物流狀態</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">4</div><div>狀態更新時自動發送 LINE / SMS 通知給買家</div></div>
					' ),
				],
			],
		];
	}

	// ── 拼團購買 ──────────────────────────────────────────────────────────────

	private function section_groupbuy(): array {
		return [
			'id'     => 'groupbuy',
			'plugin' => 'taiwan-store-group-buy/taiwan-store-group-buy.php',
			'icon'   => '👥',
			'title' => '拼團購買 Pro',
			'desc'  => '設定人數門檻，達標後自動套用團購優惠價。',
			'badge' => true,
			'articles' => [
				[
					'title'   => '建立拼團活動',
					'content' => $this->wrap( '
						<div class="ts-manual-step"><div class="ts-manual-step-num">1</div><div>後台 → 拼團活動 → 新增活動</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">2</div><div>選擇參與商品</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">3</div><div>設定目標人數（達此人數後啟用優惠）</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">4</div><div>設定拼團優惠價</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">5</div><div>設定活動截止日期</div></div>
						<div class="ts-manual-step"><div class="ts-manual-step-num">6</div><div>發佈活動</div></div>
					' ),
				],
				[
					'title'   => '前台顯示',
					'content' => $this->wrap( '
						<p>活動期間，商品頁會自動顯示：</p>
						<ul>
							<li>拼團優惠價（顯著大字）</li>
							<li>目前參與人數 / 目標人數進度條</li>
							<li>活動截止倒數計時</li>
							<li>原價劃線對比</li>
						</ul>
						<p>買家加入購物車後，系統自動套用拼團價，結帳時即為優惠金額。</p>
					' ),
				],
				[
					'title'   => '活動狀態說明',
					'content' => $this->wrap( '
						<table>
							<tr><th>狀態</th><th>說明</th></tr>
							<tr><td>pending</td><td>待審核，尚未對外顯示</td></tr>
							<tr><td>active</td><td>進行中，前台顯示拼團資訊</td></tr>
							<tr><td>success</td><td>達標成功，優惠持續有效至截止日</td></tr>
							<tr><td>expired</td><td>已截止，恢復原價</td></tr>
						</table>
						<div class="ts-tip">💡 系統每小時自動檢查截止日期，過期活動自動標記為 expired。</div>
					' ),
				],
			],
		];
	}

	// ── Helper ────────────────────────────────────────────────────────────────

	private function wrap( string $html ): string {
		return $html; // content already contains safe HTML
	}
}
