<?php
if ( !defined( 'ABSPATH' ) ) exit;

class Valuepay_GF_Init {

    // Register hooks
    public function __construct() {

        add_action( 'gform_loaded', array( $this, 'load_dependencies' ), 5 );

    }

    // Load required files
    public function load_dependencies() {

        GFForms::include_payment_addon_framework();

        require_once( VALUEPAY_GF_PATH . 'includes/class-valuepay-gf-gateway.php' );

        GFAddOn::register( 'Valuepay_GF_Gateway' );

    }

}
new Valuepay_GF_Init();
