<?php
/**
 * WooCommerce SuperFaktúra.
 *
 * @package   WooCommerce SuperFaktúra
 * @author    Webikon (Ján Bočínec) <info@webikon.sk>
 * @license   GPL-2.0+
 * @link      http://www.webikon.sk
 * @copyright 2013 Webikon s.r.o.
 */

/**
 * WC_SuperFaktura.
 *
 * @package WooCommerce SuperFaktúra
 * @author  Webikon (Ján Bočínec) <info@webikon.sk>
 */
class WC_SuperFaktura {
	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	protected $version = '1.4.11';

	/**
	 * Unique identifier for your plugin.
	 *
	 * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
	 * match the Text Domain file header in the main plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'wc-superfaktura';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     1.0.0
	 */
	private function __construct()
	{
		// Define custom functionality. Read more about actions and filters: http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		add_action('init', array($this, 'init'));

		// Load public-facing style sheet and JavaScript.
		//add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 */
	public static function activate( $network_wide ) {
		// TODO: Define activation functionality here
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {
		// TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
        if(is_checkout())
        {
            wp_enqueue_script('wc-sf-invoice-checkout-js', plugins_url( 'wc-superfaktura.js', __FILE__ ), array('jquery') );
        }
	}


    function init()
    {
		// Load plugin text domain
		$this->load_plugin_textdomain();

        // woo backend invoice integration
        add_action('woocommerce_settings_start', array($this, 'add_woo_settings'));
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_woo_settings_tab'), 30);
        add_action('woocommerce_settings_tabs_wc_superfaktura', array($this, 'add_woo_settings_tab_content'));
        add_action('woocommerce_settings_save_wc_superfaktura', array($this, 'save_woo_settings_tab_content'));

        // woo checkout billing fields and processing
        add_filter('woocommerce_billing_fields', array($this, 'billing_fields'));
        add_action('woocommerce_checkout_update_order_meta', array($this, 'checkout_order_meta'));

        // metabox hook
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));

 		$wc_get_order_statuses = $this->get_order_statuses();

        foreach ( $wc_get_order_statuses as $key => $status )
        {
        	add_action( 'woocommerce_order_status_'.$key, array( $this, 'sf_new_invoice' ), 5 );
        }

        add_action( 'woocommerce_email_order_meta', array( $this, 'sf_invoice_link' ) );
        //add_action( 'woocommerce_order_status_on-hold_notification', array( 'WC_Email_Customer_Completed_Order', 'trigger' ) );
        add_action( 'woocommerce_thankyou', array( $this, 'sf_invoice_link' ), 10 );
    }

	/**
	 * NOTE:  Actions are points in the execution of a page or process
	 *        lifecycle that WordPress fires.
	 *
	 *        WordPress Actions: http://codex.wordpress.org/Plugin_API#Actions
	 *        Action Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    1.0.0
	 */
	public function action_method_name() {
		// TODO: Define your action hook callback here
	}

