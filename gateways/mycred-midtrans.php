<?php
if ( ! defined( 'MYCRED_MIDTRANS_VERSION' ) ) exit;

if (!class_exists('myCred_Midtrans')):
    class myCred_Midtrans extends myCRED_Payment_Gateway
    {
        public function __construct($gateway_prefs)
        {
            $types = mycred_get_types();
            $default_exchange = array();

            foreach ($types as $type => $label)
                $default_exchange[$type] = 1;

            parent::__construct(
                array(
                    'id' => 'mycred-midtrans',
                    'label' => 'myCred Midtrans',
                    'gateway_logo_url' => plugins_url('assets/images/logo-blue.svg', MYCRED_MIDTRANS),
                    'defaults' => array(
                        'sandbox' => 0,
                        'merchant_id' => '',
                        'client_key' => '',
                        'server_key' => '',
                        'payment_methods' => '',
                        'snapToken' => '',
                        'currency' => 'IDR',
                        'item_name' => __('Purchase of myCRED %plural%', 'mycred'),
                        'exchange' => $default_exchange
                    )
                ),
                $gateway_prefs
            );
        }

  
    
        public function returning() {
            $errors =  array();
            if(isset($_REQUEST['order_id'])){
                $host = 'api.midtrans.com';
                if($this->sandbox_mode)
                    $host = 'api.sandbox.midtrans.com';
                
                $pending_post_id = $_REQUEST['order_id'];
                $parts = explode("-", $pending_post_id);
                $first_part = $parts[0];
                $pending_payment = $this->get_pending_payment($first_part);

                if($pending_payment!=null)
                {
                  //  var_dump($pending_payment);
                  try {
                    $order_id = $pending_post_id;
                    $retrieve_status = wp_remote_get( 'https://'.$host.'/v2/'.$order_id.'/status', 
                        array(
                            'headers'     => 
                            array(
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json',
                                'Authorization' => 'Basic ' . base64_encode($this->prefs['server_key'] . ':')
                            ),
                        ) 
                     );
                    
                     $status =  json_decode(wp_remote_retrieve_body( $retrieve_status ))->status_code;
    
                } catch ( \Exception $e ) {
    
                    $errors[] = $e->getMessage();
    
                }

                
    
                if ( empty( $errors ) && $status == '200' ) {
                    $this->complete_payment($pending_payment,$first_part);
                    $this->trash_pending_payment( $first_part);
                }
                }
                    
    
       
    
            }
        }
        
        public function prep_sale($new_transaction = false) {

            $host = 'app.midtrans.com';
            if($this->sandbox_mode)
                $host = 'app.sandbox.midtrans.com';
            
            $errors = array();

            try {
            
            $transaction_details = array(
                'order_id' => $this->transaction_id .'-mycred-'.time().'-'.rand(),
                'gross_amount' => $this->cost
            );



            $callbacks = array(
                'finish' => $this->get_thankyou()
            );

            $gopay = array(
                'enable_callback' => true,
                'callback_url' => $this->get_thankyou()
            );

            $request_body = 
                json_encode(
                    array(
                        'transaction_details' => $transaction_details,
                        'callbacks' => $callbacks,
                       // 'enabled_payments' =>["gopay"],
                        'gopay' => $gopay
                    )
                );

            $create_snap_transactions = 
                wp_remote_post('https://'.$host.'/snap/v1/transactions',
                    array(
                        'method' => 'POST',
                        'headers' =>
                            array(
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json',
                                'Authorization' => 'Basic ' . base64_encode($this->prefs['server_key'] . ':')
                            ),
                        'body' => $request_body
                    )
            );
        } catch( \Exception $e) {
            $errors[] = $e->getMessage();
          
            
        }

        if(empty( $errors)) 
        {
            $data = json_decode(wp_remote_retrieve_body( $create_snap_transactions ));
     
            $this->prefs['snapToken'] =  $data->token;
        }
         
        }

        public function ajax_buy() {

			// Construct the checkout box content
			$content  = $this->checkout_header();
			$content .= $this->checkout_logo();
			$content .= $this->checkout_order();
			$content .= $this->checkout_cancel();
			$content .= $this->checkout_footer();
            $content .= $this->checkout_javascript();

			// Return a JSON response
			$this->send_json( $content );

		}

        public function checkout_javascript(){
            $content = '';

            $host = 'app.midtrans.com';
            if($this->sandbox_mode)
                $host = 'app.sandbox.midtrans.com';
            $snapURL= 'https://'.$host.'/snap/snap.js';


            $content .= ' <script type="text/javascript" src='. $snapURL .'  data-client-key='. $this->prefs['client_key'] .'></script>';
            $content .= '<script>';
            $content .= '  var payButton = document.getElementById("checkout-action-button");';
            $content .= ' payButton.onclick = function(event) {
                snap.pay("'.$this->prefs['snapToken'] .'",{uiMode: "auto"});
            }';
            $content .= '</script>';


            return apply_filters( 'mycred_buycred_checkout_javascript', $content, $this );
            
           
        }

        public function checkout_page_body() {

            $content  = $this->checkout_header();
			$content .= $this->checkout_logo();
			$content .= $this->checkout_order();
			$content .= $this->checkout_cancel();
			$content .= $this->checkout_footer();
            $content .= $this->checkout_javascript();

            echo $content;
        
        //     echo wp_kses_post( $this->checkout_header() );
		// 	echo wp_kses_post( $this->checkout_logo( false ) );

		// 	echo wp_kses_post( $this->checkout_order() );
		// 	echo wp_kses_post( $this->checkout_cancel() );

		// 	echo wp_kses( 
		// 		$this->checkout_footer(), 
		// 		array( 
		// 			'div' => array( 'class' => array() ), 
		// 			'button' => array( 
		// 				'type' => array(), 
		// 				'id' => array(), 
		// 				'data-act' => array(), 
		// 				'data-value' => array(), 
		// 				'class' => array(), 
		// 			),
		// 			'input' => array( 
		// 				'type' => array(), 
		// 				'name' => array(), 
		// 				'value' => array()
		// 			)
		// 		) 
		// 	);

        //    echo wp_kses_post( $this->checkout_javascript() );
            
          

              
        }


        public function preferences()
        {
            $prefs = $this->prefs;
            ?>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <h3>
                        <?php esc_html_e('Details', 'mycred'); ?>
                    </h3>
                    <div class="form-group">
                        <label for="<?php echo esc_attr($this->field_id('merchant_id')); ?>"><?php esc_html_e('Merchant ID', 'mycred'); ?></label>
                        <input type="text" name="<?php echo esc_attr($this->field_name('merchant_id')); ?>"
                            id="<?php echo esc_attr($this->field_id('merchant_id')); ?>"
                            value="<?php echo esc_attr($prefs['merchant_id']); ?>" class="form-control" />
                    </div>
                    <div class="form-group">
                        <label for="<?php echo esc_attr($this->field_id('client_key')); ?>"><?php esc_html_e('Client Key', 'mycred'); ?></label>
                        <input type="text" name="<?php echo esc_attr($this->field_name('client_key')); ?>"
                            id="<?php echo esc_attr($this->field_id('client_key')); ?>"
                            value="<?php echo esc_attr($prefs['client_key']); ?>" class="form-control" />
                    </div>
                    <div class="form-group">
                        <label for="<?php echo esc_attr($this->field_id('server_key')); ?>"><?php esc_html_e('Server Key', 'mycred'); ?></label>
                        <input type="text" name="<?php echo esc_attr($this->field_name('server_key')); ?>"
                            id="<?php echo esc_attr($this->field_id('server_key')); ?>"
                            value="<?php echo esc_attr($prefs['server_key']); ?>" class="form-control" />
                    </div>
                    <div class="form-group">
                        <label for="<?php echo esc_attr($this->field_id('item_name')); ?>"><?php esc_html_e('Item Name', 'mycred'); ?></label>
                        <input type="text" name="<?php echo esc_attr($this->field_name('item_name')); ?>"
                            id="<?php echo esc_attr($this->field_id('item_name')); ?>"
                            value="<?php echo esc_attr($prefs['item_name']); ?>" class="form-control" />
                    </div>

                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <h3>
                        <?php esc_html_e('Setup', 'mycred'); ?>
                    </h3>

                    <div class="form-group">
                        <label>
                            <?php esc_html_e('Exchange Rates', 'mycred'); ?>
                        </label>

                        <?php $this->exchange_rate_setup(); ?>

                    </div>
                </div>
            </div>
            <?php
        }

        public function sanitise_preferences( $data ) {

			$new_data              = array();

			$new_data['sandbox']   = ( isset( $data['sandbox'] ) ) ? 1 : 0;
            $new_data['merchant_id']  = sanitize_text_field( $data['merchant_id'] );
			$new_data['client_key']   = sanitize_text_field( $data['client_key'] );
			$new_data['server_key'] = sanitize_text_field( $data['server_key'] );
			if ( isset( $data['exchange'] ) ) {
				foreach ( (array) $data['exchange'] as $type => $rate ) {
					if ( $rate != 1 && in_array( substr( $rate, 0, 1 ), array( '.', ',' ) ) )
						$data['exchange'][ $type ] = (float) '0' . $rate;
				}
			}
			$new_data['exchange']  = $data['exchange'];
			
			return $new_data;

		}

    }
endif;

?>