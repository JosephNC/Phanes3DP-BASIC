<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;

if ( ! class_exists( 'PQC_Public' ) ) :  

final class PQC_Public {
    
    private $ajax_action = 'public_ajax';

    /**
    * The Constructor
    * 
    */
    public function __construct() {

        if ( ! defined( 'DOING_AJAX' ) ) {
            
            $this->payment_load();

            add_shortcode( PQC_UPLOAD_SHORTCODE, array( $this, 'upload' ) );

            add_shortcode( PQC_CART_SHORTCODE, array( $this, 'cart' ) );

            add_shortcode( PQC_CHECKOUT_SHORTCODE, array( $this, 'checkout' ) );
            
            add_shortcode( PQC_ORDERS_SHORTCODE, array( $this, 'orders' ) );

            add_action( 'wp_enqueue_scripts',  array( &$this, 'public_scripts' ), 0 );

        }
        else {

            $priv   = 'wp_ajax_' . $this->ajax_action;
            $nopriv = 'wp_ajax_nopriv_' . $this->ajax_action;

            // Ajax Cart Update Actions
            add_action( "$priv-update",                 array( $this, $this->ajax_action . '_update' ) );
            add_action( "$nopriv-update",               array( $this, $this->ajax_action . '_update' ) );

            // Ajax Cart Delete Actions
            add_action( "$priv-delete",                 array( $this, $this->ajax_action . '_delete' ) );
            add_action( "$nopriv-delete",               array( $this, $this->ajax_action . '_delete' ) );

        }

    }
    
    private function payment_load() {
        
        /**
        $protocol = is_ssl() ? 'https://' : 'http://';
        
        $request = explode( '?', $_SERVER['REQUEST_URI'] )[0];
        
        $url = $protocol . $_SERVER['HTTP_HOST'] . $request;
        
        if ( isset( $_GET['page_id'] ) && ! empty( $_GET['page_id'] ) ) {
            
            if ( pqc_page_exists( 'pqc-checkout' ) !== intval( $_GET['page_id'] ) ) return;
            
        }
        elseif ( $url != get_bloginfo( 'url' ) . '/pqc-checkout' && $url != get_bloginfo( 'url' ) . '/pqc-checkout/' ) {
            
            return;
            
        }
        */
    
        if (
            ( isset( $_GET['order_status'] ) && ! empty( $_GET['order_status'] ) ) &&
            ( isset( $_GET['payment_method'] ) && ! empty( $_GET['payment_method'] ) ) &&
            ( isset( $_GET['order_id'] ) && ! empty( $_GET['order_id'] ) )
        ) {
            
            global $pqc_payment_options;
            
            $status = esc_attr( $_GET['order_status'] );
             
            $payment_method = esc_attr( $_GET['payment_method'] );
            
            $order_id = esc_attr( $_GET['order_id'] );
            
            $allowed = array( 'processing' => true, 'complete' => false );
            
            if ( ! array_key_exists( $status, $allowed ) || ! array_key_exists( $payment_method, $pqc_payment_options ) ) return;
            
            $this->load_payment_method( $order_id, $payment_method, $allowed[$status] ); 
            
        }

        if ( isset( $_POST['place_order'] ) ) $this->place_order();
        
    }
    