	public function sf_new_invoice( $order_id )
	{
		$order = new WC_Order($order_id);

		// if ( get_post_meta($order->id, 'wc_sf_internal_id', true) )
		// 	return;

		$sf_email = get_option('woocommerce_sf_email');
		$sf_key = get_option('woocommerce_sf_apikey');

		if ( get_option('woocommerce_sf_lang', 'sk') == 'cz' )
			$api = new SFAPIclientCZ( $sf_email, $sf_key );
		else
			$api = new SFAPIclient( $sf_email, $sf_key );

		//$invoice_id = get_option('woocommerce_sf_invoice_custom_num')=='yes' ? $this->generate_invoice_id($order->id) : '';
	    $ico = get_post_meta($order->id, 'billing_company_wi_id', true);
	    $ic_dph = get_post_meta($order->id, 'billing_company_wi_vat', true);
	    $dic = get_post_meta($order->id, 'billing_company_wi_tax', true);
	    //$variable = get_option('woocommerce_sf_variable');
	    //$constant = get_option('woocommerce_sf_constant');
	    //$specific = get_option('woocommerce_sf_specific');

    	$name = ( $order->billing_company ) ? $order->billing_company : $order->billing_first_name.' '.$order->billing_last_name;
    	$shipping_name = ( $order->shipping_company ) ? $order->shipping_company : $order->shipping_first_name.' '.$order->shipping_last_name;
    	//$api->getCountries()

    	// $all_countries = WC()->countries->get_countries();

    	$billing_address_2 = ! empty( $order->billing_address_2 ) ? ' ' . $order->billing_address_2 : '';

		$client_data = array(
			'name'    					=> $name,
			'ico'     					=> $ico,
			'dic'     					=> $dic,
			'ic_dph'  					=> $ic_dph,
			'email'   					=> $order->billing_email,
			'address' 					=> $order->billing_address_1 . $billing_address_2,
			// 'country' 				=> $all_countries[$order->billing_country],
			'country_iso_id' 			=> $order->billing_country,
			'city'    					=> $order->billing_city,
			'zip'     					=> $order->billing_postcode,
			'phone'   					=> $order->billing_phone,

		);	

		if ( $order->get_formatted_billing_address() != $order->get_formatted_shipping_address() )
		{
			$shipping_address_2 = ! empty( $order->shipping_address_2 ) ? ' ' . $order->shipping_address_2 : '';
			$client_data['delivery_address'] 		= $order->shipping_address_1 . $shipping_address_2;
			$client_data['delivery_city']  			= $order->shipping_city;
			// $client_data['delivery_country'  => $oder->shipping_address_1;
			$client_data['delivery_country_iso_id'] = $order->shipping_country;
			$client_data['delivery_name']  			= $shipping_name;
			$client_data['delivery_zip']  			= $order->shipping_postcode;		
		}

		//nastavime udaje klienta
		$api->setClient($client_data);				

		$shipping_methods = $order->get_shipping_methods();
		$shipping_method = reset( $shipping_methods );
		$delivery_type = get_option( 'woocommerce_sf_shipping_'.$shipping_method['method_id'] );

		$set_invoice_data = array(
			//vsetky polozky su nepovinne, v pripade ze nie su uvedene, budu doplnene automaticky
			// 'name'                 => 'nazov faktury',
			// 'variable'             => $variable, //variabilný symbol
			// 'constant'             => $constant, //konštantný symbol
			// 'specific'             => $specific, //specificky symbol
			'invoice_currency' 		=> get_post_meta($order->id, '_order_currency', true), //mena, v ktorej je faktúra vystavená. Možnosti: EUR, USD, GBP, HUF, CZK, PLN, CHF, RUB
			'payment_type'			=> get_option( 'woocommerce_sf_gateway_'.$order->payment_method ),
			'delivery_type'			=> $delivery_type,
			// 'created'              => '2013-07-01', //datum vystavenia
			//'delivery'             	=> in_array( $delivery_type, get_option('woocommerce_sf_delivery_date_visibility') ) ? 0 : -1, //datum dodania
			'delivery'             	=> get_option('woocommerce_sf_delivery_date_visibility') == 'yes' ? 0 : -1, //datum dodania
			// 'due'                  => '2013-07-01', //datum splatnosti
			'issued_by' 			=> get_option('woocommerce_sf_issued_by'), //faktúru vystavil
			'issued_by_phone' 		=> get_option('woocommerce_sf_issued_phone'), //faktúru vystavil telefón
			'issued_by_email' 		=> get_option('woocommerce_sf_issued_email'), //faktúru vystavil email
			'internal_comment'		=> $order->customer_note,
			'comment'				=> ''
     		//'order_no'              =>  $order->id, //číslo objednávky
		);

		if ( $order->get_total_discount() )
		{
			if ( wc_prices_include_tax() ) {
				$set_invoice_data['discount_total'] = $order->get_total_discount(false);
			} else {
				$tax = 1 + round( $order->get_total_tax()/($order->get_total()-$order->get_total_tax()), 2 );
				$set_invoice_data['discount_total'] = $order->get_total_discount()*$tax;
			}
		}

		if ( get_option('woocommerce_sf_comments') == 'yes' )
		{
			$set_invoice_data['header_comment']	= 'Číslo objednávky: '.$order->get_order_number();

			if ( WC()->countries->get_base_country() != $order->billing_country )
			{
				//$set_invoice_data['comment'] = get_option('woocommerce_sf_tax_liability');
				$set_invoice_data['comment'] = get_option('woocommerce_sf_tax_liability')."\r\n";
			}

			$set_invoice_data['comment'] = $set_invoice_data['comment']."\r\n".get_option('woocommerce_sf_comment'); //komentár, poznámka
		}


		//nastavime udaje pre fakturu
		$api->setInvoice($set_invoice_data);

		if ( $order->status == 'completed' )
			$api->setInvoice(array(
				'already_paid' => true, // bola uz faktura uhradena?
			));

		//pridanie polozky na fakturu, metoda moze byt volana viackrat
		//v pripade ze nie ste platca dph, uvadzajte polozku tax = 0

		$items = $order->get_items();

		foreach ( $items as $item_id => $item )
		{
			$product = $order->get_product_from_item($item);

			/*$_tax  = new WC_Tax();
			$tax_rates = $_tax->get_rates( $product->get_tax_class() );

			$total_tax = '';
			foreach ( $tax_rates as $tax_rate )
					$total_tax += $tax_rate['rate'];*/

			$item_data = array(
				'name'        => $item['name'],
				//'description' => $product->get_post_data()->post_excerpt,
				//'description' => 'Číslo objednávky: '.$order->id,
				'quantity'    => $item['qty'],
				'sku'		  => $product->get_sku(),
				'unit'        => 'ks',
				'unit_price'  => $order->get_item_subtotal($item),
				'tax'         => round($order->get_item_tax($item) / $order->get_item_total($item) * 100)
			);

			if ( isset( $item['variation_id'] ) &&  $item['variation_id'] > 0 )
				$product_id = $item['variation_id'];
			else
				$product_id = $item['product_id'];

			$product = wc_get_product( $product_id );

			if ( get_option('woocommerce_sf_product_description_visibility') == 'yes' ) {
				$item_data['description'] = $product->get_post_data()->post_excerpt;
			}

			if ( $product->is_on_sale() )
			{
				$tax = 1 + round( (( $product->get_price_including_tax() - $product->get_price_excluding_tax() ) / $product->get_price_excluding_tax()), 2 );
				$item_data['unit_price'] = $product->get_regular_price() / $tax;

				//$zlava = round( ( $product->get_regular_price() - $product->get_sale_price() ) / $product->get_regular_price() * 100 );
				$discount = ($product->get_regular_price() - $product->get_sale_price()) / $tax;

				//$item_data['discount'] = '50'; //%
				if ( $discount )
					$item_data['discount_no_vat'] = $discount;

				//$item_data['description'] = "Bola poskytnutá zľava. Pôvodná cena: " . $product->get_regular_price() . html_entity_decode( get_woocommerce_currency_symbol() ) . "\r\n";		
			}

			$api->addItem($item_data);	
		}		

	  	if ( $order->get_fees() )
	  	{
		  	foreach ( $order->get_fees() as $fee )
		  	{
				//poplatky
			    $api->addItem(array(
						'name'        => $fee['name'],
						'quantity'    => '',
						'unit'        => '',
						'unit_price'  => $fee['line_total'],
						'tax'         => $fee['line_tax'],
					));	  		
		  	}
		}

	    $shipping_tax = 0;
	    if((int)$order->order_shipping>0)
	      $shipping_tax = round($order->get_shipping_tax()/$order->order_shipping * 100);

	  	$shipping_price = $order->get_total_shipping();

	  	if ( $shipping_price > 0 )
	  	{
			//poštovné a balné
		    $api->addItem(array(
					'name'        => 'Poštovné',
					'quantity'    => '',
					'unit'        => '',
					'unit_price'  => $shipping_price,
					'tax'         => $shipping_tax,
				));
	    }

		foreach( array('regular','proforma') as $type )
		{
			if ( ! $invoice_status = $this->generate_invoice_status($order->payment_method,$type) )
					continue;

			if ( $invoice_status != $order->status )
					continue;

			$invoice_id = get_option('woocommerce_sf_invoice_custom_num')=='yes' ? $this->generate_invoice_id($order->id,$type) : '';
			//$api->setInvoice['type'] = $type;
			$api->setInvoice(array(
				'type' 					=> $type,
				'invoice_no_formatted'	=> $invoice_id,
			));

			//vytvorenie faktury
			$response = $api->save();

			if( $response->error === 0 ) 
			{
				$internal_id = $response->data->Invoice->id;

				update_post_meta($order->id, 'wc_sf_internal_id', $internal_id);

				$pdf = $api::SFAPI_URL.'/invoices/pdf/'.$internal_id.'/token:'.$response->data->Invoice->token;

				update_post_meta($order->id, 'wc_sf_invoice_'.$type, $pdf);
			}
			else
			{
				$pdf = $order->get_view_order_url();
			}
		}
	}

