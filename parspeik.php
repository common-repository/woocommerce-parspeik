<?php
/**
Plugin Name: WooCommerce PARSPEIK
Plugin URI: http://parspeik.ir/
Description: This plugin integrates <strong>ParsPeik</strong> service with WooCommerce.
Version: 1.2
Author: Domanjiri
Text Domain: parspeik
Domain Path: /lang/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
**/

function activate_WC_PARSPEIK_plugin()
{
    wp_schedule_event(time(), 'daily', 'update_ppeik_orders_state');
} 
register_activation_hook(__FILE__, 'activate_WC_PARSPEIK_plugin');


function deactivate_WC_PARSPEIK_plugin()
{
    wp_clear_scheduled_hook('update_ppeik_orders_state');
}
register_deactivation_hook(__FILE__, 'deactivate_WC_PARSPEIK_plugin');


// Check if WooCommerce is active
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
 
	function parspeik_shipping_method_init() {
            if(!class_exists('nusoap_client')) {
                include_once(plugin_dir_path(__FILE__) . 'lib/nusoap/nusoap.php');
            }
		    
            // 
            date_default_timezone_set('Asia/Tehran');
            ini_set('default_socket_timeout', 160);
            
            // Define Pishtaz method
		    if ( ! class_exists( 'WC_Parspeik_Pishtaz_Method' ) ) {
		
                        class WC_Parspeik_Pishtaz_Method extends WC_Shipping_Method 
                 {
                        var $url            = "";
                        var $wsdl_url       = "http://p24.ir/ws/pws.php?wsdl";
                        var $co_id          = "";
                        var $p_id           = "";
                        var $password       = "";
                        var $debug          = 1;
                        var $w_unit         = "";
                        var $debug_file     = "";
                        var $client         = null;
                        var $min_amount     = null;
				
			public function __construct() 
                        {
					       $this->id                 = 'parspeik_pishtaz'; 
					       $this->method_title       = __( 'پست پیشتاز' ); 
					       $this->method_description = __( 'ارسال توسط پست پیشتاز ' );
 
					       $this->init();
                                               $this->account_data();
			}
 
			function init() 
                        {
                                    $this->init_form_fields(); 
                                    $this->init_settings(); 
                    
                                    $this->enabled		= $this->get_option( 'enabled' );
                                    $this->title 		= $this->get_option( 'title' );
                                    $this->min_amount           = $this->get_option( 'min_amount', 0 );
                           
                                    $this->w_unit               = strtolower( get_option('woocommerce_weight_unit') );
                           
                                    //$test                = $this->get_option( 'test', 'no');
                                    //$this->wsdl_url      =  ($test=='yes') ? 'http://p24.ir/ws/pws.php?wsdl' : 'http://efyek.com/ws/pws.php?wsdl';
					      
                                        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                    
                        }
                        
                        function account_data() 
                        {
                            $this->co_id        = (int)$this->get_option( 'co_id', '' );
                            $this->p_id         = (int)$this->get_option( 'p_id', '' );
                            $this->password     = $this->get_option( 'password', '' );
                        }
                
                        function init_form_fields() 
                        {
   	                        global $woocommerce;

		                    if ( $this->min_amount )
		                     	$default_requires = 'min_amount';


                         	$this->form_fields = array(
	                     		'enabled' => array(
	                     						'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
						                     	'type' 			=> 'checkbox',
			                     				'label' 		=> __( 'فعال کردن پست پیشتاز', 'woocommerce' ),
			                     				'default' 		=> 'yes'
	                     					),
	                     		'title' => array(
                     	                     						'title' 		=> __( 'Method Title', 'woocommerce' ),
					                     		'type' 			=> 'text',
                     							'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
					                     		'default'		=> __( 'پست پیشتاز', 'woocommerce' ),
		                     					'desc_tip'      => true,
	                     					),
	                     		'min_amount' => array(
                     							'title' 		=> __( 'Minimum Order Amount', 'woocommerce' ),
                     							'type' 			=> 'number',
		                     					'custom_attributes' => array(
	                     							'step'	=> 'any',
	                     							'min'	=> '0'
	                     						),
			                     				'description' 	=> __( 'کمترین میزان خرید برای فعال شدن این روش ارسال.', 'woocommerce' ),
				                     			'default' 		=> '0',
				                     			'desc_tip'      => true,
			                     				'placeholder'	=> '0.00'
			                     			),
                                 'pishtaz_default' => array(
                     	                     	'title' 		=> __( 'هزینه‌ی پیش‌فرض', 'woocommerce' ),
					                     		'type' 			=> 'text',
                     							'description' 	=> __( 'هنگامی که به دلایلی امکان استعلام هزینه‌ی ارسال از سرویس پارس‌پیک ممکن نباشد، این مبلغ نمایش داده‌خواهد شد.', 'woocommerce' ),
					                     		'default'		=> 60000,
		                     					'desc_tip'      => true,
	                     					),
                                 'co_id' => array(
	                     						'title' 		=> __( 'کد فروشگاه', 'woocommerce' ),
	                     						'type' 			=> 'text',
	                     						'description' 	=> __( '', 'woocommerce' ),
	                     						'default'		=> __( '', 'woocommerce' ),
	                     						'desc_tip'      => true,
	                     					),
                                 'p_id' => array(
	                     						'title' 		=> __( 'کد همکاری', 'woocommerce' ),
	                     						'type' 			=> 'text',
	                     						'description' 	=> __( '', 'woocommerce' ),
	                     						'default'		=> __( '', 'woocommerce' ),
	                     						'desc_tip'      => true,
	                     					),
                                 'password' => array(
	                     						'title' 		=> __( 'رمز استفاده از وب سرویس', 'woocommerce' ),
	                     						'type' 			=> 'password',
	                     						'description' 	=> __( '', 'woocommerce' ),
	                     						'default'		=> __( '', 'woocommerce' ),
	                     						'desc_tip'      => true,
			                     			),
                                 /*'test' => array(
	                     						'title' 		=> __( 'حالت تست', 'woocommerce' ),
						                     	'type' 			=> 'checkbox',
			                     				'label' 		=> __( 'برای تست وب‌سرویس', 'woocommerce' ),
			                     				'default' 		=> 'no'
	                     					),*/
		                     	);

                         }
    
    
                        public function admin_options() 
                        {
                            ?>
    	                     <h3><?php _e( 'پست پیشتاز' ); ?></h3>
                         	<table class="form-table">
                         	<?php
                         		// Generate the HTML For the settings form.
                         		$this->generate_settings_html();
                         	?>
	                     	</table>
                         	<?php
                       }
    
                      function is_available( $package ) 
                      {
    	                   global $woocommerce;

                           if ( $this->enabled == "no" ) return false;
       
                           if ( ! in_array( get_woocommerce_currency(),  array( 'IRR', 'IRT' )  ) ) return false;
        
                           if( $this->w_unit != 'g' && $this->w_unit != 'kg' )
                               return false;
                           
                           if ( $this->co_id =="" || $this->p_id =="" || $this->password=="")
                               return false;
            
		                   // Enabled logic
	                   	   $has_met_min_amount = false;

	                   	   if ( isset( $woocommerce->cart->cart_contents_total ) ) {
	                   	       
			                     if ( $woocommerce->cart->prices_include_tax )
			                         	$total = $woocommerce->cart->cart_contents_total + array_sum( $woocommerce->cart->taxes );
		                      	else
				                        $total = $woocommerce->cart->cart_contents_total;

			                    if ( $total >= $this->min_amount )
				                        $has_met_min_amount = true;
		                   }


		                   if ( $has_met_min_amount ) $is_available = true;
			

		                   return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available );
                      }

		      public function calculate_shipping( $package ) 
                      {
    	                   global $woocommerce;
		                   $customer = $woocommerce->customer;

                           if( empty($package['destination']['city'])) {
                               $rate = array(
			               		'id' 		=> $this->id,
			               		'label' 	=> $this->title,
			               		'cost' 		=> 0
			               	   );
                               $this->add_rate( $rate );
                           }
                          
			               $this->shipping_total = 0;
		              	   $weight = 0;
                           $unit = ($this->w_unit == 'g') ? 1 : 1000;
            
			               $data = array();
			               if (sizeof($woocommerce->cart->get_cart()) > 0 && ($customer->get_shipping_city())) {

				              foreach ($woocommerce->cart->get_cart() as $item_id => $values) {

					              $_product = $values['data'];

					              if ($_product->exists() && $values['quantity'] > 0) {

						              if (!$_product->is_virtual()) {

							              $weight += $_product->get_weight() * $unit * $values['quantity'];
					              	  }
					             }
				              } //end foreach
                              
				              $data['weight']         = (int)$weight;
                                              $data['service_type']   = 1;  // pishtaz
				              if ($weight) {
					              $this->get_shipping_response($data, $package);
				              }
			              }
                         
                      }
        
                      function get_shipping_response($data = false, $package) 
                      {
    	                   global $woocommerce;

                           if($this->debug){
                               $this->debug_file = new WC_PARSPEIK_Debug();
                           }
            
		               	$rates             = array();
		               	$customer          = $woocommerce->customer;
		               	$update_rates      = false;
		               	$debug_response    = array();

		               	$cart_items        = $woocommerce->cart->get_cart();
		               	foreach ($cart_items as $id => $cart_item) {
		               		$cart_temp[] = $id . $cart_item['quantity'];
		               	}
		               	$cart_hash         = hash('MD5', serialize($cart_temp));
            
                        $service           = $this->ppeik_service();
                        $total_price       = (get_woocommerce_currency() == "IRT") ? $woocommerce->cart->subtotal * 10 + $service : $woocommerce->cart->subtotal + $service;
            
                        $customer_state    = $package['destination']['state'];
                        $customer_state    = explode('-', $customer_state);
                        $customer_state    = intval($customer_state[0]);
                        if( $customer_state && $customer_state >0){
                            // nothing!
                        }else{
                             if($this->debug){
                                ob_start();
                                var_dump($customer_state);
                                $text = ob_get_contents();
                                ob_end_clean();
                    
                                $this->debug_file->write('@get_shipping_response::state is not valid:'.$text);
                             }
                    
                            return false;
                        }
            
                        $customer_city      = $package['destination']['city'];
                        $customer_city      = explode('-', $customer_city);
                        $customer_city      = intval($customer_city[0]);
                        if( $customer_city && $customer_city >0){
                            // again nothing!
                        }else{
                            if($this->debug){
                                $this->debug_file->write('@get_shipping_response::city is not valid:'.$customer_city);
                            }
                    
                            return false;
                        }
            
                        $shipping_data = array(
			                             'st'             => $customer_state,
			                             'city'           => $customer_city,
			                             'weight'         => $data['weight'],
                                         'fee'            => $total_price,
                                        );

                        $cache_data         = get_transient('Parspeik_delivery_price');

			            if ($cache_data) 
                            if ($cache_data['cart_hash'] == $cart_hash && $cache_data['shipping_data']['city'] == $shipping_data['city'] && $cache_data['shipping_data']['weight'] == $shipping_data['weight'] && $cache_data['shipping_data']['fee'] == $shipping_data['fee'] )  
					            $result = unserialize($cache_data['rates']);
				            else
					            $update_rates = true;

			            else
				            $update_rates = true;
			            


			             if ($update_rates) {
                            $data = $this->ppeik_prepare($shipping_data);
				            $result = $this->ppeik_shipping($data, (int)$total_price);
                
                            if ($this->debug) {
                                ob_start();
                                var_dump($result);
                                $text = ob_get_contents();
                                ob_end_clean();
					           $this->debug_file->write('@get_shipping_response::everything is Ok:'.$text);
				            }
                

				            $cache_data['shipping_data']        = $shipping_data;
				            $cache_data['cart_hash']            = $cart_hash;
				            $cache_data['rates']                = serialize($result);
			             }
                         
			             set_transient('Parspeik_delivery_price', $cache_data, 60*60*5);
                         
                         $cost_p = intval($result['post_pish'])+intval($result['kala_khadamat_pish']);
                         $cost_s = intval($result['post_sef'])+intval($result['kala_khadamat_sef']);
                         if ($this->id == 'parspeik_pishtaz'){ //pishtaz
                            $costt = $cost_p;
                          } elseif ($this->id == 'parspeik_sefareshi'){
                            $costt = $cost_s;
                          }

			             $rate       = (get_woocommerce_currency() == "IRT") ? ((int)$costt-(int)$total_price)/10  : (int)$costt-(int)$total_price;
			
                         $my_rate = array(
					               'id' 	=> $this->id,
					               'label' => $this->title,
					               'cost' 	=> $rate,
				         );
			             $this->add_rate($my_rate);
                         
                      }
        
                      function ppeik_prepare($data = false) 
                      {
			              $data['co_id']    = $this->co_id;
                          $data['p_id']     = $this->p_id;
                          $data['password'] = $this->password;

			              return $data;
		              }

		      function ppeik_shipping($data = false, $total_price=0, $cache = false) 
                      {
		                  global $woocommerce;
            
                          if ($this->debug) {
                              $this->debug_file->write('@ppeik_shipping::here is top of function');
                          }
			
                          $this->client                      = new nusoap_client( $this->wsdl_url, true );
                          $this->client->soap_defencoding    = 'UTF-8';
                          $this->client->decode_utf8         = true;
                          
                          $response                          = $this->call("delivery_price", $data);
                          
                          if(!is_array($response)){
                              if ($this->debug) {
                                    $this->debug_file->write('@ppeik_service::'.$response['message']);
							        wc_clear_notices();
							        wc_add_notice('<p>@ppeik_shipping ParsPeik Error:</p> <p>'.$response['message'].'</p>');
				              }
                              
                              /*if ($data['service_type'] == 1){ //pishtaz
                                return  $this->get_option( 'pishtaz_default', 60000 );
                              } elseif($data['service_type'] == 0){
                                return  $this->get_option( 'sefareshi_default', 40000 );
                              }*/
                              
                              $def_res = array(
                                        'post_pish' => $this->get_option( 'pishtaz_default', 60000 ),
                                        'kala_khadamat_pish' => $total_price,
                                        'post_sef' => $this->get_option( 'sefareshi_default', 40000 ),
                                        'kala_khadamat_sef' => $total_price);
                
                              return $def_res;
                          }
                                              
            
                          if ($this->debug) {
                              ob_start();
                              var_dump($data);
                              $text = ob_get_contents();
                              ob_end_clean();
                              $this->debug_file->write('@ppeik_shipping::Everything is Ok:'.$text);
                          }

		              	  return $response;
                      }
        
                    function ppeik_service() 
                    {
                        
                         return 0; 
                         
		             }
        
                     public function call($method, $params)
	                 {
                         $result = $this->client->call($method, $params);

		             	if($this->client->fault || ((bool)$this->client->getError()))
		             	{
		             		return array('error' => true, 'fault' => true, 'message' => $this->client->getError());
		             	}
                        
                         return $result;
                     }
        
                     public function handleError($error,$status)
                     {
                         if($status =='sendprice')
                         switch ($error)
                         {
                             case 101:
                                 return 'Username or password is wrong';
                                 break;

                             case 601:
                                 return 'State and City are not match';
                                 break;

                             case 202:
                                 return 'weight or amount is invalid';
                                 break;

                              default:
                                 return false;
                                 break;

                         }
                         if($status =='register')
                         switch ($error)
                         {
                             case 101:
                                 return 'Username or password is wrong';
                                 break;
                 
                             case 202:
                                 return 'weight or amount is invalid or product name is empty';
                                 break;

                             case 201:
                                 return 'product array not set';
                                 break;
           
                              default:
                                 return false;
                                 break;

                         }
    
                     }
            } // end class
        }
        
        if ( ! class_exists( 'WC_Parspeik_Sefareshi_Method' ) ) {
			class WC_Parspeik_Sefareshi_Method extends WC_Parspeik_Pishtaz_Method {
				
                var $co_id    = "";
                var $p_id     = "";
                var $password = "";
                var $w_unit   = "";
                
				public function __construct() 
                {
				    
					$this->id                 = 'parspeik_sefareshi'; 
					$this->method_title       = __( 'پست سفارشی' ); 
					$this->method_description = __( 'ارسال توسط پست سفارشی ' );
 
					$this->init();
                    $this->account_data();
				}
 
				function init() 
                {
					
					       $this->init_form_fields(); 
					       $this->init_settings(); 
                    
                           $this->enabled		= $this->get_option( 'enabled' );
		                   $this->title 		= $this->get_option( 'title' );
		                   $this->min_amount 	= $this->get_option( 'min_amount', 0 );
                           
                           $this->w_unit 	    = strtolower( get_option('woocommerce_weight_unit') );
                           
                           //$test                = $this->get_option( 'test', 0);
                           //$this->wsdl_url      =  ($test=='yes') ? 'http://p24.ir/ws/pws.php?wsdl' : 'http://efyek.com/ws/pws.php?wsdl';
					      
					       add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                    
                }
                
                function account_data() {
                    $ins = new WC_Parspeik_Pishtaz_Method();

                    $this->co_id        = $ins->get_option( 'co_id', '' );
                    $this->p_id         = $ins->get_option( 'p_id', '' );
                    $this->password     = $ins->get_option( 'password', '' );
                    
                }
                
                function init_form_fields() 
                {
    	            global $woocommerce;

		              if ( $this->min_amount )
		          	$default_requires = 'min_amount';


    	           $this->form_fields = array(
			                     'enabled' => array(
				                     			'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
				                     			'type' 			=> 'checkbox',
				                     			'label' 		=> __( 'فعال کردن پست سفارشی', 'woocommerce' ),
				                     			'default' 		=> 'yes'
			                     			),
		                     	'title' => array(
                     				                     			'title' 		=> __( 'Method Title', 'woocommerce' ),
					                     		'type' 			=> 'text',
                     							'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                     							'default'		=> __( 'پست سفارشی', 'woocommerce' ),
                     							'desc_tip'      => true,
                     						),
                     			'min_amount' => array(
	                     						'title' 		=> __( 'Minimum Order Amount', 'woocommerce' ),
	                     						'type' 			=> 'number',
	                     						'custom_attributes' => array(
		                     						'step'	=> 'any',
	                     							'min'	=> '0'
		                     					),
		                     					'description' 	=> __( 'کمترین میزان خرید برای فعال شدن این روش ارسال.', 'woocommerce' ),
			                     				'default' 		=> '0',
			                     				'desc_tip'      => true,
			                     				'placeholder'	=> '0.00'
					                     	),
                                'sefareshi_default' => array(
                     	                     	'title' 		=> __( 'هزینه‌ی پیش‌فرض', 'woocommerce' ),
					                     		'type' 			=> 'text',
                     							'description' 	=> __( 'هنگامی که به دلایلی امکان استعلام هزینه‌ی ارسال از سرویس پارس‌پیک ممکن نباشد، این مبلغ نمایش داده‌خواهد شد.', 'woocommerce' ),
					                     		'default'		=> 40000,
		                     					'desc_tip'      => true,
	                     					),
		                     	);

                }
                
                public function admin_options() 
                {
    	           ?>
    	           <h3><?php _e( 'پست سفارشی' ); ?></h3>
    	           <table class="form-table">
    	           <?php
    		          // Generate the HTML For the settings form.
    		          $this->generate_settings_html();
    	           ?>
		          </table>
                <?php
                }
 
                public function calculate_shipping( $package ) 
                {
                           global $woocommerce;
		                   $customer = $woocommerce->customer;

                           if( empty($package['destination']['city'])) {
                               $rate = array(
			               		'id' 		=> $this->id,
			               		'label' 	=> $this->title,
			               		'cost' 		=> 0
			               	   );
                               $this->add_rate( $rate );
                           }
                          
			               $this->shipping_total = 0;
		              	   $weight = 0;
                           $unit = ($this->w_unit == 'g') ? 1 : 1000;
            
			               $data = array();
			               if (sizeof($woocommerce->cart->get_cart()) > 0 && ($customer->get_shipping_city())) {

				              foreach ($woocommerce->cart->get_cart() as $item_id => $values) {

					              $_product = $values['data'];

					              if ($_product->exists() && $values['quantity'] > 0) {

						              if (!$_product->is_virtual()) {

							              $weight += $_product->get_weight() * $unit * $values['quantity'];
					              	  }
					             }
				              } //end foreach
                              
				              $data['weight']         = $weight;
                              $data['service_type']   = 0;  // sefareshi
				              if ($weight) {
					              $this->get_shipping_response($data, $package);
				              }
			              }
                         
                      }
			     } // end class
		}
	} // end function
	add_action( 'woocommerce_shipping_init', 'parspeik_shipping_method_init' );
 
	function add_parspeik_shipping_method( $methods ) {
            $methods[] = 'WC_Parspeik_Pishtaz_Method';
            $methods[] = 'WC_Parspeik_Sefareshi_Method';
		return $methods;
	}
	add_filter( 'woocommerce_shipping_methods', 'add_parspeik_shipping_method' );