    private function place_order() {
        
        extract( $_POST );
        
        global $wpdb;
        
        $place_order_error = false;
        
        if (
        ! isset( $firstname )
        || empty( $firstname )
        || ! isset( $lastname )
        || empty( $lastname )
        || ! isset( $address )
        || empty( $address )
        || ! isset( $city )
        || empty( $city )
        || ! isset( $zipcode )
        || empty( $zipcode )
        || ! isset( $state )
        || empty( $state )
        ) {
            
            $place_order_error = true;
            
            $place_order_msg = __( '<strong> Error! </strong> Missing Required Field.', 'pqc' );
            
        }
        elseif ( ! isset( $payment_method ) || empty( $payment_method ) ) {
            
            $place_order_error = true;
            
            $place_order_msg = __( '<strong> Error! </strong> No payment method selected.', 'pqc' );
            
        }
        
        if ( ! $place_order_error ) {
            
            $locations = array( '_do_not_use', 'US', 'International' );
            
            $location = ( isset( $location ) && ( $location > 0 && $location < 3 ) ) ? $location : 1;
            
            $location = $locations[$location]; 
            
            $user_ip = pqc_real_ip();
            
            $materials = $wpdb->get_results( "SELECT * FROM " . PQC_MATERIALS_TABLE );
            
            if ( $materials ) {
                
                $g = 0;
                
                foreach ( $materials as $material ) {
                    
                    if ( $g == 0 ) $material_id = $material->ID;
                    
                    $the_materials[$material->ID] = $material->material_name;
                    
                    $g++;
                }
                
            }
            
            $fix = array(
                'quantity' => 1,
                'material' => $material_id,
            );
            
            $items = $wpdb->get_results( "SELECT * FROM " . PQC_DATA_TABLE . " WHERE user_ip = '$user_ip' AND status = 'pending'" );
            
            if ( $items ) {
                
                $args = array(
                    'post_title'        => 'Auto Draft',
                    'post_name'         => '',
                    'post_status'       => 'auto-draft',
                    'post_type'         => 'page',
                    'comment_status'    => 'closed',
                    'ping_status'       => 'closed',
                    'post_date'         => current_time( 'mysql' ),
                );
                
                $order_id = wp_insert_post( $args );
                
                $firstname  = esc_attr( $firstname );
                $lastname   = esc_attr( $lastname );
                $address    = esc_attr( $address );
                $city       = esc_attr( $city );
                $zipcode    = esc_attr( $zipcode );
                $state      = esc_attr( $state );
                $order_note = esc_html( $order_note );
                
                $buyer_data = array(
                    'first_name'        => $firstname,
                    'last_name'         => $lastname,
                    'shipping_address'  => $address,
                    'city'              => $city,
                    'zipcode'           => $zipcode,
                    'state'             => $state,
                );
                
                // Update the Buyer Data
                pqc_update_buyer_data( $buyer_data );
                
                $total_item = count( $items );
                
                $x = 0;
                
                $currency = pqc_money_format_control();
                
                $currency = $currency['currency'];
                
                foreach( $items as $item ) {
                    
                    $id = $item->ID;
                    
                    $name = $item->item_name;
                    
                    $data = maybe_unserialize( $item->item_data );
                    
                    $unique_id = $item->unique_id;
                    
                    $volume     = ceil( $data['volume'] );
                    
                    $weight     = ceil( $data['weight'] );
                    
                    $density    = $data['density'];
                    
                    $triangle   = $data['triangle'];
                    
                    $data       = wp_parse_args( $data, $fix ); // Back Compat fix
                    
                    $quantity   = $data['quantity'];
                    
                    $selected = absint( $data['material'] );
                    
                    $target_file = PQC_CONTENT_DIR . $unique_id . ".stl";
                    
                    if ( ! file_exists( $target_file ) ) continue;
                        
                    $calculate = $this->calculate( $data );
                    
                    $the_items[$x]['unique_id'] = $unique_id;
                    $the_items[$x]['name']      = $name;
                    $the_items[$x]['volume']    = $volume;
                    $the_items[$x]['weight']    = $weight;
                    $the_items[$x]['density']   = $density;
                    $the_items[$x]['triangle']  = $triangle;
                    $the_items[$x]['url']       = PQC_CONTENT_URL . $unique_id . ".stl";
                    $the_items[$x]['quantity']  = $quantity;
                    $the_items[$x]['material']  = $the_materials[$selected];
                    $the_items[$x]['price']     = $calculate['price'];
                    $the_items[$x]['amount']    = $calculate['amount'];
                    
                    $subtotal[] = $calculate['amount'];
                    
                    $complete_ids[] = "ID = " . $id;
                    
                    $delete[] = $unique_id;
                    
                    $x++;
                    
                }
                
                $subtotal = array_sum( $subtotal );
                
                $cart_total = $subtotal;
                
                if ( pqc_get_current_user_coupon() ) {
                    
                    $coupon_id = (int) pqc_get_current_user_coupon();
                    
                    $coupon_name = pqc_get_coupon_name( $coupon_id );
                    
                    $old_subtotal = $subtotal;
                    
                    $subtotal = pqc_apply_coupon( $coupon_id, $subtotal );
                    
                    if ( $subtotal === false ) $subtotal = $old_subtotal;
                    
                }
       
                $total = $subtotal;
                
                $shipping_options =  $this->get_shipping_options();
                
                $buyer_data = pqc_get_buyer_data();

                if ( $buyer_data && isset( $buyer_data['shipping_option'] ) && ! empty( $buyer_data['shipping_option'] ) ) {
                    
                    $current_shipping_option_id = (int) $buyer_data['shipping_option'];
                    
                    $current_shipping_option = $shipping_options[$current_shipping_option_id];
                    
                    $shipping_option_cost = floatval( $current_shipping_option['amount'] );
                    
                    $total = $subtotal + $shipping_option_cost;                   
                    
                }
                
                // Place Order
                $item_data['firstname']         = $firstname;
                $item_data['lastname']          = $lastname;
                $item_data['email']             = '';
                $item_data['txn_id']            = '';
                $item_data['address']           = $address;
                $item_data['city']              = $city;
                $item_data['zipcode']           = $zipcode;
                $item_data['state']             = $state;
                // $item_data['location']          = $location;
                $item_data['note']              = $order_note;
                $item_data['order_action']      = 'new';
                $item_data['order_status']      = 'pending';
                $item_data['coupon']            = ( isset( $coupon_name ) ) ? $coupon_name : '';
                $item_data['payment_method']    = $payment_method;
                $item_data['shipping_option']   = isset( $current_shipping_option['title'] ) ? $current_shipping_option['title'] : '-';
                $item_data['shipping_cost']     = $shipping_option_cost;
                $item_data['cart_total']        = $cart_total;
                $item_data['subtotal']          = $subtotal;
                $item_data['total']             = $total;
                $item_data['currency']          = $currency;
                $item_data['items']             = $the_items;
                $item_data['user_ip']           = $user_ip;
                
                $args = array(
                    'ID'                => $order_id,
                    'post_title'        => "#$order_id",
                    'post_status'       => 'publish',
                    'post_type'         => 'pqc_order',
                );
                
                wp_update_post( $args );
                
                // Insert Post Meta
                update_post_meta( $order_id, 'pqc_order_data', $item_data );

                $this->load_payment_method( $order_id, $payment_method );
                    
            }
            else {
                
                $notice = true;

                $notice_msg = sprintf(
                    __( '<strong> Sorry! </strong> You have no item in cart. <a href="%s">Add items</a>', 'pqc' ),
                    get_permalink( pqc_page_exists( 'pqc-upload' ) )
                );
                
            }
            
        }
        
    }
    