 	/**
	 * Save our meta data to an order.
	 *
	 * @since    1.0.0
	 */
    function checkout_order_meta($order_id)
    {
        if(isset($_POST['shiptobilling']) && $_POST['shiptobilling']=='1')
            update_post_meta($order_id, 'has_shipping', '0');
        else
            update_post_meta($order_id, 'has_shipping', '1');


        if(isset($_POST['wi_as_company']) && $_POST['wi_as_company']=='1')
        {
            $valid = array('billing_company_wi_id', 'billing_company_wi_vat', 'billing_company_wi_tax');

            foreach($valid as $attr)
            {
                if(isset($_POST[$attr]))
                    update_post_meta($order_id, $attr, esc_attr($_POST[$attr]));
            }
        }
    }

 	/**
	 * Add company information fields on checkout page.
	 *
	 * @since    1.0.0
	 */
    function billing_fields($fields)
    {
        $required = false;
        if(get_option('woocommerce_sf_invoice_checkout_required', false)=='yes')
            $required = true;

        $new_fields = array();
        foreach($fields as $key=>$value)
        {
            // add pay as company checkbox
            if($key=='billing_company')
            {
                $new_fields['wi_as_company'] = array(
                    'type' => 'checkbox',
                    'label' => __('Buy as Business client', 'wc-superfaktura'),
                    'class' => array('form-row-wide')
                );
            }

            $new_fields[$key] = $value;

            if($key=='billing_company')
            {
                $new_fields[$key]['required'] = $required;

                if(get_option('woocommerce_sf_invoice_checkout_id', false)=='yes')
                {
                    $new_fields['billing_company_wi_id'] = array(
                        'type' => 'text',
                        'label' => __('ID #', 'wc-superfaktura'),
                        'required' => $required,
                        'class' => array('form-row-wide')
                    );
                }

                if(get_option('woocommerce_sf_invoice_checkout_vat', false)=='yes')
                {
                    $new_fields['billing_company_wi_vat'] = array(
                        'type' => 'text',
                        'label' => __('VAT #', 'wc-superfaktura'),
                        'required' => $required,
                        'class' => array('form-row-wide')
                    );
                }

                if(get_option('woocommerce_sf_invoice_checkout_tax', false)=='yes')
                {
                    $new_fields['billing_company_wi_tax'] = array(
                        'type' => 'text',
                        'label' => __('TAX ID #', 'wc-superfaktura'),
                        'required' => $required,
                        'class' => array('form-row-wide')
                    );
                }
            }
        }

        return $new_fields;
    }

