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

        }
        
        public function prep_sale($new_transaction = false) {
           
        }

        public function ajax_buy() {

			// Construct the checkout box content
			$content  = $this->checkout_header();
			$content .= $this->checkout_logo();
			$content .= $this->checkout_order();
			$content .= $this->checkout_cancel();
			$content .= $this->checkout_footer();

			// Return a JSON response
			$this->send_json( $content );

		}

        public function checkout_page_body() {
          
        
            echo wp_kses_post( $this->checkout_header() );
			echo wp_kses_post( $this->checkout_logo( false ) );

			echo wp_kses_post( $this->checkout_order() );
			echo wp_kses_post( $this->checkout_cancel() );

			echo wp_kses( 
				$this->checkout_footer(), 
				array( 
					'div' => array( 'class' => array() ), 
					'button' => array( 
						'type' => array(), 
						'id' => array(), 
						'data-act' => array(), 
						'data-value' => array(), 
						'class' => array(), 
					),
					'input' => array( 
						'type' => array(), 
						'name' => array(), 
						'value' => array()
					)
				) 
			);

           

              
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