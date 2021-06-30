<?php if ( ! defined( 'ABSPATH' ) || ! class_exists( 'NF_Abstracts_Action' )) exit;

session_start();

/**
 * Class NF_Action_PaystackExample
 */
final class NF_Paystack_Actions_Paystack extends NF_Abstracts_Action
{
    /**
     * @var string
     */
    protected $_name  = 'paystack';

    /**
     * @var array
     */
    protected $_tags = array();

    /**
     * @var string
     */
    protected $_timing = 'normal';

    /**
     * @var int
     */
    protected $_priority = '10';


    /**
     * Constructor
     */
    public function __construct(){

        parent::__construct();

        $this->url = 'https://api.paystack.co';

        $this->environment = Ninja_Forms()->get_setting( 'paystack_jl_env' );

        $this->public_key = ( $this->environment === 'Live' ) ? Ninja_Forms()->get_setting( 'paystack_jl_public_key' ) : Ninja_Forms()->get_setting( 'paystack_jl_public_key_test' );
        
        $this->private_key = ( $this->environment === 'Live' ) ? Ninja_Forms()->get_setting( 'paystack_jl_private_key' ) : Ninja_Forms()->get_setting( 'paystack_jl_private_key_test' );

        $this->_nicename = __( 'Paystack', 'ninja-forms' );

        $this->_settings = array(
            'paystack_email' => array(
                'name' => 'paystack_email',
                'type' => 'textbox',
                'label' => __( 'Email Address', 'ninja-forms'),
                'width' => 'full',
                'group' => 'primary',
                'value' => '',
                'help' => __( 'Specify which field relates to the customer\'s email address for invoicing purposes.', 'ninja-forms' ),
                'use_merge_tags' => true
            ),
            'paystack_amount' => array(
                'name' => 'paystack_amount',
                'type' => 'textbox',
                'label' => __( 'Billing Amount', 'ninja-forms'),
                'width' => 'full',
                'group' => 'primary',
                'value' => '',
                'help' => __( 'Specify which field relates to the total amount to bill the customer.', 'ninja-forms' ),
                'use_merge_tags' => true
            ),
            'paystack_thankyou' => array(
                'name' => 'paystack_thankyou',
                'type' => 'textbox',
                'label' => __( 'Thank You URL', 'ninja-forms'),
                'width' => 'full',
                'group' => 'primary',
                'value' => '',
                'help' => __( 'Specify which field the payment should be redirected after a transaction is successfully processed.', 'ninja-forms' ),
            ),

        );

        add_action( 'init', array( $this, 'verify_transaction' ) );

        add_action( 'ninja_forms_save_sub', array( $this, 'save_sub' ) );

        add_filter( 'the_content', array( $this, 'thank_you_content' ), 10, 1 );
        
        add_filter( 'manage_nf_sub_posts_columns', array( $this, 'set_custom_edit_book_columns' ) );

        add_action( 'manage_posts_custom_column' , array( $this, 'custom_book_column' ), 10, 2 );

    }  

    /*
    * PUBLIC METHODS
    */

    public function set_custom_edit_book_columns( $columns ){

        $columns['nf_paystack_status'] = __( 'Paystack Payment', 'your_text_domain' );

        return $columns;

    }

    public function custom_book_column( $column, $post_id ){

        switch ( $column ) {
            case 'nf_paystack_status' :
                $request = get_post_meta( $post_id , 'paystack_status' , true ); 
                if( !empty( $request->status ) && $request->status === true && $request->data->status == 'success' ){
                    echo "<span style='padding: 3px 6px; background-color: green; color: #FFF; border-radius: 3px;'>Paid</span>";
                } else {
                    echo "<span style='padding: 3px 6px; background-color: red; color: #FFF; border-radius: 3px;'>Unpaid</span>";
                }
                break;

        }

    }

    public function thank_you_content( $content ){

        if( !empty( $_REQUEST['nf-paystack'] ) ){

            if( $_REQUEST['nf-paystack'] == 'success' ){
                return Ninja_Forms()->get_setting( 'paystack_jl_success_message' ).$content;
            } 

            return Ninja_Forms()->get_setting( 'paystack_jl_error_message' ).$content;

        }

        return $content;

    }
   
    public function save_sub( $sub_id ){

        $_SESSION['nf_paystack_sub'] = $sub_id;

    }


    public function verify_transaction(){

        if( !empty( $_REQUEST['trxref'] ) ){

            $request = $this->paystack_request( 'GET', '/transaction/verify/'.$_REQUEST['trxref'] );
            if( !empty( $request->status ) ){

                if( !empty( $_SESSION['nf_paystack_sub'] ) ){
                    update_post_meta( intval( $_SESSION['nf_paystack_sub'] ), 'paystack_status', $request );
                } 

                if( $request->status === true ){

                    if( $request->data->status === 'success' ){
                        if( !empty( $_SESSION['nf_paystack_thankyou'] ) ){
                            header("Location: ".$_SESSION['nf_paystack_thankyou']."?nf-paystack=success");
                        }
                    } else {
                        if( !empty( $_SESSION['nf_paystack_thankyou'] ) ){
                            header("Location: ".$_SESSION['nf_paystack_thankyou']."?nf-paystack=error");
                        }
                    }
                }
            }
            
        }

    }

    public function save( $action_settings ){
    
    }

    public function process( $action_settings, $form_id, $data ){

        $billing_email = ( !empty( $action_settings['paystack_email'] ) ) ? sanitize_text_field( $action_settings['paystack_email'] ) : "";

        $billing_amount = ( !empty( $action_settings['paystack_amount'] ) ) ? floatval( $action_settings['paystack_amount'] ) : 0;
        
        if( $billing_email !== "" && $billing_amount !== 0 ){

            $request = $this->paystack_request( 'POST', '/transaction/initialize', array( 
                'email' => $billing_email, 
                'amount' => intval( ( $billing_amount * 100 ) ),
                'callback_url' => $data['settings']['public_link'],
            ) );

            if( !empty( $request->status ) ){
                
                if( $request->status == 1 ){

                    $data[ 'actions' ][ 'redirect' ] = $request->data->authorization_url;
                    
                    $_SESSION['nf_paystack_thankyou'] = $action_settings['paystack_thankyou'];

                }
            }

        }

        return $data;
    }

    public function paystack_request( $state = 'POST', $endpoint = '', $args = array() ){

        $curl = curl_init( $this->url.$endpoint );

        curl_setopt($curl, CURLOPT_URL, $this->url.$endpoint);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "Authorization: Bearer ".$this->private_key,
        );

        if( $state === 'POST' ){
            curl_setopt( $curl, CURLOPT_POST, true);
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $args );
            // $headers[] = "Content-Type: application/json";
        }
        
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        //for debug only!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($curl);
        curl_close($curl);
        
        if( $resp ){
            return json_decode( $resp );
        }

        return false;

    }

}