class WC_PARSPEIK_Debug {
    var $handle = null;
    public function __construct() 
    {

    }
    
    private function open() 
    {
		if ( isset( $this->handle ) )
			return true;

		if ( $this->handle = @fopen( untrailingslashit( plugin_dir_path( __FILE__ ) ).'/log/ppeik_log.txt', 'w' ) )
			return true;

		return false;
	}
    
    public function write($text) 
    {
        return ;
        if ( $this->open() && is_resource( $this->handle) ) {
			$time = date_i18n( 'm-d-Y @ H:i:s -' ); //Grab Time
			@fwrite( $this->handle, $time . " " . $text . "\n" );
		}
		@fclose($this->handle);
    }
    
    public function sep() 
    {
        $this->write('------------------------------------'."\n");
    }
}     

class WC_PARSPEIK {
    var $ppeik_carrier;
    var $debug_file = "";
    var $email_handle;
    private $client = null;
    
     public function __construct() 
     {
     
        add_action( 'woocommerce_checkout_order_processed', array( $this, 'save_order'), 10, 2);
        
        add_action( 'woocommerce_before_checkout_form', array( $this, 'calc_shipping_after_login'));
        add_action( 'woocommerce_cart_collaterals', array( $this, 'remove_shipping_calculator'));
        add_action( 'woocommerce_calculated_shipping', array( $this, 'set_state_and_city_in_cart_page'));
        add_action( 'woocommerce_cart_collaterals', array( $this, 'add_new_calculator'), 20);
        add_action( 'woocommerce_before_cart', array( $this, 'remove_proceed_btn'));
        add_action( 'woocommerce_cart_totals_after_order_total', array( $this, 'add_proceed_btn'));
        
        add_action( 'woocommerce_product_options_sku', array( $this, 'add_ppeik_id_filed'));
        add_action( 'woocommerce_process_product_meta_simple', array( $this, 'save_ppeik_id_field'));
        add_action( 'woocommerce_process_product_meta_variable', array( $this, 'save_ppeik_id_field'));
        add_action( 'woocommerce_process_product_meta_external', array( $this, 'save_ppeik_id_field'));
        
        add_filter( 'woocommerce_available_payment_gateways', array( $this, 'get_available_payment_gateways'), 10, 1);
        add_filter( 'woocommerce_locate_template', array( $this, 'new_template'), 50, 3); 
        add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'remove_free_text'), 10, 2);
        add_filter( 'woocommerce_default_address_fields', array( $this, 'remove_country_field'), 10, 1);
        add_action( 'admin_enqueue_scripts', array( $this, 'add_css_file'));
        add_action( 'admin_enqueue_scripts', array( $this, 'overriade_js_file'), 11);
        
        add_action( 'update_ppeik_orders_state', array( $this, 'update_ppeik_orders_state'));
        
        add_action( 'woocommerce_before_checkout_form', array( $this, 'show_mobile_message'));
        
        add_filter( 'woocommerce_currencies', array( $this, 'check_currency'), 20 );
        add_filter( 'woocommerce_currency_symbol', array( $this, 'check_currency_symbol'), 20, 2);
        
        add_action( 'woocommerce_after_checkout_validation', array( $this, 'post_code_validation'));
        add_filter('woocommerce_states', array( $this, 'woocommerce_states'));
        
        //add_action( 'woocommerce_thankyou', array( $this, 'show_invoice'), 5 );
        
       // if ( is_page( get_option('woocommerce_cart_page_id' ) ) )
        if(! is_admin())
			wp_enqueue_script( 'ppeik-list', untrailingslashit( plugins_url( '/', __FILE__ ) ) . '/js/province_city_list.js', array(), 1.0 );

        if(!class_exists('WC_Parspeik_Pishtaz_Method') && function_exists('parspeik_shipping_method_init') && class_exists('WC_Shipping_Method'))
            parspeik_shipping_method_init();
        
    }
    
    public function woocommerce_states($st) 
    {
        return false;
    } 
    
    public function get_available_payment_gateways( $_available_gateways)
    {
        global $woocommerce;
        
        $shipping_method = $woocommerce->session->chosen_shipping_method;
        if(in_array( $shipping_method, array('parspeik_pishtaz' ,'parspeik_sefareshi' ))){   
            foreach ( $_available_gateways as $gateway ) :

			     if ($gateway->id == 'cod') $new_available_gateways[$gateway->id] = $gateway;

		    endforeach;
        
        return $new_available_gateways;
        }
        
        return $_available_gateways;
    }
    
    public function new_template( $template, $template_name, $template_path)
    {
        global $woocommerce;
        
        $shipping_method = $woocommerce->session->chosen_shipping_method;
        
        if( $template_name =='checkout/form-billing.php' OR $template_name =='checkout/form-shipping.php')
            return untrailingslashit( plugin_dir_path( __FILE__ ) ). '/'. $template_name;
        
        return $template;
    }
    
    public function save_order($id, $posted)
    {
        global $woocommerce;

        $this->email_handle =  $woocommerce->mailer();
      
        $order = new WC_Order($id);
        if(!is_object($order))
            return;
              
        $is_ppeik = false; 
        if ( $order->shipping_method ) {
            if( in_array($order->shipping_method, array('parspeik_pishtaz' ,'parspeik_sefareshi' )) ) {
                $is_ppeik = true;
                $shipping_methods = $order->shipping_method;
            }
            
		} else {
            $shipping_s = $order->get_shipping_methods();

			foreach ( $shipping_s as $shipping ) {
			    if( in_array($shipping['method_id'], array('parspeik_pishtaz' ,'parspeik_sefareshi' )) ) {
                    $is_ppeik = true;
                    $shipping_methods = $shipping['method_id'];
                    break;
                }
			}
        }
        if( !$is_ppeik || $order->payment_method != 'cod' )
            return;
           
        $this->ppeik_carrier      = new WC_Parspeik_Pishtaz_Method();
        $service_type             = ($shipping_methods == 'parspeik_pishtaz') ? 1 : 0;
        if($this->ppeik_carrier->debug){
           $this->debug_file = new WC_PARSPEIK_Debug();
           $this->debug_file->sep();
         }
        
        $unit = ($this->ppeik_carrier->w_unit == 'g') ? 1 : 1000;
        
        $orders = array();
        foreach ( $order->get_items() as $item ) {

				if ($item['product_id']>0) {
				    $_product = $order->get_product_from_item( $item );
				    $productName = str_ireplace('^', '', $_product->get_title()); // edit @ 02 14
                    $productName = str_ireplace(';', '', $productName);
                    $price  = $order->get_item_total( $item); 
                    $price  = (get_woocommerce_currency() == "IRT") ? (int)$price*10: (int)$price;
                    $ID = $_product->id;
                    $id_ppeik = get_post_meta( $ID, '_ppeik_id', true);
                    if (intval($id_ppeik) > 0)
                        $iid = $id_ppeik;
                    else
                        $iid = $item['product_id'];
				    
                    $orders[] = array(
                                'id' => $iid,
                                'prd_name' => $productName,
                                'weight' => intval($_product->weight * $unit),
                                'price' => $price,
                                'quantity' => (int)$item['qty']
                                );

				}

			}
            
            $customer_st = $order->shipping_state;
            $customer_st = explode('-', $customer_st);
            $customer_st = intval($customer_st[0]);
            if( $customer_st && $customer_st >0){
                
            }else{
                if($this->ppeik_carrier->debug){
                    $this->debug_file->write('@save_order::city is not valid');
                    die('state is not valid');
                }
                    
                return false;
            }
            
            $customer_city = $order->shipping_city;
            $customer_city = explode('-', $customer_city);
            $customer_city = intval($customer_city[0]);
            if( $customer_city && $customer_city >0){
                
            }else{
                if($this->ppeik_carrier->debug){
                    $this->debug_file->write('@save_order::city is not valid');
                    die('city is not valid');
                }
                    
                return false;
            }
        
        $params = array(
         'prd_list'         =>  $orders,
         'name'             =>  $order->billing_first_name.' '.$order->billing_last_name,
         'email'            =>  $order->billing_email,
         'phone'            =>  $order->billing_phone,
         'mobile'           =>  $order->billing_phone,
         'st'               =>  $customer_st,
         'city'             =>  $customer_city,
         'postal_code'      =>  $order->billing_postcode,
         'address'          =>  $order->billing_address_1 . ' - '. $order->billing_address_2,
         'post_type'        =>  $service_type,
         'comment'          =>  $order->customer_note,
         'ip'               =>  $this->getIp(),
         'co_id'            =>  $this->ppeik_carrier->co_id,
         'p_id'             =>  $this->ppeik_carrier->p_id,
         'password'         =>  $this->ppeik_carrier->password
         ); 
         
         list($res, $response) = $this->add_order( $params, $order );
        
         if ($res === false) {
                    if ($this->ppeik_carrier->debug) {
                            ob_start();
                            var_dump($params);
                            $text = ob_get_contents();
                            ob_end_clean();
                            $this->debug_file->write('@save_order::error in registering by webservice:'.$response.'::'.$text);
					}
                    $order->update_status( 'pending', 'Parspeik : '.$response );
                    $this->trigger($order->id, $order, '::سفارش در سیستم پارس پیک ثبت نشد::');

         } elseif($res === true) {
            
            if ($this->ppaik_carrier->debug) {
                            $this->debug_file->write('@save_order::everything is Ok');
							wc_clear_notices();
							wc_add_notice('<p>Parspeik:</p> <p>Everthing is Ok!</p>');
			}
            $this->trigger($order->id, $order, true);
            update_post_meta($id, '_ppeik_tracking_code', trim($response));
            $html_note = 'شماره سفارش در سیستم پارس پیک';
            $html_note .= '</br>';
            $html_note .= trim($response);
            $order->add_order_note($html_note);
 
         } else {
            $order->update_status( 'pending', 'Parspeik : error in webservice, Order not register!' );
            $this->trigger($order->id, $order, false);    
         }
        
    }
    
    public function add_order( $data, $order )
    {
        global $woocommerce;
        
        if ($this->ppeik_carrier->debug) {
			$this->debug_file->write('@add_order::here is top of function');
        }
        
        $this->ppeik_carrier->client = new nusoap_client( $this->ppeik_carrier->wsdl_url, true );
        $this->ppeik_carrier->client->soap_defencoding = 'UTF-8';
        $this->ppeik_carrier->client->decode_utf8 = true;
            
        $response  = $this->ppeik_carrier->call("order_register", $data);
            
        if(is_array($response) && $response['error']){
            if ($this->ppeik_carrier->debug) {
                            $this->debug_file->write('@ppeik_service::'.$response['message']);
							wc_clear_notices();
							wc_add_notice('<p>@add_order Parspeik Error:</p> <p>'.$response['message'].'</p>');
				}
                
                return array(false, $this->ppeik_carrier->handleError($response['message'],'register'));
        }
            
        //mkobject($response);
        if (count($response) == 3) {
            if ($this->ppeik_carrier->debug) {
                ob_start();
                var_dump($response);
                $text = ob_get_contents();
                ob_end_clean();
                
			   $this->debug_file->write('@add_order::An error : '.$text);
            }
            
            return array(false, $this->ppeik_carrier->handleError($response,'register'));
        }
        if ($this->ppeik_carrier->debug) {
                ob_start();
                var_dump($response);
                $text = ob_get_contents();
                ob_end_clean();
                
			   $this->debug_file->write('@add_order::everything is Ok: '.$text);
        }

        return array(true, $response);
        
    }
    
    public function show_invoice( $order_id )
    {
        $factor = get_post_meta( $order_id, '_ppeik_tracking_code', true);
        
        if( empty($factor))
            return;
        $html = '<p>';
        $html .= 'کد رهگیری سفارش شما.';
        $html .= '</br>';
        $html .= 'این کد را نزد خود نگه‌دارید و با مراجعه به سایت پست از وضعیت سفارش خود آگاه شوید. ';
        $html .= '</br>'. $factor .'</p><div class="clear"></div>';
        
        echo $html;
        return;
    }
    
    function trigger( $order_id, $order, $subject= false ) 
    {
		global $woocommerce;
        if(!$subject) {
            $message = $this->email_handle->wrap_message(
		            		'سفارش در سیستم پارس پیک ثبت نشد',
		            		sprintf( 'سفارش  %s در سیستم پارس پيک ثبت نشد، لطفن بصورت دستی اقدام به ثبت سفارش در پنل شرکت پارس پيک نمایید.', $order->get_order_number() )
						);

		  $this->email_handle->send( get_option( 'admin_email' ), sprintf('سفارش  %s در سیستم پارس پیک ثبت نشد', $order->get_order_number() ), $message );
        }else{
            $message = $this->email_handle->wrap_message(
		            		'سفارش با موفقیت در سیستم پارس پیک ثبت گردید',
		            		sprintf( 'سفارش  %s با موفقیت در سیستم پارس پیک ثبت گردید.', $order->get_order_number() )
						);

		  $this->email_handle->send( get_option( 'admin_email' ), sprintf( 'سفارش %s در سیستم پارس پیک با موفقیت ثبت گردید', $order->get_order_number() ), $message );
        }
	}
    
    public function calc_shipping_after_login( $checkout ) 
    {
        global $woocommerce;
        
        $state 		= $woocommerce->customer->get_shipping_state() ;
		$city       = $woocommerce->customer->get_shipping_city() ;
        
        if( $state && $city ) {
            $woocommerce->customer->calculated_shipping( true );
        } else {
  
            wc_add_notice( 'پیش از وارد کردن مشخصات و آدرس، لازم است استان و شهر خود را مشخص کنید.');
            $cart_page_id 	= get_option('woocommerce_cart_page_id' );//wc_get_page_id( 'cart' );
			wp_redirect( get_permalink( $cart_page_id ) );
        }

    }
    
    public function getIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
        {
             $ip = $_SERVER['HTTP_CLIENT_IP'];
        } 
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } 
        else 
        {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
    
    public function post_code_validation( $posted )
    {
        $postcode = $posted['billing_postcode'];
        
        if ( !preg_match("/([1]|[3-9]){10}/", $postcode) or strlen( trim( $postcode ) ) !=10 )
			wc_add_notice( 'کد پستی وارد شده معتبر نیست. کد پستی عددی است 10 رقمی فاقد رقم های 0 و 2 .', 'error' );
    }
    
    public function show_mobile_message()
    {
        $msg = 'لطفاً در صورت امکان در فیلد مربوط به تلفن، شماره‌ی تلفن همراه خود را وارد کنید';
        echo '<div class="woocommerce-info">'.$msg.'</div>';
    }
    
    public function add_ppeik_id_filed()
    {
        global $thepostid;
        
        woocommerce_wp_text_input( array( 'id' => '_ppeik_id', 'label' => '<abbr title="'. __( 'کد کالا در پارس‌پیک', 'woocommerce' ) .'">' . __( 'کد پارس پیک (اختیاری)','woocommerce' ) . '</abbr>', 'desc_tip' => 'true', 'description' => __( 'کد این کالا در سیستم پارس پیک. دقت کنید که این کد بصورت یک عدد است.', 'woocommerce' ) ) );
    }
    
    
    public function save_ppeik_id_field($id)
    {
        update_post_meta( $id, '_ppeik_id', $_POST['_ppeik_id'] );
    }
    
    public function remove_shipping_calculator()
    {
        if( get_option('woocommerce_enable_shipping_calc')!='no' )
            update_option('woocommerce_enable_shipping_calc', 'no');
    }
    
    public function remove_free_text( $full_label, $method)
    {
        global $woocommerce;
        
        $shipping_city = $woocommerce->customer->city;
        if(!in_array( $method->id, array('parspeik_pishtaz' ,'parspeik_sefareshi' )))
            return $full_label;

        if(empty($shipping_city))
            return $method->label;
        
        return $full_label;
        
    }
    
    public function remove_country_field( $fields )
    {
        unset( $fields['country'] );
        
        return $fields;
    }
    
    public function add_css_file()
    {
        global $typenow;
        
        if ( $typenow == '' || $typenow == "product" || $typenow == "service" || $typenow == "agent" ) {
             wp_enqueue_style( 'woocommerce_admin_override', untrailingslashit( plugins_url( '/', __FILE__ ) ) . '/css/override.css', array('woocommerce_admin_styles') );
        }
    }
    
    public function overriade_js_file()
    {
        global $woocommerce;
        
        wp_deregister_script( 'jquery-tiptip' );
        wp_register_script( 'jquery-tiptip', untrailingslashit( plugins_url( '/', __FILE__ ) ) . '/js/jquery.tipTip.min.js', array( 'jquery' ), $woocommerce->version, true );
    }
    
    public function set_state_and_city_in_cart_page()
    {
        global $woocommerce;
        
        $state 		= (woocommerce_clean( $_POST['calc_shipping_state'] )) ? woocommerce_clean( $_POST['calc_shipping_state'] ) : $woocommerce->customer->get_shipping_state() ;
		$city       = (woocommerce_clean( $_POST['calc_shipping_city'] )) ? woocommerce_clean( $_POST['calc_shipping_city'] ) : $woocommerce->customer->get_shipping_city() ;

        if ( $city && $state) {
				$woocommerce->customer->set_location( 'IR', $state, '', $city );
				$woocommerce->customer->set_shipping_location( 'IR', $state, '', $city );
			}else{
                wc_clear_notices();
                wc_add_notice('استان و شهر را انتخاب کنید. انتخاب هر دو فیلد الزامی است.', 'error');
			}
    }
    
    public function add_new_calculator()
    {
        global $woocommerce;
        
        $have_city = true;
        if( ! $woocommerce->customer->get_shipping_city()){
            echo '<style> div.cart_totals{display:none!important;}
                          p.selectcitynotice {display:block;}
                    </style>';
            
            $have_city = false;
        }
    
        include('cart/shipping-calculator.php');
    }
    
    public function remove_proceed_btn()
    {
        echo '<style>input.checkout-button,.wc-proceed-to-checkout{ display:none!important;}
                    .woocommerce .cart-collaterals .cart_totals table, .woocommerce-page .cart-collaterals .cart_totals table { border:0px; }
              </style>';
    }
    
    public function add_proceed_btn()
    {
        
        echo '<div style="padding: 1em 0px;"><a href="'.esc_url( wc_get_checkout_url() ).'" class="checkout-button button alt wc-forward">
	'.__( 'Proceed to Checkout', 'woocommerce' ).'
            </a></div>';
    }
    
    public function update_ppeik_orders_state()
    {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdab->prepare("SELECT meta.meta_value, posts.ID FROM {$wpdb->posts} AS posts

		LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
		LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
		LEFT JOIN {$wpdb->terms} AS term USING( term_id )

		WHERE 	meta.meta_key 		= '_ppeik_tracking_code'
        AND     meta.meta_value     != ''
		AND 	posts.post_type 	= 'shop_order'
		AND 	posts.post_status 	= 'publish'
		AND 	tax.taxonomy		= 'shop_order_status'
		AND		term.slug			IN ('processing', 'on-hold', 'pending')
	   "));
       
       if ( $results ) {
            $tracks = array();
	        foreach( $results as $result ) {
	           $tracks['code'][] = $result->meta_value;
               $tracks['id'][]   = $result->ID;

		    }
	   }
       
       if( empty($tracks))
            return ;

        if(!is_object($this->ppeik_carrier))
            $this->ppeik_carrier      = new WC_Parspeik_Pishtaz_Method();
        
        $this->ppeik_carrier->client = new nusoap_client( $this->ppeik_carrier->wsdl_url, true );
        $this->ppeik_carrier->client->soap_defencoding = 'UTF-8';
        $this->ppeik_carrier->client->decode_utf8 = true;
        
        //for($i = 0; $i < 5; $i++)
        //{  
            $data = array(
                'list'              =>  $tracks['code'],
                'co_id'             =>  $this->ppeik_carrier->co_id,
                'p_id'              =>  $this->ppeik_carrier->p_id,
                'password'          =>  $this->ppeik_carrier->password
                        ); 
            $response  = $this->ppeik_carrier->call("orders_status", $data);
            
            if(is_array($response) && $response['error']){
                if ($this->ppeik_carrier->debug) {
                            $this->debug_file->write('@update_ppeik_orders_state::'.$response['message']);
				}
                return;
            }
            
            //pp_mkobject($response);
            
            if ($this->ppeik_carrier->debug) {
                ob_start();
                var_dump($response);
                $text = ob_get_contents();
                ob_end_clean();
                
			   $this->debug_file->write('@update_ppeik_orders_state::everything is Ok: '.$text);
            }
            
            //$res  = explode(';', $response->GetOrderStateResult);
            $j = 0;
            foreach( $response as $res) {
                
            
            $status = false;
            switch($res) {
                /*case '0': // سفارش جدید
                       $status = 'pending';
                       break; */
                //case '1': // آماده به ارسال
                case '2': // ارسال شده
                case '3':  //توزیع شده
                       /*$status = 'processing';
                       break; */
                case '5': // وصول شده
                       $status = 'completed';
                       break; 
                case '6': // برگشتی اولیه
                case '7': //برگشتی نهایی
                       $status = 'refunded';
                       break; 
                case '0': // انصرافی
                       $status = 'cancelled';
                       break; 
            }
            if ( $status )
            {
                $order = new WC_Order( $tracks['id'][$j] );
	            $order->update_status( $status, 'سیستم پارس پیک @ ' );
            }
            $j++;
            } //end foreach
            
         //}// end for   
            
    }
    
    // thanks to  woocommerce parsi
    public function check_currency( $currencies ) 
    {
        if(empty($currencies['IRR'])) 
            $currencies['IRR'] = __( 'ریال', 'woocommerce' );
        if(empty($currencies['IRT'])) 
            $currencies['IRT'] = __( 'تومان', 'woocommerce' );
        
        return $currencies;
    }
    
    public function check_currency_symbol( $currency_symbol, $currency ) {

        switch( $currency ) {
            case 'IRR': $currency_symbol = 'ریال'; break;
            case 'IRT': $currency_symbol = 'تومان'; break;
        }
        
        return $currency_symbol;
          
    }
}
     
    $GLOBALS['PARSPEIK'] = new WC_PARSPEIK();

    function pp_mkobject(&$data) 
    {
		$numeric = false;
		foreach ($data as $p => &$d) {
			if (is_array($d))
				pp_mkobject($d);
			if (is_int($p))
				$numeric = true;
		}
		if (!$numeric)
			settype($data, 'object');
	} 
}