 	/**
	 * Add SuperFaktúra setting fields.
	 *
	 * @since    1.0.0
	 */
    function add_woo_settings()
    {
        global $woocommerce_settings;

        $upload_dir = wp_upload_dir();

        $invoice_settings = array(        	
        	array(
                'title' => __('Authorization', 'wc-superfaktura'),
                'type' => 'title',
                'desc' => 'You can find this information <a href="https://moja.superfaktura.sk/api_access">here!</a>',
                'id' => 'woocommerce_sf_invoice_title1'
            ),
        	array(
                'title' => __('Jazyk', 'wc-superfaktura'),
                'id' => 'woocommerce_sf_lang',
                'type' => 'radio',
                'desc' => '',
                'default' => 'sk',
                'options' => array( 'sk' => 'SuperFaktura.sk', 'cz' => 'SuperFaktura.cz' )
            ),            
        	array(
                'title' => __('Account Email', 'wc-superfaktura'),
                'id' => 'woocommerce_sf_email',
                'desc' => '',
                'class' => 'input-text regular-input',
                'type' => 'text',
            ),
            array(
                'title' => __('API Key', 'wc-superfaktura'),
                'id' => 'woocommerce_sf_apikey',
                'desc' => '',
                'class' => 'input-text regular-input',
                'type' => 'text',
            ),
            array(
                'type' => 'sectionend',
                'id' => 'woocommerce_wi_invoice_title1'
            ),
            array(
                'title' => __('Invoice Options', 'wc-superfaktura'),
                'type' => 'title',
                'desc' => '',
                'id' => 'woocommerce_sf_invoice_title2'
            ),
            array(
                'title' => __('Custom invoice numbering', 'wc-superfaktura'),
                'id' => 'woocommerce_sf_invoice_custom_num',
                'default' => 'no',
                'type' => 'checkbox'
            ),
            array(
                'title' => __('Invoice Nr.', 'wc-superfaktura'),
                'desc' => sprintf(__('Available Tags: %s'), '[YEAR], [MONTH], [DAY], [COUNT]'),
                'id' => 'woocommerce_sf_invoice_regular_id',
                'default' => '[YEAR][MONTH][COUNT]',
                'type' => 'text',
            ),
            array(
                'title' => __('Proforma Invoice Nr.', 'wc-superfaktura'),
                'desc' => sprintf(__('Available Tags: %s'), '[YEAR], [MONTH], [DAY], [COUNT]'),
                'id' => 'woocommerce_sf_invoice_proforma_id',
                'default' => 'ZAL[YEAR][MONTH][COUNT]',
                'type' => 'text',
            ),
            array(
                'title' => __('Current Invoice Number', 'wc-superfaktura'),
                'id' => 'woocommerce_sf_invoice_regular_count',
                'default' => '1',
                'type' => 'number',
                'class' => 'wi-small',
                'custom_attributes' => array(
                    'min' => 1,
                    'step' => 1
                )
            ),
            array(
                'title' => __('Current Proforma Invoice Number', 'wc-superfaktura'),
                'id' => 'woocommerce_sf_invoice_proforma_count',
                'default' => '1',
                'type' => 'number',
                'class' => 'wi-small',
                'custom_attributes' => array(
                    'min' => 1,
                    'step' => 1
                )
            ),
            array(
                'title' => __('Number of digits for [COUNT]', 'wc-superfaktura'),
                'id' => 'woocommerce_sf_invoice_count_decimals',
                'default' => '4',
                'type' => 'number',
                'class' => 'wi-small',
                'custom_attributes' => array(
                    'min' => 1,
                    'step' => 1
                )
            ),          
            array(
                'type' => 'sectionend',
                'id' => 'woocommerce_wi_invoice_title2'
            ),
            array(
                'title' => __('Invoice Comments', 'wc-superfaktura'),
                'type' => 'title',
                'desc' => '',
                'id' => 'woocommerce_sf_invoice_title8'
            ),
            array(
                'title' => __('Allow custom comments', 'wc-superfaktura'),
                'id' => 'woocommerce_sf_comments',
                'default' => 'yes',
                'type' => 'checkbox',
                'desc' => 'Override default comments options in SuperFaktúra. It adds information about order number, custom comment and tax liability if needed.'
            ),            
            array(
                'title' => __('Comment', 'wc-superfaktura'),
                'id' => 'woocommerce_sf_comment',
                'class' => 'input-text wide-input',
                'css'	=> 'width:100%; height: 75px;',
                //'default' => '',
                'type' => 'textarea',
            ),
        	//Prenesená daňová povinnosť             
            array(
                'title' => __('Tax Liability', 'wc-superfaktura'),
                'id' => 'woocommerce_sf_tax_liability',
                'class' => 'input-text wide-input',
                'default' => 'Dodanie tovaru je oslobodené od dane. Dodanie služby podlieha preneseniu daňovej povinnosti.',
                'type' => 'textarea',
            ),                        
            array(
                'type' => 'sectionend',
                'id' => 'woocommerce_wi_invoice_title8'
            ),            
            array(
                'title' => __('Additional Invoice Fields', 'wc-superfaktura'),
                'type' => 'title',
                'desc' => '',
                'id' => 'woocommerce_wi_invoice_title3'
            ),
            array(
                'title' => __('Add field ID #', 'wc-superfaktura'),
                'id' => 'woocommerce_sf_invoice_checkout_id',
                'default' => 'yes',
                'type' => 'checkbox',
                'desc' => ''
            ),
            array(
                'title' => __('Add field VAT #', 'wc-superfaktura'),
                'id' => 'woocommerce_sf_invoice_checkout_vat',
                'default' => 'yes',
                'type' => 'checkbox',
                'desc' => ''
            ),
            array(
                'title' => __('Add field TAX ID #', 'wc-superfaktura'),
                'id' => 'woocommerce_sf_invoice_checkout_tax',
                'default' => 'yes',
                'type' => 'checkbox',
                'desc' => ''
            ),
            array(
                'title' => __('Checkout fields required', 'wc-superfaktura'),
                'id' => 'woocommerce_sf_invoice_checkout_required',
                'default' => 'no',
                'type' => 'checkbox'
            ),
            array(
                'title' => __('Issued by', 'wc-superfaktura'),
                'id' => 'woocommerce_sf_issued_by',
                'type' => 'text',
            ),
            array(
                'title' => __('Issued by Email', 'wc-superfaktura'),
                'id' => 'woocommerce_sf_issued_email',
                'type' => 'text',
            ),
            array(
                'title' => __('Issued by Phone', 'wc-superfaktura'),
                'id' => 'woocommerce_sf_issued_phone',
                'type' => 'text',
            ),   
           array(
                'title' => __('Delivery Date', 'wc-superfaktura'),
                'id' => 'woocommerce_sf_delivery_date_visibility',
                'type' => 'checkbox',
                'desc' => 'Display a delivery date.',
                'default' => 'yes'
            ),
           array(
                'title' => __('Product Description', 'wc-superfaktura'),
                'id' => 'woocommerce_sf_product_description_visibility',
                'type' => 'checkbox',
                'desc' => 'Display a product description.',
                'default' => 'yes'
            ),                                  
            array(
                'type' => 'sectionend',
                'id' => 'woocommerce_wi_invoice_title3'
            ),
        );

        $gateways = WC()->payment_gateways->payment_gateways();

        $wc_get_order_statuses = $this->get_order_statuses();

        $shop_order_status = array( '0' => __('Don\'t generate', 'wc-superfaktura') );
        $shop_order_status = array_merge( $shop_order_status, $wc_get_order_statuses );

        $invoice_settings[] = array(
            'title' => __('Invoice Creation', 'wc-superfaktura'),
            'type' => 'title',
            'desc' => 'Select when you would like to create an invoice for each payment gateway.',
            'id' => 'woocommerce_wi_invoice_title4'
        );

        foreach($gateways as $gateway)
        {
            $invoice_settings[] = array(
                'title' => $gateway->title,
                'id' => 'woocommerce_sf_invoice_regular_'.$gateway->id,
                'default' => 0,
                'type' => 'select',
                'options' => $shop_order_status
            );
        }

        $invoice_settings[] = array(
            'type' => 'sectionend',
            'id' => 'woocommerce_wi_invoice_title4'
        );

        $invoice_settings[] = array(
                'title' => __('Proforma Invoice Creation', 'wc-superfaktura'),
                'type' => 'title',
                'desc' => 'Select when you would like to create a proforma invoice for each payment gateway.',
                'id' => 'woocommerce_wi_invoice_title5'
        );

        foreach($gateways as $gateway)
        {
            $invoice_settings[] = array(
                'title' => $gateway->title,
                'id' => 'woocommerce_sf_invoice_proforma_'.$gateway->id,
                'default' => 0,
                'type' => 'select',
                'options' => $shop_order_status
            );
        }

        $invoice_settings[] = array(
            'type' => 'sectionend',
            'id' => 'woocommerce_wi_invoice_title5'
        );

        $invoice_settings[] = array(
            'title' => __('Payment Methods', 'wc-superfaktura'),
            'type' => 'title',
            'desc' => 'Map Woocommerce payment methods to ones in SuperFaktúra.sk',
            'id' => 'woocommerce_wi_invoice_title6'
        );

        $gateway_mapping = array(
        	'0'				=> __('Don\'t use', 'wc-superfaktura'),
            'transfer' 		=> __('Bankový prevod', 'wc-superfaktura'),
            'cash' 			=> __('Hotovosť', 'wc-superfaktura'),
            'paypal' 		=> __('Paypal', 'wc-superfaktura'),
            'trustpay' 		=> __('Trustpay', 'wc-superfaktura'),
            'credit' 		=> __('Kreditná karta', 'wc-superfaktura'),
            'debit' 		=> __('Debetná karta', 'wc-superfaktura'),
            'cod' 			=> __('Dobierka', 'wc-superfaktura'),
            'accreditation' => __('Vzajomný zápočet', 'wc-superfaktura'),
        );

        foreach($gateways as $gateway)
        {
            $invoice_settings[] = array(
                'title' => $gateway->title,
                'id' => 'woocommerce_sf_gateway_'.$gateway->id,
                'default' => 0,
                'type' => 'select',
                'options' => $gateway_mapping
            );
        }

        $invoice_settings[] = array(
            'type' => 'sectionend',
            'id' => 'woocommerce_wi_invoice_title6'
        );

        $wc_shipping = WC()->shipping();
        $shippings = $wc_shipping->get_shipping_methods();

        if ( $shippings )
        {
	        $invoice_settings[] = array(
	            'title' => __('Shipping Methods', 'wc-superfaktura'),
	            'type' => 'title',
	            'desc' => 'Map Woocommerce shipping methods to ones in SuperFaktúra.sk',
	            'id' => 'woocommerce_wi_invoice_title7'
	        );

	        $shipping_mapping = array(
	        	'0'			=> __('Don\'t use', 'wc-superfaktura'),
	            'mail' 		=> __('Poštou', 'wc-superfaktura'),
	            'courier' 	=> __('Kuriérom', 'wc-superfaktura'),
	            'personal' 	=> __('Osobný odber', 'wc-superfaktura'),
	            'haulage' 	=> __('Nákladná doprava', 'wc-superfaktura')
	        );

	        foreach($shippings as $shipping)
	        {
	        	if ( $shipping->enabled == 'no' )
	        		continue;

	            $invoice_settings[] = array(
	                'title' => $shipping->title,
	                'id' => 'woocommerce_sf_shipping_'.$shipping->id,
	                'default' => 0,
	                'type' => 'select',
	                'options' => $shipping_mapping
	            );
	        }

	        //array_shift( $shipping_mapping );

            // $invoice_settings[] = array(
            //     'title' => __('Delivery Date', 'wc-superfaktura'),
            //     'id' => 'woocommerce_sf_delivery_date_visibility',
            //     'type' => 'multiselect',
            //     'desc' => 'Display a delivery date only for selected shipping methods.',
            //     'default' => array_flip( $shipping_mapping ),
            //     'options' => $shipping_mapping
            // );	        

	        $invoice_settings[] = array(
	            'type' => 'sectionend',
	            'id' => 'woocommerce_wi_invoice_title7'
	        );
        }

        $woocommerce_settings['wc_superfaktura'] = apply_filters('woocommerce_wc_superfaktura_settings', $invoice_settings);
    }

