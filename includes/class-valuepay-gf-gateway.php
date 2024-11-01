<?php
if ( !defined( 'ABSPATH' ) ) exit;

class Valuepay_GF_Gateway extends GFPaymentAddOn {

    protected $_version = VALUEPAY_GF_VERSION;
    protected $_min_gravityforms_version = '1.8.12';
    protected $_slug = 'gravityformsvaluepay';
    protected $_path = 'gravityformsvaluepay/valuepay.php';
    protected $_full_path = __FILE__;
    protected $_title = 'ValuePay for Gravity Forms';
    protected $_short_title = 'ValuePay';
    protected $_supports_callbacks = true;
    protected $_requires_credit_card = false;

    private $valuepay;

    private static $_instance = null;

    // Returns an instance of this class, and stores it in the $_instance property
    public static function get_instance() {

        if ( self::$_instance == null ) {
            self::$_instance = new self();
        }

        return self::$_instance;

    }

    // Register hooks
    public function init() {

        parent::init();

        add_filter( 'gform_pre_render', array( $this, 'identity_types_dropdown' ) );
        add_filter( 'gform_pre_validation', array( $this, 'identity_types_dropdown' ) );
        add_filter( 'gform_pre_submission_filter', array( $this, 'identity_types_dropdown' ) );
        add_filter( 'gform_admin_pre_render', array( $this, 'identity_types_dropdown' ) );

        add_filter( 'gform_pre_render', array( $this, 'banks_dropdown' ) );
        add_filter( 'gform_pre_validation', array( $this, 'banks_dropdown' ) );
        add_filter( 'gform_pre_submission_filter', array( $this, 'banks_dropdown' ) );
        add_filter( 'gform_admin_pre_render', array( $this, 'banks_dropdown' ) );

        add_filter( 'gform_pre_render', array( $this, 'payment_types_dropdown' ) );
        add_filter( 'gform_pre_validation', array( $this, 'payment_types_dropdown' ) );
        add_filter( 'gform_pre_submission_filter', array( $this, 'payment_types_dropdown' ) );
        add_filter( 'gform_admin_pre_render', array( $this, 'payment_types_dropdown' ) );

        add_action( 'wp', array( $this, 'maybe_thankyou_page' ), 5 );

    }

    // Settings icon
    public function get_menu_icon() {
        return VALUEPAY_GF_URL . 'assets/images/valuepay-icon.png';
    }

