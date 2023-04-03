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
                        'currency' => 'IDR',
                        'item_name' => __('Purchase of myCRED %plural%', 'mycred'),
                        'exchange' => $default_exchange
                    )
                ),
                $gateway_prefs
            );
        }
        
        public function returning() {

			if ( isset( $_REQUEST['tx'] ) && isset( $_REQUEST['st'] ) && $_REQUEST['st'] == 'Completed' ) {
				$this->get_page_header( __( 'Success', 'mycred' ), $this->get_thankyou() );
				echo '<h1 style="text-align:center;">' . esc_html__( 'Thank you for your purchase', 'mycred' ) . '</h1>';
				$this->get_page_footer();
				exit;
			}

		}
    
        
        public function prep_sale($new_transaction = false) {
            $host = 'app.midtrans.com';
                if($this->sandbox_mode)
                    $host = 'app.sandbox.midtrans.com';
            $snapURL= 'https://'.$host.'/snap/snap.js';

            try {
                
                $transaction_details = array(
                    'order_id' => $this->transaction_id,
                    'gross_amount' => $this->cost
                );

                $request_body = 
                    json_encode(
                        array(
                            'transaction_details' => $transaction_details
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
                $this->errors[] = $e->getMessage();
            }

            if(empty( $this->errors)) {
                $snapToken =  json_decode(wp_remote_retrieve_body( $create_snap_transactions ))->token;
                //$paymentUrl = json_decode(wp_remote_retrieve_body( $create_snap_transactions ))->redirect_url;
                // header('Location: '.$paymentUrl);
                ?>
                <html>
                    <head>
                <script type="text/javascript" src='<?=$snapURL?>' 
                 data-client-key='<?=$this->prefs['client_key']?>'></script>
            </head>
            <body>
            <script type="text/javascript">
                 window.snap.pay('<?=$snapToken?>', {
          onSuccess: function(result){
            /* You may add your own implementation here */
            alert("payment success!"); console.log(result);
          },
          onPending: function(result){
            /* You may add your own implementation here */
            alert("wating your payment!"); console.log(result);
          },
          onError: function(result){
            /* You may add your own implementation here */
            alert("payment failed!"); console.log(result);
          },
          onClose: function(){
            /* You may add your own implementation here */
            alert('you closed the popup without finishing the payment');
          }
        });
                </script>
            </body>
                </html>
               <?php
            }

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