    public function public_ajax_update() {
  
        if ( ! wp_verify_nonce( $_POST['nonce'], $this->ajax_action ) ) {
            
            _e( 'No naughty business please', 'pqc' );
        
        }
        elseif ( isset( $_POST['data'] ) ) {
            
            ob_clean(); // Clean any output
            
            global $wpdb;
            
            $user_ip  = pqc_real_ip();
            
            parse_str( $_POST['data'] );
            
            if ( isset( $quantities ) && ! empty( $quantities ) && isset( $materials ) && ! empty( $materials ) ) {
                
                $items = $wpdb->get_results( "SELECT * FROM " . PQC_DATA_TABLE . " WHERE user_ip = '$user_ip' AND status = 'pending'" );
                
                if ( $items ) {
                    
                    $the_data = array();
                    
                    $the_items = array();

                    foreach( $items as $item ) $the_data[$item->ID] = maybe_unserialize( $item->item_data );
                    
                    foreach( $quantities as $ID => $quantity ) {
                        
                        if ( isset( $apply_coupon ) && $apply_coupon == 1 ) {
                            
                            $the_data[$ID]['quantity'] = isset( $the_data[$ID]['quantity'] ) ? absint( $the_data[$ID]['quantity'] ) : 1;
                            
                        }
                        else {
                            
                            $the_data[$ID]['quantity'] = absint( $quantity ) < 1 ? 1 : absint( $quantity );
                            
                        }
                        
                        $the_data[$ID]['material'] = absint( $materials[$ID] );

                        $calculate = $this->calculate( $the_data[$ID] );

                        $cost = pqc_money_format( $calculate['price'] );

                        $total = pqc_money_format( $calculate['amount'] );
                        
                        $the_items[$ID]['cost'] = $cost;
                        
                        $the_items[$ID]['total'] = $total;
                        
                        $subtotal[] = $calculate['amount'];
                        
                        $the_data[$ID] = maybe_serialize( $the_data[$ID] );
                        
                        $sql[] = "($ID,'$the_data[$ID]')";
                        
                    }
                    
                    $type = 'success';
                    
                    $msg = __( 'Cart updated successfully.', 'pqc' );
                    
                    $subtotal = array_sum( $subtotal );
                    
                    // If coupon was sent, let's apply it
                    if ( ( isset( $apply_coupon ) && isset( $coupon ) ) && ( $apply_coupon == 1 && ! empty( $coupon ) ) ) {
                        
                        $msg = '';
                        
                        $coupon = esc_attr( $coupon );
                        
                        $coupon_id = pqc_get_coupon_id( $coupon );
                        
                        if ( $coupon_id ) {
                            
                            $coupon_type = 'success';
                            
                            $coupon_msg = __( 'Coupon applied successfully.', 'pqc' );
                            
                            if ( ! pqc_get_current_user_coupon() || pqc_get_current_user_coupon() != $coupon_id ) {
                                
                                $old_subtotal = $subtotal;
                                
                                $subtotal = pqc_apply_coupon( absint( $coupon_id ), $subtotal );
                                
                                if ( $subtotal === false ) {
                                    
                                    $subtotal = $old_subtotal;
                                    
                                    $coupon_type = 'error';
                                    
                                    $coupon_msg = __( 'Error! Coupon could not be applied.', 'pqc' );
                                    
                                }
                                
                            }

                        } else {
                            
                            $coupon_type = 'error';
                            
                            if ( pqc_get_current_user_coupon() )
                                $coupon_msg = __( 'Invalid Coupon used, reverted to previous coupon used.', 'pqc' );
                            else
                                $coupon_msg = __( 'Invalid Coupon used.', 'pqc' );
                            
                        }
                        
                    }
                    elseif ( isset( $remove_coupon ) && $remove_coupon == 1 ) {
                        
                        if ( pqc_get_current_user_coupon() ) {
                            
                            $coupon_type = 'success';
                                
                            $coupon_msg = __( 'Coupon has been removed.', 'pqc' );
                            
                            $coupon_id = (int) pqc_get_current_user_coupon();
                            
                            $this->delete_current_user_coupon();
                            
                        }
                        
                    }
                    elseif ( pqc_get_current_user_coupon() ) {
                        
                        $coupon_type = 'success';
                            
                        $coupon_msg = __( 'Coupon is being used. <a href="#" id="remove-coupon">remove coupon</a>', 'pqc' );
                        
                        $coupon_id = (int) pqc_get_current_user_coupon();
                        
                        $old_subtotal = $subtotal;
                        
                        $subtotal = pqc_apply_coupon( absint( $coupon_id ), $subtotal );
                        
                        if ( $subtotal === false ) $subtotal = $old_subtotal;
                        
                    }
                    
                    $total = $subtotal;

                    $subtotal       = pqc_money_format( $subtotal );
                    
                    $sql = implode( ',', $sql );
                    
                    $update = $wpdb->query( "
                    INSERT INTO " . PQC_DATA_TABLE . " (ID,item_data)
                    VALUES $sql
                    ON DUPLICATE KEY UPDATE item_data = VALUES(item_data);
                    " );
                    
                    $return = array(
                        'type'          => $type,
                        'the_items'     => $the_items,
                        'sub_total'     => $subtotal,
                        'msg'           => $msg,
                        'coupon_type'   => isset( $coupon_type ) ? $coupon_type : '',
                        'coupon_msg'    => isset( $coupon_msg ) ? $coupon_msg : '',
                    );
                    
                    $return = $return;
                    
                }
                
            }
            
            $return = isset( $update ) ? $return : array( 'type' => 'error' );
            
            sleep(1); // Let's Wait for 1 sec    
            
        }
        else {
            
            $return = array( 'type' => 'error' );
        }
        
        echo json_encode( $return );
        
        wp_die();
        
    }
    
    public function public_ajax_delete() {
        
        if ( ! wp_verify_nonce( $_POST['nonce'], $this->ajax_action ) ) {
            
            _e( 'No naughty business please', 'pqc' );
        
        }
        elseif ( isset( $_POST['unique_ids'] ) ) {
            
            ob_clean(); // Clean any output
            
            global $wpdb;
            
            $user_ip  = pqc_real_ip();
                
            $delete = $this->delete_item( $_POST['unique_ids'] );
            
            if ( $delete ) {
                
                $items = $wpdb->get_results( "SELECT * FROM " . PQC_DATA_TABLE . " WHERE user_ip = '$user_ip' AND status = 'pending'" );

                if ( $items ) {
                    
                    foreach( $items as $item ) {
                        
                        $the_data = maybe_unserialize( $item->item_data );
                        
                        $calculate = $this->calculate( $the_data );
                        
                        $subtotal[] = $calculate['amount'];
                        
                    }
                    
                    $subtotal = array_sum( $subtotal );
                    
                    if ( pqc_get_current_user_coupon() ) {
                        
                        $coupon_type = 'success';
                            
                        $coupon_msg = __( 'Coupon is being used. <a href="#" id="remove-coupon">remove coupon</a>', 'pqc' );
                        
                        $coupon_id = (int) pqc_get_current_user_coupon();
                        
                        $old_subtotal = $subtotal;
                        
                        $subtotal = pqc_apply_coupon( $coupon_id, $subtotal );
                        
                        if ( $subtotal === false ) $subtotal = $old_subtotal;
                        
                    }
                    
                    $subtotal = pqc_money_format( $subtotal );
                    
                    $notice_msg = sprintf(
                        _n( '%s item removed from cart.', '%s items removed from cart.', count( $_POST['unique_ids'] ), 'pqc' ),
                        count( $_POST['unique_ids'] )
                    );
                    
                }
                else {
                    
                    $notice_msg = sprintf(
                        __( 'All item deleted successfully. <a href="%s">Add item to cart</a>', 'pqc' ),
                        get_permalink( pqc_page_exists( 'pqc-upload' ) )
                    );
                    
                }
                
                $return = array(
                    'type'          => 'success',
                    'sub_total'     => $subtotal,
                    'msg'           => $notice_msg,
                    'coupon_type'   => isset( $coupon_type ) ? $coupon_type : '',
                    'coupon_msg'    => isset( $coupon_msg ) ? $coupon_msg : '',
                );
                    
                $return = $return;
                
            }
            
            $return = isset( $delete ) ? $return : array( 'type' => 'error' );
            
            sleep(1); // Let's Wait for a sec
  
        }
        else {
            
            $return = array( 'type' => 'error' );
        }
        
        echo json_encode( $return );
        
        wp_die();
        
    }
    
    /**
    * Display the Upload page
    * @param mixed $args
    */
    public function upload( $args ) {
        
        $options = maybe_unserialize( get_option( PQC_SETTING_OPTIONS ) );
        
        $pqc_general_settings = (object) $options['pqc_general_settings'];
        
        $max_upload = $pqc_general_settings->max_file_upload;
        
        require_once PQC_PATH . 'templates/upload.php';
        
        wp_localize_script( PQC_NAME, 'PQC', array( 'max_upload' => $max_upload ) );
        
        wp_localize_script( PQC_NAME, 'PQC_Page', array( 'page' => 1 ) );
        
    }
    
    /**
    * Display the Cart page
    * @param mixed $args
    */
    public function cart( $args ) {
        
        global $wpdb;
        
        $user_ip = pqc_real_ip();
        
        if ( isset( $_POST['pqc_file_upload'] ) ) {
            
            $error          = false;                
            $notice         = false;
            $settings       = maybe_unserialize( get_option( PQC_SETTING_OPTIONS ) );
            $files          = $_FILES['pqc_file'];
            
            // Check if exceeds Max. File Upload
            $limit = absint( $settings['pqc_general_settings']['max_file_size'] );
            
            if ( count( $files['name'] ) > $limit ) {
                
                $error = true;
                
                $error_msg = __( '<strong> Sorry! </strong> Max File upload is ' . $limit, 'pqc' );

            }
            elseif( count( $files['error'] ) == 1 && $files['error'][0] > 0 ) {
                
                $error = true;
                
                $error_msg = __( '<strong> Error Occurred! </strong> ', 'pqc' ) . $this->file_upload_error( $files['error'][0] );
                
            }
            else {
                
                for( $i = 0; $i < count( $files['name'] ); $i++ ) {
                    
                    $the_files[$i]['name'] = $files['name'][$i];
                    $the_files[$i]['type'] = $files['type'][$i];
                    $the_files[$i]['tmp_name'] = $files['tmp_name'][$i];
                    $the_files[$i]['error'] = $files['error'][$i];
                    $the_files[$i]['size'] = $files['size'][$i];
                    
                }
                
                if ( count( $files['name'] ) == count( $the_files ) ) {
                    
                    foreach( $the_files as $file ) {
                        
                        if ( isset( $file ) && $file['error'] == 0 ) {
                            
                            $type           = pathinfo( $file['name'], PATHINFO_EXTENSION );
                            $unique_id      = pqc_get_random_string();
                            $name           = ucfirst( str_replace( array( '_', '-' ), array( ' ', ' ' ), basename( $file['name'], ".$type" ) ) );
                            $type           = strtolower( $type );
                            $file['name']   = $unique_id . ".$type";
                            $target_dir     = PQC_CONTENT_DIR;
                            $target_url     = PQC_CONTENT_URL . basename( $file['name'] );
                            $target_file    = $target_dir . basename( $file['name'] );
                            $allowed_types  = apply_filters( 'pqc_permitted_files', array( 'stl' ) );                
                            $allowed_size   = (int) absint( $settings['pqc_general_settings']['max_file_size'] ) * 1000000; // In Bytes

                            // Check file extension
                            if ( ! in_array( strtolower( $type ), $allowed_types ) ) {
                                
                                $error = true;
                                
                                $error_msg = __( '<strong> Sorry! </strong> File type not supported.', 'pqc' );
                                
                            }
                            
                            // Check file size
                            if ( $file['size'] > $allowed_size ) {
                                
                                $error = true;
                                
                                $error_msg = __( '<strong> Oops! </strong> File too large.', 'pqc' );

                            }
                            
                            // If no error, let's do the job
                            if ( ! $error ) {
                                
                                if ( ! file_exists( $target_dir ) ) mkdir( $target_dir, 0777, true );

                                if ( ! file_exists( $target_file ) ) {
                                    
                                    $args = array(
                                        'tmp_name'      => $file["tmp_name"],
                                        'file_size'     => $file['size'],
                                        'type'          => $type,
                                        'unique_id'     => $unique_id,
                                        'name'          => $name,
                                        'user_ip'       => $user_ip,
                                    );
                                    
                                    $this->do_upload( $args );
                                    
                                } else {
                                    
                                    $args = array(
                                        'tmp_name'      => $file["tmp_name"],
                                        'file_size'     => $file['size'],
                                        'type'          => $type,
                                        'unique_id'     => pqc_get_random_string(),
                                        'name'          => $name,
                                        'user_ip'       => $user_ip,
                                    );
                                    
                                    $this->do_upload( $args );
                                    
                                }
                
                            }
                            
                        }
                        elseif ( $file['error'] > 0 ) {
                            
                            $error = true;
                            
                            $error_msg = __( '<strong> Error Occurred! </strong>  ', 'pqc' ) . $this->file_upload_error( $file['error'] );    
                        }
                        else {
                            
                            $error = true;
                            
                            $upload_error = true;
                            
                            $error_files[] = $file['name'];
              
                        }
                        
                    }
                    
                    if ( isset( $upload_error ) ) {
                        
                        $error_files = implode( ', ', $error_files );
                        
                        $error_msg = __( '<strong> Oops! </strong> There was an error uploading ', 'pqc' ) . $error_files;
                        
                    }
                    
                }
                else {
                    
                    $error = true;
                    
                    $error_msg = __( '<strong> Sorry! </strong> Error Occurred while parsing files.', 'pqc' );
                    
                }
                
            }
            
        }
        
        $this->display_cart();
        
        wp_localize_script( PQC_NAME, 'PQC_Page', array( 'page' => 2 ) );
    }

    /**
    * Display Checkout page
    * @param mixed $args
    */
    public function checkout( $args ) {
        
        global $wpdb;
        
        $user_ip  = pqc_real_ip();
        
        if ( isset( $_POST['pqc_proceed_checkout'] ) && isset( $_POST['shipping_option_id'] ) ) {

            $shipping_option_id = (int) $_POST['shipping_option_id'];
            
            $buyer_data = array( 'shipping_option' => $shipping_option_id );
            
            pqc_update_buyer_data( $buyer_data );
            
        }
  
        $materials = $wpdb->get_results( "SELECT * FROM " . PQC_MATERIALS_TABLE );
        
        if ( $materials ) {
            
            $g = 0;
            
            foreach ( $materials as $material ) {
                
                if ( $g == 0 ) $material_id = $material->ID;
                
                $the_materials[$material->ID] = $material->material_name;
                
                $g++;
            }
            
        }
        
        $fix = array(
            'quantity' => 1,
            'material' => $material_id,
        );
        
        $items = $wpdb->get_results( "SELECT * FROM " . PQC_DATA_TABLE . " WHERE user_ip = '$user_ip' AND status = 'pending'" );
        
        if ( $items ) {
            
            $total_item = count( $items );
            
            $x = 0;
            
            $the_items = array();
            
            $subtotal = array();
            
            foreach( $items as $item ) {
                
                $id = $item->ID;
                
                $name = $item->item_name;
                
                $data = maybe_unserialize( $item->item_data );
                
                $unique_id = $item->unique_id;
                
                $volume     = ceil( $data['volume'] );
                
                $weight     = ceil( $data['weight'] );
                
                $density    = $data['density'];
                
                $triangle   = $data['triangle'];
                
                $data       = wp_parse_args( $data, $fix ); // Back Compat fix
                
                $quantity   = $data['quantity'];
                
                $selected = absint( $data['material'] );
                
                $target_file = PQC_CONTENT_DIR . $unique_id . ".stl";
                
                if ( file_exists( $target_file ) ) {
                    
                    $calculate = $this->calculate( $data );
                    
                    $cost = pqc_money_format( $calculate['price'] );
                    
                    $total = pqc_money_format( $calculate['amount'] );
                    
                    $the_items[$x]['ID'] = $id;
                    $the_items[$x]['unique_id'] = $unique_id;
                    $the_items[$x]['name'] = $name;
                    $the_items[$x]['volume'] = $volume;
                    $the_items[$x]['weight'] = $weight;
                    $the_items[$x]['density'] = $density;
                    $the_items[$x]['triangle'] = $triangle;
                    $the_items[$x]['quantity'] = $quantity;
                    $the_items[$x]['cost'] = $cost;
                    $the_items[$x]['total'] = $total;
                    
                    $subtotal[] = $calculate['amount'];
                    
                    $selected_material[$id] = $the_materials[$selected];
                    
                    $x++;
                        
                }
                else {
                    
                    $notice = true;
                    
                    $notice_msg = sprintf(
                        __( '<strong> Sorry! </strong> Your item does not exist anymore. <a href="%s">Add new item</a>', 'pqc' ),
                        get_permalink( pqc_page_exists( 'pqc-upload' ) )
                    );
                }
                
            }
            
            $subtotal = array_sum( $subtotal );
            
            $cart_total = pqc_money_format( $subtotal );
            
            if ( pqc_get_current_user_coupon() ) {
                    
                $coupon_msg = __( 'Coupon is being used.', 'pqc' );
                
                $coupon_id = (int) pqc_get_current_user_coupon();
                
                $coupon_name = pqc_get_coupon_name( $coupon_id );
                
                $old_subtotal = $subtotal;
                
                $subtotal = pqc_apply_coupon( $coupon_id, $subtotal );
                
                if ( $subtotal === false ) $subtotal = $old_subtotal;
                
            }
   
            $total = $subtotal;
            
            $shipping_options =  $this->get_shipping_options();
            
            $buyer_data = pqc_get_buyer_data();

            if ( $buyer_data && isset( $buyer_data['shipping_option'] ) && ! empty( $buyer_data['shipping_option'] ) ) {
                
                $current_shipping_option_id = (int) $buyer_data['shipping_option'];
                
                $current_shipping_option = $shipping_options[$current_shipping_option_id];
                
                $shipping_option_cost = floatval( $current_shipping_option['amount'] );
                
                $total = $subtotal + $shipping_option_cost;
                
                $shipping_option_set = true;                   
                
            }
            else {
                
                $shipping_option_set = false;
                
            }
            
            $subtotal = pqc_money_format( $subtotal );
            
            $total = pqc_money_format( $total );
                
        }
        else {
            
            $notice = true;

            $notice_msg = sprintf(
                __( '<strong> Sorry! </strong> You have no item in cart. <a href="%s">Add items</a>', 'pqc' ),
                get_permalink( pqc_page_exists( 'pqc-upload' ) )
            );
            
        }
        
        $settings = maybe_unserialize( get_option( PQC_SETTING_OPTIONS, array() ) );
        
        $s_options = array(
            '1' => array( __( 'Zip Code', 'pqc' ), __( 'State', 'pqc' ) ),
            '2' => array( __( 'Postal Code', 'pqc' ), __( 'County', 'pqc' ) ),
        );
        
        $shop_location = isset( $settings['pqc_checkout_settings']['shop_location'] ) ? intval( $settings['pqc_checkout_settings']['shop_location'] ) : 1;
        
        $location_info = $s_options[$shop_location];
        
        require_once PQC_PATH . 'templates/checkout.php';
        
        wp_localize_script( PQC_NAME, 'PQC_Page', array( 'page' => 3 ) );
        
    }
    
    public function orders() {
        
        global $wpdb;
        
        $user_ip = pqc_real_ip();
        
        $results = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE post_type = 'pqc_order' AND post_status = 'publish' ORDER BY ID DESC" );
        
        $total_item = 0;
        
        if ( $results ) {
            
            foreach( $results as $result ) {
                
                $id = $result->ID;
                
                $post_meta = get_post_meta( $id, 'pqc_order_data' )[0];
                
                if ( $post_meta['user_ip'] != $user_ip ) continue;
                
                $title = $result->post_title;
                
                $args = array(
                    'id'              => $id,
                    'title'           => $title,
                    'txn_id'          => ( ! empty( $post_meta['txn_id'] ) ) ? $post_meta['txn_id'] : '-',
                    'order_action'    => $post_meta['order_action'],
                    'order_status'    => $post_meta['order_status'],
                    'payment_method'  => $post_meta['payment_method'],
                    'total'           => $post_meta['total'],
                    'currency'        => $post_meta['currency'],
                    'date'            => $result->post_date,
                );
                
                $the_orders[$id] = $args;
                
            }
            
            $total_item = ( isset( $the_orders ) ) ? count( $the_orders ) : $total_item;
            
        }
        
        require_once PQC_PATH . 'templates/orders.php';
        
        wp_localize_script( PQC_NAME, 'PQC_Page', array( 'page' => 4 ) );
        
    }
    
    /**
    * Display Cart
    * @param mixed $materials
    */
    private function display_cart() {
        
        global $wpdb;
        
        $user_ip  = pqc_real_ip();
        
        $materials = $wpdb->get_results( "SELECT * FROM " . PQC_MATERIALS_TABLE );
    
        foreach ( $materials as $material ) {
            
            $material_id = $material->ID;
            
            break;
            
        }
        
        $fix = array(
            'quantity' => 1,
            'material' => $material_id,
        );
        
        $items = $wpdb->get_results( "SELECT * FROM " . PQC_DATA_TABLE . " WHERE user_ip = '$user_ip' AND status = 'pending'" );
        
        if ( $items ) {
            
            $total_item = count( $items );
            
            $x = 0;
            
            $target_urls = array();
            
            $the_items = array();
            
            $subtotal = array();
            
            foreach( $items as $item ) {
                
                $id = $item->ID;
                
                $name = $item->item_name;
                
                $data = maybe_unserialize( $item->item_data );
                
                $unique_id = $item->unique_id;
                
                $volume     = ceil( $data['volume'] );
                
                $weight     = ceil( $data['weight'] );
                
                $density    = $data['density'];
                
                $triangle   = $data['triangle'];
                
                $data       = wp_parse_args( $data, $fix ); // Back Compat fix
                
                $quantity   = $data['quantity'];
                
                $selected = absint( $data['material'] );
                
                $target_url = PQC_CONTENT_URL . $unique_id . ".stl";
                
                $target_file = PQC_CONTENT_DIR . $unique_id . ".stl";
                
                if ( file_exists( $target_file ) ) {
                    
                    $calculate = $this->calculate( $data );
                    
                    $cost = pqc_money_format( $calculate['price'] );
                    
                    $total = pqc_money_format( $calculate['amount'] );
                    
                    $target_urls[] = $target_url;
                    
                    $the_items[$x]['ID'] = $id;
                    $the_items[$x]['unique_id'] = $unique_id;
                    $the_items[$x]['name'] = $name;
                    $the_items[$x]['volume'] = $volume;
                    $the_items[$x]['weight'] = $weight;
                    $the_items[$x]['density'] = $density;
                    $the_items[$x]['triangle'] = $triangle;
                    $the_items[$x]['quantity'] = $quantity;
                    $the_items[$x]['cost'] = $cost;
                    $the_items[$x]['total'] = $total;
                    
                    $subtotal[] = $calculate['amount'];
                    
                    $selected_material[$id] = $selected;
                    
                    $x++;
                        
                } else {
                    
                    $notice = true;
                    
                    $notice_msg = sprintf(
                        __( '<strong> Sorry! </strong> Your item does not exist anymore. <a href="%s">Add new item</a>', 'pqc' ),
                        get_permalink( pqc_page_exists( 'pqc-upload' ) )
                    );
                }
                
            }
            
            $subtotal = array_sum( $subtotal );
            
            if ( pqc_get_current_user_coupon() ) {
                
                $coupon_type = 'success';
                    
                $coupon_msg = __( 'Coupon is being used. <a href="#" id="remove-coupon">remove coupon</a>', 'pqc' );
                
                $coupon_id = (int) pqc_get_current_user_coupon();
                
                $old_subtotal = $subtotal;
                
                $subtotal = pqc_apply_coupon( $coupon_id, $subtotal );

                if ( $subtotal === false ) $subtotal = $old_subtotal;
                
            }
            
            if ( $target_urls ) {
                
                $total = $subtotal;
                
                $shipping_options =  $this->get_shipping_options();
                
                $buyer_data = pqc_get_buyer_data();
                
                $current_shipping_option_id = 0;
                
                $pqc_cdata = array(
                    'url'       => $target_urls,
                    'subtotal'  => $subtotal,
                    'money_format_control' => pqc_money_format_control(),
                );
                
                if ( $buyer_data && isset( $buyer_data['shipping_option'] ) && ! empty( $buyer_data['shipping_option'] ) ) {
                    
                    $current_shipping_option_id = (int) $buyer_data['shipping_option'];
                    
                    if ( $shipping_options || ! empty( $shipping_options ) ) {
                        
                        $current_shipping_option = $shipping_options[$current_shipping_option_id];
                        
                        $total = $subtotal + floatval( $current_shipping_option['amount'] );
                        
                        $shipping_options = $shipping_options + array( 'shipping_set' => 1 );
                        
                        
                    }                   
                    
                }
                else {

                    $shipping_options = $shipping_options || ! empty( $shipping_options ) ? $shipping_options + array( 'shipping_set' => 0 ) : array( 'shipping_set' => 0 );
                    
                }
                
                wp_localize_script( PQC_NAME, 'PQC_Shipping', $shipping_options );
                
                wp_localize_script( PQC_NAME . '_STL', 'PQC', $pqc_cdata );
                
                unset( $shipping_options['shipping_set'] );
                
                $subtotal = pqc_money_format( $subtotal );
                
                $total = pqc_money_format( $total );
                
            }
                
        }
        elseif( isset( $_POST['pqc_file_upload'] ) && count( $_FILES['pqc_file']['error'] ) == 1 && $_FILES['pqc_file']['error'][0] > 0 ) {
            
            $error = true;
            
            $error_msg = sprintf(
                __( '<strong> Error Occurred! </strong> %1$s <a href="%2$s">Add item</a>', 'pqc' ),
                $this->file_upload_error( $_FILES['pqc_file']['error'][0] ),
                get_permalink( pqc_page_exists( 'pqc-upload' ) )
            );
            
        }
        else {
            
            $notice = true;

            $notice_msg = sprintf(
                __( '<strong> Sorry! </strong> You have no item in cart. <a href="%s">Add item</a>', 'pqc' ),
                get_permalink( pqc_page_exists( 'pqc-upload' ) )
            );
            
        }
  
        require_once PQC_PATH . 'templates/cart.php';
        
    }
    
    private function get_shipping_cost( $shipping_id ) {

        $shipping_id = (int) $shipping_id;
        
        $meta = maybe_unserialize( get_post_custom_values( 'pqc_shipping_option_data', $shipping_id )[0] );
        
        return ( $meta && isset( $meta['amount'] ) ) ? floatval( $meta['amount'] ) : false;
        
    }
    
    private function get_shipping_options() {

        global $wpdb;
        
        $results = $wpdb->get_results( "
            SELECT * FROM $wpdb->posts
            WHERE post_type = 'pqc_shipping_option'
            AND post_status = 'publish';
        " );
        
        if ( ! $results ) return null;
        
        foreach ( $results as $result ) {
            
            $meta = maybe_unserialize( get_post_custom_values( 'pqc_shipping_option_data', $result->ID )[0] );
            
            $desc = $meta['description'];
            $amount = $meta['amount'];
            
            $values[$result->ID] = array(
                'ID'    => $result->ID,
                'title' => $result->post_title,
                'desc'  => $desc,            
                'cost'  => pqc_money_format( $amount ),            
                'amount'=> $amount,            
            );
            
        }
        
        return $values;
        
    }
    
    /**
    * Deletes the current user coupon id
    */
    private function delete_current_user_coupon() {

        $buyer_data = array( 'coupon' => '' );
        
        return pqc_update_buyer_data( $buyer_data );
        
    }
    
    /**
    * Display all payment methods
    * @param mixed $item_data
    */
    public function payment_options( $item_data ) {

        global $pqc_payment_options;
        
        if ( empty( $pqc_payment_options ) ) return __( 'No checkout option available.', 'pqc' );

        foreach ( $pqc_payment_options as $key => $data ) {
            
            $label = $data['label'];
            
            $desc = '<div class="payment-options-description"><p>' . $data['desc'] . '</p></div>';
            
            $content = $label . $desc;
            
            echo '<li id="' . $key . '"><label><input value="' . $key . '" style="vertical-align: middle;" class="pqc-control" name="payment_method" type="radio">' . $content . '</label></li>';
            
        }
    }

    /**
    * Process Payment in frontend
    * 
    * @param mixed $unique_id
    * @param mixed $material_id
    * @param mixed $payment_data
    */
    public static function process_payment( $unique_id, $material_id, $payment_data ) {
        
        global $wpdb;
        
        if ( ! is_array( $payment_data ) ) return false;
        
        $error          = false;
                        
        $notice         = false;
        
        $default = array( 'txn_id', 'amount', 'currency', 'payer_email', 'payment_status', 'payment_method' );
        
        foreach ( $default as $key ) {
            
            if ( ! array_key_exists( $key, $payment_data ) || empty( $payment_data[$key] ) ) return false;
            
        }
        
        $item_data = $wpdb->get_row( $wpdb->prepare( "SELECT item_name, item_data FROM " . PQC_DATA_TABLE . " WHERE unique_id = %s", $unique_id ) );
        
        if ( $item_data ) {
            
            $item_name = $item_data->item_name;
            $item_data = maybe_unserialize( $item_data->item_data );
            
            $volume = $item_data['volume'];
            $weight = $item_data['weight'];
            $density = $item_data['density'];
            $triangle = $item_data['triangle'];
            
            $options = maybe_unserialize( get_option( PQC_SETTING_OPTIONS ) );
            
            $pqc_general_settings = (object) $options['pqc_general_settings'];
            
            $num_decimals   = $pqc_general_settings->num_decimals;
            $decimals_sep   = $pqc_general_settings->decimal_sep;
            $thousand_sep   = $pqc_general_settings->thousand_sep;
            
            $payment_gross = $payment_data['amount'];
            
            $txn_id = $payment_data['txn_id'];
            
            $currency = $payment_data['currency'];
            
            $payment_status = $payment_data['payment_status'];
            
            $payer_email = $payment_data['payer_email'];
            
            $payment_method = $payment_data['payment_method'];
            
            // Get Material Rate
            $material = $wpdb->get_row( $wpdb->prepare( "SELECT material_name, material_cost FROM " . PQC_MATERIALS_TABLE . " WHERE ID = %s", $material_id ) );
            
            $material_rate = number_format( $material->material_cost, $num_decimals, $decimals_sep, $thousand_sep );
            
            $material_name = $material->material_name;
            
            $vol = number_format( ceil( $volume ), $num_decimals, $decimals_sep, $thousand_sep );
            
            $payment_gross = number_format( $payment_gross, $num_decimals, $decimals_sep, $thousand_sep );
            
            $flat_rate = number_format( $material_rate * $vol, $num_decimals, $decimals_sep, $thousand_sep );

            if ( ! empty( $txn_id ) && $payment_gross == $flat_rate ) {

                // Check if payment data exists with the same TXN ID.
                $prev_payment = $wpdb->get_var( "SELECT ID FROM " . PQC_ORDERS_TABLE . " WHERE txn_id = '$txn_id'" );

                if ( $prev_payment ) {
                    
                    $last_insert_id = $txn_id;
                    
                } else {
                    
                    $item = array(
                        'name'      => $item_name,
                        'volume'    => $volume . " cubic cm",
                        'weight'    => $weight . " gm",
                        'density'   => $density . " gm/cc",
                        'triangle'  => $triangle . " triangle faces",
                        'currency'  => $currency,
                        'url'       => PQC_CONTENT_URL . $unique_id . '.stl',
                        'payment_method' => $payment_method
                    );
                    
                    // Insert tansaction data into the database
                    $data = array(
                        'item'              => maybe_serialize( $item ),
                        'txn_id'            => $txn_id,
                        'payer_email'       => $payer_email,
                        'material'          => $material_name,
                        'payment_gross'     => $flat_rate,
                        'payment_status'    => $payment_status,
                        'date_created'      => current_time( 'mysql' ),
                    );

                    $last_insert_id = $wpdb->insert( PQC_ORDERS_TABLE, $data, array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );
                    
                    $last_insert_id = $txn_id;
                }

            } else {
                
                $error = true;
                
                $msg = __( ' <strong> Ooops! </strong> There is error in your transaction.', 'pqc' );
                
            }
                
        }
        else {
            
            $error = true;
            
            $msg = __( ' <strong> Sorry! </strong> The item you paid for does not exist.', 'pqc' );
            
        }
        
        if ( $error ) {
            
            return array( 'error' => true, 'msg' => $msg );
            
        } else {
            
            return $last_insert_id;
            
        }
        
        
    }
    
    /**
    * Run the Payment Method function or file
    * @param string $item_date The Payment Method
    */
    public function load_payment_method( $post_id, $payment_method, $is_start = true ) {
        
        global $pqc_payment_options;
        
        if ( ! array_key_exists( $payment_method, $pqc_payment_options ) ) return;
        
        extract( $pqc_payment_options[$payment_method]['callback'] ); // Extract the callback of the payment method
        
        if ( ( ! isset( $url ) || empty( $url ) ) && ( ! isset( $start ) || empty( $start ) ) ) return;
        
        if ( ! $is_start && ( ! isset( $end ) || empty( $end ) ) ) return;
        
        $post_id = (int) $post_id;
        
        if ( ! empty( $url ) ) require_once $url;
        
        $function = $is_start ? $start : $end;

        if ( empty( $function ) || ! is_callable( $function, true ) ) return false;
        
        if ( is_array( $function ) ) {
            
            $obj = new $function[0]();
            
            $func = call_user_func_array( array( $obj, $function[1] ), array( $post_id ) ); // $obj->$function[1]( $post_id );
            
            if ( $func['error'] ) {
                
                $GLOBALS['pqc_payment_message'] = isset( $func['response'] ) ? $func['response'] : __( 'Error Occurred', 'pqc' );
                
                add_filter( 'pqc_payment_response', array( $this, 'add_payment_response' ) );

            }
        }
        else {
            
            $func = call_user_func( $function, $post_id ); // $function( $post_id );
            
            if ( $func['error'] ) {
                
                $GLOBALS['pqc_payment_message'] = isset( $func['response'] ) ? $func['response'] : __( 'Error Occurred', 'pqc' );
                
                add_filter( 'pqc_payment_response', array( $this, 'add_payment_response' ) );

            }

        }

    }
    
    public function add_payment_response() {
        
        return $GLOBALS['pqc_payment_message'];
        
    }
    
    /**
    * Provides plain-text error messages for file upload errors.
    * @param mixed $error_integer
    */
    private function file_upload_error( $error_integer ) {
        
        $upload_errors = array(
            UPLOAD_ERR_OK           => __( "No errors.", 'pqc' ),
            UPLOAD_ERR_INI_SIZE     => __( "File is larger than upload_max_filesize.", 'pqc' ),
            UPLOAD_ERR_FORM_SIZE    => __( "File is larger than form MAX_FILE_SIZE.", 'pqc' ),
            UPLOAD_ERR_PARTIAL      => __( "Partial upload.", 'pqc' ),
            UPLOAD_ERR_NO_FILE      => __( "No file added.", 'pqc' ),
            UPLOAD_ERR_NO_TMP_DIR   => __( "No temporary directory.", 'pqc' ),
            UPLOAD_ERR_CANT_WRITE   => __( "Can't write to disk.", 'pqc' ),
            UPLOAD_ERR_EXTENSION    => __( "File upload stopped by extension.", 'pqc' )
        );
        
        return $upload_errors[$error_integer];
    }
    
    /**
    * Calculate the cost for the uploaded item
    * 
    * @param mixed $data The data having volume, weight, density, quantity, material etc. 
    * @param object $materials The Material Object
    */
    private function calculate( $data ) {
        
        extract( $data );
        
        global $wpdb;
        
        $selected_material = $material;

        $options = maybe_unserialize( get_option( PQC_SETTING_OPTIONS ) );
        
        $pqc_general_settings = (object) $options['pqc_general_settings'];
        
        $initial_price  = $pqc_general_settings->initial_price;
        
        $price = $initial_price;
        
        $materials = $wpdb->get_results( "SELECT * FROM " . PQC_MATERIALS_TABLE );
        
        /**
        * Materials
        */
        if ( $materials ) {
            
            foreach( $materials as $material ) {
                
                if ( $material->ID != absint( $selected_material ) ) continue;

                $vol = ceil( $volume );

                $price = $material->material_cost * $vol;
                
                break;
            }   
        }
        
        $amount = floatval( $price * absint( $quantity ) );
        
        $return = array(
            'price' => $price,
            'amount' => $amount,
        );
        
        return $return;
        
    }
    
    /**
    * Upload the file
    * 
    * @param mixed $args
    * @param int $material_id
    */
    private function do_upload( $args ) {
        
        if ( empty( $args ) ) return false;
        
        global $wpdb;
        
        $materials = $wpdb->get_results( "SELECT * FROM " . PQC_MATERIALS_TABLE );
   
        foreach( $materials as $material ) {
            
            $material_id = $material->ID;
            
            break;
        }
        
        parse_str( http_build_query( $args ) );
        
        $target_file = PQC_CONTENT_DIR . $unique_id . ".$type";
        
        $options = maybe_unserialize( get_option( PQC_SETTING_OPTIONS ) );
        
        $file_stay = absint( $options['pqc_general_settings']['max_file_stay'] );
        
        require_once PQC_PATH . 'core/lib/STLStats.php';
        
        $sql = $wpdb->prepare(
            "SELECT * FROM " . PQC_DATA_TABLE . "
            WHERE user_ip = %s
            AND item_name = %s
            AND status = 'pending'",
        $user_ip, $name );
        
        $exist = $wpdb->get_row( $sql );
        
        if ( $exist ) {
            
            if ( filesize( PQC_CONTENT_DIR . $exist->unique_id . ".stl" ) == $file_size ) {
                
                $old_data = maybe_unserialize( $exist->item_data );
                
                $already_exists = true;
                
                $quantity = isset( $old_data['quantity'] ) ? absint( $old_data['quantity'] ) : 1;
                
                $target_file = PQC_CONTENT_DIR . $exist->unique_id . ".stl";
                
                $unique_id = $exist->unique_id;
                
                $material_id = isset( $old_data['material_id'] ) ? absint( $old_data['material_id'] ) : absint( $material_id );
                
            } else {
                
                $already_exists = false;
                
                move_uploaded_file( $tmp_name, $target_file );
                
            }
 
        } else {
            
            move_uploaded_file( $tmp_name, $target_file );
        }
        
        $obj = new STLStats( $target_file );
        
        $item_data = array(
            'volume' => $obj->getVolume( "cm" ),
            'weight' => $obj->getWeight(),
            'density' => $obj->getDensity(),
            'triangle' => $obj->getTrianglesCount(),
            'quantity' => $exist && $already_exists ? $quantity + 1 : 1,
            'material' => $material_id,
        );

        $data = array(
            'unique_id'     => $unique_id,
            'item_name'     => $name,
            'user_ip'       => $user_ip,
            'item_data'     => maybe_serialize( $item_data ),
            'date_created'  => current_time( 'mysql' ),
            'expiry_date'   => date( 'Y-m-d h:i:s', strtotime( "+$file_stay days" ) ),
        );
        
        if ( ( $exist && ! $already_exists ) || ! $exist ) {
            
            $save = $wpdb->insert( PQC_DATA_TABLE, $data, array( '%s', '%s', '%s', '%s', '%s', '%s' ) );
                
        } elseif ( $exist && $already_exists ) {

            $save = $wpdb->update( PQC_DATA_TABLE, $data, array( 'ID' => $exist->ID ), array( '%s', '%s', '%s', '%s', '%s' ), array( '%d' ) );
            
        }

        return $save;
        
    }
    
    /**
    * Remove files and data
    * @param array $unique_ids
    * @since 1.6
    */
    private function delete_item( $unique_ids ) {
        
        global $wpdb;
        
        foreach( $unique_ids as $unique_id ) {
 
            $file = PQC_CONTENT_DIR . $unique_id . ".stl";

            if ( file_exists( $file ) ) unlink( $file );
            
            $sql = $wpdb->prepare(
                "DELETE FROM " . PQC_DATA_TABLE . "
                WHERE unique_id = %s
                AND status = 'pending'"
            , $unique_id );
            
            $delete = $wpdb->query( $sql );
                
        }
        
        return $delete;
        
        //$unique_ids = implode( "' OR unique_id = '", $unique_ids );
        
        //return $wpdb->query( "DELETE FROM $table WHERE unique_id = '$unique_ids'" );
        
    }

    /**
    * Prints the front end scripts
    * @since 1.0
    */
    public function public_scripts() {
        
        global $wp_scripts;
        
        wp_enqueue_style( PQC_NAME, PQC_URL . 'assets/css/public.css', array(), PQC_VERSION, 'all' );
        
        wp_enqueue_script( PQC_NAME . '_JSC3D', PQC_URL . 'assets/js/jsc3d.js', array( 'jquery' ), PQC_VERSION, true );
        
        wp_enqueue_script( PQC_NAME . '_JSC3D-CONSOLE', PQC_URL . 'assets/js/jsc3d.console.js', array( PQC_NAME . '_JSC3D' ), PQC_VERSION, true );
        
        if ( is_page( pqc_page_exists( 'pqc-cart' ) ) ) {
            
            wp_enqueue_script( PQC_NAME . '_STL', PQC_URL . 'assets/js/stl.js', array( PQC_NAME . '_JSC3D-CONSOLE' ), PQC_VERSION, true );
            
        }
        
        wp_enqueue_script( PQC_NAME . ' URL SCRIPT', PQC_URL . 'assets/js/uri.min.js', array(), PQC_VERSION, true );
        
        wp_enqueue_script( PQC_NAME, PQC_URL . 'assets/js/public.js', array( PQC_NAME . '_JSC3D-CONSOLE' ), PQC_VERSION, true );
        
        wp_localize_script(
            PQC_NAME, 'PQC_Ajax',
            array(
                'url'       => admin_url( 'admin-ajax.php' ),
                'action'    => $this->ajax_action,
                'nonce'     => wp_create_nonce( $this->ajax_action ),
                'err_msg'   => __( 'Error occurred. Please Try again', 'pqc' ),
            )
        );
        
    }

}

endif;

if ( ! is_admin() || ( is_admin() && defined( 'DOING_AJAX' ) && DOING_AJAX )  ) new PQC_Public();