    // Feed settings
    public function feed_settings_fields() {

        $default_settings = parent::feed_settings_fields();

        $fields = array(
            array(
                'name'          => 'username',
                'label'         => esc_html__( 'Merchant Username', 'valuepay-gf' ),
                'type'          => 'text',
                'class'         => 'medium',
                'required'      => true,
                'tooltip'       => '<h6>' . esc_html__( 'Merchant Username', 'valuepay-gf' ) . '</h6>' . esc_html__( 'Merchant username can be obtained in ValuePay merchant dashboard in Business Profile page.', 'valuepay-gf' ),
            ),
            array(
                'name'          => 'app_key',
                'label'         => esc_html__( 'Application Key', 'valuepay-gf' ),
                'type'          => 'text',
                'class'         => 'medium',
                'required'      => true,
                'tooltip'       => '<h6>' . esc_html__( 'Application Key', 'valuepay-gf' ) . '</h6>' . esc_html__( 'Application key can be obtained in ValuePay merchant dashboard in Business Profile page.', 'valuepay-gf' ),
            ),
            array(
                'name'          => 'app_secret',
                'label'         => esc_html__( 'Application Secret', 'valuepay-gf' ),
                'type'          => 'text',
                'class'         => 'medium',
                'required'      => true,
                'tooltip'       => '<h6>' . esc_html__( 'Application Secret', 'valuepay-gf' ) . '</h6>' . esc_html__( 'Application secret can be obtained in ValuePay merchant dashboard in Business Profile page.', 'valuepay-gf' ),
            ),
            array(
                'name'          => 'collection_id',
                'label'         => esc_html__( 'Collection ID', 'valuepay-gf' ),
                'type'          => 'text',
                'class'         => 'medium',
                'required'      => false,
                'tooltip'       => '<h6>' . esc_html__( 'Collection ID', 'valuepay-gf' ) . '</h6>' . esc_html__( 'Collection ID can be obtained under FPX Payment menu, in My Collection List page. Leave blank to disable one time payment.', 'valuepay-gf' ),
            ),
            array(
                'name'          => 'mandate_id',
                'label'         => esc_html__( 'Mandate ID', 'valuepay-gf' ),
                'type'          => 'text',
                'class'         => 'medium',
                'required'      => false,
                'tooltip'       => '<h6>' . esc_html__( 'Mandate ID', 'valuepay-gf' ) . '</h6>' . esc_html__( 'Mandate ID can be obtained from ValuePay merchant dashboard under E-Mandate Collection menu, in My Mandate List page. Leave blank to disable recurring payment.', 'valuepay-gf' ),
            ),
            array(
                'name'          => 'frequency_type',
                'label'         => esc_html__( 'Frequency Type', 'valuepay-gf' ),
                'type'          => 'select',
                'required'      => true,
                'tooltip'       => '<h6>' . esc_html__( 'Frequency Type', 'valuepay-gf' ) . '</h6>' . esc_html__( 'Select frequency type for the mandate above.', 'valuepay-gf' ),
                'dependency'    => array(
                    'live'   => true,
                    'fields' => array(
                        array(
                            'field' => 'mandate_id',
                        ),
                    ),
                ),
                'choices'       => array(
                    array(
                        'label' => __( 'Weekly', 'valuepay-gf' ),
                        'value' => 'weekly',
                    ),
                    array(
                        'label' => __( 'Monthly', 'valuepay-gf' ),
                        'value' => 'monthly',
                    ),
                ),
                'default_value' => 'monthly',
            ),
        );

        $default_settings = $this->add_field_after( 'feedName', $fields, $default_settings );

        // Remove Subscription from Transaction Type dropdown
        $transaction_type = $this->get_field( 'transactionType', $default_settings );
        unset( $transaction_type['choices'][2] );

        $default_settings = $this->replace_field( 'transactionType', $transaction_type, $default_settings );

        return $default_settings;

    }

    // Extra billing information fields
    public function billing_info_fields() {

        return array(
            array(
                'name'     => 'name',
                'label'    => __( 'Name', 'valuepay-gf' ),
                'required' => true,
            ),
            array(
                'name'     => 'email',
                'label'    => __( 'Email', 'valuepay-gf' ),
                'required' => true,
            ),
            array(
                'name'     => 'telephone',
                'label'    => __( 'Telephone', 'valuepay-gf' ),
                'required' => true,
            ),
            array(
                'name'     => 'identity_type',
                'label'    => __( 'Identity Type', 'valuepay-gf' ),
            ),
            array(
                'name'     => 'identity_value',
                'label'    => __( 'Identity Value', 'valuepay-gf' ),
            ),
            array(
                'name'     => 'bank',
                'label'    => __( 'Bank', 'valuepay-gf' ),
            ),
            array(
                'name'     => 'payment_type',
                'label'    => __( 'Payment Type', 'valuepay-gf' ),
            ),
        );

    }

    // Populate list of identity types on mapped field
    public function identity_types_dropdown( $form ) {

        // Skip if no active payment feed found
        if ( !$feed = $this->get_active_payment_feed() ) {
            return $form;
        }

        $identity_type_field_id = isset( $feed['meta']['billingInformation_identity_type'] ) ? $feed['meta']['billingInformation_identity_type'] : false;

        // Skip if identity type field is not mapped
        if ( !$identity_type_field_id ) {
            return $form;
        }

        $identity_types = valuepay_gf_get_identity_types();

        foreach ( $form['fields'] as &$field ) {
            if ( $field->id != $identity_type_field_id || $field->type !== 'select' ) {
                continue;
            }

            $choices = array();

            foreach ( $identity_types as $key => $value ) {
                $choices[] = array(
                    'text'  => $value,
                    'value' => $key,
                );
            }

            $field->choices = $choices;
        }

        return $form;

    }

