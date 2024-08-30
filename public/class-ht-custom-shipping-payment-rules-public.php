<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://hellenictechnologies.com
 * @since      1.0.0
 *
 * @package    Ht_Custom_Shipping_Payment_Rules
 * @subpackage Ht_Custom_Shipping_Payment_Rules/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Ht_Custom_Shipping_Payment_Rules
 * @subpackage Ht_Custom_Shipping_Payment_Rules/public
 * @author     hHellenic Technologies <info@hellenictechnologies.com>
 */
class Ht_Custom_Shipping_Payment_Rules_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Ht_Custom_Shipping_Payment_Rules_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Ht_Custom_Shipping_Payment_Rules_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ht-custom-shipping-payment-rules-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Ht_Custom_Shipping_Payment_Rules_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Ht_Custom_Shipping_Payment_Rules_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ht-custom-shipping-payment-rules-public.js', array( 'jquery' ), $this->version, false );

	}

	public function include_shipping_method()
	{
		require_once plugin_dir_path( dirname( __FILE__ ) ) . '/includes/class-ht-custom-shipping-method.php';
	}

	public function add_shipping_method($methods)
	{
		$methods['ht_custom_shipping'] = 'HT_Shipping_Method';
		return $methods;
	}

	public function clear_wc_shipping_rates_cache($methods)
	{
		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}
	

	public function enable_disable_payment_gateway($available_gateways)
	{
		if(empty(WC()->session)){ return; }
		$cash_on_delivery_props = WC()->session->get('ht_cash_on_delivery_props');
		// error_log(print_r($cash_on_delivery_props,true));
		// error_log(print_r($available_gateways,true));

		if(!$cash_on_delivery_props['enabled']){
			unset($available_gateways['cod']);
		}
		return $available_gateways;
	}

	public function add_cod_payment_gateway_fee()
	{
		if (is_admin() && !defined('DOING_AJAX')) {
			return;
		}
		if(empty(WC()->session)){ return; }

		// Get the chosen payment method
		$chosen_payment_method = WC()->session->get('chosen_payment_method');

		// Get the COD props from the session
		$cash_on_delivery_props = WC()->session->get('ht_cash_on_delivery_props');
		$cod_fee = $cash_on_delivery_props['cost'] ?? 0;

		if ($chosen_payment_method === 'cod' && (float)$cod_fee > 0) {
			error_log('adding fee');
			WC()->cart->add_fee(__('ΑΝΤΙΚΑΤΑΒΟΛΗ', 'htech'), (float)$cod_fee, true, 'standard');
		}

	}
}
