<?php
if ( !defined( 'ABSPATH' ) ) exit;

class Valuepay_GF_API extends Valuepay_GF_Client {

    // Initialize API
    public function __construct( $username, $app_key, $app_secret, $debug = false ) {

        $this->username   = $username;
        $this->app_key    = $app_key;
        $this->app_secret = $app_secret;
        $this->debug      = $debug;

    }

    // Query bank list
    public function get_banks( array $params ) {
        return $this->post( 'querybanklist', $params );
    }

    // Create a bill
    public function create_bill( array $params ) {
        return $this->post( 'createbill', $params );
    }

    // Set enrolment data
    public function set_enrol_data( array $params ) {
        return $this->post( 'setenroldata', $params );
    }

}