    // Populate list of banks on mapped field
    public function banks_dropdown( $form ) {

        // Skip if no active payment feed found
        if ( !$feed = $this->get_active_payment_feed() ) {
            return $form;
        }

        $bank_field_id = isset( $feed['meta']['billingInformation_bank'] ) ? $feed['meta']['billingInformation_bank'] : false;

        // Skip if payment type field is not mapped
        if ( !$bank_field_id ) {
            return $form;
        }

        $banks = $this->get_banks();

        foreach ( $form['fields'] as &$field ) {
            if ( $field->id != $bank_field_id || $field->type !== 'select' ) {
                continue;
            }

            $choices = array();

            foreach ( $banks as $key => $value ) {
                $choices[] = array(
                    'text'  => $value,
                    'value' => $key,
                );
            }

            $field->choices = $choices;
        }

        return $form;

    }

    // Get list of banks from ValuePay
    private function get_banks() {

        $banks = get_transient( 'valuepay_gf_banks' );

        if ( !$banks || !is_array( $banks ) ) {

            if ( !$feed = $this->get_active_payment_feed() ) {
                return false;
            }

            $this->init_api( $feed['meta'] );

            $banks = array();

            try {
                $banks_query = $this->valuepay->get_banks( array(
                    'username' => $this->valuepay->username,
                    'reqhash'  => md5( $this->valuepay->app_key . $this->valuepay->username ),
                ) );

                if ( isset( $banks_query[1]['bank_list'] ) && !empty( $banks_query[1]['bank_list'] ) ) {
                    $banks = $banks_query[1]['bank_list'];

                    // Set transient, so that we can retrieve using transient
                    // instead of retrieve through API request to ValuePay.
                    set_transient( 'valuepay_gf_banks', $banks, DAY_IN_SECONDS );
                }
            } catch ( Exception $e ) {}
        }

        return $banks;

    }

    // Populate list of payment types on mapped field
    public function payment_types_dropdown( $form ) {

        // Skip if no active payment feed found
        if ( !$feed = $this->get_active_payment_feed() ) {
            return $form;
        }

        $payment_type_field_id = isset( $feed['meta']['billingInformation_payment_type'] ) ? $feed['meta']['billingInformation_payment_type'] : false;

        // Skip if payment type field is not mapped
        if ( !$payment_type_field_id ) {
            return $form;
        }

        foreach ( $form['fields'] as &$field ) {
            if ( $field->id != $payment_type_field_id || $field->type !== 'select' ) {
                continue;
            }

            $choices = array();

            $collection_id  = isset( $feed['meta']['collection_id'] ) ? $feed['meta']['collection_id'] : null;
            $mandate_id     = isset( $feed['meta']['mandate_id'] ) ? $feed['meta']['mandate_id'] : null;
            $frequency_type = isset( $feed['meta']['frequency_type'] ) ? $feed['meta']['frequency_type'] : null;

            $frequency_type_label = $frequency_type === 'weekly' ? __( 'Weekly', 'valuepay-gf' ) : __( 'Monthly', 'valuepay-gf' );

            if ( $collection_id ) {
                $choices[] = array(
                    'text'  => __( 'One Time Payment', 'valuepay-gf' ),
                    'value' => 'single',
                );
            }

            if ( $mandate_id ) {
                $choices[] = array(
                    'text'  => sprintf( __( 'Recurring %s Payment', 'valuepay-gf' ), $frequency_type_label ),
                    'value' => 'recurring',
                );
            }

            $field->choices = $choices;
        }

        return $form;

    }

    // Get payment feed without need to pass $entry_id
    // $this->get_payment_feed() from Gravity Forms requires to pass $entry_id
    private function get_active_payment_feed() {

        $feeds = $this->get_active_feeds();

        $payment_feed = false;

        foreach ( $feeds as $feed ) {
            if ( $feed['is_active'] && $this->is_feed_condition_met( $feed, $form, $entry ) ) {
                $payment_feed = $feed;
                break;
            }
        }

        return $payment_feed;

    }

    // Remove Options field (before Conditional Logic field)
    public function option_choices() {
        return false;
    }

    // Initialize API
    private function init_api( $data ) {

        if ( !$this->valuepay ) {
            $username   = isset( $data['username'] ) ? $data['username'] : null;
            $app_key    = isset( $data['app_key'] ) ? $data['app_key'] : null;
            $app_secret = isset( $data['app_secret'] ) ? $data['app_secret'] : null;
            $debug      = defined( 'VALUEPAY_GF_API_DEBUG' ) ? VALUEPAY_GF_API_DEBUG : false;

            $this->valuepay = new Valuepay_GF_API( $username, $app_key, $app_secret, $debug );
        }

    }

