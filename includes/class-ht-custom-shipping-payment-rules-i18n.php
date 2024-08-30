<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://hellenictechnologies.com
 * @since      1.0.0
 *
 * @package    Ht_Custom_Shipping_Payment_Rules
 * @subpackage Ht_Custom_Shipping_Payment_Rules/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Ht_Custom_Shipping_Payment_Rules
 * @subpackage Ht_Custom_Shipping_Payment_Rules/includes
 * @author     hHellenic Technologies <info@hellenictechnologies.com>
 */
class Ht_Custom_Shipping_Payment_Rules_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'ht-custom-shipping-payment-rules',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
