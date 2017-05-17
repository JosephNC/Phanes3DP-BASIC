<?php

if ( ! function_exists( 'pqc_time_difference' ) ) :
/**
* Functions for Phanes 3DP Calculator - Basic
* @package Phanes 3DP Calculator - Basic
* @since 1.6
*/

/**
* Returns the number of seconds/minutes/hour/days/weeks/months/year ago
* 
* @param string $time_1 The datetime to parse
* @param string $time_2 The datetime to parse
* @param boolean $complete Whether to return the full time ago. Default is false.
* @param boolean $past Whether to return time ago or remaining time. Default is true.
* 
* @since 1.1.0
* @since 1.6 The 'past' argument is now used
*/
function pqc_time_difference( $time_1, $time_2, $complete = false, $past = true ) {
    
    $time_1 = new DateTime( $time_1 ); // Initiate the DateInterval Class
    
    $time_2 = new DateTime( $time_2 ); // Initiate the DateInterval Class
    
    $diff = $time_1->diff( $time_2 );

    $diff->w = floor( $diff->d / 7 );
    
    $diff->d -= $diff->w * 7;
    
    $args = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ( $args as $key => &$value ) {
        
        if ( $diff->$key ) {
            
            $value = $diff->$key . ' ' . $value . ( $diff->$key > 1 ? 's' : '' );
            
        } else {
            
            unset( $args[$key] );
        }
    }

    if ( ! $complete ) $args = array_slice( $args, 0, 1 );
    
    if ( $past === true )
        $return = ( $args ) ? implode( ', ', $args ) . ' ago' : 'just now';
    else
        $return = ( $args ) ? implode( ', ', $args ) : 'a moment';
    
    return $return;
}
endif;

if ( ! function_exists( 'pqc_get_random_string' ) ) :
/**
* Create random string
* 
* @param int $length The length of random string to get. Default is 20
*/
function pqc_get_random_string( $length = 20 ) {
    
    $characters = '56789abcdefghijklmABCDEFGHIJKLM01234nopqrstuvwxyzNOPQRSTUVWXYZ';
    
    $random_string = '';
    
    for ( $i = 0; $i < $length; $i++ ) {
        
        $random_string .= $characters[rand( 0, strlen( $characters ) - 1 )];
        
    }
    
    return $random_string;
}
endif;

if ( ! function_exists( 'pqc_page_exists' ) ) :
/**
* Check if page exist given the page name.
* 
* Page must be published
* 
* @param mixed $pagename. The page name to search for
* @return int The page ID if exist or 0 if not exist
*/
function pqc_page_exists( $pagename ) {
    
    global $wpdb;

    $sql = $wpdb->prepare( "
        SELECT ID
        FROM $wpdb->posts
        WHERE post_name = %s
        AND post_type = 'page'
        AND post_status = 'publish'
    ", $pagename );
    
    $page = (int) $wpdb->get_var( $sql );
    
    return ( $page === 0 ) ? 0 : $page;
    
}
endif;

if ( ! function_exists( 'pqc_force_redirect' ) ) :
/**
* Redirect to a webpage using javascript instead of wp_redirect
* 
* @param mixed $url Url to redirect to
*/
function pqc_force_redirect( $url ) {
    
    echo "<script>window.location='$url'</script>";
    
}
endif;

if ( ! function_exists( 'pqc_real_ip' ) ) :
/**
* Get IP Address
*/
function pqc_real_ip() {
    
    $header_checks = array(
        'HTTP_CLIENT_IP',
        'HTTP_PRAGMA',
        'HTTP_XONNECTION',
        'HTTP_CACHE_INFO',
        'HTTP_XPROXY',
        'HTTP_PROXY',
        'HTTP_PROXY_CONNECTION',
        'HTTP_VIA',
        'HTTP_X_COMING_FROM',
        'HTTP_COMING_FROM',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'ZHTTP_CACHE_CONTROL',
        'REMOTE_ADDR'
    );
 
    foreach ( $header_checks as $key ) {
        
        if ( array_key_exists( $key, $_SERVER ) === true ) {
            
            foreach ( explode( ',', $_SERVER[$key] ) as $ip ) {
                
                $ip = trim( $ip );
                 
                // Filter the ip with filter functions
                if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) !== false )
                    return $ip;
                else
                    return '127.0.0.1';
            }
            
        }
        
    }
    
}
endif;

