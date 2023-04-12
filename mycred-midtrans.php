<?php

/**
 * Plugin Name: myCred Midtrans
 */

if (!class_exists('buyCred_Midtrans_Gateway_Core')):

    final class buyCred_Midtrans_Gateway_Core
    {

        public $version = '1.0.0';

        protected static $_instance = NULL;

        // This is a static method that creates and returns an instance of the class. The method checks if the current instance is null, and if so, it creates a new instance of the class using the "new" keyword and assigns it to the $_instance variable before returning it. This ensures that only one instance of the class is created and used throughout the application, which can be useful in scenarios where you want to avoid creating multiple instances of the same object.
        public static function instance()
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        private function define($name, $value)
        {
            if (!defined($name))
                define($name, $value);
        }

        public function file($required_file)
        {
            if (file_exists($required_file))
                require_once $required_file;
        }

        public function __construct()
        {
            $this->define_constants();
            $this->mycred();
        }

        private function define_constants()
        {
            $this->define('MYCRED_MIDTRANS_VERSION', $this->version);
            $this->define('MYCRED_MIDTRANS', __FILE__);
            $this->define('MYCRED_MIDTRANS_ROOT_DIR', plugin_dir_path(MYCRED_MIDTRANS));
            $this->define('MYCRED_MIDTRANS_GATEWAY_DIR', MYCRED_MIDTRANS_ROOT_DIR . 'gateways/');
        }

        public function mycred()
        {
            // register_activation_hook( MYCRED_MIDTRANS, array(__CLASS__, 'activate_plugin') );
            // register_deactivation_hook( MYCRED_MIDTRANS, array(__CLASS__, 'deactivate_plugin') );
            // register_uninstall_hook( MYCREAD_MIDTRANS, array(__CLASS__, 'unistall_plugin') );

            add_filter('mycred_setup_gateways', array($this, 'add_gateway'));
            add_action('mycred_buycred_load_gateways', array($this, 'load_gateways'));
            add_filter('mycred_buycred_refs', array($this, 'add_refs'));



        }

        public function add_refs($addons)
        {
            $addons['buy_creds_with_mycred_midtrans'] = __('buyCRED Purchase (MyCred Midtrans)', 'mycred');
            return $addons;
        }

        public function load_gateways()
        {
            $this->file(MYCRED_MIDTRANS_GATEWAY_DIR . 'mycred-midtrans.php');
        }

        public function add_gateway($gateways)
        {
            $gateways['mycred-midtrans'] = array(
                'title' => __('MyCred Midtrans', 'mycred'),
                'callback' => array('myCred_Midtrans'),
                'icon' => 'dashicons-admin-generic',
                'sandbox' => true,
                'external' => true,
                'custome_rate' => true
            );

            return $gateways;
        }

    }

endif;

function buycred_midtrans_gateway()
{
    return buyCred_Midtrans_Gateway_Core::instance();
}

buycred_midtrans_gateway();