	/**
	 * Create SuperFaktúra tab on Woocommerce settings page.
	 *
	 * @since    1.0.0
	 */
    function add_woo_settings_tab($tabs)
    {
    	$tabs['wc_superfaktura'] = __('SuperFaktúra', 'wc-superfaktura');

        return $tabs;
    }

    function save_woo_settings_tab_content()
    {
    	if(class_exists('WC_Admin_Settings'))
        {
     		global $woocommerce_settings;
            WC_Admin_Settings::save_fields($woocommerce_settings['wc_superfaktura']);
        }
    }

	/**
	 * Display SuperFaktúra setting fields.
	 *
	 * @since    1.0.0
	 */
    function add_woo_settings_tab_content()
    {
        global $woocommerce_settings;
        echo '<p>Máte s modulom technický problém? Napíšte nám na <a href="mailto:support@webikon.sk">support@webikon.sk</a></p>';
        woocommerce_admin_fields($woocommerce_settings['wc_superfaktura']);
    }

    function add_meta_boxes()
    {
        add_meta_box('wc_sf_invoice_box', __('Invoices', 'wc-superfaktura'), array($this, 'add_box'), 'shop_order', 'side');
    }

    function add_box($order)
    {
        $invoice = get_post_meta($order->ID, 'wc_sf_invoice_regular', true);
        $proforma = get_post_meta($order->ID, 'wc_sf_invoice_proforma', true);

        echo '<p><h4 class="wc_sf_invoice_box">'.__('View Generated Invoices', 'wc-superfaktura').':</h4>';
        if(!empty($proforma))
            echo '<a href="'.$proforma.'" class="button" target="_blank">'.__('Proforma', 'wc-superfaktura').'</a>';

        if(!empty($invoice))
            echo '<a href="'.$invoice.'" class="button" target="_blank">'.__('Invoice', 'wc-superfaktura').'</a>';

        if(empty($proforma) && empty($invoice))
            echo __('No invoice was generated', 'wc-superfaktura');
        echo '</p>';

        //if(!empty($proforma) || !empty($invoice))
            //echo '<p><a href="'.admin_url('post.php?post='.$_GET['post'].'&action=edit&wc_sf_invoice_resend').'" class="button">'.__('Resend Invoices', 'wc-superfaktura').'</a></p>';
    }

