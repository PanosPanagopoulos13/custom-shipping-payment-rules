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

	/**
	 * Include the custom shipping method class
	 *
	 * @return void
	 */
	public function include_shipping_method()
	{
		require_once plugin_dir_path( dirname( __FILE__ ) ) . '/includes/class-ht-custom-shipping-method.php';
	}

	/**
	 * Add the custom shipping method to the available shipping methods
	 *
	 * @param $methods The available shipping methods
	 * @return array
	 */
	public function add_shipping_method($methods)
	{
		$methods['ht_custom_shipping'] = 'HT_Shipping_Method';
		return $methods;
	}

	/**
	 * Clear the WC shipping rates cache
	 *
	 * @param $methods The available shipping methods
	 * @return void
	 */
	public function clear_wc_shipping_rates_cache($methods)
	{
		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}
	
	/**
	 * Maybe disable the COD payment gateway
	 *
	 * @param $cash_on_delivery_props The COD props from the session
	 * @return array
	 */
	public function enable_disable_payment_gateway($available_gateways)
	{
		if(empty(WC()->session)){ return; }
		$cash_on_delivery_props = WC()->session->get('ht_cash_on_delivery_props');
		// error_log(print_r($cash_on_delivery_props,true));
		// error_log(print_r($available_gateways,true));

		if(isset($cash_on_delivery_props['enabled']) && !$cash_on_delivery_props['enabled']){
			unset($available_gateways['cod']);
		}
		return $available_gateways;
	}

	/**
	 * Maybe add a fee to the cart if the chosen payment method is COD
	 *
	 * @param $chosen_payment_method The chosen payment method
	 * @param $cash_on_delivery_props The COD props from the session
	 * @return void
	 */
	public function add_cod_payment_gateway_fee($cart)
	{
		if (is_admin() && !defined('DOING_AJAX')) {
			return;
		}
		if(empty(WC()->session)){ return; }

		// Get available payment gateways
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

		if(!isset($available_gateways['cod'])){
			// Reset the session for the cash on delivery
			WC()->session->set( 'ht_cash_on_delivery_props', null);
			return;
		}

		// Get the chosen payment method
		$chosen_payment_method = WC()->session->get('chosen_payment_method');

		// Get the COD props from the session
		$cash_on_delivery_props = WC()->session->get('ht_cash_on_delivery_props');
		$cod_fee = $cash_on_delivery_props['cost'] ?? 0;

		if ($chosen_payment_method === 'cod' && (float)$cod_fee > 0) {
			WC()->cart->add_fee(__('ΑΝΤΙΚΑΤΑΒΟΛΗ', 'htech'), (float)$cod_fee, true, 'standard');
		}

	}

	/**
	 * Maybe disable slm_delivery_service shipping method
	 *
	 * @param $rates The available shipping rates
	 * @param $package The package
	 * @return array
	 */
	public function enable_disable_shipping_methods($rates, $package)
	{
	
		$disable = false;

		foreach ($package['contents'] as $cart_item) {
			$product = $cart_item['data'];
			$product_id = $product->get_id();
		}
	
		$shipping_method_id = 'slm_delivery_service';
	
		// Check the condition
		if ($disable) {
			// Loop through the available rates
			foreach ($rates as $rate_key => $rate) {
				// Check if this is the shipping method we want to disable
				if ($rate->method_id === $shipping_method_id) {
					unset($rates[$rate_key]); // Remove the shipping method
				}
			}
		}
	
		return $rates;
	}

	/**
	 * Add hidden field for transport type
	 *
	 * @param array $fields
	 * @return array
	 */
	public function woocommerce_checkout_fields($fields)
	{
		$fields['billing']['ht_transport_type'] = [
			'type' => 'hidden',
			'class' => [''],
			'clear' => true,
			'priority' => 0,
		];
		return $fields;
	}
	
	/**
	 * Print script to change the transport type if ht_custom_shipping method is chosen
	 *
	 * @return void
	 */
	public function woocommerce_review_order_after_order_total()
	{
		$transport_type = '';

		if(!empty(WC()->session)){ 

			// Get the selected shipping methods array
			$chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');

				// Check if a shipping method is selected
				if (!empty($chosen_shipping_methods) && is_array($chosen_shipping_methods)) {
				
					$selected_shipping_method = $chosen_shipping_methods[0];
					//error_log('Selected Shipping Method: ' . $selected_shipping_method);

					if ($selected_shipping_method === 'ht_custom_shipping') {
						$transport_type = WC()->session->get('ht_transport_company_type');
					}
				}
		}

		echo '
		<script>
			document.getElementById("ht_transport_type").value="'. $transport_type . '";
		</script>';
	}

	/**
	 * Save the transport type to order
	 *
	 * @return void
	 */
	public function woocommerce_checkout_update_order_meta($order_id, $data)
	{
		$order = wc_get_order($order_id);
        if($order){
            error_log(print_r($data, true));
            $shipping_items = $order->get_items( 'shipping' );
            if(is_array($shipping_items) && !empty($shipping_items)){
                foreach ( $shipping_items as $item_id => $shipping_item ) {
                    if($shipping_item->get_method_id() == 'ht_custom_shipping'){
                        $order->update_meta_data('ht_transport_type', sanitize_text_field($_POST['ht_transport_type']));
                        $order->save();
                    }
                    break;
                }
            }
        }
	}
}
