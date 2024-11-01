<?php
if ( !defined( 'ABSPATH' ) ) exit;

class Valuepay_GF_Admin {

    // Register hooks
    public function __construct() {

        add_action( 'admin_notices', array( $this, 'gravityforms_notice' ) );
        add_action( 'admin_notices', array( $this, 'currency_not_supported_notice' ) );

    }

    // Check if Gravity Forms is installed and activated
    private function is_gravityforms_activated() {
        return in_array( 'gravityforms/gravityforms.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
    }

    // Show notice if Gravity Forms not installed
    public function gravityforms_notice() {

        if ( !$this->is_gravityforms_activated() ) {
            valuepay_gf_notice( __( 'Gravity Forms needs to be installed and activated.', 'valuepay-gf' ), 'error' );
        }

    }

    // Show notice if currency selected is not supported by ValuePay
    public function currency_not_supported_notice() {

        if ( !method_exists( 'GFCommon', 'get_currency' ) ) {
            return false;
        }

        if ( GFCommon::get_currency() !== 'MYR' ) {
            valuepay_gf_notice( sprintf( __( 'Currency not supported by ValuePay. <a href="%s">Change currency</a>', 'valuepay-gf' ), admin_url( 'admin.php?page=gf_settings' ) ), 'error' );
        }

    }

}
new Valuepay_GF_Admin();
