<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;

class PQC_Orders {
    
    private $key = 'pqc_order';
    
    private $default_data = array(
        'actions'           => array( 'new', 'cancelled', 'processing', 'completed', 'refunded' ),                
        'status'            => array( 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed' ),        
        'items'             => array(),
        'payment_method'    => 'PayPal',
        'firstname'         => '',
        'lastname'          => '',
        'address'           => '',                
        'city'              => '',                
        'zipcode'           => '',                
        'state'             => '',                
        // 'location'          => '',                
        'note'              => '',
        'order_action'      => '',
        'order_status'      => '',                
        'coupon'            => '',                
        'shipping_option'   => '',                
        'shipping_cost'     => 0.00,                
        'cart_total'        => '',                
        'subtotal'          => '',                
        'total'             => '',       
        'currency'          => '',       
        'user_ip'           => '',       
    );
    
    /**
    * The Constructor
    * 
    */
    public function __construct() {
        
        // Action Before initialization
        do_action( 'pqc_order_before_init' );
        
        $this->init();
        
        // Action After initialization
        do_action( 'pqc_order_after_init' );
        
    }
    
    /**
    * Initialize Order
    * 
    */
    private function init() {
        
        // Register Order Post Type
        $this->register_order();
        
        add_action( 'admin_head', array( $this, 'admin_head' ) );
        
        add_action( 'save_post_' . $this->key, array( $this, 'metabox_save' ), 10, 3 );
        
        add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_updated_messages' ), 10, 2 );
        
        add_filter( 'bulk_actions-edit-' . $this->key, array( $this, 'bulk_actions' ) );
        
        add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 1 );
        
        add_filter( 'manage_edit-' . $this->key . '_columns', array( $this, 'edit_columns' ) );

        add_action( 'manage_posts_custom_column', array( $this, 'custom_columns' ) );
        
        add_action( 'add_meta_boxes_' . $this->key, array( $this, 'metabox' ) );
        
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 999 );

    }

    /**
    * Register Order Post type
    * 
    */
    private function register_order() {
        
        $labels = array(
            'name'                  => _x( 'Orders', 'Post Type General Name', 'pqc' ),
            'singular_name'         => _x( 'Order', 'Post Type Singular Name', 'pqc' ),
            'menu_name'             => __( 'Orders', 'pqc' ),
            'name_admin_bar'        => __( 'Orders', 'pqc' ),
            'parent_item_colon'     => __( 'Parent Orders:', 'pqc' ),
            'all_items'             => __( 'Orders', 'pqc' ),
            'add_new_item'          => __( 'Add New Order', 'pqc' ),
            'add_new'               => __( 'Add Order', 'pqc' ),
            'new_item'              => __( 'New Order', 'pqc' ),
            'edit_item'             => __( 'View Order', 'pqc' ),
            'update_item'           => __( 'Update Order', 'pqc' ),
            'view_item'             => __( 'View Order', 'pqc' ),
            'view_items'            => __( 'View Orders', 'pqc' ),
            'search_items'          => __( 'Search Order', 'pqc' ),
            'not_found'             => __( 'No Order found', 'pqc' ),
            'not_found_in_trash'    => __( 'No Order found in Trash', 'pqc' ),
        );
        
        $args = array(
            'label'                 => __( 'Orders', 'pqc' ),
            'description'           => __( 'Order used in Phanes 3DP Calculator - Basic', 'pqc' ),
            'labels'                => $labels,
            'supports'              => array( 'title' ),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => 'pqc-settings-page',
            'show_in_admin_bar'     => false,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,        
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'rewrite'               => false,
            'map_meta_cap'          => true,
            'capabilities'          => array(
                'create_posts'      => 'do_not_allow',
            )
        );
        
        register_post_type( $this->key, $args );
        
        remove_post_type_support( $this->key, 'title' );
        
        // Action after Registering the Order Post Type
        do_action( 'pqc_after_register_order' );
        
    }
    
    public function admin_head() {
        if ( get_post_type() != $this->key ) return;
        ?>
        <style>
        div#order_data.postbox button.handlediv,
        div#order_data.postbox h2.hndle,
        div#submitdiv.postbox,
        div#post-body-content {
            display: none;
        }
        </style>
        <?php
        
    }
    
    public function bulk_actions( $actions ){
        unset( $actions[ 'edit' ] );
        return $actions;
    }
    
    public function row_actions( $actions ) {
        
        if ( $this->key != get_current_screen()->post_type ) return $actions;
        
        unset( $actions['edit'] );
        unset( $actions['inline hide-if-no-js'] );
        $actions['view'] = sprintf( '<a href="%s">%s</a>', admin_url( 'post.php?post=' . get_the_ID() . '&action=edit' ) , __( 'View' ) );;
        
        return array_reverse( $actions );
    }

    /**
    * Sets Messages for Bulk Update
    * 
    * @param mixed $bulk_messages
    * @param mixed $bulk_counts
    */
    public function bulk_updated_messages( $bulk_messages, $bulk_counts ) {
        
        if ( $this->key != get_current_screen()->post_type ) return;

        $bulk_messages[$this->key] = array(
            'updated'   => _n( '%s order updated.', '%s orders updated.', $bulk_counts['updated'] ),
            'locked'    => _n( '%s order not updated, somebody is editing it.', '%s orders not updated, somebody is editing them.', $bulk_counts['locked'] ),
            'deleted'   => _n( '%s order permanently deleted.', '%s orders permanently deleted.', $bulk_counts['deleted'] ),
            'trashed'   => _n( '%s order moved to the Trash.', '%s orders moved to the Trash.', $bulk_counts['trashed'] ),
            'untrashed' => _n( '%s order restored from the Trash.', '%s orders restored from the Trash.', $bulk_counts['untrashed'] ),
        );

        return $bulk_messages;

    }
    
    /**
    * Modify colums
    * 
    * @param mixed $columns
    */
    public function edit_columns( $columns ) {
        
        if ( $this->key != get_current_screen()->post_type ) return;
        
        $columns = array(
            'cb'                => '<input type="checkbox">',
            'title'             => __( 'Order', 'pqc' ),
            'order_items'       => __( 'Purchased', 'pqc' ),
            'shipping_address'  => __( 'Ship to', 'pqc' ),
            'total'             => __( 'Total', 'pqc' ),
            'status'            => __( 'Status', 'pqc' ),
            'date'              => __( 'Date', 'pqc' ),
        );
         
        return $columns;
    }
    
    /**
    * Sets custom columns for order
    * 
    * @param mixed $column
    */
    public function custom_columns( $column ) {
        
        if ( $this->key != get_current_screen()->post_type ) return;

        $data = get_post_custom();
        
        $data               = isset( $data[$this->key . '_data'] ) ? maybe_unserialize( maybe_unserialize( $data[$this->key . '_data'][0] ) ) : $this->default_data;
        $items              = isset( $data['items'] ) ? $data['items'] : $this->default_data['items'];
        $payment_method     = isset( $data['payment_method'] ) ? $data['payment_method'] : $this->default_data['payment_method'];
        $firstname          = isset( $data['firstname'] ) ? $data['firstname'] : $this->default_data['firstname'];
        $lastname           = isset( $data['lastname'] ) ? $data['lastname'] : $this->default_data['lastname'];
        $address            = isset( $data['address'] ) ? $data['address'] : $this->default_data['address'];
        $note               = isset( $data['note'] ) ? $data['note'] : $this->default_data['note'];
        $status             = isset( $data['order_status'] ) ? $data['order_status'] : $this->default_data['order_status'];
        $coupon             = isset( $data['coupon'] ) ? $data['coupon'] : $this->default_data['coupon'];
        $shipping_option    = isset( $data['shipping_option'] ) ? $data['shipping_option'] : $this->default_data['shipping_option'];
        $cart_total         = isset( $data['cart_total'] ) ? $data['cart_total'] : $this->default_data['cart_total'];
        $subtotal           = isset( $data['subtotal'] ) ? $data['subtotal'] : $this->default_data['subtotal'];
        $total              = isset( $data['total'] ) ? $data['total'] : $this->default_data['total'];
        $currency           = isset( $data['currency'] ) ? $data['currency'] : $this->default_data['currency'];
        
        switch ( $column ) {
            case 'order_items':
                echo count( $items ) . ' items';
                break;
            case 'shipping_address':
                echo $address . '<br>Via ' . $shipping_option;
                break;
            case 'total':
                echo pqc_money_format( $total, $currency, true ) . '<br>Via ' . $payment_method;
                break;
            case 'status':
                echo $status;
                break;
        }
    }
    
    /**
    * Order Meta Box to the Edit Screen
    * 
    */
    public function metabox() {
        
        if ( $this->key != get_current_screen()->post_type ) return;

        add_meta_box( 'order_data', 'Order Data', array( $this, 'metabox_html' ), $this->key, 'normal', 'high' );
        
        add_meta_box( 'order_meta', 'Order Meta', array( $this, 'metabox_order_meta_html' ), $this->key, 'side', 'high' );
        
        add_meta_box( 'order_actions', 'Order Actions', array( $this, 'metabox_order_actions_html' ), $this->key, 'side', 'high' );
        
        // Remove the Slug Meta Box
        remove_meta_box( 'slugdiv', $this->key, 'normal' );
    
    }
    
    /**
    * HTML content to add to order metabox
    * 
    */
    public function metabox_html() {
        
        if ( $this->key != get_current_screen()->post_type ) return;
        
        $post = get_post();

        $data = get_post_custom();
        
        $data               = isset( $data[$this->key . '_data'] ) ? maybe_unserialize( maybe_unserialize( $data[$this->key . '_data'][0] ) ) : $this->default_data;
        $items              = isset( $data['items'] ) ? $data['items'] : $this->default_data['items'];
        $payment_method     = isset( $data['payment_method'] ) ? $data['payment_method'] : $this->default_data['payment_method'];
        $firstname          = isset( $data['firstname'] ) ? $data['firstname'] : $this->default_data['firstname'];
        $lastname           = isset( $data['lastname'] ) ? $data['lastname'] : $this->default_data['lastname'];
        $address            = isset( $data['address'] ) ? $data['address'] : $this->default_data['address'];
        $city               = isset( $data['city'] ) ? $data['city'] : $this->default_data['city'];
        $zipcode            = isset( $data['zipcode'] ) ? $data['zipcode'] : $this->default_data['zipcode'];
        $state              = isset( $data['state'] ) ? $data['state'] : $this->default_data['state'];
        // $location           = isset( $data['location'] ) ? $data['location'] : $this->default_data['location'];
        $note               = isset( $data['note'] ) ? $data['note'] : $this->default_data['note'];
        $status             = isset( $data['order_status'] ) ? $data['order_status'] : $this->default_data['order_status'];
        $coupon             = isset( $data['coupon'] ) ? $data['coupon'] : $this->default_data['coupon'];
        $shipping_option    = isset( $data['shipping_option'] ) ? $data['shipping_option'] : $this->default_data['shipping_option'];
        $cart_total         = isset( $data['cart_total'] ) ? $data['cart_total'] : $this->default_data['cart_total'];
        $subtotal           = isset( $data['subtotal'] ) ? $data['subtotal'] : $this->default_data['subtotal'];
        $total              = isset( $data['total'] ) ? $data['total'] : $this->default_data['total'];
        $currency           = isset( $data['currency'] ) ? $data['currency'] : $this->default_data['currency'];
        ?>
        <div class="order_options pqc_options_panel" style="padding: 1.5%;">
            
            <?php wp_nonce_field( $this->key . '_nonce', $this->key . '_data' ); ?>
            
            <input name="post_title" value="<?php echo $post->post_title; ?>" type="hidden">
            
            <h2><?php printf( __( 'Order %s details', 'pqc' ), $post->post_title ); ?> <span style="float: right;color: #8BC34A;"><?php echo pqc_money_format( $total, $currency, true ); ?></span></h2>
            <p class="order_number"><?php printf( __( 'Payment via %s - %s', 'pqc' ), $payment_method, $status ); ?></p>
            
            <fieldset>
                <legend><?php _e( '<h1>Buyer Details</h1>', 'pqc' ); ?></legend>
                <ul style="font-size: 15px; list-style-type: circle; margin-left: 2em;">
                    <li style="margin: 2% 0;"><p><?php echo 'First Name: ' . $firstname; ?></p></li>
                    <li style="margin: 2% 0;"><p><?php echo 'Last Name: ' . $lastname; ?></p></li>
                    <li style="margin: 2% 0;"><p><?php echo 'Shipping Address: ' . $address; ?></p></li>
                    <li style="margin: 2% 0;"><p><?php echo 'City/Zipcode: ' . "$city / $zipcode"; ?></p></li>
                    <li style="margin: 2% 0;"><p><?php echo 'State: ' . $state; ?></p></li>
                    <?php /** <li style="margin: 2% 0;"><p><?php echo 'Location: ' . $location; ?></p></li><?php */ ?>
                    <li style="margin: 2% 0;"><p><?php echo 'Shipping Option: ' . $shipping_option; ?></p></li>
                    <li style="margin: 2% 0;"><p><?php echo 'Customer Note: ' . $note; ?></p></li>
                </ul>
            </fieldset>
            
            
            <fieldset>
                <legend><?php _e( '<h1>Items</h1>', 'pqc' ); ?></legend>
                <ol style="font-size: 15px;">
                    <?php
                    foreach( $items as $item ) {
                        $name = $item['name'];
                        $qty = $item['quantity'];
                        $amount = pqc_money_format( $item['amount'] * $qty, $currency, true );
                        $url = $item['url'];
                        printf( '<li style="margin: 2%% 0;"> %s x %s - %s<a style="float:right;" href="%s" target="_blank">Download File</a> </li>',
                        $name, $qty, $amount, $url );
                    }
                    ?>
                </ol>
            </fieldset>
        </div>
        <?php
        
    }
    
    /**
    * HTML content to add to order metabox
    * 
    */
    public function metabox_order_meta_html() {
        
        if ( $this->key != get_current_screen()->post_type ) return;
        
        $post = get_post();

        $data = get_post_custom();
        
        $data               = isset( $data[$this->key . '_data'] ) ? maybe_unserialize( maybe_unserialize( $data[$this->key . '_data'][0] ) ) : $this->default_data;
        $coupon             = isset( $data['coupon'] ) ? $data['coupon'] : $this->default_data['coupon'];
        $shipping_option    = isset( $data['shipping_option'] ) ? $data['shipping_option'] : $this->default_data['shipping_option'];
        $shipping_cost      = isset( $data['shipping_cost'] ) ? $data['shipping_cost'] : $this->default_data['shipping_cost'];
        $cart_total         = isset( $data['cart_total'] ) ? $data['cart_total'] : $this->default_data['cart_total'];
        $subtotal           = isset( $data['subtotal'] ) ? $data['subtotal'] : $this->default_data['subtotal'];
        $total              = isset( $data['total'] ) ? $data['total'] : $this->default_data['total'];
        $currency           = isset( $data['currency'] ) ? $data['currency'] : $this->default_data['currency'];
        ?>
        <div class="order_options pqc_options_panel" style="padding: 1.5%;">
            <ul style="font-size: 15px; margin: 0%;">
                <?php if ( ! empty( $coupon ) ) : ?>
                <li style="margin: 2% 0;"><p><?php echo 'Coupon Used: ' . $coupon; ?></p></li>
                <?php endif; ?>
                <li style="margin: 2% 0;"><p><?php echo 'Cart Total: ' . pqc_money_format( $cart_total, $currency, true ); ?></p></li>
                <li style="margin: 2% 0;"><p><?php echo 'Shipping Cost: ' . pqc_money_format( $shipping_cost, $currency, true ); ?></p></li>
                <li style="margin: 2% 0;"><p><?php echo 'Subtotal: ' . pqc_money_format( $subtotal, $currency, true ); ?></p></li>
                <li style="margin: 2% 0;"><p><?php echo 'Total: ' . pqc_money_format( $total, $currency, true ); ?></p></li>
            </ul>
        </div>
        <?php
        
    }
    
    /**
    * HTML content to add to order metabox
    * 
    */
    public function metabox_order_actions_html() {
        
        if ( $this->key != get_current_screen()->post_type ) return;
        
        $post = get_post();

        $data = get_post_custom();
        
        $data      = isset( $data[$this->key . '_data'] ) ? maybe_unserialize( maybe_unserialize( $data[$this->key . '_data'][0] ) ) : $this->default_data;
        $action    = isset( $data['order_action'] ) ? $data['order_action'] : $this->default_data['order_action'];        
        ?>
        <div class="order_options pqc_options_panel" style="padding: 1.5%;">

            <div class="form-field type_field" style="margin: 1em 0;">

                <select id="order_action" name="order_action" style="width: 100%;">
                    <?php foreach( $this->default_data['actions'] as $key ) : ?>
                    <option value="<?php echo $key; ?>" <?php if ( $key == $action ) echo 'selected="selected"'; ?>><?php echo ucfirst( $key ) . ' Order'; ?></option>
                    <?php endforeach; ?>
                </select> 
                
            </div>
        
            <div id="major-publishing-actions">
                <div id="delete-action"><a style="color: #a00;" class="submitdelete deletion" href="<?php echo get_delete_post_link( $post->ID ); ?>">Move to Trash</a></div>

                <div id="publishing-action">
                    <input name="save" id="publish" class="button button-primary button-large" value="Update" type="submit">
                </div>
                
                <div class="clear"></div>
            </div>
        </div>
        <?php
        
    }
    
    /**
    * Save post metadata when a post is saved
    * @param int     $post_ID Post ID.
    * @param WP_Post $post    Post object.
    * @param bool    $update  Whether this is an existing post being updated or not.
    */
    public function metabox_save( $post_id, $post, $update ) {

        if ( $post->post_type != $this->key ) return;
        
        if ( ! current_user_can( 'manage_options' ) ) return;
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || $post->post_status == 'auto-draft' ) return;
        
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return;
        
        if ( ! isset( $_POST[$this->key . '_data'] ) ) return;
        
        if ( wp_verify_nonce( $_POST[$this->key . '_data'], $this->key . '_nonce' ) == false ) return;

        $order_action   = sanitize_text_field( $_POST['order_action'] );
        
        // Validate
        if ( ! in_array( $order_action, $this->default_data['actions'] ) ) return;

        $data = get_post_custom();
        
        $data      = isset( $data[$this->key . '_data'] ) ? maybe_unserialize( maybe_unserialize( $data[$this->key . '_data'][0] ) ) : $this->default_data;
        
        $data = wp_parse_args( array( 'order_action' => $order_action ), $data );
        
        update_post_meta( $post_id, $this->key . '_data', $data );        
    }

    /**
    * Enqueue/Dequeue Admin Scripts
    * 
    */
    public function admin_scripts() {
        
        if ( $this->key != get_current_screen()->post_type ) return;

        wp_dequeue_script( PQC_NAME . ' URL MOD' );
        
    }

}

new PQC_Orders();