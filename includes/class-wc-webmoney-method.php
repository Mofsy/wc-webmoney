<?php
/**
 * Main method class
 *
 * @package Mofsy/WC_Webmoney
 */
defined('ABSPATH') || exit;

class Wc_Webmoney_Method extends WC_Payment_Gateway
{
	/**
	 * All support currency
	 *
	 * @var array
	 */
	public $currency_all = array
	(
		'RUB', 'USD', 'EUR', 'UAH'
	);

	/**
	 * Unique gateway id
	 *
	 * @var string
	 */
	public $id = 'webmoney';

	/**
	 * Current purse
	 *
	 * @var string (12)
	 */
	public $current_purse;

	/**
	 * Secret key for current purse
	 *
	 * @var string
	 */
	public $secret_key;

	/**
	 * Active wallets
	 *
	 * @var array
	 */
	public $purse_all = array();

	/**
	 * Form url for Merchant
	 *
	 * @var string
	 */
	public $form_url = 'https://merchant.webmoney.ru/lmi/payment_utf.asp';

	/**
	 * User language
	 *
	 * @var string
	 */
	public $user_interface_language = 'ru';

	/**
	 * Test mode
	 *
	 * @var mixed
	 */
	public $test = '';

	/**
	 * WC_Webmoney constructor
	 */
	public function __construct()
	{
		/**
		 * The gateway shows fields on the checkout OFF
		 */
		$this->has_fields = false;

		/**
		 * Admin title
		 */
		$this->method_title = __( 'Webmoney', 'wc-webmoney' );

		/**
		 * Admin method description
		 */
		$this->method_description = __( 'Pay via Webmoney.', 'wc-webmoney' );

		/**
		 * Initialize filters
		 */
		$this->init_filters();

		/**
		 * Load settings
		 */
		$this->init_form_fields();
		$this->init_settings();
		$this->init_options();
		$this->init_actions();

		/**
		 * Save admin options
		 */
		if(current_user_can( 'manage_options' ))
		{
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this,
				'process_admin_options'
			));
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this,
				'last_settings_update_version'
			));
		}

		/**
		 * Receipt page
		 */
		add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

		/**
		 * Payment listener/API hook
		 */
		add_action('woocommerce_api_wc_' . $this->id, array($this, 'input_payment_notifications' ));
	}

	/**
	 * Initialize filters
	 */
	public function init_filters()
	{
		/**
		 * Add setting fields
		 */
		add_filter('wc_webmoney_init_form_fields', array($this, 'init_form_fields_main'), 10);
		add_filter('wc_webmoney_init_form_fields', array($this, 'init_form_fields_interface'), 20);
		add_filter('wc_webmoney_init_form_fields', array($this, 'init_form_fields_purse_wmr' ), 30);
		add_filter('wc_webmoney_init_form_fields', array($this, 'init_form_fields_purse_wmp' ), 40);
		add_filter('wc_webmoney_init_form_fields', array($this, 'init_form_fields_purse_wme' ), 50);
		add_filter('wc_webmoney_init_form_fields', array($this, 'init_form_fields_purse_wmz' ), 60);
		add_filter('wc_webmoney_init_form_fields', array($this, 'init_form_fields_purse_wmu' ), 70);
	}

	/**
	 * Init actions
	 */
	public function init_actions()
	{
		/**
		 * Payment fields description show
		 */
		add_action('wc_webmoney_payment_fields_show', array($this, 'payment_fields_description_show'));

		/**
		 * Payment fields test mode show
		 */
		add_action('wc_webmoney_payment_fields_after_show', array($this, 'payment_fields_test_mode_show'));

		/**
		 * Receipt form show
		 */
		add_action('wc_webmoney_receipt_page_show', array($this, 'receipt_page_show_form' ), 10);
	}

	/**
	 * Last settings update version
	 */
	public function last_settings_update_version()
	{
		update_option('wc_webmoney_last_settings_update_version', '2.0');
	}

	/**
	 * Init gateway options
	 *
	 * @filter woocommerce_webmoney_icon
	 */
	public function init_options()
	{
		/**
		 * Gateway not enabled?
		 */
		if($this->get_option('enabled') !== 'yes')
		{
			$this->enabled = false;
		}

		/**
		 * Title for user interface
		 */
		$this->title = $this->get_option('title');

		/**
		 * Set description
		 */
		$this->description = $this->get_option('description');

		/**
		 * Test mode
		 */
		$this->test = $this->get_option('test');

		/**
		 * Default language for webmoney interface
		 */
		$this->user_interface_language = $this->get_option('language');

		/**
		 * Automatic language
		 */
		if($this->get_option('language_auto') === 'yes')
		{
			$lang = '';
			if(function_exists('get_locale'))
			{
				$lang = get_locale();
			}

			switch($lang)
			{
				case 'en_EN':
					$this->user_interface_language = 'en';
					break;
				default:
					$this->user_interface_language = 'ru';
					break;
			}
		}

		/**
		 * Rewrite form url
		 */
		if($this->user_interface_language !== 'ru')
		{
			$this->form_url = 'https://merchant.wmtransfer.com/lmi/payment_utf.asp';
		}

		/**
		 * Set order button text
		 */
		$this->order_button_text = $this->get_option('order_button_text');

		/**
		 * Set icon
		 */
		if($this->get_option('enable_icon') === 'yes')
		{
			$this->icon = apply_filters('woocommerce_webmoney_icon', WC_WEBMONEY_URL . 'assets/img/webmoney.jpg');
		}

		/**
		 * Set WMR
		 */
		if($this->get_option('purse_wmr_secret') !== '')
		{
			$this->add_purse('RUB', $this->get_option('purse_wmr'), $this->get_option('purse_wmr_secret'));
		}

		/**
		 * Set WMP
		 */
		if($this->get_option('purse_wmp_secret') !== '')
		{
			$this->add_purse('RUB', $this->get_option('purse_wmp'), $this->get_option('purse_wmp_secret'));
		}

		/**
		 * Set WME
		 */
		if($this->get_option('purse_wme_secret') !== '')
		{
			$this->add_purse('EUR', $this->get_option('purse_wme'), $this->get_option('purse_wme_secret'));
		}

		/**
		 * Set WMZ
		 */
		if($this->get_option('purse_wmz_secret') !== '')
		{
			$this->add_purse('USD', $this->get_option('purse_wmz'), $this->get_option('purse_wmz_secret'));
		}

		/**
		 * Set WMU
		 */
		if($this->get_option('purse_wmu_secret') !== '')
		{
			$this->add_purse('UAH', $this->get_option('purse_wmu'), $this->get_option('purse_wmu_secret'));
		}

		/**
		 * Select purse
		 */
		$data_wallet = $this->get_purse_from_currency(WC_Webmoney::instance()->get_wc_currency());
		$this->current_purse = $data_wallet['purse'];

		/**
		 * Gateway allowed?
		 */
		if ($this->is_valid_for_use() == false)
		{
			$this->enabled = false;
		}
	}

	/**
	 * Validate purse
	 *
	 * @param $purse
	 *
	 * @return bool
	 */
	public function validate_purse($purse)
	{
		if(!preg_match('#^[ZREUP][0-9]{12}$#i', trim($purse)))
		{
			return false;
		}

		return true;
	}

	/**
	 * Get purse from currency
	 *
	 * @param $currency
	 * @return mixed
	 */
	public function get_purse_from_currency($currency)
	{
		if(array_key_exists($currency, $this->purse_all))
		{
			return $this->purse_all[$currency];
		}

		return false;
	}

	/**
	 * Add purse to active wallets
	 *
	 * @param $currency
	 * @param $purse
	 * @param $secret_key
	 *
	 * @return bool
	 */
	public function add_purse($currency, $purse, $secret_key)
	{
		/**
		 * Validate purse
		 */
		if($this->validate_purse($purse) === false)
		{
			return false;
		}
		else
		{
			/**
			 * Add purse to active purses
			 */
			$this->purse_all[$currency] = array
			(
				'purse' => $purse,

				'secret_key' => $secret_key
			);

			return true;
		}
	}

	/**
	 * Initialise gateway settings form fields
	 *
	 * @access public
	 * @filter wc_webmoney_init_form_fields
	 *
	 * @return void
	 */
	public function init_form_fields()
	{
		$this->form_fields = apply_filters('wc_webmoney_init_form_fields', array());
	}

	/**
	 * Add main settings
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_form_fields_main($fields)
	{
		$fields['main'] = array
		(
			'title' => __( 'Main settings', 'wc-webmoney' ),
			'type' => 'title',
			'description' => __( 'Work is impossible without these settings. Copy Result, Fail, Success urls for purses settings in Webmoney site.', 'wc-webmoney' ),
		);

		$fields['enabled'] = array
		(
			'title' => __('Online / Offline gateway', 'wc-webmoney'),
			'type' => 'checkbox',
			'label' => __('Enable display of the payment gateway on the website', 'wc-webmoney'),
			'description' => '',
			'default' => 'off'
		);

		$fields['sign_method'] = array
		(
			'title' => __( 'Signature method', 'wc-webmoney' ),
			'type' => 'select',
			'options' => array
			(
				'sha256' => 'sha256'
			),
			'default' => 'sha256',
			'description' => __( 'A method of forming the control signature. Must match the methods specified in the settings of all configured wallets.', 'wc-webmoney' ),
		);

		$result_url_description = '<p class="input-text regular-input webmoney_urls">' . WC_webmoney::instance()->get_result_url() . '</p>' . __( 'Address to notify the site of the results of operations in the background. Copy the address and enter it in site Webmoney in the purse technical settings. Notification method: POST or GET.', 'wc-webmoney' );

		$fields['result_url'] = array
		(
			'title' => __('Result URL', 'wc-webmoney'),
			'type' => 'text',
			'disabled' => true,
			'description' => $result_url_description,
			'default' => ''
		);

		$success_url_description = '<p class="input-text regular-input webmoney_urls">' . WC_Webmoney::instance()->get_success_url() . '</p>' . __( 'The address for the user to go to the site after successful payment. Copy the address and enter it in site Webmoney in the purse technical settings. Notification method: POST or GET. You can specify other addresses of your choice.', 'wc-webmoney' );

		$fields['success_url'] = array
		(
			'title' => __('Success URL', 'wc-webmoney'),
			'type' => 'text',
			'disabled' => true,
			'description' => $success_url_description,
			'default' => ''
		);

		$fail_url_description = '<p class="input-text regular-input webmoney_urls">' . WC_Webmoney::instance()->get_fail_url() . '</p>' . __( 'The address for the user to go to the site, after payment with an error. Copy the address and enter it in site Webmoney in the purse technical settings. Notification method: POST or GET. You can specify other addresses of your choice.', 'wc-webmoney' );

		$fields['fail_url'] = array
		(
			'title' => __('Fail URL', 'wc-webmoney'),
			'type' => 'text',
			'disabled' => true,
			'description' => $fail_url_description,
			'default' => ''
		);

		$fields['test'] = array
		(
			'title' => __( 'Test mode', 'wc-webmoney' ),
			'type' => 'select',
			'description' =>  __( 'The field is used only in the test mode.', 'wc-webmoney' ),
			'default' => '2',
			'options' => array
			(
				'' => __( 'Off', 'wc-webmoney' ),
				'0' => __( 'Will be all successful', 'wc-webmoney' ),
				'1' => __( 'Will be all fail', 'wc-webmoney' ),
				'2' => __( '80% of will be successful, 20% of will be fail', 'wc-webmoney' ),
			)
		);

		$fields['logger'] = array
		(
			'title' => __( 'Enable logging?', 'wc-webmoney' ),
			'type' => 'select',
			'description' => __( 'You can enable gateway logging, specify the level of error that you want to benefit from logging. You can send reports to developer manually by pressing the button. All sensitive data in the report are deleted. By default, the error rate should not be less than ERROR.', 'wc-webmoney' ),
			'default' => '400',
			'options' => array
			(
				'' => __( 'Off', 'wc-webmoney' ),
				'100' => 'DEBUG',
				'200' => 'INFO',
				'250' => 'NOTICE',
				'300' => 'WARNING',
				'400' => 'ERROR',
				'500' => 'CRITICAL',
				'550' => 'ALERT',
				'600' => 'EMERGENCY'
			)
		);

		return $fields;
	}

	/**
	 * Add settings for interface
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_form_fields_interface($fields)
	{
		$fields['interface'] = array
		(
			'title' => __( 'Interface', 'wc-webmoney' ),
			'type' => 'title',
			'description' => __( 'Customize the appearance. Can leave it at that.', 'wc-webmoney' ),
		);

		$fields['enable_icon'] = array
		(
			'title' => __('Show gateway icon?', 'wc-webmoney'),
			'type' => 'checkbox',
			'label' => __('Show', 'wc-webmoney'),
			'default' => 'yes'
		);

		$fields['language'] = array
		(
			'title' => __( 'Language interface', 'wc-webmoney' ),
			'type' => 'select',
			'options' => array
			(
				'ru' => __('Russian', 'wc-webmoney'),
				'en' => __('English', 'wc-webmoney')
			),
			'description' => __( 'What language interface displayed for the customer on Webmoney Transfer?', 'wc-webmoney' ),
			'default' => 'ru'
		);

		$fields['language_auto'] = array
		(
			'title' => __( 'Language based on the locale?', 'wc-webmoney' ),
			'type' => 'select',
			'options' => array
			(
				'yes' => __('Yes', 'wc-webmoney'),
				'no' => __('No', 'wc-webmoney')
			),
			'description' => __( 'Trying to get the language based on the user locale?', 'wc-webmoney' ),
			'default' => 'ru'
		);

		$fields['title'] = array
		(
			'title' => __('Title', 'wc-webmoney'),
			'type' => 'text',
			'description' => __( 'This is the name that the user sees during the payment.', 'wc-webmoney' ),
			'default' => __('webmoney', 'wc-webmoney')
		);

		$fields['order_button_text'] = array
		(
			'title' => __('Order button text', 'wc-webmoney'),
			'type' => 'text',
			'description' => __( 'This is the button text that the user sees during the payment.', 'wc-webmoney' ),
			'default' => __('Goto pay', 'wc-webmoney')
		);

		$fields['description'] = array
		(
			'title' => __( 'Description', 'wc-webmoney' ),
			'type' => 'textarea',
			'description' => __( 'Description of the method of payment that the customer will see on our website.', 'wc-webmoney' ),
			'default' => __( 'Payment via Webmoney.', 'wc-webmoney' )
		);

		return $fields;
	}

	/**
	 * Add settings for purse WMR
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_form_fields_purse_wmr($fields)
	{
		$fields['title_wmr'] = array
		(
			'title' => __( 'Purse type: WMR', 'wc-webmoney' ),
			'type' => 'title',
			'description' => __( 'This type of wallet receives money from orders in currency: RUB', 'wc-webmoney' ),
		);

		$fields['purse_wmr'] = array
		(
			'title' => __('Purse WMR', 'wc-webmoney'),
			'type' => 'text',
			'description' => __( 'Russian rubles webmoney purse to which the buyer has to make a payment for billing in rubles.', 'wc-webmoney' ),
			'default' => __('R', 'wc-webmoney')
		);

		$fields['purse_wmr_secret'] = array
		(
			'title' => __('Secret key for WMR', 'wc-webmoney'),
			'type' => 'text',
			'description' => __('Please write Secret key for WMR purse.', 'wc-webmoney'),
			'default' => ''
		);

		return $fields;
	}

	/**
	 * Add settings for purse WMP
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_form_fields_purse_wmp($fields)
	{
		$fields['title_wmp'] = array
		(
			'title' => __( 'Purse type: WMP', 'wc-webmoney' ),
			'type' => 'title',
			'description' => __( 'This type of wallet receives money from orders in currency: RUB', 'wc-webmoney' ),
		);

		$fields['purse_wmp'] = array
		(
			'title' => __('Purse WMP', 'wc-webmoney'),
			'type' => 'text',
			'description' => __( 'Russian rubles webmoney purse to which the buyer has to make a payment for billing in rubles.', 'wc-webmoney' ),
			'default' => __('P', 'wc-webmoney')
		);

		$fields['purse_wmp_secret'] = array
		(
			'title' => __('Secret key for WMP', 'wc-webmoney'),
			'type' => 'text',
			'description' => __('Please write Secret key for WMP purse.', 'wc-webmoney'),
			'default' => ''
		);

		return $fields;
	}

	/**
	 * Add settings for purse WME
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_form_fields_purse_wme($fields)
	{
		$fields['title_wme'] = array
		(
			'title' => __( 'Purse type: WME', 'wc-webmoney' ),
			'type' => 'title',
			'description' => __( 'This type of wallet receives money from orders in currency: EUR', 'wc-webmoney' ),
		);

		$fields['purse_wme'] = array
		(
			'title' => __('Purse WME', 'wc-webmoney'),
			'type' => 'text',
			'description' => __( 'Euros webmoney purse to which the buyer has to make a payment for billing in euros.', 'wc-webmoney' ),
			'default' => __('E', 'wc-webmoney')
		);

		$fields['purse_wme_secret'] = array
		(
			'title' => __('Secret key for WME', 'wc-webmoney'),
			'type' => 'text',
			'description' => __('Please write Secret key for WME purse.', 'wc-webmoney'),
			'default' => ''
		);

		return $fields;
	}

	/**
	 * Add settings for purse WMZ
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_form_fields_purse_wmz($fields)
	{
		$fields['title_wmz'] = array
		(
			'title' => __( 'Purse type: WMZ', 'wc-webmoney' ),
			'type' => 'title',
			'description' => __( 'This type of wallet receives money from orders in currency: USD', 'wc-webmoney' ),
		);

		$fields['purse_wmz'] = array
		(
			'title' => __('Purse WMZ', 'wc-webmoney'),
			'type' => 'text',
			'description' => __( 'Dollars webmoney purse to which the buyer has to make a payment for billing in dollars.', 'wc-webmoney' ),
			'default' => __('Z', 'wc-webmoney')
		);

		$fields['purse_wmz_secret'] = array
		(
			'title' => __('Secret key for WMZ', 'wc-webmoney'),
			'type' => 'text',
			'description' => __('Please write Secret key for WMZ purse.', 'wc-webmoney'),
			'default' => ''
		);

		return $fields;
	}

	/**
	 * Add settings for purse WMU
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_form_fields_purse_wmu($fields)
	{
		$fields['title_wmu'] = array
		(
			'title' => __( 'Purse type: WMU', 'wc-webmoney' ),
			'type' => 'title',
			'description' => __( 'This type of wallet receives money from orders in currency: UAH', 'wc-webmoney' ),
		);

		$fields['purse_wmu'] = array
		(
			'title' => __('Purse WMU', 'wc-webmoney'),
			'type' => 'text',
			'description' => __( 'UAH webmoney purse to which the buyer has to make a payment for billing in UAH.', 'wc-webmoney' ),
			'default' => __('U', 'wc-webmoney')
		);

		$fields['purse_wmu_secret'] = array
		(
			'title' => __('Secret key for WMU', 'wc-webmoney'),
			'type' => 'text',
			'description' => __('Please write Secret key for WMU purse.', 'wc-webmoney'),
			'default' => ''
		);

		return $fields;
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
		if (!in_array(WC_Webmoney::instance()->get_wc_currency(), $this->currency_all, false))
		{
			$return = false;

			/**
			 * Logger notice
			 */
			WC_Webmoney::instance()->get_logger()->addInfo('Currency not support: ' . WC_Webmoney::instance()->get_wc_currency());
		}

		/**
		 * Check test mode and admin rights
		 *
		 * @todo сделать возможность тестирования не только админами
		 */
		if ($this->test !== '' && !current_user_can( 'manage_options' ))
		{
			$return = false;

			/**
			 * Logger notice
			 */
			WC_Webmoney::instance()->get_logger()->addNotice('Test mode only admins.');
		}

		return $return;
	}

	/**
	 * Output the gateway settings screen.
	 */
	public function admin_options()
	{
		// hook
		do_action('wc_webmoney_admin_options_before_show');

		echo '<h2>' . esc_html( $this->get_method_title() );
		wc_back_link( __( 'Return to payment gateways', 'wc-webmoney' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );
		echo '</h2>';

		// hook
		do_action('wc_webmoney_admin_options_method_description_before_show');

		echo wp_kses_post( wpautop( $this->get_method_description() ) );

		// hook
		do_action('wc_webmoney_admin_options_method_description_after_show');

		// hook
		do_action('wc_webmoney_admin_options_form_before_show');

		echo '<table class="form-table">' . $this->generate_settings_html( $this->get_form_fields(), false ) . '</table>';

		// hook
		do_action('wc_webmoney_admin_options_form_after_show');

		// hook
		do_action('wc_webmoney_admin_options_after_show');
	}

	/**
	 * There are no payment fields for sprypay, but we want to show the description if set.
	 *
	 * @action wc_webmoney_payment_fields_before_show
	 * @action wc_webmoney_payment_fields_show
	 * @action wc_webmoney_payment_fields_after_show
	 **/
	public function payment_fields()
	{
		// hook
		do_action('wc_webmoney_payment_fields_before_show');

		// hook
		do_action('wc_webmoney_payment_fields_show');

		// hook
		do_action('wc_webmoney_payment_fields_after_show');
	}

	/**
	 * Show description on site
	 */
	public function payment_fields_description_show()
	{
		if ($this->description)
		{
			echo wpautop(wptexturize($this->description));
		}
	}

	/**
	 * Show test mode on site
	 */
	public function payment_fields_test_mode_show()
	{
		if ($this->test !== '')
		{
			echo '<div style="padding:10px; background-color: #ff8982;text-align: center;">';
			echo __('TEST mode is active. Payment will not be charged. After checking, disable this mode.', 'wc-webmoney');
			echo '</div>';
		}
	}

	/**
	 * Process the payment and return the result
	 *
	 * @param int $order_id
	 *
	 * @action wc_webmoney_process_payment_start
	 *
	 * @return array
	 */
	public function process_payment($order_id)
	{
		/**
		 * Get order object
		 */
		$order = wc_get_order($order_id);

		/**
		 * Order fail
		 */
		if($order === false)
		{
			/**
			 * Return data
			 */
			return array
			(
				'result' => 'failure',
				'redirect' => ''
			);
		}

		// hook
		do_action('wc_webmoney_process_payment_start', $order_id, $order);

		/**
		 * Add order note
		 */
		$order->add_order_note(__('The client started to pay.', 'wc-webmoney'));

		/**
		 * Return data
		 */
		return array
		(
			'result' => 'success',
			'redirect' => $order->get_checkout_payment_url( true )
		);
	}

	/**
	 * Receipt page
	 *
	 * @param $order
	 *
	 * @action wc_webmoney_receipt_page_before_show
	 * @action wc_webmoney_receipt_page_show
	 * @action wc_webmoney_receipt_page_after_show
	 *
	 * @return void
	 */
	public function receipt_page($order)
	{
		// hook
		do_action('wc_webmoney_receipt_page_before_show', $order);

		// hook
		do_action('wc_webmoney_receipt_page_show', $order);

		// hook
		do_action('wc_webmoney_receipt_page_after_show', $order);
	}

	/**
	 * @param $order
	 *
	 * @return void
	 */
	public function receipt_page_show_form($order)
	{
		echo $this->generate_form($order);
	}

	/**
	 * Generate payments form
	 *
	 * @param $order_id
	 *
	 * @return string Payment form
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
		$out_sum = number_format($order->get_total(), 2, '.', '');
		$args['LMI_PAYMENT_AMOUNT'] = $out_sum;

		/**
		 * Product description
		 */
		$description = __('Order number: ' . $order_id, 'wc-webmoney');
		$args['LMI_PAYMENT_DESC'] = $description;
		$args['LMI_PAYMENT_DESC_BASE64'] = base64_encode($description);

		/**
		 * Rewrite currency from order
		 */
		WC_Webmoney::instance()->set_wc_currency($order->get_currency());

		/**
		 * Order id
		 */
		$args['LMI_PAYMENT_NO'] = $order_id;

		/**
		 * Purse merchant
		 */
		$data_wallet = $this->get_purse_from_currency(WC_Webmoney::instance()->get_wc_currency());
		$this->current_purse = $data_wallet['purse'];
		$args['LMI_PAYEE_PURSE'] = $this->current_purse;

		/**
		 * Billing email
		 */
		if(!empty($order->get_billing_email()))
		{
			$args['LMI_PAYMER_EMAIL'] = $order->get_billing_email();
		}

		/**
		 * Test mode
		 */
		if ($this->test !== '')
		{
			$args['LMI_SIM_MODE'] = $this->test;
		}

		/**
		 * Execute filter wc_webmoney_form_args
		 */
		$args = apply_filters('wc_webmoney_payment_form_args', $args);

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
		return '<form action="'.esc_url($this->form_url).'" method="POST" id="wc_webmoney_payment_form" accept-charset="utf-8">'."\n".
		       implode("\n", $args_array).
		       '<input type="submit" class="button alt" id="submit_wc_webmoney_payment_form" value="'.__('Pay', 'wc-webmoney').
		       '" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel & return to cart', 'wc-webmoney').'</a>'."\n".
		       '</form>';
	}

	/**
	 * Get signature
	 *
	 * @param $string
	 * @param $method
	 *
	 * @return string
	 */
	public function get_signature($string, $method = 'sha256')
	{
		switch($method)
		{
			case 'sha256':
				$signature = strtoupper(hash('sha256', $string));
				break;

			default:

				$signature = strtoupper(md5($string));
		}

		return $signature;
	}

	/**
	 * Check instant payment notification
	 *
	 * @action wc_webmoney_input_payment_notifications
	 *
	 * @return void
	 */
	public function input_payment_notifications()
	{
		// hook
		do_action('wc_webmoney_input_payment_notifications');

		/**
		 * Debug
		 */
		WC_Webmoney::instance()->get_logger()->addDebug('input_payment_notifications get', $_GET);
		WC_Webmoney::instance()->get_logger()->addDebug('input_payment_notifications post', $_POST);

		/**
		 * Action not found
		 */
		if(!array_key_exists('action', $_GET) || $_GET['action'] === '')
		{
			/**
			 * Send Service unavailable
			 */
			wp_die(__('Action error.', 'wc-webmoney'), 'Payment error', array('response' => '200'));
		}

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
		 * Check purse allow
		 */
		$local_purse = $this->get_purse_from_currency(WC_Webmoney::instance()->get_wc_currency());
		$this->current_purse = $local_purse['purse'];
		$this->secret_key = $local_purse['secret_key'];

		/**
		 * Local hash
		 *
		 * @todo to method
		 */
		$local_hash = strtoupper(hash('sha256', $LMI_PAYEE_PURSE.$LMI_PAYMENT_AMOUNT.$LMI_PAYMENT_NO.$LMI_MODE.$LMI_SYS_INVS_NO.$LMI_SYS_TRANS_NO.$LMI_SYS_TRANS_DATE.$this->secret_key.$LMI_PAYER_PURSE.$LMI_PAYER_WM));

		/**
		 * Get order object
		 */
		$order = wc_get_order($LMI_PAYMENT_NO);

		/**
		 * Order not found
		 */
		if($order === false && $_GET['action'] !== 'result')
		{
			/**
			 * Debug
			 */
			WC_Webmoney::instance()->get_logger()->addError('input_payment_notifications order not found by id: '. $LMI_PAYMENT_NO);

			/**
			 * Send Service unavailable
			 */
			wp_die(__('Order not found.', 'wc-webmoney'), 'Payment error', array('response' => '503'));
		}

		/**
		 * Result
		 */
		if ($_GET['action'] === 'result')
		{
			/**
			 * Check in settings
			 */
			if($order === false)
			{
				/**
				 * Debug
				 */
				WC_Webmoney::instance()->get_logger()->addInfo('input_payment_notifications check result url');

				/**
				 * Send Service available
				 */
				die('Ok');
			}
			/**
			 * Check pre request
			 */
			if($LMI_PREREQUEST === 1)
			{
				/**
				 * Debug
				 */
				WC_Webmoney::instance()->get_logger()->addInfo('input_payment_notifications PRE request success'. $LMI_PAYMENT_NO);

				/**
				 * Add order note
				 */
				$order->add_order_note(sprintf(__('Webmoney PRE request success. WMID: %1$s and purse: %2$s and IP: %3$s', 'wc-webmoney'), $LMI_PAYER_WM, $LMI_PAYER_PURSE, $LMI_PAYER_IP));

				die('YES');
			}
			else
			{
				/**
				 * Validated flag
				 */
				$validate = true;

				/**
				 * Check hash
				 */
				if($local_hash !== $LMI_HASH)
				{
					/**
					 * Debug
					 */
					WC_Webmoney::instance()->get_logger()->addError('input_payment_notifications Validate hash error'. $LMI_PAYMENT_NO);

					$validate = false;

					/**
					 * Add order note
					 */
					$order->add_order_note(sprintf(__('Validate hash error. Local: %1$s Remote: %2$s', 'wc-webmoney'), $local_hash, $LMI_HASH));
				}

				/**
				 * Check secret key
				 */
				if($LMI_SECRET_KEY !== '' && $this->secret_key !== $LMI_SECRET_KEY)
				{
					/**
					 * Debug
					 */
					WC_Webmoney::instance()->get_logger()->addError('input_payment_notifications Validate secret key error'. $LMI_PAYMENT_NO);

					$validate = false;

					/**
					 * Add order note
					 */
					$order->add_order_note(sprintf(__('Validate secret key error. Local: %1$s Remote: %2$s', 'wc-webmoney'), $this->secret_key, $LMI_SECRET_KEY));
				}

				/**
				 * Validated
				 */
				if($validate === true)
				{
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
						$order->add_order_note(sprintf(__('Order successfully paid (TEST MODE). WMID: %1$s and purse: %2$s and IP: %3$s', 'wc-webmoney'), $LMI_PAYER_WM, $LMI_PAYER_PURSE, $LMI_PAYER_IP));
					}
					/**
					 * Real payment
					 */
					else
					{
						/**
						 * Add order note
						 */
						$order->add_order_note(sprintf(__('Order successfully paid. WMID: %1$s and purse: %2$s and IP: %3$s', 'wc-webmoney'), $LMI_PAYER_WM, $LMI_PAYER_PURSE, $LMI_PAYER_IP));
					}

					/**
					 * Debug
					 */
					WC_Webmoney::instance()->get_logger()->addError('input_payment_notifications payment_complete'. $LMI_SYS_TRANS_NO);

					/**
					 * Set status is payment
					 */
					$order->payment_complete($LMI_SYS_TRANS_NO);
					die();
				}
				else
				{
					/**
					 * Debug
					 */
					WC_Webmoney::instance()->get_logger()->addError('input_payment_notifications Validate false'. $LMI_SYS_TRANS_NO);

					/**
					 * Send Service unavailable
					 */
					wp_die(__('Payment error, please pay other time.', 'wc-webmoney'), 'Payment error', array('response' => '503'));
				}
			}

			/**
			 * Debug
			 */
			WC_Webmoney::instance()->get_logger()->addError('input_payment_notifications Result final error'. $LMI_SYS_TRANS_NO);

			/**
			 * Send Service unavailable
			 */
			wp_die(__('Payment error, please pay other time.', 'wc-webmoney'), 'Payment error', array('response' => '503'));
		}
		/**
		 * Success
		 */
		else if ($_GET['action'] === 'success')
		{
			/**
			 * Debug
			 */
			WC_Webmoney::instance()->get_logger()->addDebug('input_payment_notifications Success');

			/**
			 * Add order note
			 */
			$order->add_order_note(__('Client return to success page.', 'wc-webmoney'));

			/**
			 * Empty cart
			 */
			WC()->cart->empty_cart();

			/**
			 * Redirect to success
			 */
			wp_redirect( $this->get_return_url( $order ) );
			die();
		}
		/**
		 * Fail
		 */
		else if ($_GET['action'] === 'fail')
		{
			/**
			 * Debug
			 */
			WC_Webmoney::instance()->get_logger()->addDebug('input_payment_notifications Fail');

			/**
			 * Add order note
			 */
			$order->add_order_note(__('Client return to fail url. The order has not been paid.', 'wc-webmoney'));

			/**
			 * Set status is failed
			 */
			//$order->update_status('failed');

			/**
			 * Redirect to cancel
			 */
			wp_redirect( str_replace('&amp;', '&', $order->get_cancel_order_url() ) );
			die();
		}

		/**
		 * Send Service unavailable
		 */
		wp_die(__('Api request error. Action not found.', 'wc-webmoney'), 'Payment error', array('response' => '503'));
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * @since 1.0.0.1
	 *
	 * @return bool
	 */
	public function is_available()
	{
		$is_available = parent::is_available();

		return $is_available;
	}
}