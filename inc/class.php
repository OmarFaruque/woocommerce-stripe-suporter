<?php
/*
* woocommerce_stripe_suporter Class 
*/


if (!class_exists('woocommerce_stripe_suporterClass')) {
    
    class woocommerce_stripe_suporterClass{
        public $plugin_url;
        public $plugin_dir;
        public $wpdb;
        public $option_tbl; 
        
        /**Plugin init action**/ 
        public function __construct() {
            global $wpdb;
            $this->plugin_url 				= woocommerce_stripe_suporterURL;
            $this->plugin_dir 				= woocommerce_stripe_suporterDIR;
            $this->wpdb 					= $wpdb;	
            $this->option_tbl               = $this->wpdb->prefix . 'options';
         
            $this->init();
        }

        private function init(){
            
            //Backend Script
            add_action( 'admin_enqueue_scripts', array($this, 'larasoftNote_backend_script') );
            //Frontend Script
            add_action( 'wp_enqueue_scripts', array($this, 'larasoftbd_Note_frontend_script') );

            add_action( 'init', array($this, 'register_vendor_custom_fields') );

            add_filter( 'wc_stripe_params',  array($this, 'wc_stripe_params_function'), 10, 3 );
            add_filter( 'woocommerce_stripe_request_headers',  array($this, 'woocommerce_stripe_request_headers_function'), 10, 3 );
            
            add_action( 'admin_init',  array($this, 'removeMenuPage') );

            // registers vendor menus
            add_action( 'admin_menu', array($this, 'register_vendor_menus' ) );
            add_filter( 'wc_stripe_payment_request_params', array( $this, 'wc_stripe_payment_request_params_function'), 10, 3 );

            // add_action('wp_head', array($this, 'testF'));
        }


        

        public function testF(){
         

            // echo 'ip : ' . get_option('ip') . '<br/>';
            // echo 'real ip: ' . getUserIp() . '<br/>';
            $secret_key = get_transient( 'secret_key_' . getUserIp() );
            echo 'secreat key: ' . $secret_key . '<br/>';

            echo 'from webhook: ' . get_option( 'secreakKey' ) . '<br/>';

        }


        public function wc_stripe_payment_request_params_function( $params ) {
                        $product_id = '';
                        foreach( WC()->cart->get_cart() as $cart_item ){
                            $product_id = $cart_item['product_id'];
                        }
            
                        $post_term = wp_get_post_terms( $product_id, WC_PRODUCT_VENDORS_TAXONOMY );
                        $term_id = ! empty( $post_term ) ? $post_term[0]->term_id : '';
            
                        $vendor_stripe_publickey = esc_attr( get_term_meta( $term_id, 'vendor_stripe_publickey', true ) );
            
                        $vendor_data      = WC_Product_Vendors_Utils::get_vendor_data_by_id( $term_id );
                        $publickey         = ! empty( $vendor_data['publickey'] ) ? $vendor_data['publickey'] : '';
                        $secretkey         = ! empty( $vendor_data['secretkey'] ) ? $vendor_data['secretkey'] : '';
            
                        $vendor_stripe_publickey = ! empty( $publickey ) ? $publickey : $vendor_stripe_publickey;
                        $params[ 'key' ] = $vendor_stripe_publickey;
                        return $params;
            }	


        public function removeMenuPage(){
            // remove_menu_page( 'admin.php?page=wcpv-vendor-settings');
            remove_menu_page( 'wcpv-vendor-settings');
        }

        public function register_vendor_menus() {
            add_menu_page( __( 'Store Settings', 'woocommerce-product-vendors' ), __( 'Store Settings', 'woocommerce-product-vendors' ), 'manage_product', 'wcpv-suporter-vendor-settings', array( $this, 'render_settings_page' ), 'dashicons-admin-settings', 60 );
        }

        /**
         * Renders the vendor settings page
         *
         * @access public
         * @since 2.0.0
         * @version 2.0.0
         * @return bool
         */
        public function render_settings_page() {
            wp_enqueue_script( 'wc-enhanced-select' );
            wp_enqueue_script( 'jquery-tiptip' );
    
            $vendor_data = WC_Product_Vendors_Utils::get_vendor_data_from_user();
    
            // handle form submission
            if ( ! empty( $_POST['wcpv_save_vendor_settings_nonce'] ) && ! empty( $_POST['vendor_data'] ) ) {
                // continue only if nonce passes
                if ( wp_verify_nonce( $_POST['wcpv_save_vendor_settings_nonce'], 'wcpv_save_vendor_settings' ) ) {
    
                    $posted_vendor_data = $_POST['vendor_data'];
    
                    // sanitize
                    $posted_vendor_data = array_map( 'sanitize_text_field', $posted_vendor_data );
                    $posted_vendor_data = array_map( 'stripslashes', $posted_vendor_data );
    
                    // Sanitize html editor content.
                    $posted_vendor_data['profile'] = ! empty( $_POST['vendor_data']['profile'] ) ? wp_kses_post( $_POST['vendor_data']['profile'] ) : '';
    
                    // merge the changes with existing settings
                    $posted_vendor_data = array_merge( $vendor_data, $posted_vendor_data );
    
                    if ( update_term_meta( WC_Product_Vendors_Utils::get_logged_in_vendor(), 'vendor_data', $posted_vendor_data ) ) {
    
                        // grab the newly saved settings
                        $vendor_data = WC_Product_Vendors_Utils::get_vendor_data_from_user();
                    }
                }
            }
    
            // logo image
            $logo = ! empty( $vendor_data['logo'] ) ? $vendor_data['logo'] : '';
    
            $hide_remove_image_link = '';
    
            $logo_image_url = wp_get_attachment_image_src( $logo, 'full' );
    
            if ( empty( $logo_image_url ) ) {
                $hide_remove_image_link = 'display:none;';
            }
    
            $profile           = ! empty( $vendor_data['profile'] ) ? $vendor_data['profile'] : '';
            $email             = ! empty( $vendor_data['email'] ) ? $vendor_data['email'] : '';
            $paypal            = ! empty( $vendor_data['paypal'] ) ? $vendor_data['paypal'] : '';
            $vendor_commission = ! empty( $vendor_data['commission'] ) ? $vendor_data['commission'] : get_option( 'wcpv_vendor_settings_default_commission', '0' );
            $tzstring          = ! empty( $vendor_data['timezone'] ) ? $vendor_data['timezone'] : '';
            $publickey         = ! empty( $vendor_data['publickey'] ) ? $vendor_data['publickey'] : '';
            $secretkey         = ! empty( $vendor_data['secretkey'] ) ? $vendor_data['secretkey'] : '';
    
            if ( empty( $tzstring ) ) {
                $tzstring = WC_Product_Vendors_Utils::get_default_timezone_string();
            }
    
            include_once( 'views/html-vendor-store-settings-page.php' );
    
            return true;
        }

        

        /*
        * Appointment backend Script
        */
        function larasoftNote_backend_script(){
            
            wp_enqueue_style( 'b_ws_suporterCSS', $this->plugin_url . 'asset/css/woocommerce_stripe_suporter_backend.css', array(), true, 'all' );
            wp_enqueue_script( 'b_ws_suporterJS', $this->plugin_url . 'asset/js/woocommerce_stripe_suporter_backend.js', array(), true );

        }

        /*
        * Appointment frontend Script
        */
        function larasoftbd_Note_frontend_script(){

            wp_enqueue_style( 'f_ws_suporterCSS', $this->plugin_url . 'asset/css/woocommerce_stripe_suporter_frontend.css', array(), true, 'all' );
            wp_enqueue_script('f_ws_suporterJS', $this->plugin_url . 'asset/js/woocommerce_stripe_suporter_frontend.js', array('jquery'), time(), true);
           
        }


        /**************************************************************************************************************************
        * Register EXTRA fields for Vendors                                                                                       *
        * SOURCE: https://www.strategylions.com.au/development/wordpress/adding-custom-fields-to-the-vendor-registration-form/    *
        ***************************************************************************************************************************/
        
        public function register_vendor_custom_fields() {
            require_once( WP_PLUGIN_DIR . '/woocommerce-product-vendors/includes/class-wc-product-vendors-utils.php' );

            // if ( WC_Product_Vendors_Utils::is_admin_vendor() ) {
				// add_menu_page( __( 'Store Settings', 'woocommerce-product-vendors' ), __( 'Store Settings', 'woocommerce-product-vendors' ), 'manage_product', 'wcpv-vendor-settings', array( $this, 'render_settings_page' ), 'dashicons-admin-settings', 60.77 );
                remove_menu_page( 'admin.php?page=wcpv-vendor-settings');
			// }


            add_action( WC_PRODUCT_VENDORS_TAXONOMY . '_add_form_fields', array($this, 'add_vendor_custom_fields') );
            add_action( WC_PRODUCT_VENDORS_TAXONOMY . '_edit_form_fields', array($this, 'edit_vendor_custom_fields'), 10 );
            add_action( 'edited_' . WC_PRODUCT_VENDORS_TAXONOMY, array($this, 'save_vendor_custom_fields') );
            add_action( 'created_' . WC_PRODUCT_VENDORS_TAXONOMY,  array($this, 'save_vendor_custom_fields') );
        }

        /*****************************************************
        * Add the Extra Vendor Fields to the Vendor ADD form *
        ******************************************************/
        function add_vendor_custom_fields() {
            wp_nonce_field( basename( __FILE__ ), 'vendor_custom_fields_nonce' );
            ?>

            <div class="form-field">
                <label for="vendor_stripe_publickey"><?php _e( 'Stripe Public Key', 'domain' ); ?></label>
                <input type="text" name="vendor_stripe_publickey" id="vendor_stripe_publickey" value="" />
            </div>

            <div class="form-field">
                <label for="vendor_stripe_secretkey"><?php _e( 'Stripe Secret Key', 'domain' ); ?></label>
                <input type="text" name="vendor_stripe_secretkey" id="vendor_stripe_secretkey" value="" />
            </div>
            <?php
        }


        /******************************************************
        * Add the Extra Vendor Fields to the Vendor EDIT form *
        *******************************************************/
        function edit_vendor_custom_fields( $term ) {
            wp_nonce_field( basename( __FILE__ ), 'vendor_custom_fields_nonce' );
            ?>
            <tr class="form-field">
                <th scope="row" valign="top"><label for="vendor_stripe_publickey"><?php _e( 'Stripe Public Key' ); ?></label></th>
                <td><input type="text" name="vendor_stripe_publickey" id="vendor_stripe_publickey" value="<?php echo esc_attr( get_term_meta( $term->term_id, 'vendor_stripe_publickey', true ) ); ?>" /></td>
            </tr>
            <tr class="form-field">
                <th scope="row" valign="top"><label for="vendor_stripe_secretkey"><?php _e( 'Stripe Secret Key' ); ?></label></th>
                <td><input type="text" name="vendor_stripe_secretkey" id="vendor_stripe_secretkey" value="<?php echo esc_attr( get_term_meta( $term->term_id, 'vendor_stripe_secretkey', true ) ); ?>" /></td>
            </tr>
            <?php
        }

        /***********************************************
        * Save the extra Vendor Fields to the database *
        ************************************************/
        function save_vendor_custom_fields( $term_id ) {
            if ( ! wp_verify_nonce( $_POST['vendor_custom_fields_nonce'], basename( __FILE__ ) ) ) {
                return;
            }
            $old_vendor_stripe_publickey	= get_term_meta( $term_id, 'vendor_stripe_publickey', true );
            $old_vendor_stripe_secretkey	= get_term_meta( $term_id, 'vendor_stripe_secretkey', true );
            $new_vendor_stripe_publickey	= esc_attr( $_POST['vendor_stripe_publickey'] );
            $new_vendor_stripe_secretkey	= esc_attr( $_POST['vendor_stripe_secretkey'] );
            if ( ! empty( $old_vendor_stripe_publickey ) && $new_vendor_stripe_publickey === '' ) {
                delete_term_meta( $term_id, 'vendor_stripe_publickey' );
            }
            else if ( $old_vendor_stripe_publickey !== $new_vendor_stripe_publickey ) {
                update_term_meta( $term_id, 'vendor_stripe_publickey', $new_vendor_stripe_publickey, $old_vendor_stripe_publickey );
            }
            
            if ( ! empty( $old_vendor_stripe_secretkey ) && $new_vendor_stripe_secretkey === '' ) {
                delete_term_meta( $term_id, 'vendor_stripe_secretkey' );
            }
            else if ( $old_vendor_stripe_secretkey !== $new_vendor_stripe_secretkey ) {
                update_term_meta( $term_id, 'vendor_stripe_secretkey', $new_vendor_stripe_secretkey, $old_vendor_stripe_secretkey );
            }
        // 	update_term_meta( $term_id, 'vendor_stripe_publickey', $new_vendor_stripe_publickey, $old_vendor_stripe_publickey );
        // 	update_term_meta( $term_id, 'vendor_stripe_secretkey', $new_vendor_stripe_secretkey, $old_vendor_stripe_secretkey );
        }


        function wc_stripe_params_function($params){
            global $wp;
            $product_id = '';

            foreach( WC()->cart->get_cart() as $cart_item ){
                $product_id = $cart_item['product_id'];
            }

            if(isset($wp->query_vars['order-pay'])):
                $order_id = $wp->query_vars['order-pay'];
                $order = new WC_Order( $order_id );
                foreach ( $order->get_items() as $item_id => $item ) {
                    $product_id = $item->get_product_id();
                }
            endif;

            $vendor_id = WC_Product_Vendors_Utils::get_vendor_id_from_product( $product_id );
			$post_term = wp_get_post_terms( $product_id, WC_PRODUCT_VENDORS_TAXONOMY );
            $term_id = ! empty( $post_term ) ? $post_term[0]->term_id : '';

            $vendor_stripe_publickey = esc_attr( get_term_meta( $term_id, 'vendor_stripe_publickey', true ) );
            $vendor_stripe_secretkey = esc_attr( get_term_meta( $term_id, 'vendor_stripe_secretkey', true ) );

            $vendor_data      = WC_Product_Vendors_Utils::get_vendor_data_by_id( $vendor_id );
            $publickey        = ! empty( $vendor_data['publickey'] ) ? $vendor_data['publickey'] : $vendor_stripe_publickey;
            $secret_key       = ! empty( $vendor_data['secretkey'] ) ? $vendor_data['secretkey'] : $vendor_stripe_secretkey;

            if(!empty($publickey)){
                $params[ 'key' ] = $publickey;
                set_transient( 'secret_key', $secret_key, 12 * HOUR_IN_SECONDS );
            }else{
                delete_transient( 'secret_key' );
            }
            
            return $params;
        }

        public function woocommerce_stripe_request_headers_function( $headers_args ) {
            $secret_key = get_transient( 'secret_key' );
            // $secret_key = 'sk_test_51GllUtLHBJwMkkeOfLz2l0WZ1wLgmIVRB0uzmgKKAJVl8YVQjtOIQtsWSLAXAyPZlIbfdtZSzWkUJg0UAYDaBfKE0069t5of7Z';
            if(!empty($secret_key)){
                $secret_key = base64_encode($secret_key);
                $headers_args[ 'Authorization' ] = 'Basic ' . $secret_key;
            }
            return $headers_args;
        }

    } // End Class
} // End Class check if exist / not