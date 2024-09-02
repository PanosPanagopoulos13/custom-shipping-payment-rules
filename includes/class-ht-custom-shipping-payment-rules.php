<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://hellenictechnologies.com
 * @since      1.0.0
 *
 * @package    Ht_Custom_Shipping_Payment_Rules
 * @subpackage Ht_Custom_Shipping_Payment_Rules/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Ht_Custom_Shipping_Payment_Rules
 * @subpackage Ht_Custom_Shipping_Payment_Rules/includes
 * @author     hHellenic Technologies <info@hellenictechnologies.com>
 */
class Ht_Custom_Shipping_Payment_Rules {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Ht_Custom_Shipping_Payment_Rules_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'HT_CUSTOM_SHIPPING_PAYMENT_RULES_VERSION' ) ) {
			$this->version = HT_CUSTOM_SHIPPING_PAYMENT_RULES_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'ht-custom-shipping-payment-rules';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Ht_Custom_Shipping_Payment_Rules_Loader. Orchestrates the hooks of the plugin.
	 * - Ht_Custom_Shipping_Payment_Rules_i18n. Defines internationalization functionality.
	 * - Ht_Custom_Shipping_Payment_Rules_Admin. Defines all hooks for the admin area.
	 * - Ht_Custom_Shipping_Payment_Rules_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ht-custom-shipping-payment-rules-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ht-custom-shipping-payment-rules-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ht-custom-shipping-payment-rules-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-ht-custom-shipping-payment-rules-public.php';

		$this->loader = new Ht_Custom_Shipping_Payment_Rules_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Ht_Custom_Shipping_Payment_Rules_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Ht_Custom_Shipping_Payment_Rules_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Ht_Custom_Shipping_Payment_Rules_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Ht_Custom_Shipping_Payment_Rules_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		// Create the shipping method.
		$this->loader->add_action( 'woocommerce_shipping_init', $plugin_public, 'include_shipping_method' );
		$this->loader->add_filter( 'woocommerce_shipping_methods', $plugin_public, 'add_shipping_method' );

		// Clear shipping rates cache for checkout.
		$this->loader->add_action( 'woocommerce_checkout_update_order_review', $plugin_public, 'clear_wc_shipping_rates_cache' );

		// Enable disable payment methods
		$this->loader->add_filter( 'woocommerce_available_payment_gateways', $plugin_public, 'enable_disable_payment_gateway' );
		// Enable disable shipping methods
		//$this->loader->add_filter( 'woocommerce_package_rates', $plugin_public, 'enable_disable_shipping_methods',10,2 );

		// Add a fee to the COD payment gateway
		$this->loader->add_action( 'woocommerce_cart_calculate_fees', $plugin_public, 'add_cod_payment_gateway_fee' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Ht_Custom_Shipping_Payment_Rules_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