    // Create a bill
    public function redirect_url( $feed, $submission_data, $form, $entry ) {

        $this->log_debug( __METHOD__ . '(): Creating bill for entry #' . $entry['id'] );

        $this->init_api( $feed['meta'] );

        try {
            $this->log_debug( __METHOD__ . sprintf( '(): Creating bill for entry #%s', $entry['id'] ) );

            $payment_type = rgar( $submission_data, 'payment_type' );

            if ( !$payment_type ) {
                $payment_type = 'single';
            }

            if ( $payment_type === 'recurring' ) {
                $payment_url = $this->get_enrolment_url( $feed, $submission_data, $form, $entry );
            } else {
                $payment_url = $this->get_bill_url( $feed, $submission_data, $form, $entry );
            }

            $this->log_debug( __METHOD__ . sprintf( '(): Bill created for entry #%d', $entry['id'] ) );

            $return_url = $this->get_return_url( $form['id'], $entry['id'] );

            // Save thank you page URL in entry meta
            gform_update_meta( $entry['id'], 'return_url', $return_url );

            return $payment_url;

        } catch ( Exception $e ) {
            $this->log_debug( __METHOD__ . sprintf( '(): Error creating bill for entry #%1$d: %2$s', $entry['id'], $e->getMessage() ) );
        }

    }

    // Create an enrolment in ValuePay (for recurring payment)
    private function get_enrolment_url( $feed, $submission_data, $form, $entry ) {

        $full_name      = rgar( $submission_data, 'name' );
        $email          = rgar( $submission_data, 'email' );
        $telephone      = valuepay_gf_format_telephone( rgar( $submission_data, 'telephone' ) );
        $identity_type  = rgar( $submission_data, 'identity_type' );
        $identity_value = rgar( $submission_data, 'identity_value' );
        $bank           = rgar( $submission_data, 'bank' );

        if ( !$full_name ) {
            throw new Exception( __( 'Name is required', 'valuepay-gf' ) );
        }

        if ( !$email ) {
            throw new Exception( __( 'Email is required', 'valuepay-gf' ) );
        }

        if ( !$telephone ) {
            throw new Exception( __( 'Telephone is required', 'valuepay-gf' ) );
        }

        if ( !$identity_type || !$identity_value ) {
            throw new Exception( __( 'Identity information is required for recurring payment', 'valuepay-gf' ) );
        }

        if ( !$bank ) {
            throw new Exception( __( 'Bank is required for recurring payment', 'valuepay-gf' ) );
        }

        $params = array(
            'username'        => $this->valuepay->username,
            'sub_fullname'    => $full_name,
            'sub_ident_type'  => $identity_type,
            'sub_ident_value' => $identity_value,
            'sub_telephone'   => $telephone,
            'sub_email'       => $email,
            'sub_mandate_id'  => rgar( $feed['meta'], 'mandate_id' ),
            'sub_bank_id'     => $bank,
            'sub_amount'      => (float) rgar( $submission_data, 'payment_amount' ),
        );

        $hash_data = array(
            $this->valuepay->app_key,
            $this->valuepay->username,
            $params['sub_fullname'],
            $params['sub_ident_type'],
            $params['sub_telephone'],
            $params['sub_email'],
            $params['sub_mandate_id'],
            $params['sub_bank_id'],
            $params['sub_amount'],
        );

        $params['reqhash'] = md5( implode( '', array_values( $hash_data ) ) );

        list( $code, $response ) = $this->valuepay->set_enrol_data( $params );

        if ( isset( $response['method'] ) && isset( $response['method'] ) == 'GET' && isset( $response['action'] ) ) {

            GFAPI::update_entry( array(
                'entry_id'       => $entry['id'],
                'payment_status' => 'Authorized',
            ) );

            return $response['action'];
        }

        return false;

    }

