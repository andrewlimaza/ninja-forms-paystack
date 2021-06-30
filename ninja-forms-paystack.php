<?php if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Plugin Name: Ninja Forms - Paystack
 * Plugin URI: https://jarrydlong.co.za/ninja-forms-paystack-integration/
 * Description: Accept payments for form submissions using Paystack
 * Version: 3.0.0
 * Author: Jarryd Long
 * Author URI: https://jarrydlong.co.za/ninja-forms-paystack-integration/
 * Text Domain: ninja-forms-paystack
 *
 * Copyright 2021 .
 */

if( version_compare( get_option( 'ninja_forms_version', '0.0.0' ), '3', '<' ) || get_option( 'ninja_forms_load_deprecated', FALSE ) ) {

    //include 'deprecated/ninja-forms-paystack.php';

} else {

    /**
     * Class NF_Paystack
     */
    final class NF_Paystack
    {
        const VERSION = '1.0.0';
        const SLUG    = 'paystack';
        const NAME    = 'Paystack';
        const AUTHOR  = '';
        const PREFIX  = 'NF_Paystack';

        /**
         * @var NF_Paystack
         * @since 3.0
         */
        private static $instance;

        /**
         * Plugin Directory
         *
         * @since 3.0
         * @var string $dir
         */
        public static $dir = '';

        /**
         * Plugin URL
         *
         * @since 3.0
         * @var string $url
         */
        public static $url = 'https://jarrydlong.co.za/ninja-forms-paystack-integration/';

        /**
         * Main Plugin Instance
         *
         * Insures that only one instance of a plugin class exists in memory at any one
         * time. Also prevents needing to define globals all over the place.
         *
         * @since 3.0
         * @static
         * @static var array $instance
         * @return NF_Paystack Highlander Instance
         */
        public static function instance()
        {
            if (!isset(self::$instance) && !(self::$instance instanceof NF_Paystack)) {
                self::$instance = new NF_Paystack();

                self::$dir = plugin_dir_path(__FILE__);

                self::$url = plugin_dir_url(__FILE__);

                /*
                 * Register our autoloader
                 */
                spl_autoload_register(array(self::$instance, 'autoloader'));
            }
            
            return self::$instance;
        }

        public function __construct()
        {
            /*
             * Required for all Extensions.
             */
            add_action( 'admin_init', array( $this, 'setup_license') );

            /*
             * Optional. If your extension processes or alters form submission data on a per form basis...
             */
            add_filter( 'ninja_forms_register_actions', array($this, 'register_actions'));
           

        }

        

        /**
         * Optional. If your extension creates a new field interaction or display template...
         */
        
        public function register_fields($actions)
        {
            $actions[ 'paystack' ] = new NF_Paystack_Fields_PaystackExample(); // includes/Fields/PaystackExample.php

            return $actions;
        }

        /**
         * Optional. If your extension processes or alters form submission data on a per form basis...
         */
        public function register_actions($actions)
        {
            $actions[ 'paystack' ] = new NF_Paystack_Actions_Paystack(); // includes/Actions/PaystackExample.php

            return $actions;
        }

        /**
         * Optional. If your extension collects a payment (ie Strip, PayPal, etc)...
         */
        public function register_payment_gateways($payment_gateways)
        {
            $payment_gateways[ 'paystack' ] = new NF_Paystack_PaymentGateways_PaystackExample(); // includes/PaymentGateways/PaystackExample.php

            return $payment_gateways;
        }

        /*
         * Optional methods for convenience.
         */

        public function autoloader($class_name)
        {
            // if (class_exists($class_name)) return;

            // if ( false === strpos( $class_name, self::PREFIX ) ) return;

            // $class_name = str_replace( self::PREFIX, '', $class_name );
            // $classes_dir = realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
            // $class_file = str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';

            // if (file_exists($classes_dir . $class_file)) {
            //     require_once $classes_dir . $class_file;
            // }
            
            require_once plugin_dir_path( __FILE__ ).'nf-actions.php';
            // require_once plugin_dir_path( __FILE__ ).'nf-fields.php';
            // require_once plugin_dir_path( __FILE__ ).'nf-gateway.php';
            require_once plugin_dir_path( __FILE__ ).'nf-settings.php';

        }
        
        /**
         * Template
         *
         * @param string $file_name
         * @param array $data
         */
        public static function template( $file_name = '', array $data = array() )
        {
            if( ! $file_name ) return;

            extract( $data );

            include self::$dir . 'includes/Templates/' . $file_name;
        }
        
        /**
         * Config
         *
         * @param $file_name
         * @return mixed
         */
        public static function config( $file_name )
        {
            return include self::$dir . 'includes/Config/' . $file_name . '.php';
        }

        /*
         * Required methods for all extension.
         */

        public function setup_license()
        {
            if ( ! class_exists( 'NF_Extension_Updater' ) ) return;

            new NF_Extension_Updater( self::NAME, self::VERSION, self::AUTHOR, __FILE__, self::SLUG );
        }
    }

    /**
     * The main function responsible for returning The Highlander Plugin
     * Instance to functions everywhere.
     *
     * Use this function like you would a global variable, except without needing
     * to declare the global.
     *
     * @since 3.0
     * @return {class} Highlander Instance
     */
    function NF_Paystack()
    {
        return NF_Paystack::instance();
    }

    NF_Paystack();
}