if ( ! function_exists( 'pqc_money_format' ) ) :
/**
* Formats a number as a currency string
* 
* @param number The number to be formatted
* @param string Optional Currency to use , will be replaced with symbol if symbol is true
* @param bool Whether to use the curreny symbol or not
* @return string Returns the formatted string
*/
function pqc_money_format( $number, $currency = '', $symbol = false ) {
    
    extract( pqc_money_format_control() );
    
    $number = empty( $number ) ? 0.00 : $number;
    
    $number = number_format( $number, $num_decimals, $decimals_sep, $thousand_sep );

    if ( ! empty( $currency ) && is_string( $currency ) && $symbol = true ) {
        
        $currencies = array(
            'AUD' => '$',
            'CNY' => '¥',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'USD' => '$',
        );
        
        $currency_symbol = $currencies[$currency];
        
    }
    elseif ( ! empty( $currency ) && is_string( $currency ) && $symbol = false ) {
        
        $currency_symbol = $currency;
        
    }
    
    switch ( $currency_pos ) {
        case 'left' :           $money = "$currency_symbol$number"; break;
        case 'left_space' :     $money = "$currency_symbol $number"; break;
        case 'right' :          $money = "$number$currency_symbol"; break;
        case 'right_space' :    $money = "$number $currency_symbol"; break;
        default :               $money = "$currency_symbol$number"; break;
        
    }

    return $money;
    
}
endif;

if ( ! function_exists( 'pqc_money_format_control' ) ) :
/**
* Return PQC Money formating strings
* 
*/
function pqc_money_format_control() {
    
    $options        = maybe_unserialize( get_option( PQC_SETTING_OPTIONS ) );
    
    $general_settings = (object) $options['pqc_general_settings'];
    
    $num_decimals   = $general_settings->num_decimals;
    
    $decimals_sep   = $general_settings->decimal_sep;
    
    $thousand_sep   = $general_settings->thousand_sep;
    
    $currency       = $general_settings->currency;
    
    $currency_pos   = $general_settings->currency_pos;
    
    $currencies = array(
        'AUD' => '$',
        'CNY' => '¥',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
        'USD' => '$',
    );
    
    $currency_symbol = $currencies[$currency];

    $args = array(
        'num_decimals'      => $num_decimals,
        'decimals_sep'      => $decimals_sep,
        'thousand_sep'      => $thousand_sep,
        'currency'          => $currency,
        'currency_symbol'   => $currency_symbol,
        'currency_pos'      => $currency_pos,
    );

    return $args;
    
}
endif;

if ( ! function_exists( 'pqc_is_paypal_ready' ) ) :
/**
 * Check if PayPal has been setup
 * @return bool true if ready and false on failure or PayPal is not ready
 */
function pqc_is_paypal_ready() {
    
    $options = maybe_unserialize( get_option( PQC_SETTING_OPTIONS, false ) );
    
    if ( ! $options ) return false;
    
    $email = $options['pqc_checkout_settings']['paypal_email'];
    
    return ( isset( $email ) && ! empty( $email ) ) ? true : false;
    
}
endif;

if ( ! function_exists( 'pqc_apply_coupon' ) ) :
/**
* Apply given coupon to cart subtotal
* 
* @param mixed $coupon_id
* @param mixed $subtotal
* 
* @return bool|double false if not applied and new subtotal if applied
*/
function pqc_apply_coupon( $coupon_id, $subtotal ) {
    
    if ( ! is_int( $coupon_id ) ) return $subtotal;
    
    $coupon_data = maybe_unserialize( get_post_meta( $coupon_id, 'pqc_coupon_data' ) );
    
    if ( ! $coupon_data || empty( $coupon_data ) ) return $subtotal;
    
    $coupon_data = $coupon_data[0];

    $type = $coupon_data['type'];
    
    $expiry_date = $coupon_data['expiry_date'];

    $types =  array( 'fixed_cart', 'percent' );
    
    $update = false;
    
    // Let's check if coupon have expire
    if ( strtotime( $expiry_date ) < strtotime( current_time( 'Y-m-d' ) ) ) return false;
    
    if ( $type == 'fixed_cart' ) {

        $subtotal = floatval( $subtotal - floatval( $coupon_data['amount'] ) );
        
        $update = pqc_get_current_user_coupon() == $coupon_id ? $subtotal : pqc_update_current_user_coupon( $coupon_id );                                    
        
    }
    elseif( $type == 'percent' ) {
        
        $percent = ( floatval( $coupon_data['amount'] ) / 100 ) * $subtotal;
        
        $subtotal = floatval( $subtotal - floatval( $percent ) );
        
        $update = pqc_get_current_user_coupon() == $coupon_id ? $subtotal : pqc_update_current_user_coupon( $coupon_id );
        
    }

    return $update !== false ? $subtotal : false;
    
}
endif;