    // Create a bill in ValuePay (for one time payment)
    private function get_bill_url( $feed, $submission_data, $form, $entry ) {

        $full_name = rgar( $submission_data, 'name' );
        $email     = rgar( $submission_data, 'email' );
        $telephone = valuepay_gf_format_telephone( rgar( $submission_data, 'telephone' ) );

        if ( !$full_name ) {
            throw new Exception( __( 'Name is required', 'valuepay-gf' ) );
        }

        if ( !$email ) {
            throw new Exception( __( 'Email is required', 'valuepay-gf' ) );
        }

        if ( !$telephone ) {
            throw new Exception( __( 'Telephone is required', 'valuepay-gf' ) );
        }

        $callback_url = add_query_arg( array(
            'callback' => $this->_slug,
            'entry_id' => $entry['id'],
        ), site_url( '/' ) );

        $params = array(
            'username'          => $this->valuepay->username,
            'orderno'           => (int) $entry['id'],
            'bill_amount'       => (float) rgar( $submission_data, 'payment_amount' ),
            'collection_id'     => rgar( $feed['meta'], 'collection_id' ),
            'buyer_data'        => array(
                'buyer_name'    => $full_name,
                'mobile_number' => $telephone,
                'email'         => $email,
            ),
            'bill_frontend_url' => $callback_url,
            'bill_backend_url'  => $callback_url,
        );

        $hash_data = array(
            $this->valuepay->app_key,
            $this->valuepay->username,
            $params['bill_amount'],
            $params['collection_id'],
            $params['orderno'],
        );

        $params['reqhash'] = md5( implode( '', array_values( $hash_data ) ) );

        list( $code, $response ) = $this->valuepay->create_bill( $params );

        if ( isset( $response['bill_id'] ) ) {
            gform_update_meta( $entry['id'], 'bill_id', $response['bill_id'] );
        }

        if ( isset( $response['bill_url'] ) ) {
            return $response['bill_url'];
        }

        return false;

    }

    // Handle IPN response
    public function callback() {

        if ( !$this->is_gravityforms_supported() ) {
            return false;
        }

        $entry_id = absint( rgget( 'entry_id' ) );

        if ( !$entry_id ) {
            return false;
        }

        $entry = GFAPI::get_entry( $entry_id );

        if ( is_wp_error( $entry ) ) {
            $this->log_error( __METHOD__ . '(): Entry #' . $entry_id . ' not found.' );
            return false;
        }

        if ( $entry['status'] == 'spam' ) {
            $this->log_debug( __METHOD__ . '(): Entry #' . $entry['id'] . 'is marked as spam.' );
            return false;
        }

        $bill_id = gform_get_meta( $entry['id'], 'bill_id' );

        if ( !$bill_id ) {
            $this->log_debug( __METHOD__ . '(): Bill for entry #' . $entry['id'] . ' not found.' );
            return false;
        }

        $feed = $this->get_payment_feed( $entry );

        // Check if payment gateway is still active for specified form
        if ( !$feed || !rgar( $feed, 'is_active' ) ) {
            $this->log_debug( __METHOD__ . '(): ValuePay no longer active for form #' . $entry['form_id'] );
            return false;
        }

        //////////////////////////////////////////////////////

        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
            wp_redirect( gform_get_meta( $entry['id'], 'return_url' ) );
            exit;
        }

        $this->init_api( $feed['meta'] );

        $response = $this->valuepay->get_ipn_response();

        try {
            $this->log_debug( __METHOD__ . '(): Verifying hash for entry #' . $entry_id );
            $this->valuepay->validate_ipn_response( $response );
        } catch ( Exception $e ) {
            valuepay_gf_logger( $e->getMessage() );
            wp_die( $e->getMessage(), 'ValuePay IPN', array( 'response' => 200 ) );
        } finally {
            $this->log_debug( __METHOD__ . '(): Verified hash for entry #' . $entry_id );
        }

