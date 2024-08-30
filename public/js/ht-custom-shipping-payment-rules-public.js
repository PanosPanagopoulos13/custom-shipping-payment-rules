(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	
	// Update checkout on payment method change. This is needed beause we wanna add a custom fee based on the shipping method
	// @see Ht_Custom_Shipping_Payment_Rules_Public::add_cod_payment_gateway_fee()
	$( document ).ready(function() {
		console.log('ready');
		console.log($('form.checkout input[name="payment_method"]'));
		$('form.checkout').on('change', 'input[name="payment_method"]', () => {
			console.log('updating');
			$( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
		});
	});

})( jQuery );