    function generate_invoice_id($order_id,$key='regular')
    {
        $invoice_id = get_post_meta($order_id, 'wc_sf_invoice_'.$key.'_id', true);
        if(!empty($invoice_id))
            return $invoice_id;

        $invoice_id_template = get_option('woocommerce_sf_invoice_'.$key.'_id', true);
        if(empty($invoice_id_template))
            $invoice_id_template = '[YEAR][MONTH][COUNT]';

        $num_decimals = get_option('woocommerce_sf_invoice_count_decimals', true);
        if(empty($num_decimals))
            $num_decimals = 4;

        $count = get_option('woocommerce_sf_invoice_'.$key.'_count', true);
        update_option('woocommerce_sf_invoice_'.$key.'_count', intval($count)+1);
        $count = str_pad($count, intval($num_decimals), '0', STR_PAD_LEFT);

        $date = current_time('timestamp');
        $year = date('Y', $date);
        $month = date('m', $date);
        $day = date('d', $date);

        $invoice_id = str_replace('[YEAR]', $year, $invoice_id_template);
        $invoice_id = str_replace('[MONTH]', $month, $invoice_id);
        $invoice_id = str_replace('[DAY]', $day, $invoice_id);
        $invoice_id = str_replace('[COUNT]', $count, $invoice_id);

        update_post_meta($order_id, 'wc_sf_invoice_'.$key.'_id', $invoice_id);
        return $invoice_id;
    }

