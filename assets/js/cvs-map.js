/* mydybox-taiwan-for-woocommerce: cvs-map.js */
jQuery(document).ready(function ($) {

	var CVS_ECPAY_ID   = 'mydybox_cvs';
	var CVS_NEWEBPAY_ID = 'taiwan_store_newebpay_cvs';
	var mapWindow = null;

	/* ── Show/hide store selector based on chosen shipping method ── */
	function getActiveProvider() {
		var method = $('input[name="shipping_method[0]"]:checked').val() || '';
		if ( method.indexOf( CVS_ECPAY_ID ) !== -1 )    return 'ecpay';
		if ( method.indexOf( CVS_NEWEBPAY_ID ) !== -1 ) return 'newebpay';
		return null;
	}

	function checkShippingMethod() {
		var provider = getActiveProvider();
		if ( provider ) {
			$('#ts-cvs-store-wrap').slideDown(200);
		} else {
			$('#ts-cvs-store-wrap').slideUp(200);
		}
	}

	$('body').on('change', 'input[name^="shipping_method"]', checkShippingMethod);
	$(document).on('updated_checkout', checkShippingMethod);
	checkShippingMethod();

	/* ── Restore stored session data if exists ── */
	var $storeId   = $('#mydybox_cvs_store_id');
	var $storeName = $('#mydybox_cvs_store_name');
	var $storeAddr = $('#mydybox_cvs_store_addr');
	var $storeType = $('#mydybox_cvs_store_type');

	if ( $storeId.val() ) {
		updateStoreDisplay({
			id:   $storeId.val(),
			name: $storeName.val(),
			addr: $storeAddr.val(),
			type: $storeType.val(),
		});
	}

	/* ── Open map popup ── */
	$('body').on('click', '#ts-cvs-select-btn', function (e) {
		e.preventDefault();

		var provider = getActiveProvider();
		if ( ! provider ) return;

		var $method = $('input[name="shipping_method[0]"]:checked');
		var cvsType = $method.data('cvs-type') || ( provider === 'ecpay' ? 'UNIMART' : 'SEVEN' );
		var action  = provider === 'ecpay' ? 'mydybox_open_cvs_map' : 'mydybox_open_newebpay_cvs_map';

		$.post( mydyboxCvs.ajaxUrl, {
			action:   action,
			nonce:    mydyboxCvs.nonce,
			cvs_type: cvsType,
		}, function (res) {
			if ( ! res.success ) return;
			var popup = window.open('', 'mydybox_cvs_map', 'width=1000,height=680,scrollbars=yes');
			popup.document.open();
			popup.document.write( res.data.form );
			popup.document.close();
			// Submit the redirect form from the parent: the server-rendered form intentionally
			// contains no inline <script> tag (wp.org guideline) so we trigger the submit here.
			try { popup.document.forms[0].submit(); } catch ( e ) {}
			mapWindow = popup;
		});
	});

	/* ── Receive postMessage callback from popup ── */
	window.addEventListener('message', function (e) {
		if ( ! e.data || e.data.type !== 'mydybox_cvs_store' ) return;
		var store = e.data.store;
		if ( ! store || ! store.id ) return;

		$storeId.val( store.id );
		$storeName.val( store.name );
		$storeAddr.val( store.addr );
		$storeType.val( store.type );

		updateStoreDisplay( store );

		if ( mapWindow ) {
			mapWindow.close();
			mapWindow = null;
		}
	});

	/* ── Update the store info display box ── */
	function updateStoreDisplay(store) {
		var typeLabel = getCvsLabel( store.type );
		var html = '<strong>' + typeLabel + '</strong>｜' + store.name + '<br>'
				 + '<small style="color:#6b7280;">' + store.addr + '</small>';

		$('#ts-cvs-store-text').html( html );
		$('#ts-cvs-store-info').css({ background: '#f0fdf4', borderColor: '#86efac', color: '#166534' });
		$('#ts-cvs-select-btn').text('🔄 ' + mydyboxCvs.changeStore);
	}

	function getCvsLabel(type) {
		var map = {
			// ECPay
			'UNIMART':    '7-ELEVEN',
			'UNIMARTC2C': '7-ELEVEN（交貨便）',
			'FAMI':       '全家 FamilyMart',
			'FAMIC2C':    '全家（好賣+）',
			'HILIFE':     '萊爾富',
			'HILIFEC2C':  '萊爾富（Hi-Life）',
			'OKMART':     'OK 超商',
			// NewebPay
			'SEVEN':      '7-ELEVEN',
			'FAMILY':     '全家 FamilyMart',
			'OK':         'OK 超商',
		};
		return map[type] || type;
	}

	/* ── Validation: require store selection before checkout ── */
	$('body').on('checkout_place_order', function () {
		var provider = getActiveProvider();
		if ( provider && ! $storeId.val() ) {
			alert( mydyboxCvs.noStoreSelected );
			$('#ts-cvs-select-btn').focus();
			return false;
		}
	});
});
