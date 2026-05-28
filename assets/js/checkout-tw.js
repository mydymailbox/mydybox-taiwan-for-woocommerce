/* taiwan-store-core: checkout-tw.js */
jQuery(document).ready(function ($) {

	/* ── 0. Checkout progress steps ── */
	(function () {
		if (!$('form.checkout').length) return;

		var steps = [
			{ label: '購物車',    icon: '🛒' },
			{ label: '結帳資訊',  icon: '📋' },
			{ label: '付款',      icon: '💳' },
			{ label: '完成',      icon: '✅' },
		];

		// On checkout page we are at step 2 (index 1)
		var currentStep = 1;

		var html = '<div class="ts-checkout-steps">';
		steps.forEach(function (s, i) {
			var cls = i < currentStep ? 'done' : (i === currentStep ? 'active' : '');
			var circle = i < currentStep ? '✓' : (i + 1);
			html += '<div class="ts-step ' + cls + '">';
			html += '<div class="ts-step-circle">' + circle + '</div>';
			html += '<div class="ts-step-label">' + s.label + '</div>';
			html += '</div>';
		});
		html += '</div>';

		$('form.checkout').before(html);
	})();


	/* ── 1. Address preview (路名 + 巷弄組合預覽) ── */
	function updateAddressPreview( prefix ) {
		var state    = $('#' + prefix + '_state option:selected').text();
		var city     = $('#' + prefix + '_city option:selected').text();
		var addr1    = $('#' + prefix + '_address_1').val();
		var addr2    = $('#' + prefix + '_address_2').val();
		var postcode = $('#' + prefix + '_postcode').val();
		var previewId = '#ts-address-preview-' + prefix;

		if ( !addr1 ) { $(previewId).hide(); return; }

		var full = postcode ? postcode + ' ' : '';
		full += [state, city, addr1, addr2].filter(Boolean).join('');
		$(previewId).text('📍 ' + full).show();
	}

	['billing', 'shipping'].forEach(function(prefix) {
		var $wrap = $('#' + prefix + '_address_1_field');
		if ($wrap.length) {
			$wrap.after('<p id="ts-address-preview-' + prefix + '" style="font-size:13px;color:#10b981;margin:-8px 0 12px;display:none;grid-column:1/-1;"></p>');
		}
		$('body').on('input change', '#' + prefix + '_address_1, #' + prefix + '_address_2, #' + prefix + '_state, #' + prefix + '_city', function() {
			updateAddressPreview(prefix);
		});
	});

	/* ── 2a. Invoice type description (below select2, not inside dropdown) ── */
	var $invoiceField = $('#billing_taiwan_store_core_invoice_type_field');
	if ($invoiceField.length) {
		$invoiceField.append(
			'<span class="ts-invoice-desc" style="display:block;margin-top:5px;font-size:12px;color:#666;line-height:1.5;">' +
			'個人發票自動存入財政部雲端；手機載具請輸入 /XXXXXXX 格式；公司發票需填寫統一編號。' +
			'</span>'
		);
	}

	/* ── 2. Invoice type field visibility ── */
	function toggleFields() {
		var type = $('#billing_taiwan_store_core_invoice_type').val();

		if (type === 'company') {
			$('.taiwan-store-core-company-field').show();
		} else {
			$('.taiwan-store-core-company-field').hide();
		}

		if (['carrier_phone', 'carrier_cert', 'donate'].indexOf(type) !== -1) {
			$('.taiwan-store-core-carrier-field').show();
		} else {
			$('.taiwan-store-core-carrier-field').hide();
		}
	}

	$('body').on('change', '#billing_taiwan_store_core_invoice_type', toggleFields);
	toggleFields();

	/* ── 3. Phone number masking (09xx-xxx-xxx) ── */
	$('body').on('input', '#billing_phone', function (e) {
		if (e.originalEvent && (
			e.originalEvent.inputType === 'deleteContentBackward' ||
			e.originalEvent.inputType === 'deleteContentForward'
		)) {
			return;
		}

		var input      = e.target;
		var cursorPos  = input.selectionStart;
		var val        = input.value;
		var digits     = val.replace(/\D/g, '').substring(0, 10);
		var formatted  = digits;

		if (digits.length > 7) {
			formatted = digits.substring(0, 4) + '-' + digits.substring(4, 7) + '-' + digits.substring(7);
		} else if (digits.length > 4) {
			formatted = digits.substring(0, 4) + '-' + digits.substring(4);
		}

		if (val !== formatted) {
			var hyphensBefore    = (val.substring(0, cursorPos).match(/-/g) || []).length;
			var digitsBeforeCursor = cursorPos - hyphensBefore;

			input.value = formatted;

			var newPos = 0, digitsSeen = 0;
			while (newPos < formatted.length && digitsSeen < digitsBeforeCursor) {
				if (formatted[newPos] !== '-') digitsSeen++;
				newPos++;
			}
			input.setSelectionRange(newPos, newPos);
		}
	});

	/* ── 4. Tax ID auto-lookup ── */
	if (wcTwCheckout.lookupEnabled !== 'yes') return;

	var taxLookupTimer = null;

	function doTaxLookup($field) {
		var tax_id = $field.val().replace(/\D/g, '');
		if (tax_id.length !== 8) return;

		var $title = $('#billing_taiwan_store_core_company_title');
		if ($title.data('looked-up') === tax_id) return;

		var original_val = $title.val();

		// Show spinner
		var $wrap = $title.closest('.form-row');
		$wrap.find('.ts-lookup-spinner').remove();
		$wrap.append('<span class="ts-lookup-spinner" style="display:inline-block;margin-left:8px;color:#64748b;font-size:12px;">⏳ ' + wcTwCheckout.lookingUp + '</span>');
		$title.prop('disabled', true);

		$.post(wcTwCheckout.ajaxUrl, {
			action: 'ts_lookup_tax_id',
			tax_id: tax_id,
			nonce:  wcTwCheckout.nonce,
			_t:     Date.now()
		}, function (response) {
			$wrap.find('.ts-lookup-spinner').remove();
			$title.prop('disabled', false);

			if (response.success) {
				$title.val(response.data.name).data('looked-up', tax_id);
				// Show success hint
				$wrap.find('.ts-lookup-msg').remove();
				$wrap.append('<span class="ts-lookup-msg" style="display:block;margin-top:4px;color:#16a34a;font-size:12px;">✓ ' + wcTwCheckout.found + '</span>');
				setTimeout(function () { $wrap.find('.ts-lookup-msg').fadeOut(400, function () { $(this).remove(); }); }, 3000);
			} else {
				var errMsg = (typeof response.data === 'object' ? response.data.message : response.data) || wcTwCheckout.notFound;
				$title.val('').attr('placeholder', wcTwCheckout.notFound);
				// Show error below field
				$wrap.find('.ts-lookup-msg').remove();
				$wrap.append('<span class="ts-lookup-msg" style="display:block;margin-top:4px;color:#dc2626;font-size:12px;">✗ ' + errMsg + '</span>');
			}
		}).fail(function () {
			$wrap.find('.ts-lookup-spinner').remove();
			$title.prop('disabled', false).val(original_val);
		});
	}

	$('body').on('input', '#billing_taiwan_store_core_company_tax_id', function () {
		clearTimeout(taxLookupTimer);
		var $f = $(this);
		if ($f.val().replace(/\D/g, '').length === 8) {
			taxLookupTimer = setTimeout(function () { doTaxLookup($f); }, 300);
		}
	});

	$('body').on('change blur', '#billing_taiwan_store_core_company_tax_id', function () {
		clearTimeout(taxLookupTimer);
		doTaxLookup($(this));
	});
});
