<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;  

final class PQC_PayPal {
    
    private $api_url = PQC_PHANES_API_URI;
    
    /**
    * Get Access Token From PayPal
    * 
    * @param mixed $url
    * @param mixed $postdata
    */
    public function get_access_token( $url, $postdata ) {
        
        $options = maybe_unserialize( get_option( PQC_SETTING_OPTIONS ) );
        
        $pqc_checkout_settings = (object) $options['pqc_checkout_settings'];
        
        $client_id       =   $pqc_checkout_settings->paypal_client_id;
        $client_secret   =   $pqc_checkout_settings->paypal_client_secret_key;

        $curl = curl_init( $url ); 
        curl_setopt( $curl, CURLOPT_POST, true ); 
        curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $curl, CURLOPT_USERPWD, $client_id . ":" . $client_secret );
        curl_setopt( $curl, CURLOPT_HEADER, false ); 
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true ); 
        curl_setopt( $curl, CURLOPT_POSTFIELDS, $postdata ); 
        #    curl_setopt( $curl, CURLOPT_VERBOSE, TRUE );
        $response = curl_exec( $curl );
        
        if ( empty( $response ) ) {
            
            // some kind of an error happened
            $error = curl_error( $curl );
            
            error_log( $error );
            
            curl_close( $curl ); // close cURL handler
            
            return array( 'error' => true, 'response' => $error );
            
        }
        else {
            
            $info = curl_getinfo( $curl );
            
            // echo "Time took: " . $info['total_time']*1000 . "ms\n";
            curl_close( $curl ); // close cURL handler
            
            if ( $info['http_code'] != 200 && $info['http_code'] != 201 ) {
                
                error_log( $response );
                
                return array( 'error' => true, 'response' => $response );
                
            }
        }

        // Convert the result from JSON format to a PHP array 
        $json_response = json_decode( $response );
        
        return $json_response->access_token;
    }
    
    public function make_post_call( $url, $postdata, $headers ) {
        
        $curl = curl_init();

        curl_setopt_array( $curl, array(
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_POST            => true,
            CURLOPT_MAXREDIRS       => 5,
            CURLOPT_HTTPHEADER      => $headers,
            CURLOPT_POSTFIELDS      => array( '_3dpcapi' => $postdata ),
            
        ) );
        
        $response = curl_exec( $curl );

        if ( empty( $response ) ) {
            
            // some kind of an error happened
            $error = curl_error( $curl );
            
            error_log( $error );
            
            curl_close( $curl ); // close cURL handler
            
            return array( 'error' => true, 'response' => $error );
            
        }
        else {
            
            $info = curl_getinfo( $curl );
            
            curl_close( $curl ); // close cURL handler
            
            if ( $info['http_code'] != 200 && $info['http_code'] != 201 ) {
                
                error_log( $response );
                
                return array( 'error' => true, 'response' => $response );
                
            }
        }

        // Convert the result from JSON format to a PHP array 
        $json_response = json_decode( $response, TRUE );
        
        return $json_response;
    }
    
    public function payment_start( $order_id ) {
        
        if ( ! pqc_is_paypal_ready() ) return false;

        $options = maybe_unserialize( get_option( PQC_SETTING_OPTIONS ) );
        
        $pqc_checkout_settings = (object) $options['pqc_checkout_settings'];
        
        $paypal_sandbox = $pqc_checkout_settings->paypal_sandbox;
        $paypal_email = $pqc_checkout_settings->paypal_email;

        if ( preg_match( '/page_id=/i', get_permalink( pqc_page_exists( 'pqc-orders' ) ) ) == 1 )
            $query_string =  "&order_id=$order_id&order_status=complete&payment_method=paypal";
        else
            $query_string = "?order_id=$order_id&order_status=complete&payment_method=paypal";
        
        $processor_link = get_permalink( pqc_page_exists( 'pqc-orders' ) ) . $query_string;
        
        $cancel_link    = get_permalink( pqc_page_exists( 'pqc-checkout' ) );
        $description    = esc_html__( 'Payment for printing and shipping items at ', 'pqc' ) . esc_html( home_url() );
        
        $order_meta     = get_post_meta( $order_id, 'pqc_order_data' )[0];
        
        if ( $order_meta['order_status'] != 'pending' ) return array( 'error' => true, 'response' => __( 'Sorry, payment cannot be proccessed for this order.', 'pqc' ) );
        
        $items          = $order_meta['items'];
        $currency       = esc_attr( $order_meta['currency'] );
        $shipping_cost  = number_format( floatval( $order_meta['shipping_cost'] ), 2, '.', ',' );
        $cart_total     = number_format( floatval( $order_meta['cart_total'] ), 2, '.', ',' );
        $subtotal       = number_format( floatval( $order_meta['subtotal'] ), 2, '.', ',' );
        $total          = number_format( floatval( $order_meta['total'] ), 2, '.', ',' );
        $coupon         = $order_meta['coupon'];
        
        if ( ! empty( $coupon ) ) $coupon_id = pqc_get_coupon_id( $coupon );
        
        $payment = array(
            'paypal_email'      => $paypal_email,
            'redirect_urls'     => array(
                'return_url' => $processor_link,
                'cancel_url' => $cancel_link
            ),
        );

        $payment['transaction'] = array(
            'amount'    => array(
                'total'     => $total,
                'currency'  => $currency,
                'details'   => array(
                    'subtotal'  => $subtotal,
                    'tax'       => '0.00',
                    'shipping'  => $shipping_cost
                )
            ),
            'description'   => $description
        );
        
        foreach( $items as $item_data ) {
            
            $name = $item_data['name'];
            $qty = $item_data['quantity'];
            $price = number_format( $item_data['price'], 2, '.', ',' );

            $payment['transaction']['items'][] = array(
                'quantity'  => "$qty",
                'name'      => "$name",
                'price'     => "$price",
                'currency'  => "$currency",
                'sku'       => 'Paying for ' . $item_data['unique_id'] . ' - Material Used: ' . $item_data['material'],
            );    
            
        }
        
        if ( isset( $coupon_id ) ) {

            $discount_amount = number_format( floatval( $order_meta['cart_total'] ) - floatval( $order_meta['subtotal'] ), 2, '.', ',' );

            $payment['transaction']['items'][] = array(
                'quantity'  => 1,
                'name'      => "Discount",
                'price'     => "-$discount_amount",
                'currency'  => "$currency",
                'sku'       => "Coupon ($coupon) for Order #$order_id",
            );

        }
        
        $headers = array(
            "3DPCBASIC: 1",
            "3DPCBASIC-HOST: " . home_url(),
            "3DPCBASIC-INTENT: Pay",
            "3DPCBASIC-SANDBOX: $paypal_sandbox",
        );
        
        $json = json_encode( $payment );
        
        $json_resp = $this->make_post_call( $this->api_url, $json, $headers );
        
        if ( $json_resp['error'] == true ) {
            
            if ( isset( $json_resp['errors'] ) ) return array( 'error' => true, 'response' => $json_resp['errors'][0]['message'] );
            elseif ( isset( $json_resp['response'] ) ) return array( 'error' => true, 'response' => $json_resp['response'] );
            else return array( 'error' => true, 'response' => __( 'Error Occurred', 'pqc' ) );
            
        }
        
        $pay_key        = $json_resp['data']['payKey'];
        $tracking_ID    = $json_resp['data']['trackingId'];
        $approval_url   = $json_resp['data']['approvalUrl'];
        
        $data = array(
            'paypal_pay_key' => $pay_key,
            'paypal_tracking_ID' => $tracking_ID,
        );
        
        if ( is_user_logged_in() ) {
            $save_data = update_user_meta( get_current_user_id(), 'pqc_buyer_paypal_data', $data );
        }
        else {
            $save_data = update_option( 'pqc_buyer_paypal_data_' . pqc_real_ip(), $data );
        }

        wp_redirect( esc_url_raw( $approval_url ) );
        
        exit;
        
    }
    
    public function payment_end( $order_id ) {

        if ( ! pqc_is_paypal_ready() ) return;

        $options = maybe_unserialize( get_option( PQC_SETTING_OPTIONS ) );
        
        $pqc_checkout_settings = (object) $options['pqc_checkout_settings'];
        
        $paypal_sandbox = $pqc_checkout_settings->paypal_sandbox;

        if ( is_user_logged_in() ) {
            $save_data = get_user_meta( get_current_user_id(), 'pqc_buyer_paypal_data', false )[0];
        }
        else {
            $save_data = get_option( 'pqc_buyer_paypal_data_' . pqc_real_ip(), array() )[0];
        }
        
        if ( $save_data && ! empty( $save_data ) ) {
        
            $tracking_ID    =   $save_data['paypal_tracking_ID'];
            
            $payment = array(
                'trackingId' => $tracking_ID
            );
            
            $json =   json_encode( $payment );
        
            $headers = array(
                "3DPCBASIC: 1",
                "3DPCBASIC-HOST: " . home_url(),
                "3DPCBASIC-INTENT: PaymentDetails",
                "3DPCBASIC-SANDBOX: $paypal_sandbox",
            );
            
            $json = json_encode( $payment );
            
            $json_resp = $this->make_post_call( $this->api_url, $json, $headers );
            
            /*
            var_dump( $json_resp );
            
            echo '<pre>';
            print_r( $json_resp );
            echo '</pre>';
            */
            
            if ( $json_resp['error'] == true ) return array( 'error' => true, 'response' => $json_resp['errors'][0]['message'] );
            
            $status = strtolower( $json_resp['data']['status'] );
            
            switch( $status ) {
                case 'created' :
                case 'processing' :
                    $prev = get_post_meta( $order_id, 'pqc_order_data' )[0];
                    
                    // Order approved
                    $prev['email']             = '';
                    $prev['txn_id']            = $json_resp['data']['transactionId'];
                    $prev['order_status']      = 'processing';

                    // Update Post Meta
                    update_post_meta( $order_id, 'pqc_order_data', $prev );
                    
                    $GLOBALS['payment_message'] = __( 'The payment request was received; transaction will be completed once the payment is approved.', 'pqc' );
                    break;
                case 'completed' :
                    $prev = get_post_meta( $order_id, 'pqc_order_data' )[0];
                    
                    // Order approved
                    $prev['email']             = sanitize_email( $json_resp['data']['senderEmail'] );
                    $prev['txn_id']            = $json_resp['data']['transactionId'];
                    $prev['order_status']      = 'completed';

                    // Update Post Meta
                    update_post_meta( $order_id, 'pqc_order_data', $prev );
                    
                    $GLOBALS['payment_message'] = __( 'Transaction Complete, Transaction ID - ', 'pqc' ) . $prev['txn_id'];
                    break;
                case 'expired' :
                    $prev = get_post_meta( $order_id, 'pqc_order_data' )[0];
                    
                    // Order approved
                    $prev['email']             = isset( $json_resp['data']['senderEmail'] ) ? sanitize_email( $json_resp['data']['senderEmail'] ) : '';
                    $prev['txn_id']            = $json_resp['data']['transactionId'];
                    $prev['order_status']      = 'cancelled';

                    // Update Post Meta
                    update_post_meta( $order_id, 'pqc_order_data', $prev );
                    
                    $GLOBALS['payment_message'] = __( 'The payment request has expired.', 'pqc' );
                    break;
                case 'incomplete' :
                    $prev = get_post_meta( $order_id, 'pqc_order_data' )[0];
                    
                    // Order approved
                    $prev['email']             = isset( $json_resp['data']['senderEmail'] ) ? sanitize_email( $json_resp['data']['senderEmail'] ) : '';
                    $prev['txn_id']            = $json_resp['data']['transactionId'];
                    $prev['order_status']      = 'on-hold';

                    // Update Post Meta
                    update_post_meta( $order_id, 'pqc_order_data', $prev );
                    
                    $GLOBALS['payment_message'] = __( 'The payment request was received; transaction will be completed once the payment is approved.', 'pqc' );
                    break;
                case 'pending' :
                    $prev = get_post_meta( $order_id, 'pqc_order_data' )[0];
                    
                    // Order approved
                    $prev['email']             = isset( $json_resp['data']['senderEmail'] ) ? sanitize_email( $json_resp['data']['senderEmail'] ) : '';
                    $prev['txn_id']            = $json_resp['data']['transactionId'];
                    $prev['order_status']      = 'pending';

                    // Update Post Meta
                    update_post_meta( $order_id, 'pqc_order_data', $prev );
                    
                    $GLOBALS['payment_message'] = __( 'The payment is awaiting processing.', 'pqc' );
                    break;
                case 'error' :
                default :
                    $prev = get_post_meta( $order_id, 'pqc_order_data' )[0];
                    
                    // Order approved
                    $prev['email']             = isset( $json_resp['data']['senderEmail'] ) ? sanitize_email( $json_resp['data']['senderEmail'] ) : '';
                    $prev['txn_id']            = $json_resp['data']['transactionId'];
                    $prev['order_status']      = 'failed';

                    // Update Post Meta
                    update_post_meta( $order_id, 'pqc_order_data', $prev );
                    
                    $GLOBALS['payment_message'] = __( 'The payment failed and all attempted transfers failed or all completed transfers were successfully reversed.', 'pqc' );
                    break;
                
            }
            
        }
            
    }
    

}