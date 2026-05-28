/**
 * Auto-submit the first <form> on the page.
 * Used by ECPay / NewebPay redirect templates to forward signed payloads.
 */
(function () {
	var form = document.forms[0];
	if ( form ) {
		form.submit();
	}
}());
