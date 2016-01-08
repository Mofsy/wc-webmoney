<?php

if(!defined('ABSPATH'))
{
    exit;
}

class WC_Webmoney extends WC_Payment_Gateway
{
    /**
     * Current woocommerce version
     *
     * @var
     */
    public $wc_version;

    /**
     * Current currency
     *
     * @var string
     */
    public $currency;

    /**
     * Secret key for Merchant
     *
     * @var string
     */
    public $secret_key;

    /**
     * Unique gateway id
     *
     * @var string
     */
    public $id = 'webmoney';

    /**
     * Form url for Merchant
     *
     * @var string
     */
    public $form_url = 'https://merchant.webmoney.ru/lmi/payment.asp';

    /**
     * User language
     *
     * @var string
     */
    public $language;

    /**
     * Dollar
     *
     * @var string
     */
    public $wallet_wmz;

    /**
     * Ruble
     *
     * @var string
     */
    public $wallet_wmr;

    /**
     * Euro
     *
     * @var string
     */
    public $wallet_wme;

    /**
     * Uah
     *
     * @var string
     */
    public $wallet_wmu;

    /**
     * @var mixed
     */
    public $test = false;

    /**
     * WC_Webmoney constructor
     */
    public function __construct()
    {
        /**
         * Get currency
         */
        $this->currency = get_woocommerce_currency();

        /**
         * Set woocommerce version
         */
        $this->wc_version = woocommerce_webmoney_get_version();

        /**
         * Set unique id
         */
        $this->id = 'webmoney';

        /**
         * What?
         */
        $this->has_fields = false;

        /**
         * Load settings
         */
        $this->init_form_fields();
        $this->init_settings();

        /**
         * Title for user interface
         */
        $this->title = $this->get_option('title');

        /**
         * Testing?
         */
        $this->test = $this->get_option('test');

        /**
         * Default language for webmoney interface
         */
        $this->language = $this->get_option('language');

        /**
         * Automatic language
         */
        if($this->get_option('language_auto') === 'yes')
        {
            $lang = get_locale();
            switch($lang)
            {
                case 'en_EN':
                    $this->language = 'en';
                    break;
                case 'ru_RU':
                    $this->language = 'ru';
                    break;
                default:
                    $this->language = 'ru';
                    break;
            }
        }
        if($this->language !== 'ru')
        {
            $this->form_url = 'https://merchant.wmtransfer.com/lmi/payment.asp';
        }

        /**
         * Set description
         */
        $this->description = $this->get_option('description');

        /**
         * Set WMR
         */
        $this->wallet_wmr = $this->get_option('wallet_wmr');

        /**
         * Set WME
         */
        $this->wallet_wme = $this->get_option('wallet_wme');

        /**
         * Set WMZ
         */
        $this->wallet_wmz = $this->get_option('wallet_wmz');

        /**
         * Set WMU
         */
        $this->wallet_wmu = $this->get_option('wallet_wmu');

        /**
         * Set secret key
         */
        $this->secret_key = $this->get_option('secret_key');

        /**
         * Set icon
         */
        if($this->get_option('enable_icon') === 'yes')
        {
            $this->icon = apply_filters('woocommerce_webmoney_icon', WC_WEBMONEY_URL . '/assets/img/webmoney.jpg');
        }

        /**
         * Save admin options
         */
        if(current_user_can( 'manage_options' ))
        {
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        /**
         * Gate allow?
         */
        if ($this->is_valid_for_use())
        {
            /**
             * Receipt page
             */
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

            /**
             * Payment listener/API hook
             */
            add_action('woocommerce_api_wc_' . $this->id, array($this, 'check_ipn'));
        }
        else
        {
            $this->enabled = false;
        }
    }

    /**
     * Check if this gateway is enabled and available in the user's country
     */
    public function is_valid_for_use()
    {
        $return = true;

        /**
         * Check allow currency
         */
        if (!in_array($this->currency, array('RUB', 'EUR', 'USD', 'UAH'), false ))
        {
            $return = false;
        }

        /**
         * Check test mode and admin rights
         */
        if ($this->test !== '' && !current_user_can( 'manage_options' ))
        {
            $return = false;
        }

        return $return;
    }

    /**
     * Admin Panel Options
     **/
    public function admin_options()
    {
        ?>
        <h1><?php _e('Webmoney', 'wc-webmoney'); ?></h1><?php $this->get_icon(); ?>
        <p><?php _e('Setting receiving payments through Webmoney Merchant.', 'wc-webmoney'); ?></p>

        <?php if ( $this->is_valid_for_use() ) : ?>

            <table class="form-table">
                <?php $this->generate_settings_html(); ?>
            </table>

        <?php else : ?>
            <div class="inline error"><p><strong><?php _e('Gateway offline', 'wc-webmoney'); ?></strong>: <?php _e('Webmoney does not support the currency your store.', 'wc-webmoney' ); ?></p></div>
            <?php
        endif;
    }

    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    public function init_form_fields()
    {
        $this->form_fields = array
        (
            'enabled' => array
            (
                'title' => __('Online/Offline gateway', 'wc-webmoney'),
                'type' => 'checkbox',
                'label' => __('Online', 'wc-webmoney'),
                'default' => 'yes'
            ),
            'interface' => array(
                'title'       => __( 'Interface', 'wc-webmoney' ),
                'type'        => 'title',
                'description' => '',
            ),
            'enable_icon' => array
            (
                'title' => __('Show gateway icon?', 'wc-webmoney'),
                'type' => 'checkbox',
                'label' => __('Show', 'wc-webmoney'),
                'default' => 'yes'
            ),
            'language' => array
            (
                'title' => __( 'Language interface', 'wc-webmoney' ),
                'type' => 'select',
                'options' => array
                (
                    'ru' => 'Russian',
                    'en' => 'English'
                ),
                'description' => __( 'What language interface displayed for the customer on Webmoney Transfer?', 'wc-webmoney' ),
                'default' => 'ru'
            ),
            'language_auto' => array
            (
                'title' => __( 'Language based on the locale?', 'wc-webmoney' ),
                'type' => 'select',
                'options' => array
                (
                    'yes' => 'Yes',
                    'no' => 'No'
                ),
                'description' => __( 'Trying to get the language based on the locale?', 'wc-webmoney' ),
                'default' => 'ru'
            ),
            'title' => array
            (
                'title' => __('Title', 'wc-webmoney'),
                'type' => 'text',
                'description' => __( 'This is the name that the user sees during the payment.', 'wc-webmoney' ),
                'default' => __('Webmoney', 'wc-webmoney')
            ),
            'description' => array
            (
                'title' => __( 'Description', 'wc-webmoney' ),
                'type' => 'textarea',
                'description' => __( 'Description of the method of payment that the customer will see on our website.', 'wc-webmoney' ),
                'default' => __( 'Payment by Webmoney.', 'wc-webmoney' )
            ),
            'wallets' => array(
                'title'       => __( 'Wallets', 'wc-webmoney' ),
                'type'        => 'title',
                'description' => '',
            ),
            'wallet_wmz' => array
            (
                'title' => __('Wallet WMZ', 'wc-webmoney'),
                'type' => 'text',
                'description' => __( 'Dollars webmoney purse to which the buyer has to make a payment for billing in dollars.', 'wc-webmoney' ),
                'default' => __('Z', 'wc-webmoney')
            ),
            'wallet_wme' => array
            (
                'title' => __('Wallet WME', 'wc-webmoney'),
                'type' => 'text',
                'description' => __( 'Euros webmoney purse to which the buyer has to make a payment for billing in euros.', 'wc-webmoney' ),
                'default' => __('E', 'wc-webmoney')
            ),
            'wallet_wmr' => array
            (
                'title' => __('Wallet WMR', 'wc-webmoney'),
                'type' => 'text',
                'description' => __( 'Russian rubles webmoney purse to which the buyer has to make a payment for billing in rubles.', 'wc-webmoney' ),
                'default' => __('R', 'wc-webmoney')
            ),
            'wallet_wmu' => array
            (
                'title' => __('Wallet WMU', 'wc-webmoney'),
                'type' => 'text',
                'description' => __( 'UAH webmoney purse to which the buyer has to make a payment for billing in UAH.', 'wc-webmoney' ),
                'default' => __('U', 'wc-webmoney')
            ),
            'secret_key' => array
            (
                'title' => __('Secret key', 'wc-webmoney'),
                'type' => 'text',
                'description' => __('Please write Secret key', 'wc-webmoney'),
                'default' => ''
            ),
            'technical' => array(
                'title'       => __( 'Technical details', 'wc-webmoney' ),
                'type'        => 'title',
                'description' => '',
            ),
            'sign_method' => array
            (
                'title' => __( 'Signature method', 'wc-webmoney' ),
                'type' => 'select',
                'options' => array
                (
                    'sha256' => 'sha256'
                ),
                'default' => 'sha256'
            ),
            'test' => array
            (
                'title' => __( 'Test mode', 'wc-webmoney' ),
                'type'        => 'select',
                'description'	=>  __( 'The field is used only in the test mode.', 'wc-webmoney' ),
                'default'	=> '2',
                'options'     => array
                (
                    '' => __( 'Off', 'wc-webmoney' ),
                    '0' => __( 'All test payments will be successful', 'wc-webmoney' ),
                    '1' => __( 'All test payments will fail', 'wc-webmoney' ),
                    '2' => __( '80%% of test payments will be successful, 20%% of test payments will fail', 'wc-webmoney' ),
                )
            )
        );
    }

    /**
     * There are no payment fields for sprypay, but we want to show the description if set.
     **/
    public function payment_fields()
    {
        if ($this->description)
        {
            echo wpautop(wptexturize($this->description));
        }
    }

    /**
     * @param $statuses
     * @param $order
     * @return mixed
     */
    public static function valid_order_statuses_for_payment($statuses, $order)
    {
        if($order->payment_method !== 'webmoney')
        {
            return $statuses;
        }

        $option_value = get_option( 'woocommerce_payment_status_action_pay_button_controller', array() );

        if(!is_array($option_value))
        {
            $option_value = array('pending', 'failed');
        }

        if( is_array($option_value) && !in_array('pending', $option_value, false) )
        {
            $pending = array('pending');
            $option_value = array_merge($option_value, $pending);
        }

        return $option_value;
    }

    /**
     * Generate the dibs button link
     *
     * @param $order_id
     *
     * @return string
     **/
    public function generate_form($order_id)
    {
        /**
         * Create order object
         */
        $order = wc_get_order($order_id);

        /**
         * Form parameters
         */
        $args = array();

        /**
         * Sum
         */
        $out_sum = number_format($order->order_total, 2, '.', '');
        $args['LMI_PAYMENT_AMOUNT'] = $out_sum;

        /**
         * Product description
         */
        $description = '';
        $items = $order->get_items();
        foreach ( $items as $item )
        {
            $description .= $item['name'];
        }
        if(count($description) > 200)
        {
            $description = __('Product number: ' . $order_id, 'wc-webmoney');
        }
        $args['LMI_PAYMENT_DESC'] = $description;
        $args['LMI_PAYMENT_DESC_BASE64'] = base64_encode($description);

        /**
         * Order id
         */
        $args['LMI_PAYMENT_NO'] = $order_id;

        /**
         * Purse merchant
         */
        $this->currency = $order->order_currency;
        $args['LMI_PAYEE_PURSE'] = $this->wallet_wmr;

        /**
         * Test mode
         */
        if ($this->test !== '')
        {
            $args['LMI_SIM_MODE'] = $this->test;
        }

        /**
         * Billing email
         */
        if(!empty($order->billing_email))
        {
            $args['LMI_PAYMER_EMAIL'] = $order->billing_email;
        }

        /**
         * Rewrite url:
         *
         * result LMI_RESULT_URL
         * success LMI_SUCCESS_URL
         * fail LMI_FAIL_URL
         *
         * Rewrite method:
         *
         * success LMI_SUCCESS_METHOD
         * fail LMI_FAIL_METHOD
         */

        /**
         * what?
         */
        $args = apply_filters('woocommerce_webmoney_args', $args);

        /**
         * Form inputs generic
         */
        $args_array = array();
        foreach ($args as $key => $value)
        {
            $args_array[] = '<input type="hidden" name="'.esc_attr($key).'" value="'.esc_attr($value).'" />';
        }

        /**
         * Return full form
         */
        return '<form action="'.esc_url($this->form_url).'" method="POST" id="webmoney_payment_form" accept-charset="windows-1251">'."\n".
            implode("\n", $args_array).
        '<input type="submit" class="button alt" id="submit_webmoney_payment_form" value="'.__('Pay', 'wc-webmoney').
        '" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel & return to cart', 'wc-webmoney').'</a>'."\n".
        '</form>';
    }

