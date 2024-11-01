<?php
if ( !defined( 'ABSPATH' ) ) exit;

// Display notice
function valuepay_gf_notice( $message, $type = 'success' ) {

    $plugin = esc_html__( 'ValuePay for Gravity Forms', 'valuepay-gf' );

    printf( '<div class="notice notice-%1$s"><p><strong>%2$s:</strong> %3$s</p></div>', esc_attr( $type ), $plugin, $message );

}

// Log a message in Gravity Forms logs
function valuepay_gf_logger( $message ) {

    if ( class_exists( 'GFLogging' ) && class_exists( 'KLogger' ) ) {
        GFLogging::include_logger();
        GFLogging::log_message( 'gravityformsvaluepay', $message, KLogger::DEBUG );
    }

}

// List of identity types accepted by ValuePay
function valuepay_gf_get_identity_types() {

    return array(
        1 => __( 'New IC No.', 'valuepay-gf' ),
        2 => __( 'Old IC No.', 'valuepay-gf' ),
        3 => __( 'Passport No.', 'valuepay-gf' ),
        4 => __( 'Business Reg. No.', 'valuepay-gf' ),
        5 => __( 'Others', 'valuepay-gf' ),
    );

}

// Get readable identity type
function valuepay_gf_get_identity_type( $key ) {
    $types = valuepay_gf_get_identity_types();
    return isset( $types[ $key ] ) ? $types[ $key ] : false;
}

// Format telephone number
function valuepay_gf_format_telephone( $telephone ) {

    // Get numbers only
    $telephone = preg_replace( '/[^0-9]/', '', $telephone );

    // Add country code in the front of phone number if the phone number starts with zero (0)
    if ( strpos( $telephone, '0' ) === 0 ) {
        $telephone = '+6' . $telephone;
    }

    // Add + symbol in the front of phone number if the phone number has no + symbol
    if ( strpos( $telephone, '+' ) !== 0 ) {
        $telephone = '+' . $telephone;
    }

    return $telephone;

}
