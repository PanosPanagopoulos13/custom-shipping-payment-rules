<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'HT_Shipping_Method' ) ) {
    class HT_Shipping_Method extends WC_Shipping_Method {

        public function __construct( $instance_id = 0 ) {
            $this->instance_id 	  = absint( $instance_id );
            $this->id                 = 'ht_custom_shipping';//this is the id of our shipping method
            $this->method_title       = __( 'HT Custom Shipping', 'htech' );
            $this->method_description = __( 'HT Custom Shipping Rules', 'htech' );
            //add to shipping zones list
            $this->supports = [
                'shipping-zones',
                //'settings', //use this for separate settings page
                'instance-settings',
                'instance-settings-modal',
            ];
            //make it always enabled
            $this->title = __( 'HT Custom Shipping', 'htech' );
            $this->enabled = 'yes';

            $this->init();
        }

        function init() {
            // Load the settings API
            $this->init_form_fields();
            $this->init_settings();

            // Save settings in admin if you have any defined
            add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        //Fields for the settings page
        function init_form_fields() {

            //fields for the modal form from the Zones window
            $this->instance_form_fields = [
                'title' => [
                    'title' => __( 'Title', 'htech' ),
                    'type' => 'text',
                    'description' => __( 'Title to be display on site', 'htech' ),
                    'default' => __( 'Μεταφορικά', 'htech' )
                ],
            ];

            //$this->form_fields - use this with the same array as above for setting fields for separate settings page
        }

        /**
         *  This method is used to calculate the cost for this particular shipping. Package is an array that contains all the products included in the shipment.
         *  @param array $package
         */
        public function calculate_shipping( $package = array()) {
            //as we are using instances for the cost and the title we need to take those values drom the instance_settings
            $intance_settings =  $this->instance_settings;

            // Register the rate
            $this->add_rate([
                'id'      => $this->id,
                'label'   => $intance_settings['title'],
                'package' => $package,
                'cost' => $this->get_cost_for_domain_logic($package),
                'taxes'   => false,
            ]);
        }

        /**
         *  Domain logic for calculating the cost of the shipping
         *  @see https://app.diagrams.net/#G1nKXSYiMxtNAH5q3fE-HuhP_efJRRC0iC#%7B%22pageId%22%3A%22mVnaOOLQajzNjgoc72j5%22%7D
         */
        public function get_cost_for_domain_logic($package)
        {
            $cost = 0;
            $total_weight = 0;
            $items_total_cost = 0;
            $order_has_a_bicycle = false;
            $shipping_country = $package['destination']['country']; // This returns the country code, e.g., 'US'

            foreach ($package['contents'] as $cart_item) {
                $product = $cart_item['data'];
                $product_id = $product->get_id();
        
                // Get the product weight
                // Weight is in kilograms by default
                // For this domain is in grams
                $weight = (float)$product->get_weight()/1000;

                // Get the product volumetric weight
                $volumetric_weight = get_post_meta($product_id, 'volumetric_weight', true);
                if($volumetric_weight && !empty($volumetric_weight)){
                    $volumetric_weight = (float)$volumetric_weight;
                    $weight = $volumetric_weight > $weight ? $volumetric_weight : $weight;
                }

                $total_weight += $weight;

                // Get the line total for the current item (price * quantity)
                $line_total = $cart_item['line_total']; // This includes the total cost of the item, including quantity
                $items_total_cost += $line_total;

                // Check if the product belongs to a specific category or category's childs
                if(!$order_has_a_bicycle){
                    $order_has_a_bicycle = $this->product_belongs_to_categories( $product, $product_id );
                }
            }
            
            // Reset the session for the cash on delivery
            WC()->session->set( 'ht_cash_on_delivery_props', [
                'enabled' => true,
                'cost' => 0,
            ]);

            if($shipping_country == 'GR'){
                $cost = match ($order_has_a_bicycle) {
                    true => $this->get_cost_for_order_with_bicycle_GR($total_weight, $items_total_cost),
                    false => $this->get_cost_for_order_without_bicycle_GR($total_weight, $items_total_cost),
                };
            }else{
                // DISABLE CASH ON DELIVERY for all Cyprus orders
                WC()->session->set( 'ht_cash_on_delivery_props', [
                    'enabled' => false,
                    'cost' => 0,
                ]);
                $cost = match ($order_has_a_bicycle) {
                    true => $this->get_cost_for_order_with_bicycle_CY($total_weight, $items_total_cost),
                    false => $this->get_cost_for_order_without_bicycle_CY($total_weight, $items_total_cost),
                };
            }

            return $cost;
        }


        /**
         * Check if the product belongs to a specific category or category's childs
         */
        public function product_belongs_to_categories($product, $product_id)
        {
            if($product->get_type() == 'variation'){
                $product_id = $product->get_parent_id();
            }
            if(!$product_id){
                return false;
            }
            
            $all_cats = [];

            $cat = get_term_by('slug', 'podilata', 'product_cat');
            $children_cats = get_term_children($cat->term_id, 'product_cat');
            $children_cats[] = $cat->term_id;
            $all_cats = array_merge($all_cats, $children_cats);
    
            $cat = get_term_by('slug', 'podilata-2', 'product_cat');
            $children_cats = get_term_children($cat->term_id, 'product_cat');
            $children_cats[] = $cat->term_id;
            $all_cats = array_merge($all_cats, $children_cats);

            if (has_term($all_cats, 'product_cat', $product_id)) {
                return true;
            }

            return false;
        }
        
        /**
         * Get the cost for the order wich includes at least one bicycle 
         */
        public function get_cost_for_order_with_bicycle_GR($total_weight, $items_total_cost)
        {
            // DISABLE CASH ON DELIVERY
            WC()->session->set( 'ht_cash_on_delivery_props', [
                'enabled' => false,
                'cost' => 0,
            ]);
            if($items_total_cost < 180){

                return 10; // CARGO GR
            }
            return 0; // FREE SHIPPING
        }

        /**
         * Get the cost for the order wich includes at least one bicycle
         */
        public function get_cost_for_order_with_bicycle_CY($total_weight, $items_total_cost)
        {
            if($items_total_cost < 300){
                return 55; // COURIER COST CY
            }
            return 0; // FREE SHIPPING
        }

        public function get_cost_for_order_without_bicycle_GR($total_weight, $items_total_cost)
        {
            if($total_weight >= 20){
                // DISABLE CASH ON DELIVERY
                WC()->session->set( 'ht_cash_on_delivery_props', [
                    'enabled' => false,
                    'cost' => 0,
                ]);

                if($items_total_cost >= 180){
                    return 0; // FREE SHIPPING
                }
                return 10; // CARGO GR - DISABLE CASH ON DELIVERY

            }elseif($total_weight >= 3){
                

                // Enable or disable cash on delivery based on the total cost of the order
                if($items_total_cost <= 500){
                    // ENABLE CASH ON DELIVERY
                    WC()->session->set( 'ht_cash_on_delivery_props', [
                        'enabled' => true,
                        'cost' => 1,
                    ]);
                }else{
                    // DISABLE CASH ON DELIVERY
                    WC()->session->set( 'ht_cash_on_delivery_props', [
                        'enabled' => false,
                        'cost' => 0,
                    ]);
                }

                if($items_total_cost >= 120){

                    return 0; // FREE SHIPPING
                }

                // 2.99 + 0.75 per kg
                return 2.99 + ($total_weight * 0.75);

            }else{
                error_log('hereeqwewqeqeqweqw');
                // Enable or disable cash on delivery based on the total cost of the order
                if($items_total_cost <= 500){
                    // ENABLE CASH ON DELIVERY
                    WC()->session->set( 'ht_cash_on_delivery_props', [
                        'enabled' => true,
                        'cost' => 1,
                    ]);
                }else{
                    // DISABLE CASH ON DELIVERY
                    WC()->session->set( 'ht_cash_on_delivery_props', [
                        'enabled' => false,
                        'cost' => 0,
                    ]);
                }

                if($items_total_cost >= 60){
                    return 0; // FREE SHIPPING
                }
                return 2.99; // COURIER COST

            }
        }

        public function get_cost_for_order_without_bicycle_CY($total_weight, $items_total_cost)
        {
            if($total_weight >= 20){

                if($items_total_cost >= 300){
                    return 0; // FREE SHIPPING
                }
                return 55; // COURIER COST CY

            }elseif($total_weight >= 10){

                if($items_total_cost >= 180){
                    return 0; // FREE SHIPPING
                }
                return 24.90; // COURIER COST CY

            }elseif($total_weight >= 2){

                if($items_total_cost >= 120){
                    return 0; // FREE SHIPPING
                }
                return 18.90; // COURIER COST CY

            }else{

                if($items_total_cost >= 60){
                    return 0; // FREE SHIPPING
                }
                return 9.90; // // COURIER COST CY
            }
        }

    }
}
