<?php
if ( !defined( 'ABSPATH' ) ) exit;

class Valuepay_GF {

    // Load dependencies
    public function __construct() {

        // Functions
        require_once( VALUEPAY_GF_PATH . 'includes/functions.php' );

        // API
        require_once( VALUEPAY_GF_PATH . 'includes/abstracts/abstract-valuepay-gf-client.php' );
        require_once( VALUEPAY_GF_PATH . 'includes/class-valuepay-gf-api.php' );

        // Admin
        require_once( VALUEPAY_GF_PATH . 'admin/class-valuepay-gf-admin.php' );

        // Initialize payment gateway
        require_once( VALUEPAY_GF_PATH . 'includes/class-valuepay-gf-init.php' );

    }

}
new Valuepay_GF();