if ( ! function_exists( 'pqc_get_current_user_coupon' ) ) :
/**
* Return the current user coupon id
*/
function pqc_get_current_user_coupon() {
    
    $buyer_data = pqc_get_buyer_data();
    
    $coupon = $buyer_data['coupon'];
    
    if ( ! $coupon ) return false;

    // Let's check if coupon have expire
    $post_meta = get_post_meta( absint( $coupon ), 'pqc_coupon_data' );

    if ( ! $post_meta || empty( $post_meta ) ) { pqc_update_current_user_coupon( absint( $coupon ) ); return false; }
    
    $expiry_date = $post_meta[0]['expiry_date'];
    
    pqc_update_current_user_coupon( absint( $coupon ) );

    return ( strtotime( $expiry_date ) > strtotime( current_time( 'Y-m-d' ) ) ) ? absint( $coupon ) : false;
    
}
endif;

if ( ! function_exists( 'pqc_update_current_user_coupon' ) ) :
/**
* Updates the current user coupon id
* 
* @param int $coupon_id
*/
function pqc_update_current_user_coupon( $coupon_id ) {
    
    $coupon_id = (int) $coupon_id;
    
    // Let's check if coupon have expire
    $post_meta = get_post_meta( $coupon_id, 'pqc_coupon_data' );

    if ( ! $post_meta || empty( $post_meta ) ) $coupon_id = '';
    
    $expiry_date = $post_meta[0]['expiry_date'];

    $coupon_id = ( strtotime( $expiry_date ) > strtotime( current_time( 'Y-m-d' ) ) ) ? $coupon_id : '';
    
    $buyer_data = array( 'coupon' => $coupon_id );
    
    return ( empty( $coupon_id ) ) ? false : pqc_update_buyer_data( $buyer_data );
    
}
endif;

if ( ! function_exists( 'pqc_get_buyer_data' ) ) :
/**
* Returns Current Buyer Data
* 
* @return array The current user pqc data
*/
function pqc_get_buyer_data() {
    
    $default = array(
        'coupon'            => '',
        'shipping_option'   => '',
        'first_name'        => '',
        'last_name'         => '',
        'shipping_address'  => '',
        'city'              => '',
        'zipcode'           => '',
        'state'             => '',
    );

    $data = get_option( 'pqc_buyer_data_' . pqc_real_ip(), array() );

    if ( is_user_logged_in() ) {
        
        $data = get_user_meta( get_current_user_id(), 'pqc_buyer_data', false );
        $data = ( $data || ! empty( $data ) ) ? $data[0] : array();
        
    }
    
    return ( ! $data || empty( $data ) ) ? $default : $data;
    
}
endif;

if ( ! function_exists( 'pqc_update_buyer_data' ) ) :  
/**
* Updates Buyer data
* 
* @param array $data
*/
function pqc_update_buyer_data( $data ) {
    
    if ( ! is_array( $data ) ) return false;
    
    $default = pqc_get_buyer_data();
    
    $args = wp_parse_args( $data, $default );
    
    if ( array_diff_assoc( $args, $default ) === array_diff_assoc( $default, $args ) ) return true;
    
    if ( is_user_logged_in() )
        return update_user_meta( get_current_user_id(), 'pqc_buyer_data', $args );
    else
        return update_option( 'pqc_buyer_data_' . pqc_real_ip(), $args );
    
}
endif;

if ( ! function_exists( 'pqc_get_coupon_id' ) ) :
/**
* Return coupon id
* 
* @param string $coupon The Coupon
* @return int|bool The ID on success and false on failure
*/
function pqc_get_coupon_id( $coupon ) {
    
    global $wpdb;
    
    $coupon_sql = $wpdb->prepare(
        "SELECT ID FROM $wpdb->posts
        WHERE post_title = %s
        AND post_type = 'pqc_coupon'
        AND post_status = 'publish' LIMIT 1
        ", $coupon
    );
        
    $coupon_id = $wpdb->get_var( $coupon_sql );
    
    return $coupon_id ? absint( $coupon_id ) : false;
    
}
endif;

if ( ! function_exists( 'pqc_get_coupon_name' ) ) :
/**
* Return coupon name|title
* 
* @param string $coupon The Coupon ID
* @return string|bool The title|name on success and false on failure
*/
function pqc_get_coupon_name( $coupon_id ) {
    
    global $wpdb;
    
    $coupon_sql = $wpdb->prepare(
        "SELECT post_title FROM $wpdb->posts
        WHERE ID = %d
        AND post_type = 'pqc_coupon'
        AND post_status = 'publish' LIMIT 1
        ", $coupon_id
    );
        
    $coupon = $wpdb->get_var( $coupon_sql );
    
    return $coupon ? $coupon : false;
    
}
endif;

if ( ! function_exists( 'PQC' ) ) :
/**
 * Main instance of PQC.
 *
 * Returns the main instance of PQC to prevent the need to use globals.
 *
 * @since  1.6
 * @return PQC
 */
function PQC() {
    if ( method_exists( 'PQC', 'instance' ) ) return PQC::instance();
}
endif;