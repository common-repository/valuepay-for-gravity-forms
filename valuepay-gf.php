<?php
/**
 * Plugin Name:       ValuePay for Gravity Forms
 * Description:       Accept payment on Gravity Forms using ValuePay.
 * Version:           1.0.3
 * Requires at least: 4.6
 * Requires PHP:      7.0
 * Author:            Valuefy Solutions Sdn Bhd
 * Author URI:        https://valuepay.my/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( !defined( 'ABSPATH' ) ) exit;

if ( class_exists( 'Valuepay_GF' ) ) return;

define( 'VALUEPAY_GF_FILE', __FILE__ );
define( 'VALUEPAY_GF_URL', plugin_dir_url( VALUEPAY_GF_FILE ) );
define( 'VALUEPAY_GF_PATH', plugin_dir_path( VALUEPAY_GF_FILE ) );
define( 'VALUEPAY_GF_BASENAME', plugin_basename( VALUEPAY_GF_FILE ) );
define( 'VALUEPAY_GF_VERSION', '1.0.3' );

// Plugin core class
require( VALUEPAY_GF_PATH . 'includes/class-valuepay-gf.php' );