    function generate_invoice_status($payment_method, $type = 'regular')
    {
        if($type!='regular' && $type!='proforma')
            $type = 'regular';

        $generate = get_option('woocommerce_sf_invoice_'.$type.'_'.$payment_method);

        // if(!in_array($generate, array('new_order', 'processing', 'completed')))
        //     $generate = false;

        return $generate;
    }

    function sf_invoice_link($order)
    {
    	$order_id = is_int($order) ? $order : $order->id;
    	if ( $pdf = get_post_meta($order_id,'wc_sf_invoice_regular', true) )
    		echo '<h2>Faktúra na stiahnutie: </h2><a href="'.$pdf.'">'.$pdf.'</a>';
    	elseif ( $pdf = get_post_meta($order_id,'wc_sf_invoice_proforma', true) )
    		echo '<h2>Proforma faktúra na stiahnutie: </h2><a href="'.$pdf.'">'.$pdf.'</a>';
    }

	function get_order_statuses()
	{	
		if ( function_exists( 'wc_get_order_statuses' ) )
		{
			$wc_get_order_statuses = wc_get_order_statuses();

			return $this->alter_wc_statuses( $wc_get_order_statuses );
		}
		else
		{
			$order_status_terms = get_terms('shop_order_status','hide_empty=0');

			$shop_order_statuses = array();
			if ( ! is_wp_error( $order_status_terms ) )
			{
		        foreach ( $order_status_terms as $term )
		        {
		        	$shop_order_statuses[$term->slug] = $term->name;
		        }
	        }     

	    	return $shop_order_statuses;  	 
       }
	}

    function alter_wc_statuses( $array )
    {
    	$new_array = array();
    	foreach ( $array as $key => $value )
    	{
    		$new_array[substr($key,3)] = $value;
    	}

    	return $new_array;
    }
}
