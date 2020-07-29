<?php
/**
 * Plugin Name: WooCommerce Stripe Supporter
 * Plugin URI: http://larasoftbd.net/
 * Description: WooCommerce Stripe Supporter. 
 * Version: 1.0.0
 * Author: larasoft
 * Author URI: https://larasoftbd.net
 * Text Domain: woocommerce_stripe_suporter
 * Domain Path: /languages
 * Requires at least: 4.0
 * Tested up to: 4.8
 *
 * @package     woocommerce_stripe_suporter
 * @category 	Core
 * @author 		LaraSoft
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
define('woocommerce_stripe_suporterDIR', plugin_dir_path( __FILE__ ));
define('woocommerce_stripe_suporterURL', plugin_dir_url( __FILE__ ));
/*
* User ip
*/
function getUserIp(){
            if(!empty($_SERVER['HTTP_CLIENT_IP'])){
                //ip from share internet
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
                //ip pass from proxy
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }else{
                $ip = $_SERVER['REMOTE_ADDR'];
            }
            $ip = str_replace('.', '', $ip);
            $ip = str_replace(',', '', $ip);
            return $ip;
}

require_once(woocommerce_stripe_suporterDIR . 'inc/class.php');

new woocommerce_stripe_suporterClass;