    /**
     * Process the payment and return the result
     **/
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        if ( !version_compare( $this->wc_version, '2.1.0', '<' ) )
        {
            return array
            (
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url( true )
            );
        }

        return array
        (
            'result' => 'success',
            'redirect'	=> add_query_arg('order-pay', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
        );
    }

    /**
     * receipt_page
     **/
    public function receipt_page($order)
    {
        echo '<p>'.__('Thank you for your order, please press the button below to pay.', 'wc-webmoney').'</p>';
        echo $this->generate_form($order);
    }

    /**
     * Check instant payment notification
     **/
    public function check_ipn()
    {
        /**
         * Hook wc_webmoney
         */
        if ($_GET['wc-api'] === 'wc_webmoney')
        {
            /**
             * Order id
             */
            $LMI_PAYMENT_NO = 0;
            if(array_key_exists('LMI_PAYMENT_NO', $_POST))
            {
                $LMI_PAYMENT_NO = $_POST['LMI_PAYMENT_NO'];
            }

            /**
             * Sum
             */
            $LMI_PAYMENT_AMOUNT = 0;
            if(array_key_exists('LMI_PAYMENT_AMOUNT', $_POST))
            {
                $LMI_PAYMENT_AMOUNT = $_POST['LMI_PAYMENT_AMOUNT'];
            }

            /**
             * Pre request flag
             */
            $LMI_PREREQUEST = 0;
            if(array_key_exists('LMI_PREREQUEST', $_POST))
            {
                $LMI_PREREQUEST = (int) $_POST['LMI_PREREQUEST'];
            }

            /**
             * Purse merchant
             */
            $LMI_PAYEE_PURSE = '';
            if(array_key_exists('LMI_PAYEE_PURSE', $_POST))
            {
                $LMI_PAYEE_PURSE = $_POST['LMI_PAYEE_PURSE'];
            }

            /**
             * Testing?
             */
            $LMI_MODE = 0;
            if(array_key_exists('LMI_MODE', $_POST))
            {
                $LMI_MODE = (int) $_POST['LMI_MODE'];
            }

            /**
             * Secret key
             */
            $LMI_SECRET_KEY = '';
            if(array_key_exists('LMI_SECRET_KEY', $_POST))
            {
                $LMI_SECRET_KEY = $_POST['LMI_SECRET_KEY'];
            }

            /**
             * Order id from WebMoney Transfer
             */
            $LMI_SYS_INVS_NO = 0;
            if(array_key_exists('LMI_SYS_INVS_NO', $_POST))
            {
                $LMI_SYS_INVS_NO = $_POST['LMI_SYS_INVS_NO'];
            }

            /**
             * Payment id from WebMoney Transfer
             */
            $LMI_SYS_TRANS_NO = 0;
            if(array_key_exists('LMI_SYS_TRANS_NO', $_POST))
            {
                $LMI_SYS_TRANS_NO = $_POST['LMI_SYS_TRANS_NO'];
            }

            /**
             * Payment date and time from WebMoney Transfer
             */
            $LMI_SYS_TRANS_DATE = '';
            if(array_key_exists('LMI_SYS_TRANS_DATE', $_POST))
            {
                $LMI_SYS_TRANS_DATE = $_POST['LMI_SYS_TRANS_DATE'];
            }

            /**
             * Purse client
             */
            $LMI_PAYER_PURSE = '';
            if(array_key_exists('LMI_PAYER_PURSE', $_POST))
            {
                $LMI_PAYER_PURSE = $_POST['LMI_PAYER_PURSE'];
            }

            /**
             * WMID client
             */
            $LMI_PAYER_WM = '';
            if(array_key_exists('LMI_PAYER_WM', $_POST))
            {
                $LMI_PAYER_WM = $_POST['LMI_PAYER_WM'];
            }

            /**
             * WMID Capitaller
             */
            $LMI_CAPITALLER_WMID = '';
            if(array_key_exists('LMI_CAPITALLER_WMID', $_POST))
            {
                $LMI_CAPITALLER_WMID =  $_POST['LMI_CAPITALLER_WMID'];
            }

            /**
             * Email client
             */
            $LMI_PAYMER_EMAIL = '';
            if(array_key_exists('LMI_PAYMER_EMAIL', $_POST))
            {
                $LMI_PAYMER_EMAIL =  $_POST['LMI_PAYMER_EMAIL'];
            }

            /**
             * IP client
             */
            $LMI_PAYER_IP = '';
            if(array_key_exists('LMI_PAYER_IP', $_POST))
            {
                $LMI_PAYER_IP =  $_POST['LMI_PAYER_IP'];
            }

            /**
             * Hash
             */
            $LMI_HASH = '';
            if(array_key_exists('LMI_HASH', $_POST))
            {
                $LMI_HASH = $_POST['LMI_HASH'];
            }

            /**
             * Local hash
             */
            $local_hash = strtoupper(hash('sha256', $LMI_PAYEE_PURSE.$LMI_PAYMENT_AMOUNT.$LMI_PAYMENT_NO.$LMI_MODE.$LMI_SYS_INVS_NO.$LMI_SYS_TRANS_NO.$LMI_SYS_TRANS_DATE.$this->secret_key.$LMI_PAYER_PURSE.$LMI_PAYER_WM));

            /**
             * Result
             */
            if ($_GET['action'] === 'result')
            {
                /**
                 * Check pre request
                 */
                if($LMI_PREREQUEST === 1)
                {
                    die('YES');
                }
                else
                {
                    /**
                     * Check hash or secret key
                     */
                    if((count($LMI_HASH) > 0 && $local_hash === $LMI_HASH) || ($this->secret_key === $LMI_SECRET_KEY))
                    {
                        /**
                         * Get order object
                         */
                        $order = wc_get_order($LMI_PAYMENT_NO);

                        /**
                         * Check purse
                         */
                        //todo: implements

                        /*
                         * Check sum
                         */
                        //todo: implements

                        /**
                         * Check mode
                         */
                        $test = false;
                        if($LMI_MODE === 1)
                        {
                            $test = true;
                        }

                        /**
                         * Testing
                         */
                        if($test)
                        {
                            /**
                             * Add order note
                             */
                            $order->add_order_note(__('Order successfully paid (TEST MODE).', 'wc-webmoney'));

                            /**
                             * Set status is payment
                             */
                            $order->update_status('processing');
                        }
                        /**
                         * Real payment
                         */
                        else
                        {
                            /**
                             * Add order note
                             */
                            $order->add_order_note(__('Order successfully paid.', 'wc-webmoney'));

                            /**
                             * Set status is payment
                             */
                            $order->update_status('processing');
                        }
                    }

                    wp_die(__('Payment error, please pay other time.', 'wc-webmoney'));
                }
            }
            /**
             * Success
             */
            else if ($_GET['action'] === 'success')
            {
                $order = wc_get_order($LMI_PAYMENT_NO);

                WC()->cart->empty_cart();

                /**
                 * Redirect to success
                 */
                wp_redirect( $this->get_return_url( $order ) );
            }
            /**
             * Fail
             */
            else if ($_GET['action'] === 'fail')
            {
                $order = wc_get_order($LMI_PAYMENT_NO);

                /**
                 * Add order note
                 */
                $order->add_order_note(__('The order has not been paid.', 'wc-webmoney'));

                /**
                 * Sen status is failed
                 */
                $order->update_status('failed');

                /**
                 * Redirect to cancel
                 */
                wp_redirect( str_replace('&amp;', '&', $order->get_cancel_order_url() ) );
            }
        }
        else
        {
            die('IPN Request Failure');
        }

        die();
    }

    /**
     * Can the order be refunded via Webmoney?
     *
     * @param  WC_Order $order
     * @return bool
     */
    public function can_refund_order( $order )
    {
        return $order && $order->get_transaction_id();
    }

    /**
     * Process a refund if supported
     *
     * @param  int $order_id
     * @param  float $amount
     * @param  string $reason
     *
     * @return  boolean true or false
     */
    public function process_refund( $order_id, $amount = null, $reason = '' )
    {
        $order = wc_get_order( $order_id );

        if ( ! $this->can_refund_order( $order ) )
        {
            return false;
        }

        //todo: implements

        //$order->add_order_note( sprintf( __( 'Refunded %s - Refund ID: %s', 'woocommerce' ), $result['GROSSREFUNDAMT'], $result['REFUNDTRANSACTIONID'] ) );

        return true;
    }
}