        if ( $response['bill_status'] === 'paid' ) {
            return array(
                'id'               => $response['bill_id'],
                'type'             => 'complete_payment',
                'amount'           => (float) $response['bill_amount'],
                'transaction_id'   => $response['bill_id'],
                'entry_id'         => $entry_id,
                'payment_status'   => 'Paid',
                'payment_date'     => $response['date_create'],
                'payment_method'   => $this->_short_title,
            );
        } else {
            return false;
        }

    }

    // Generate thank you page URL
    private function get_return_url( $form_id, $entry_id ) {

        $page_url = GFCommon::is_ssl() ? 'https://' : 'http://';

        $server_name = sanitize_text_field( $_SERVER['SERVER_NAME'] );
        $server_port = sanitize_text_field( $_SERVER['SERVER_PORT'] );
        $request_uri = sanitize_text_field( $_SERVER['REQUEST_URI'] );

        if ( $server_port !== '80' ) {
            $page_url .= $server_name . ':' . $server_port . $request_uri;
        } else {
            $page_url .= $server_name . $request_uri;
        }

        // Hashing form and entry ID in thank you page URL
        $ids_query = "ids={$form_id}|{$entry_id}";
        $ids_query .= '&hash=' . wp_hash( $ids_query );

        $page_url = add_query_arg( 'valuepay_gf_return', base64_encode( $ids_query ), $page_url );

        return $page_url;

    }

    // Handle thank you page
    public function maybe_thankyou_page() {

        if ( !$this->is_gravityforms_supported() ) {
            return;
        }

        if ( $str = rgget( 'valuepay_gf_return' ) ) {
            $str = base64_decode( $str );

            parse_str( $str, $query );

            if ( wp_hash( 'ids=' . $query['ids'] ) == $query['hash'] ) {
                list( $form_id, $entry_id ) = explode( '|', $query['ids'] );

                $form = GFAPI::get_form( $form_id );
                $lead = GFAPI::get_entry( $entry_id );

                if ( !class_exists( 'GFFormDisplay' ) ) {
                    require_once( GFCommon::get_base_path() . '/form_display.php' );
                }

                $confirmation = GFFormDisplay::handle_confirmation( $form, $lead, false );

                if ( is_array( $confirmation ) && isset( $confirmation['redirect'] ) ) {
                    wp_redirect( $confirmation['redirect'] );
                    exit;
                }

                GFFormDisplay::$submission[ $form_id ] = array(
                    'is_confirmation'      => true,
                    'confirmation_message' => $confirmation,
                    'form'                 => $form,
                    'lead'                 => $lead,
                );
            }
        }

    }

    // Register public hooks
    public function init_frontend() {

        parent::init_frontend();

        // Disable post creation and notification on form submit.
        // We will handle this after payment received.
        add_filter( 'gform_disable_post_creation', '__return_true' );
        add_filter( 'gform_disable_notification', '__return_true' );

    }

    // Register admin hooks
    public function init_admin() {

        parent::init_admin();

        // Allow user to update payment details
        add_action( 'gform_payment_status', array( $this, 'admin_edit_payment_status' ), 3, 3);
        add_action( 'gform_payment_date', array( $this, 'admin_edit_payment_date' ), 3, 3);
        add_action( 'gform_payment_transaction_id', array( $this, 'admin_edit_payment_transaction_id' ), 3, 3);
        add_action( 'gform_payment_amount', array( $this, 'admin_edit_payment_amount' ), 3, 3);
        add_action( 'gform_after_update_entry', array( $this, 'admin_update_payment' ), 4, 2);

    }

    // Register supported notification events
    public function supported_notification_events( $form ) {

        return array(
            'complete_payment' => esc_html__( 'Payment Completed', 'valuepay-gf' ),
            'fail_payment'     => esc_html__( 'Payment Failed', 'valuepay-gf' ),
        );

    }

    // Payment status field (admin side)
    public function admin_edit_payment_status( $payment_status, $form, $entry ) {

        if ( !$this->is_allowed_edit_payment( $entry ) ) {
            return $payment_status;
        }

        $input = gform_tooltip( 'valuepay_gravityforms_edit_payment_status', true );
        $input .= '<select id="payment_status" name="payment_status">';
        $input .= '<option value="' . $payment_status . '"selected>' . $payment_status . '</option>';
        $input .= '<option value="' . esc_html__( 'Paid', 'valuepay-gf' ) . '"selected>' . esc_html__( 'Paid', 'valuepay-gf' ) . '</option>';
        $input .= '</select>';

        return $input;

    }

    // Payment date field (admin side)
    public function admin_edit_payment_date( $payment_date, $form, $entry ) {

        if ( !$this->is_allowed_edit_payment( $entry ) ) {
            return $payment_date;
        }

        $payment_date = $entry['payment_date'];
        if ( empty( $payment_date ) ) {
            $payment_date = get_the_date( 'y-m-d H:i:s' );
        }

        return '<input type="text" id="payment_date" name="payment_date" value="' . $payment_date . '">';

    }

    // Transaction ID field (admin side)
    public function admin_edit_payment_transaction_id( $transaction_id, $form, $entry ) {

        if ( !$this->is_allowed_edit_payment( $entry ) ) {
            return $transaction_id;
        }

        return '<input type="text" id="' . $this->id . '_transaction_id" name="' . $this->id . '_transaction_id" value="' . $transaction_id . '">';

    }

    // Payment amount field (admin side)
    public function admin_edit_payment_amount( $payment_amount, $form, $entry ) {

        if ( !$this->is_allowed_edit_payment( $entry ) ) {
            return $payment_amount;
        }

        if ( empty( $payment_amount ) ) {
            $payment_amount = GFCommon::get_order_total( $form, $entry );
        }

        return '<input type="text" id="payment_amount" name="payment_amount" class="gform_currency" value="' . $payment_amount . '">';

    }

    // Handle payment details update
    public function admin_update_payment( $form, $entry_id ) {

        check_admin_referer( 'gforms_save_entry', 'gforms_save_entry' );

        global $current_user;

        if ( !$current_user ) {
            return;
        }

        $current_user_data = get_userdata( $current_user->ID );

        $entry = GFFormsModel::get_lead( $entry_id );

        if ( !$this->is_allowed_edit_payment( $entry, 'update' ) ) {
            return;
        }

        $payment_status = rgpost( 'payment_status' );

        // If no payment status, get it from entry
        if ( empty( $payment_status ) ) {
            $payment_status = $entry['payment_status'];
        }

        $payment_date   = rgpost( 'payment_date' );
        $payment_amount = GFCommon::to_number( rgpost( 'payment_amount' ) );
        $transaction_id = rgpost( $this->id . '_transaction_id' );

        $status_unchanged         = $entry['payment_status'] == $payment_status;
        $date_unchanged           = $entry['payment_date'] == $payment_date;
        $amount_unchanged         = $entry['payment_amount'] == $payment_amount;
        $transaction_id_unchanged = $entry['transaction_id'] == $transaction_id;

        // If no change on all payment details, don't update it
        if ( $status_unchanged && $date_unchanged && $amount_unchanged && $transaction_id_unchanged ) {
            return;
        }

        if ( $payment_date ) {
            $payment_date = date( 'Y-m-d H:i:s', strtotime( $payment_date ) );
        } else {
            $payment_date = get_the_date( 'Y-m-d H:i:s' );
        }

        $entry['payment_status'] = $payment_status;
        $entry['payment_date']   = $payment_date;
        $entry['payment_amount'] = $payment_amount;
        $entry['payment_method'] = $this->_short_title;
        $entry['transaction_id'] = $transaction_id;

        // Check if payment is paid and not already fulfilled
        if ( $payment_status == 'Paid' && !$entry['is_fulfilled'] ) {
            $action['id']             = $transaction_id;
            $action['type']           = 'complete_payment';
            $action['amount']         = $payment_amount;
            $action['transaction_id'] = $transaction_id;
            $action['entry_id']       = $entry['id'];
            $action['payment_status'] = $payment_status;

            $this->complete_payment( $entry, $action );
        }

        GFAPI::update_entry( $entry );

        $note = sprintf(
            esc_html__( 'Payment details was manually updated. Payment Method: %s. Transaction ID: %s. Amount: %s. Status: %s. Date: %s.', 'valuepay-gf' ),
            $entry['payment_method'],
            $entry['transaction_id'],
            GFCommon::to_money( $entry['payment_amount'], $entry['currency'] ),
            $entry['payment_status'],
            $entry['payment_date']
        );
        GFFormsModel::add_note( $entry['id'], $current_user->ID, $current_user->display_name, $note );

    }

    // Check if have permission to edit the payment
    private function is_allowed_edit_payment( $entry, $action = 'edit' ) {

        // Don't allow if payment gateway for the entry is not our payment gateway
        if ( !$this->is_payment_gateway( $entry['id'] ) ) {
            return false;
        }

        // Don't allow if payment  status already paid or transaction type is subscription
        if ( rgar( $entry, 'payment_status' ) == 'Paid' || rgar( $entry, 'transaction_type' ) == 2 ) {
            return false;
        }

        // Allow if in edit page
        if ( $action == 'edit' && rgpost( 'screen_mode' ) == 'edit' ) {
            return true;
        }

        if ( $action == 'update' && rgpost( 'screen_mode' ) == 'view' && rgpost( 'action' ) == 'update' ) {
            return true;
        }

        return false;

    }

}
